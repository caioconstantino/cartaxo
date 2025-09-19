<?php

namespace App\Services;

use Exception;
use App\Enums\Status;
use App\Models\Order;
use App\Models\Stock;
use App\Models\Outlet;
use App\Models\Address;
use App\Models\Product;
use App\Enums\OrderType;
use App\Models\StockTax;
use App\Enums\AddressType;
use App\Enums\OrderStatus;
use App\Models\OrderCoupon;
use App\Enums\PaymentStatus;
use App\Events\SendOrderSms;
use App\Models\OrderAddress;
use App\Events\SendOrderMail;
use App\Events\SendOrderPush;
use App\Models\ProductVariation;
use App\Models\OrderOutletAddress;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\OrderRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\PaginateRequest;
use App\Http\Requests\OrderStatusRequest;

class FrontendOrderService
{

    public object $order;
    protected array $frontendOrderFilter = [
        'order_serial_no',
        'user_id',
        'total',
        'order_type',
        'order_datetime',
        'payment_method',
        'payment_status',
        'status',
        'active'
    ];

    protected array $exceptFilter = [
        'excepts'
    ];

    /**
     * @throws Exception
     */
    public function myOrder(PaginateRequest $request)
    {
        try {
            $requests            = $request->all();
            $method              = $request->get('paginate', 0) == 1 ? 'paginate' : 'get';
            $methodValue         = $request->get('paginate', 0) == 1 ? $request->get('per_page', 10) : '*';
            $frontendOrderColumn = $request->get('order_column') ?? 'id';
            $frontendOrderType   = $request->get('order_by') ?? 'desc';

            return Order::where('order_type', "!=", OrderType::POS)->where(function ($query) use ($requests) {
                $query->where('user_id', auth()->user()->id);
                foreach ($requests as $key => $request) {
                    if (in_array($key, $this->frontendOrderFilter)) {
                        if ($key === "status") {
                            $query->where($key, (int)$request);
                        } else {
                            $query->where($key, 'like', '%' . $request . '%');
                        }
                    }
                    if (in_array($key, $this->exceptFilter)) {
                        $explodes = explode('|', $request);
                        if (is_array($explodes)) {
                            foreach ($explodes as $explode) {
                                $query->where('status', '!=', $explode);
                            }
                        }
                    }
                }
            })->orderBy($frontendOrderColumn, $frontendOrderType)->$method(
                $methodValue
            );
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function myOrderStore(OrderRequest $request)
    {
        try {
            Log::info('Entrou em myOrderStore', $request->all());

            $products = json_decode($request->products, true);
            Log::info('Produtos recebidos', ['products' => $products]);

            return DB::transaction(function () use ($request, $products) {

                Log::info('Iniciando criação do pedido');

                $this->order = Order::create([
                    'order_serial_no' => strtoupper(uniqid('ORD-')),
                    'user_id' => auth()->id(),
                    'subtotal' => $request->subtotal,
                    'total' => $request->total,
                    'discount' => $request->discount ?? 0,
                    'coupon_id' => $request->coupon_id ?? null,
                    'delivery_type' => $request->delivery_type ?? 'delivery',
                    'payment_method' => $request->payment_method,
                    'status' => OrderStatus::PENDING,
                    'notes' => $request->notes ?? null,
                    'shipping_charge' => $request->shipping_charge ?? 0,
                    'shipping_method' => $request->shipping_method ?? 0,
                    'store_id' => $request->store_id
                ]);

                Log::info('Pedido criado', ['order_id' => $this->order->id]);

                // Criação dos produtos do pedido
                foreach ($products as $product) {
                    Log::info('Criando item do pedido', ['product' => $product]);

                    Stock::create([
                        'user_id' => auth()->id(),
                        'product_id' => $product['product_id'],
                        'store_id' => $request->store_id,
                        'order_id' => $this->order->id,
                        'price' => $product['price'],
                        'quantity' => $product['quantity'],
                        'subtotal' => $product['subtotal'],
                        'total' => $product['total'],
                        'type' => 'out',
                        'variation_names' => $product['variation_names'] ?? null,
                        'variation_id' => $product['variation_id'] ?? null,
                        'model_type' => Order::class,
                        'model_id' => $this->order->id,
                        'item_id' => $product['product_id'],
                        'item_type' => \App\Models\Product::class,
                    ]);
                }

                Log::info('Todos os produtos processados com sucesso');

                // Vincula o pedido aos endereços

                if ($request->shipping_id) {
                    $shippingAddress = Address::find($request->shipping_id);
                    if ($shippingAddress) {
                        OrderAddress::create([
                            'order_id'      => $this->order->id,
                            'user_id'       => auth()->id(),
                            'address_type'  => AddressType::SHIPPING,
                            'full_name'     => $shippingAddress->full_name,
                            'email'         => $shippingAddress->email,
                            'country_code'  => $shippingAddress->country_code,
                            'phone'         => $shippingAddress->phone,
                            'country'       => $shippingAddress->country,
                            'address'       => $shippingAddress->address,
                            'state'         => $shippingAddress->state,
                            'city'          => $shippingAddress->city,
                            'zip_code'      => $shippingAddress->zip_code,
                            'latitude'      => $shippingAddress->latitude,
                            'longitude'     => $shippingAddress->longitude
                        ]);
                    }
                }

                if ($request->billing_id) {
                    $billingAddress = Address::find($request->billing_id);
                    if ($billingAddress) {
                        OrderAddress::create([
                            'order_id'      => $this->order->id,
                            'user_id'       => auth()->id(),
                            'address_type'  => AddressType::BILLING,
                            'full_name'     => $billingAddress->full_name,
                            'email'         => $billingAddress->email,
                            'country_code'  => $billingAddress->country_code,
                            'phone'         => $billingAddress->phone,
                            'country'       => $billingAddress->country,
                            'address'       => $billingAddress->address,
                            'state'         => $billingAddress->state,
                            'city'          => $billingAddress->city,
                            'zip_code'      => $billingAddress->zip_code,
                            'latitude'      => $billingAddress->latitude,
                            'longitude'     => $billingAddress->longitude
                        ]);
                    }
                }


                Log::info('Endereços vinculados ao pedido');

                return $this->order;
            });
        } catch (\Exception $exception) {
            Log::error('Erro ao criar pedido: ' . $exception->getMessage(), [
                'trace' => $exception->getTraceAsString(),
            ]);
            return response(['status' => false, 'message' => 'Erro ao criar pedido: ' . $exception->getMessage()], 500);
        }
    }



    /**
     * @throws Exception
     */
    public function show(Order $order): Order|array
    {
        try {
            if ($order->user_id == Auth::user()->id) {
                return $order;
            }
            return [];
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function changeStatus(Order $order, OrderStatusRequest $request): Order
    {
        try {
            if ($order->user_id == Auth::user()->id) {
                if ($request->status == OrderStatus::CANCELED) {
                    if ($order->status >= OrderStatus::CONFIRMED) {
                        throw new Exception(trans('all.message.order_confirmed'), 422);
                    } else {
                        if ($order->transaction) {
                            app(PaymentService::class)->cashBack(
                                $order,
                                'credit',
                                rand(111111111111111, 99999999999999)
                            );
                        }
                        SendOrderMail::dispatch(['order_id' => $order->id, 'status' => $request->status]);
                        SendOrderSms::dispatch(['order_id' => $order->id, 'status' => $request->status]);
                        SendOrderPush::dispatch(['order_id' => $order->id, 'status' => $request->status]);

                        $stocks = Stock::where(['model_type' => Order::class, 'model_id' => $order->id])->get();
                        foreach ($stocks as $stock) {
                            $stock->status = Status::INACTIVE;
                            $stock->save();
                        };

                        $order->status = $request->status;
                        $order->save();
                    }
                }
            }
            return $order;
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }
}
