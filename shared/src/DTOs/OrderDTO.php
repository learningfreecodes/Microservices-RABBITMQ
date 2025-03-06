<?php

namespace Ecommerce\Shared\DTOs;

class OrderDTO
{
    public string $orderId;
    public int $userId;
    public float $total;
    public array $items;
    public string $status;

    public function __construct(array $data)
    {
        $this->orderId = $data['order_id'];
        $this->userId = $data['user_id'];
        $this->total = $data['total'];
        $this->items = $data['items'];
        $this->status = $data['status'] ?? 'pending';
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'user_id' => $this->userId,
            'total' => $this->total,
            'items' => $this->items,
            'status' => $this->status,
        ];
    }
}