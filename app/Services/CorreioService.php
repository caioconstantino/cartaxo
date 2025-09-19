<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CorreioService
{
    private string $usuario = "57465902000110";
    private string $senha   = "GN5cxIsM4rpIW5z9uxvtCt0mOWmbeMPZmfeycEVJ";
    private string $cartaoPostagem = "0079023932";

    private ?string $token = null;

    /**
     * Autentica no Correios e gera token
     */
    private function autenticar(): string
    {
        if ($this->token) {
            return $this->token;
        }

        $response = Http::withBasicAuth($this->usuario, $this->senha)
            ->acceptJson()
            ->post("https://api.correios.com.br/token/v1/autentica/cartaopostagem", [
                "numero" => $this->cartaoPostagem
            ]);

        if (!$response->successful()) {
            throw new \Exception("Falha ao autenticar: {$response->status()} - {$response->body()}");
        }

        $this->token = $response->json("token");
        return $this->token;
    }

    /**
     * Processa pedidos, gera pré-postagem e rótulos
     */
    public function processarPedidos(array $orders): array
    {
        $token = $this->autenticar();
        $codigosObjetos = [];
        $idsPrePostagem = [];
        $ordersData = [];
        $errors = [];

        foreach ($orders as $order) {
            try {
                $postData = $this->montarPayloadPrePostagem($order);

                $response = Http::withToken($token)
                    ->acceptJson()
                    ->post("https://api.correios.com.br/prepostagem/v1/prepostagens", $postData);

                if (!$response->successful()) {
                    $errors[] = ["order_id" => $order["id"], "error" => $response->body()];
                    continue;
                }

                $decoded = $response->json();

                $idPrePostagem = $decoded["id"] ?? null;
                $codigoObjeto  = $decoded["codigoObjeto"] ?? null;

                if ($idPrePostagem && $codigoObjeto) {
                    $codigosObjetos[] = $codigoObjeto;
                    $idsPrePostagem[] = $idPrePostagem;

                    $ordersData[] = [
                        "idPrePostagem" => $idPrePostagem,
                        "codigoObjeto"  => $codigoObjeto,
                        "id_pedido"     => $order["id"]
                    ];
                } else {
                    $errors[] = [
                        "order_id" => $order["id"],
                        "error"    => "Resposta inválida",
                        "json"     => $decoded
                    ];
                }
            } catch (\Exception $e) {
                $errors[] = ["order_id" => $order["id"], "error" => $e->getMessage()];
            }
        }

        // Se conseguiu pré-postagens, gerar rótulo
        $idRecibo = null;
        $responseRotulo = null;
        if (!empty($codigosObjetos)) {
            $rotulo = $this->gerarRotulo($token, $codigosObjetos);
            $idRecibo = $rotulo["idRecibo"] ?? null;
            $responseRotulo = $rotulo;

            // 👉 aqui você pode atualizar cada pedido com o idRecibo
            foreach ($ordersData as &$orderData) {
                $orderData["idRecibo"] = $idRecibo;
            }
        }

        return [
            "orders" => $ordersData,
            "errors" => $errors,
            "idRecibo" => $idRecibo,
            "responseRotulo" => $responseRotulo
        ];
    }


    /**
     * Monta payload da pré-postagem
     */
    private function montarPayloadPrePostagem(array $order): array
    {
        $pesoTotal = $alturaTotal = $larguraTotal = $comprimentoTotal = 0;

        foreach ($order["order_products"] as $product) {
            $pesoTotal        += (float) $product["weight"] * (int) $product["quantity"];
            $alturaTotal      += (float) $product["height"] * (int) $product["quantity"];
            $larguraTotal     += (float) $product["width"] * (int) $product["quantity"];
            $comprimentoTotal += (float) $product["length"] * (int) $product["quantity"];
        }

        // Endereço do cliente
        $enderecoCliente = $order["order_address"][0]["address"];
        $cep = $order["order_address"][0]["zip_code"];

        // Buscar endereço completo no ViaCEP
        $viacep = Http::get("https://viacep.com.br/ws/{$cep}/json/")->json();

        // Separar logradouro e número
        preg_match("/^(.*?)(\d+)$/", trim($enderecoCliente), $matches);
        $logradouro = $matches[1] ?? $enderecoCliente;
        $numero     = $matches[2] ?? "S/N";

        return [
            "idCorreios" => $this->usuario,
            "remetente" => [
                "nome" => "CARTAXO SERVICOS ONLINE LTDA",
                "cpfCnpj" => $this->usuario,
                "email" => "constantinofcaio@gmail.com",
                "endereco" => [
                    "cep" => "08080650",
                    "logradouro" => "RUA DOUTOR JOSE DE PORCIUNCULA",
                    "numero" => "151",
                    "complemento" => "LOTE 14 QUADRA31",
                    "bairro" => "Parque Paulistano",
                    "cidade" => "São Paulo",
                    "uf" => "SP"
                ]
            ],
            "destinatario" => [
                "nome" => $order["user"]["name"],
                "endereco" => [
                    "cep" => $cep,
                    "logradouro" => $viacep["logradouro"] ?? $logradouro,
                    "numero" => $numero,
                    "bairro" => $viacep["bairro"] ?? "",
                    "cidade" => $viacep["localidade"] ?? "",
                    "uf" => $viacep["uf"] ?? "",
                ]
            ],
            "codigoServico" => $order['shipping_method'],
            "numeroNotaFiscal" => $order['nfe_num'],
            "numeroCartaoPostagem" => $this->cartaoPostagem,
            "chaveNFe" => $order['nfe_chave'],
            "pesoInformado" => $pesoTotal,
            "codigoFormatoObjetoInformado" => "2",
            "alturaInformada" => $alturaTotal,
            "larguraInformada" => $larguraTotal,
            "comprimentoInformado" => $comprimentoTotal,
            "cienteObjetoNaoProibido" => "1",
            "solicitarColeta" => "N",
            "dataPrevistaPostagem" => date("d/m/Y", strtotime("+1 day")),
            "modalidadePagamento" => "2",
            "logisticaReversa" => "N",
            "dataValidadeLogReversa" => date("d/m/Y", strtotime("+20 days"))
        ];
    }


    /**
     * Solicita rótulo
     */
    private function gerarRotulo(string $token, array $codigosObjetos): array
    {
        $rotuloData = [
            "codigosObjeto" => $codigosObjetos,
            "idCorreios" => $this->usuario,
            "numeroCartaoPostagem" => $this->cartaoPostagem,
            "tipoRotulo" => "P",
            "formatoRotulo" => "ET",
            "imprimeRemetente" => "S"
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->post("https://api.correios.com.br/prepostagem/v1/prepostagens/rotulo/assincrono/pdf", $rotuloData);

        if (!$response->successful()) {
            throw new \Exception("Erro ao gerar rótulo: {$response->status()} - {$response->body()}");
        }

        return $response->json();
    }

    public function baixarPdfEtiqueta(string $idRecibo): string
    {
        $token = $this->autenticar();

        // aqui pedimos JSON, não PDF
        $response = Http::withToken($token)
            ->acceptJson()
            ->get("https://api.correios.com.br/prepostagem/v1/prepostagens/rotulo/download/assincrono/$idRecibo");

        if (!$response->successful()) {
            throw new \Exception("Erro ao consultar rótulo: {$response->status()} - {$response->body()}");
        }

        $json = $response->json();

        // normalmente o PDF vem como base64 em algum campo do JSON
        $pdfBase64 = $json['dados'] ?? null;

        if (!$pdfBase64) {
            throw new \Exception("Etiqueta ainda não disponível para o recibo {$idRecibo}");
        }

        return $pdfBase64;
    }
}
