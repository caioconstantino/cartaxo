<?php

namespace App\Http\Controllers\Frontend;


use App\Enums\Activity;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\SendOrderGotMail;
use App\Events\SendOrderGotPush;
use App\Events\SendOrderGotSms;
use App\Events\SendOrderMail;
use App\Events\SendOrderPush;
use App\Events\SendOrderSms;
use App\Http\Requests\PaymentRequest;
use App\Libraries\AppLibrary;
use App\Models\Currency;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\ThemeSetting;
use App\Services\PaymentManagerService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Smartisan\Settings\Facades\Settings;
use Illuminate\Support\Facades\Http; // Para usar o HTTP client
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private PaymentManagerService $paymentManagerService;

    public function __construct(PaymentManagerService $paymentManagerService)
    {
        $this->paymentManagerService = $paymentManagerService;
    }

    public function index(PaymentGateway $paymentGateway, Order $order): \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse
    {
        $credit          = false;
        $cashOnDelivery  = false;
        $paymentGateways = PaymentGateway::with('gatewayOptions')->where(['status' => Activity::ENABLE])->get();
        $company         = Settings::group('company')->all();
        $site            = Settings::group('site')->all();
        $logo            = ThemeSetting::where(['key' => 'theme_logo'])->first();
        $faviconLogo     = ThemeSetting::where(['key' => 'theme_favicon_logo'])->first();
        $currency        = Currency::findOrFail(Settings::group('site')->get('site_default_currency'));
        if ($order?->user?->balance >= $order->total) {
            $credit = true;
        }

        if ($site['site_cash_on_delivery'] == Activity::ENABLE) {
            $cashOnDelivery = true;
        }

        if (blank($order->transaction) && $order->payment_status === PaymentStatus::UNPAID) {
            return view('payment', [
                'company'         => $company,
                'logo'            => $logo,
                'currency'        => $currency,
                'faviconLogo'     => $faviconLogo,
                'paymentGateways' => $paymentGateways,
                'order'           => $order,
                'creditAmount'    => AppLibrary::currencyAmountFormat($order->user?->balance),
                'credit'          => $credit,
                'cashOnDelivery'  => $cashOnDelivery,
                'paymentMethod'   => $paymentGateway
            ]);
        }
        return redirect()->route('home')->with('error', trans('all.message.payment_canceled'));
    }

    public function payment(Order $order, PaymentRequest $request)
    {
        if ($this->paymentManagerService->gateway($request->paymentMethod)->status()) {
            $className = 'App\\Http\\PaymentGateways\\PaymentRequests\\' . ucfirst($request->paymentMethod);
            $gateway   = new $className;
            $request->validate($gateway->rules());
            return $this->paymentManagerService->gateway($request->paymentMethod)->payment($order, $request);
        } else {
            return redirect()->route('payment.index', ['paymentGateway' => $request->paymentMethod, 'order' => $order])->with(
                'error',
                trans('all.message.payment_gateway_disable')
            );
        }
    }

    public function webhook(Request $request)
    {
        try {
            // Captura o ID do pagamento enviado pelo Mercado Pago
            $resourceId = $request->get('data')['id'] ?? null;

            Log::warning("$resourceId");

            if ($resourceId) {
                // Faz a requisição para obter os detalhes do pagamento
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.mercadopago.access_token'),
                ])->get("https://api.mercadopago.com/v1/payments/$resourceId");

                // Verifica se a requisição foi bem-sucedida
                if ($response->successful()) {
                    $payment = $response->json();
                    $status = $payment['status'] ?? null;
                    $orderReference = $payment['external_reference'] ?? null;

                    Log::warning("$status || $orderReference");
                    if ($status && $orderReference) {
                        if ($status === 'approved') {
                            Log::warning("Chegou aqui");
                        
                            $order = Order::find($orderReference);
                            if ($order) {
                                $paymentGateway = PaymentGateway::where('slug', 'mercadopago')->first();
                                if ($paymentGateway) {
                                    $request->merge([
                                        'status' => $status ?? null,
                                        'payment_id' => $resourceId ?? null, // O ID do pagamento no Mercado Pago
                                    ]);
                                    
                                    // Chama o método success diretamente sem retorno
                                    $this->success($paymentGateway, $order, $request);
                                }
                            }
                        } elseif ($status === 'rejected') {
                            Log::warning("Deu bosta aqui");

                            // Redirecionar para a rota de falha
                            redirect()->route('payment.fail', [
                                'order' => $orderReference,
                                'paymentGateway' => 'mercadopago'
                            ]);
                        }
                    } else {
                        Log::warning("Status ou referência do pedido não encontrados para o pagamento ID: {$resourceId}");
                    }
                } else {
                    Log::error("Erro ao consultar o pagamento no Mercado Pago. ID: {$resourceId}, Status: {$response->status()}");
                }
            }

            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            Log::error('Erro no Webhook MercadoPago: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }



    public function success(PaymentGateway $paymentGateway, Order $order, Request $request)
    {
        return $this->paymentManagerService->gateway($paymentGateway->slug)->success($order, $request);
    }

    public function fail(PaymentGateway $paymentGateway, Order $order, Request $request)
    {
        return $this->paymentManagerService->gateway($paymentGateway->slug)->fail($order, $request);
    }

    public function cancel(PaymentGateway $paymentGateway, Order $order, Request $request)
    {
        return $this->paymentManagerService->gateway($paymentGateway->slug)->cancel($order, $request);
    }

    public function successful(Order $order): \Illuminate\Foundation\Application|\Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse|\Illuminate\Contracts\Foundation\Application
    {
        try {
            SendOrderMail::dispatch(['order_id' => $order->id, 'status' => OrderStatus::PENDING]);
            SendOrderSms::dispatch(['order_id' => $order->id, 'status' => OrderStatus::PENDING]);
            SendOrderPush::dispatch(['order_id' => $order->id, 'status' => OrderStatus::PENDING]);

            SendOrderGotMail::dispatch(['order_id' => $order->id]);
            SendOrderGotSms::dispatch(['order_id' => $order->id]);
            SendOrderGotPush::dispatch(['order_id' => $order->id]);
        } catch (\Exception $e) {
        }

        return redirect('/account/order-details/' . $order->id . '?status=success');
    }
}

