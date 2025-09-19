<?php

namespace App\Http\Requests;

use App\Enums\Activity;
use App\Enums\OrderType;
use App\Rules\ValidJsonOrder;
use Illuminate\Validation\Rule;
use Smartisan\Settings\Facades\Settings;
use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        // Se for atualização de informações de envio, aplica regras específicas
        if ($this->routeIs('order.updateShippingInfos')) {
            return [
                'orders'                => 'required|array|min:1',
                'orders.*.id_pedido'    => ['required', 'integer', Rule::exists('orders', 'id')->whereNotIn('status', [7, 8])],
                'orders.*.codigoObjeto' => 'required|string',
                'orders.*.idPrePostagem' => 'required|string',
            ];
        }

        // Verifica se é uma atualização de pedido
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');

        // Tipo de pedido
        $orderType = (int) $this->input('order_type');

        return [
            'subtotal'        => $isUpdate ? ['nullable', 'numeric'] : ['required', 'numeric'],
            'discount'        => ['nullable', 'numeric'],
            'shipping_charge' => $orderType == OrderType::DELIVERY ? ['required', 'numeric'] : ['nullable'],
            'shipping_method' => ['nullable', 'string'],
            'idPrePostagem'   => ['nullable', 'string'],
            'codigoObjeto'    => ['nullable', 'string'],
            'idRecibo'    => ['nullable', 'string'],
            'nfe_id'    => ['nullable', 'string'],
            'nfe_num'    => ['nullable', 'string'],
            'nfe_xml'    => ['nullable', 'string'],
            'nfe_chave'    => ['nullable', 'string'],
            'tax'             => $isUpdate ? ['nullable', 'numeric'] : ['required', 'numeric'],
            'total'           => $isUpdate ? ['nullable', 'numeric'] : ['required', 'numeric'],
            'order_type'      => $isUpdate ? ['nullable', 'numeric'] : ['required', 'numeric'],
            'shipping_id'     => $orderType == OrderType::DELIVERY ? ['required', 'numeric'] : ['nullable'],
            'billing_id'      => $orderType == OrderType::DELIVERY ? ['required', 'numeric'] : ['nullable'],
            'outlet_id'       => $orderType == OrderType::PICK_UP ? ['required', 'numeric', 'not_in:0'] : ['nullable'],
            'coupon_id'       => ['nullable', 'numeric'],
            'source'          => $isUpdate ? ['nullable', 'numeric'] : ['required', 'numeric'],
            'payment_method'  => $isUpdate ? ['nullable', 'numeric'] : ['required', 'numeric'],
            'products'        => $isUpdate ? ['nullable', 'json', new ValidJsonOrder] : ['required', 'json', new ValidJsonOrder]
        ];
    }


    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('order_type')) {
                $orderType = $this->input('order_type');

                // Depuração para ver o valor recebido
                \Log::info('Validação personalizada OrderRequest', [
                    'Tipo recebido' => $orderType,
                    'Tipo convertido' => (int) $orderType,
                    'Tipos permitidos' => [OrderType::DELIVERY, OrderType::PICK_UP]
                ]);


                if (!in_array((int) $orderType, [OrderType::DELIVERY, OrderType::PICK_UP])) {
                    $validator->errors()->add('order_type', 'O tipo de pedido selecionado não está disponível. Tente outro ou entre em contato com o suporte.');
                }
            }
        });
    }
}
