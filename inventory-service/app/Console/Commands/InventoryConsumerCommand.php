<?php

namespace App\Console\Commands;

use Ecommerce\Shared\Services\MessageQueue\RabbitMQService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Это команда консумера для подписки на сообщения
 */
class InventoryConsumerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:consume';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume order created events';

    /**
     * Сервис Rabbit, который использует данная команда консумера
     *
     * @var RabbitMQService
     */
    private RabbitMQService $rabbitMQService;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();
        $config = config('queue.connections.rabbitmq');

        if (!$config) {
            throw new Exception('RabbitMQ configuration is missing.');
        }

        // Instantiate RabbitMQService with the required configuration
        $this->rabbitMQService = new RabbitMQService($config);
    }

    /**
     * В этой команде происходит подписывание на сообщения RabbitMQ
     */
    public function handle(): void
    {
        /*
         * Внутрь функции "consumeMessages" происходит передача коллбэка в виде анонимной функции.
         * В функции имеется параметр $message-сообщение
         */

        $this->rabbitMQService->consumeMessages('order_created', function ($message) {
            try {
                // Декодирование тела сообщения "$message->body"
                $orderData = json_decode($message->body, true);
                //Получает идентификатор заказа
                $orderId = $orderData['payload']['order_id'];

                //Получает элементы связанные с заказом из Базы Данных
                $orderItems = DB::table('order_items')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->where('orders.order_id', $orderId)
                    ->select('order_items.quantity', 'products.id as product_id', 'products.name', 'products.stock')
                    ->get();

                // Цикл по элементам заказа
                foreach ($orderItems as $orderItem) {
                    //Если остаток товара больше заказанного количества
                    if ($orderItem->stock >= $orderItem->quantity) {
                        // Уменьшаем остаток товара
                        DB::table('products')
                            ->where('id', $orderItem->product_id)
                            ->decrement('stock', $orderItem->quantity);

                        // Сохраняем количество в инвертаризационные логи. Это логи о продаже товара
                        DB::table('inventory_logs')->insert([
                            'product_id' => $orderItem->product_id,
                            'quantity' => -$orderItem->quantity,
                            'type' => 'sale',
                            'notes' => "Sold {$orderItem->quantity} of {$orderItem->name}",
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        //Сообщение что товаров недостаточно для продажи
                        $this->error("Not enough stock for product ID: {$orderItem->product_id}");
                    }
                }

                // Создаем новое сообщение в Rabbit о том что осуществляется инвертаризация
                $this->rabbitMQService->publishMessage('inventory_processed', [
                    'order_id' => $orderId,
                ]);

                // Даёт знать rabbitMQ что сообщение успешно получено. Чтобы избежать переполнений памяти. Вообщем так корректно будет
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                $this->info('Inventory updated successfully for Order ID: ' . $orderId);
            } catch (\Exception $e) {
                // Log any errors to the console
                $this->error('Inventory processing error: ' . $e->getMessage());

                // Даёт знать rabbitMQ что произошла ошибка при обработки сообщений rabbitMQ
                if (isset($message->delivery_info)) {
                    $message->delivery_info['channel']->basic_reject($message->delivery_info['delivery_tag'], true);
                }
            }
        });
    }
}
