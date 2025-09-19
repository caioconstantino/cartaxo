<?php

namespace App\Http\PaymentGateways\Gateways;


use Exception;
use App\Enums\Activity;
use App\Enums\GatewayMode;
use App\Models\Currency;
use App\Models\PaymentGateway;
use App\Services\PaymentService;
use App\Services\PaymentAbstract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SantiGraviano\LaravelMercadoPago\MP;
use Smartisan\Settings\Facades\Settings;

class Mercadopago extends PaymentAbstract
{
    public mixed $response;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $paymentService = new PaymentService();
        parent::__construct($paymentService);
        $this->paymentGateway = PaymentGateway::with('gatewayOptions')->where(['slug' => 'mercadopago'])->first();
        if (!blank($this->paymentGateway)) {
            $this->paymentGatewayOption = $this->paymentGateway->gatewayOptions->pluck('value', 'option');
            $this->gateway = new MP($this->paymentGatewayOption['mercadopago_client_id'], $this->paymentGatewayOption['mercadopago_client_secret']);
        }
    }

    public function payment($order, $request): \Illuminate\Http\RedirectResponse
    {
        try {
            Log::info('Iniciando pagamento MercadoPago', ['order_id' => $order->id]);

            $currencyCode = 'ARS';
            $currencyId = Settings::group('site')->get('site_default_currency');
            if (!blank($currencyId)) {
                $currency = Currency::find($currencyId);
                if ($currency) {
                    $currencyCode = $currency->code;
                }
            }

            Log::info('Moeda utilizada', ['currency_code' => $currencyCode]);

            $notificationUrl = route('payment.webhook');

            $data = [
                'items' => [
                    [
                        'id' => $order->order_serial_no,
                        'title' => $order->order_serial_no,
                        'quantity' => 1,
                        'currency_id' => $currencyCode,
                        'unit_price' => floatval($order->total),
                    ]
                ],
                'external_reference' => $order->id,
                'notification_url' => $notificationUrl,
                'auto_return' => 'approved',
                'back_urls' => (object)[
                    'success' => route('payment.success', ['order' => $order, 'paymentGateway' => 'mercadopago']),
                    'failure' => route('payment.fail', ['order' => $order, 'paymentGateway' => 'mercadopago']),
                    'pending' => route('payment.index', ['order' => $order, 'paymentGateway' => 'mercadopago'])
                ]
            ];

            Log::info('Payload enviado para MercadoPago', ['payload' => $data]);

            $response = $this->gateway->create_preference($data);

            Log::info('Resposta MercadoPago', ['response' => $response]);

            if (isset($response['response']['client_id'])) {
                $redirectUrl = $this->paymentGatewayOption['mercadopago_mode'] == GatewayMode::SANDBOX
                    ? $response['response']['sandbox_init_point']
                    : $response['response']['init_point'];

                Log::info('Redirecionando para URL de pagamento', ['url' => $redirectUrl]);

                return redirect()->to($redirectUrl);
            } else {
                Log::warning('client_id não encontrado na resposta do MercadoPago', ['response' => $response]);
                return redirect()->route('payment.index', ['order' => $order, 'paymentGateway' => 'mercadopago'])->with(
                    'error',
                    trans('all.message.something_wrong')
                );
            }
        } catch (Exception $e) {
            Log::error('Erro ao criar pagamento no MercadoPago', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('payment.index', ['order' => $order, 'paymentGateway' => 'mercadopago'])->with(
                'error',
                $e->getMessage()
            );
        }
    }


    public function status(): bool
    {
        $paymentGateways = PaymentGateway::where(['slug' => 'mercadopago', 'status' => Activity::ENABLE])->first();
        if ($paymentGateways) {
            return true;
        }
        return false;
    }

    public function success($order, $request): \Illuminate\Http\RedirectResponse
    {
        try {

            Log::info("AQUI É SUCCESS: Status - " . ($request->input('status') ?? 'N/A') .
                " | Payment ID - " . ($request->input('payment_id') ?? 'N/A'));

            if (isset($request['status']) && $request['status'] == 'approved') {
                $this->paymentService->payment($order, 'mercadopago', $request['payment_id']);
                return redirect()->route('payment.successful', ['order' => $order])->with(
                    'success',
                    trans('all.message.payment_successful')
                );
            } else {
                return redirect()->route('payment.fail', ['order' => $order, 'paymentGateway' => 'mercadopago'])->with(
                    'error',
                    $this->response['message'] ?? trans('all.message.something_wrong')
                );
            }
        } catch (Exception $e) {
            Log::info($e->getMessage());
            DB::rollBack();
            return redirect()->route('payment.fail', ['order' => $order, 'paymentGateway' => 'mercadopago'])->with(
                'error',
                $e->getMessage()
            );
        }
    }

    public function fail($order, $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('payment.cancel', ['order' => $order, 'paymentGateway' => 'mercadopago'])->with(
            'error',
            trans('all.message.something_wrong')
        );
    }

    public function cancel($order, $request): \Illuminate\Http\RedirectResponse
    {
        return redirect('/#/checkout/payment');
    }
}
