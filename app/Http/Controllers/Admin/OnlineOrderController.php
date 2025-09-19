<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Exports\OrderExport;
use App\Services\OrderService;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\PaginateRequest;
use App\Http\Requests\OrderStatusRequest;
use App\Http\Requests\PaymentStatusRequest;
use App\Http\Resources\OrderDetailsResource;
use App\Http\Requests\OrderRequest;

class OnlineOrderController extends AdminController
{
    private OrderService $orderService;

    public function __construct(OrderService $order)
    {
        parent::__construct();
        $this->orderService = $order;
        $this->middleware(['permission:online-orders'])->only(
            'index',
            'show',
            'export',
            'changeStatus',
            'changePaymentStatus',
            'updateShippingInfos'
        );
    }

    public function index(PaginateRequest $request): \Illuminate\Http\Response | \Illuminate\Http\Resources\Json\AnonymousResourceCollection | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return OrderDetailsResource::collection($this->orderService->list($request));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function show(Order $order): \Illuminate\Http\Response | OrderDetailsResource | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return new OrderDetailsResource($this->orderService->show($order, false));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function export(PaginateRequest $request): \Illuminate\Http\Response | \Symfony\Component\HttpFoundation\BinaryFileResponse | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return Excel::download(new OrderExport($this->orderService, $request), 'Online-Order.xlsx');
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function changeStatus(Order $order, OrderStatusRequest $request): \Illuminate\Http\Response | OrderDetailsResource | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return new OrderDetailsResource($this->orderService->changeStatus($order, $request, false));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function changePaymentStatus(Order $order, PaymentStatusRequest $request): \Illuminate\Http\Response | OrderDetailsResource | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return new OrderDetailsResource($this->orderService->changePaymentStatus($order, $request, false));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function updateShippingInfos(OrderRequest $request)
    {
        try {
            if (!is_array($request->orders) || empty($request->orders)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Erro: Nenhum pedido foi enviado na requisição.'
                ], 400);
            }

            $updatedOrders = [];
            foreach ($request->orders as $orderData) {
                if (!isset($orderData['id_pedido'], $orderData['codigoObjeto'], $orderData['idPrePostagem'])) {
                    continue; // ignora se faltar dados
                }

                $order = Order::findOrFail($orderData['id_pedido']);

                $order->update([
                    'status'        => 7,
                    'codigoObjeto'  => $orderData['codigoObjeto'],
                    'idPrePostagem' => $orderData['idPrePostagem'],
                    'idRecibo'      => $orderData['idRecibo'] ?? null
                ]);

                $updatedOrders[] = $order;
            }

            return response()->json([
                'status'  => true,
                'message' => 'Pedidos atualizados com sucesso!',
                'orders'  => $updatedOrders
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'status'  => false,
                'message' => 'Erro ao atualizar pedido: ' . $exception->getMessage()
            ], 500);
        }
    }

    public function updateNfeInfo(OrderRequest $request)
    {
        try {
            $orderId   = $request->input('id_pedido');
            $nfeId     = $request->input('nfe_id');
            $nfeNum     = $request->input('nfe_num');
            $nfeXml    = $request->input('nfe_xml');
            $nfeChave  = $request->input('nfe_chave');

            if (!$orderId) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Erro: ID do pedido não informado.'
                ], 400);
            }

            // Busca o pedido no banco
            $order = Order::findOrFail($orderId);

            // Atualiza os dados do pedido
            $order->update([
                'nfe_id'    => $nfeId,
                'nfe_num'   => $nfeNum,
                'nfe_xml'   => $nfeXml,
                'nfe_chave' => $nfeChave,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Pedido atualizado com sucesso!',
                'data'    => $order
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'status'  => false,
                'message' => 'Erro ao atualizar pedido: ' . $exception->getMessage()
            ], 500);
        }
    }
}
