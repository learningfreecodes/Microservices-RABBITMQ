<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Ecommerce\Shared\DTOs\OrderDTO;
use Ecommerce\Shared\Events\OrderCreated;
use Ecommerce\Shared\Services\MessageQueue\RabbitMQService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Контроллер на который приходит заказ
 */
class OrderController extends Controller
{
    private RabbitMQService $rabbitMQService;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $config = config('queue.connections.rabbitmq');

        if (!$config) {
            throw new Exception('RabbitMQ configuration is missing.');
        }

        // Instantiate RabbitMQService with the required configuration
        $this->rabbitMQService = new RabbitMQService($config);
    }

    /**
     * Create a new order.
     *
     * @throws Exception
     */
    public function createOrder(Request $request): JsonResponse
    {
        // Валидация реквеста
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'total' => 'required|numeric|min:0',
        ]);

        try {
            // Создаётся DTO-объект после валидации данных
            $orderDTO = new OrderDTO([
                'order_id' => uniqid('order_'),
                'user_id' => $validated['user_id'],
                'total' => $validated['total'],
                'items' => $validated['items'],
            ]);

            // Данные DTO сохраняются в Базу Данных
            $order = Order::query()->create([
                'order_id' => $orderDTO->orderId,
                'user_id' => $orderDTO->userId,
                'total' => $orderDTO->total,
                'status' => $orderDTO->status,
            ]);

            // Элементы заказа сохраняются в Базу Данных
            foreach ($orderDTO->items as $item) {
                $product = Product::query()->findOrFail($item['product_id']);

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);
            }

            // Create and publish the event using DTO
            $event = new OrderCreated($orderDTO);
            //Публикуется сообщение в RabbitMQ о том что заказ создан для его обработки
            $this->rabbitMQService->publishMessage('order_created', $event->toArray());

            return response()->json(['message' => 'Order placed successfully!'], 201);
        } catch (Exception $e) {
            Log::error('Failed to place order', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Failed to place order.'], 500);
        }
    }
}
