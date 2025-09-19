<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\BlingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BlingController extends Controller
{
    public function __construct(private BlingService $bling) {}

    public function refresh(Request $request)
    {
        Log::info("Iniciando refresh token");
        $token = $this->bling->refreshToken();
        return response()->json(['data' => [
            'access_token' => $token->access_token,
            'expires_at'   => $token->expires_at,
        ]]);
    }

    public function emitir(Request $request)
    {
        $request->validate(['order_id' => 'required|integer|exists:orders,id']);
        Log::info("Iniciando emitir NFe", ['order_id' => $request->order_id]);

        $res = $this->bling->emitirNfe((int)$request->order_id);
        Log::info("NFe emitida", ['res' => $res]);

        return response()->json(['data' => $res]);
    }

    public function enviar($id)
    {
        Log::info("Iniciando enviar NFe", ['nfe_id' => $id]);
        $res = $this->bling->enviarNfe((int)$id);
        Log::info("NFe enviada", ['res' => $res]);
        return response()->json(['data' => $res]);
    }

    /** One-shot: emitir e enviar numa tacada só */
    public function emitirEEnviar(Request $request)
    {
        Log::info("Iniciando emitirEEnviar", ['request' => $request->all()]);

        try {
            $request->validate(['order_id' => 'required|integer|exists:orders,id']);
            Log::info("Validação do request ok", ['order_id' => $request->order_id]);

            $emit = $this->bling->emitirNfe((int)$request->order_id);
            Log::info("NFe emitida", ['emit' => $emit]);

            if (!isset($emit['id'])) {
                Log::error("Erro: ID da NFe não retornou no emit", ['emit' => $emit]);
                return response()->json(['error' => 'Falha ao emitir NFe'], 500);
            }

            $send = $this->bling->enviarNfe((int)$emit['id']);
            Log::info("NFe enviada", ['send' => $send]);

            if (!isset($send['xml'])) {
                Log::error("Erro: XML não retornou no enviar", ['send' => $send]);
                return response()->json(['error' => 'Falha ao autorizar NFe'], 500);
            }

            if (!empty($send['xml'])) {
                $xml = simplexml_load_string($send['xml']);

                // registra o namespace da NFe
                $xml->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');

                // extrai chave e protocolo via XPath
                $chave = (string) ($xml->xpath('//nfe:chNFe')[0] ?? null);
                $protocolo = (string) ($xml->xpath('//nfe:nProt')[0] ?? null);

                Log::info("Informações NFE", [
                    'chave'     => $chave,
                    'protocolo' => $protocolo,
                ]);

                // salva no banco
                Order::where('id', $request->order_id)->update([
                    'nfe_id'       => $emit['id'],
                    'nfe_num'      => $emit['numero'],
                    'nfe_xml'      => $send['xml'],
                    'nfe_chave'    => $chave,
                ]);
            }

            $responseData = [
                'nfeId'     => $emit['id'],
                'numero'    => $emit['numero'],
                'serie'     => $emit['serie'],
                'xml'       => $send['xml'],
                'chave'     => $chave,
                'protocolo' => $protocolo,
            ];


            Log::info("emitirEEnviar finalizado com sucesso", ['response' => $responseData]);
            return response()->json(['data' => $responseData]);
        } catch (\Exception $e) {
            Log::error("Erro no emitirEEnviar", [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Erro ao emitir e enviar NFe', 'details' => $e->getMessage()], 500);
        }
    }
}
