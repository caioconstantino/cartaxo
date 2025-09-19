<?php

namespace App\Services;

use App\Models\BlingToken;
use App\Models\Order;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class BlingService
{
    private Client $http;
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = 'https://api.bling.com.br/Api/v3';
        $this->http = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 30,
        ]);
    }

    /** Atualiza token usando refresh_token */
    public function refreshToken(?string $refreshToken = null): BlingToken
    {

        $body = [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken ?: optional(BlingToken::first())->refresh_token,
        ];

        Log::info("Solicitando refresh token", ['body' => $body]);

        $basic = 'Basic ' . config('services.bling.basic_auth');
        $res = (new \GuzzleHttp\Client())->post(
            'https://bling.com.br/Api/v3/oauth/token',
            [
                'headers' => [
                    'Authorization' => $basic,
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                ],
                'form_params' => $body,
            ]
        );



        $data = json_decode((string) $res->getBody(), true);

        $expiresAt = now()->addSeconds((int)($data['expires_in'] ?? 1800) - 30);

        $token = BlingToken::first() ?? new BlingToken();
        $token->fill([
            'access_token'  => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $token->refresh_token,
            'token_type'    => $data['token_type'] ?? 'Bearer',
            'expires_in'    => $data['expires_in'] ?? null,
            'expires_at'    => $expiresAt,
            'scope'         => $data['scope'] ?? null,
        ])->save();

        Log::info("Novo token salvo", ['token' => $token->toArray()]);

        return $token;
    }

    /** Garante access_token válido */
    public function ensureAccessToken(): string
    {
        $token = BlingToken::first();

        if (!$token || !$token->access_token || !$token->expires_at || $token->expires_at->isPast()) {
            $token = $this->refreshToken(optional($token)->refresh_token);
        } elseif ($token->expires_at->lte(now()->addMinute())) {
            $token = $this->refreshToken($token->refresh_token);
        }

        return $token->access_token;
    }

    /** Emite a NFe */
    public function emitirNfe(int $orderId): array
    {
        $access = $this->ensureAccessToken();

        /** @var Order $order */
        $order = Order::with([
            'user',
            'address',
            'orderProducts.product',    // pega o produto para nome, sku, etc.
        ])->findOrFail($orderId);


        Log::info("Order para construir o body da nfe", ['Order' => $order]);

        $body = $this->buildNfeBodyFromOrder($order);

        Log::info("Enviando NFe para o Bling", ['body' => $body]);

        $res = $this->http->post('/Api/v3/nfe', [
            'headers' => [
                'Authorization' => "Bearer {$access}",
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
            'json' => $body,
        ]);

        $data = json_decode((string)$res->getBody(), true);

        return [
            'id'     => $data['data']['id']    ?? null,
            'numero' => $data['data']['numero'] ?? null,
            'serie'  => $data['data']['serie'] ?? null,
            'raw'    => $data,
        ];
    }

    /** Autoriza a NFe criada */
    public function enviarNfe(int $nfeId): array
    {
        $access = $this->ensureAccessToken();

        Log::info("Autorizando NFe no Bling", ['nfeId' => $nfeId]);

        $res = $this->http->post("/Api/v3/nfe/{$nfeId}/enviar", [
            'headers' => [
                'Authorization' => "Bearer {$access}",
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
            'json' => (object)[],
        ]);

        $data = json_decode((string)$res->getBody(), true);

        return [
            'xml' => $data['data']['xml'] ?? null,
            'raw' => $data,
        ];
    }

    protected function buildNfeBodyFromOrder(Order $order): array
    {
        $user = $order->user;
        $shipping = $order->address()->first();
        $cep = preg_replace('/\D/', '', $shipping?->zip_code ?? '');
        $fullAddress = $shipping?->address ?? '';
        $numero = '';

        if (preg_match('/^(.*?)(\d+)$/', $fullAddress, $matches)) {
            $logradouro = trim($matches[1]); // Rua sem número
            $numero = $matches[2];           // Número da casa
        }

        // Buscar endereço completo via ViaCEP
        $enderecoCompleto = $this->getAddressByCep($cep);

        $doc = preg_replace('/\D/', '', (string)($user->cpfcnpj ?? ''));
        $tipoPessoa = strlen($doc) === 14 ? 'J' : 'F';
        if (!in_array(strlen($doc), [11, 14])) {
            throw new \RuntimeException('CPF/CNPJ inválido para o cliente.');
        }

        $itens = [];
        foreach ($order->orderProducts as $item) {
            $ncm = \App\Models\ProductTax::getNcmByProductId($item->product_id);

            if (!$ncm) {
                throw new \RuntimeException("Item {$item->product?->name} sem NCM (classificacaoFiscal).");
            }

            $codigo = $item->sku ?? $item->product_id ?? \Illuminate\Support\Str::uuid()->toString();

            $itens[] = [
                'codigo'              => (string)$codigo,
                'descricao'           => $item->product?->name ?? 'Produto sem nome',
                'unidade'             => 'UN',
                'quantidade'          => (float)$item->quantity,
                'valor'               => (float)$item->total,
                'tipo'                => 'P',
                'classificacaoFiscal' => $ncm,
                'origem'              => 2,
            ];
        }

        return [
            'tipo'          => 1,
            'dataOperacao'  => now()->format('Y-m-d H:i:s'),
            'contato'       => [
                'nome'            => $user->name,
                'tipoPessoa'      => $tipoPessoa,
                'numeroDocumento' => $doc,
                'contribuinte'    => 9,
                'email'           => $user->email,
                'endereco'        => [
                    'endereco'   => $enderecoCompleto['logradouro'] ?? $shipping?->address ?? '',
                    'numero'     => $numero ?? '00', // se ViaCEP não retornar, pode deixar em branco
                    'bairro'     => $enderecoCompleto['bairro'] ?? '',
                    'cep'        => $cep,
                    'municipio'  => $enderecoCompleto['municipio'] ?? '',
                    'uf'         => $enderecoCompleto['uf'] ?? '',
                    'pais'       => 'BR',
                ],
            ],
            'naturezaOperacao' => [
                'id' => (int)config('services.bling.natureza_operacao_id'),
            ],
            'finalidade'    => 1,
            'despesas'      => (float)($order->shipping_charge ?? 0),
            'desconto'      => (float)($order->discount ?? 0),
            'observacoes'   => $order->reason ?? '',
            'itens'         => $itens,
            'parcelas'      => [],
            'transporte'    => [
                'fretePorConta' => 0,
                'frete'         => (float)($order->shipping_charge ?? 0),
            ],
        ];
    }

    /**
     * Busca dados de endereço via CEP usando ViaCEP
     */
    protected function getAddressByCep(string $cep): array
    {
        try {
            $response = Http::get("https://viacep.com.br/ws/{$cep}/json/")->json();

            if (isset($response['erro']) && $response['erro'] === true) {
                return [
                    'logradouro' => '',
                    'bairro'     => '',
                    'municipio'  => '',
                    'uf'         => '',
                    'numero'     => '',
                ];
            }

            return [
                'logradouro' => $response['logradouro'] ?? '',
                'bairro'     => $response['bairro'] ?? '',
                'municipio'  => $response['localidade'] ?? '',
                'uf'         => $response['uf'] ?? '',
                'numero'     => '', // ViaCEP não retorna número, você pode deixar em branco ou preencher manualmente
            ];
        } catch (\Exception $e) {
            return [
                'logradouro' => '',
                'bairro'     => '',
                'municipio'  => '',
                'uf'         => '',
                'numero'     => '',
            ];
        }
    }
}
