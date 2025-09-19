<?php

use App\Enums\OrderStatus;

return [
    OrderStatus::PENDING    => "Pendente",
    OrderStatus::CONFIRMED  => "Confirmado",
    OrderStatus::ON_THE_WAY => "A Caminho",
    OrderStatus::DELIVERED  => "Entregue",
    OrderStatus::CANCELED   => "Cancelado",
    OrderStatus::REJECTED   => "Rejeitado",
];
