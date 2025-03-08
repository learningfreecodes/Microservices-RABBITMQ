<?php

namespace App\Console\Commands;

use Ecommerce\Shared\Services\MessageQueue\RabbitMQService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Random\RandomException;

/**
 * Команда работающая с сообщениями по ключу роутинга "inventory_processed"
 */
class InventoryConsumerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:consume';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume inventory processed events and create notifications';

    private RabbitMQService $rabbitMQService;

    /**
     * NotificationConsumerCommand constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        $config = config('queue.connections.rabbitmq');

        if (!$config) {
            throw new \Exception('RabbitMQ configuration is missing.');
        }

        // Instantiate RabbitMQService with the required configuration
        $this->rabbitMQService = new RabbitMQService($config);
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        //Подписка на события 'inventory_processed'
        $this->rabbitMQService->consumeMessages('inventory_processed', function ($message) {
            try {
                // Десериализация сообщения
                $data = json_decode($message->body, true);
                $orderId = $data['order_id'];

                // Восттанавливаем по данным события запаиси о заказах в Базе Данных
                $order = DB::table('orders')->where('order_id', $orderId)->first();

                if (!$order) {
                    throw new \Exception("Order with ID {$orderId} not found.");
                }

                // Создаем нотификацию в Базе Данных о том что заказа обработан

                $notificationId = DB::table('notifications')->insertGetId([
                    'user_id' => $order->user_id,
                    'title' => 'Your order has been processed!',
                    'message' => "Order ID {$orderId} has been successfully processed. Thank you for shopping with us!",
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Отправка нотификацию на какой-нибудь внешний сервис
                $isSent = $this->sendNotification($notificationId);

                // Обновление статуса нотификации
                DB::table('notifications')
                    ->where('id', $notificationId)
                    ->update(['status' => $isSent ? 'sent' : 'failed']);

                // Логгируем нотификацию
                DB::table('notification_logs')->insert([
                    'notification_id' => $notificationId,
                    'status' => $isSent ? 'sent' : 'failed',
                    'notes' => $isSent ? 'Notification sent successfully.' : 'Notification sending failed.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                //Сообщаем RabbitMQ о том что сообщение обработано
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                $this->info("Notification processed successfully for Order ID: {$orderId}");
            } catch (\Exception $e) {
                // Log error to console
                $this->error("Notification processing error: " . $e->getMessage());

                // Сообщаем RabbitMQ о том что при обработке сообщения произошла ошибка
                if (isset($message->delivery_info)) {
                    $message->delivery_info['channel']->basic_reject($message->delivery_info['delivery_tag'], true);
                }
            }
        });
    }

    /**
     * Simulate sending a notification.
     *
     * @param int $notificationId
     * @return bool
     * @throws RandomException
     */
    private function sendNotification(int $notificationId): bool
    {
        // Simulate sending logic (e.g., API call or email)
        return random_int(0, 1) === 1;
    }
}
