<?php

namespace Ecommerce\Shared\Events;

use Ecommerce\Shared\DTOs\OrderDTO;

class OrderCreated extends Event
{
    public function __construct(OrderDTO $order)
    {
        parent::__construct($order->toArray());
    }
}