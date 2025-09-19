<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Services\CorreioService;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class CorreioController extends Controller
{
    public function __construct(private CorreioService $correioService) {}

    public function gerarRotulo(Request $request)
    {
        Log::info("Recebendo pedidos para gerar rótulo", ['payload' => $request->all()]);

        try {
            $payload = $request->all();
            $orders = $payload['confirmedOrders'] ?? [];

            if (empty($orders)) {
                return response()->json([
                    'status' => 'erro',
                    'message' => 'Nenhum pedido confirmado recebido'
                ]);
            }

            $result = $this->correioService->processarPedidos($orders);
            Log::info("Result:", $result);

            // 🚀 Atualiza cada pedido no banco
            $updatedOrders = [];
            foreach ($result['orders'] as $orderData) {
                try {
                    $order = Order::findOrFail($orderData['id_pedido']);
                    $order->update([
                        'status'        => 7, // enviado aos Correios
                        'codigoObjeto'  => $orderData['codigoObjeto'],
                        'idPrePostagem' => $orderData['idPrePostagem'],
                        'idRecibo'      => $orderData['idRecibo'] ?? $result['idRecibo']
                    ]);
                    $updatedOrders[] = $order;
                } catch (\Exception $e) {
                    Log::error("Erro ao atualizar pedido", [
                        'order_id' => $orderData['id_pedido'],
                        'erro'     => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'status'  => 'sucesso',
                'message' => 'Rótulos gerados e pedidos atualizados com sucesso!',
                'orders'  => $updatedOrders,
                'rotulo'  => $result['responseRotulo']
            ]);
        } catch (\Exception $e) {
            Log::error("Erro ao gerar rótulo", ['erro' => $e->getMessage()]);
            return response()->json([
                'status'  => 'erro',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadEtiqueta(string $idRecibo)
    {
        try {
            $pdfBase64 = $this->correioService->baixarPdfEtiqueta($idRecibo);
            $pdfContent = base64_decode($pdfBase64);

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename=etiqueta.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'erro',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
