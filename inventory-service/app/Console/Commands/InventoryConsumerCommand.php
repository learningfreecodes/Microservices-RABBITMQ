<?php

namespace App\Console\Commands;

use Ecommerce\Shared\Services\MessageQueue\RabbitMQService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->rabbitMQService->consumeMessages('order_created', function ($message) {
            try {
                // Decode the message body
                $orderData = json_decode($message->body, true);
                $orderId = $orderData['payload']['order_id'];

                $orderItems = DB::table('order_items')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->where('orders.order_id', $orderId)
                    ->select('order_items.quantity', 'products.id as product_id', 'products.name', 'products.stock')
                    ->get();

                // Loop through the order items and update inventory
                foreach ($orderItems as $orderItem) {
                    if ($orderItem->stock >= $orderItem->quantity) {
                        // Update the stock
                        DB::table('products')
                            ->where('id', $orderItem->product_id)
                            ->decrement('stock', $orderItem->quantity);

                        // Log the inventory change into the inventory_logs table
                        DB::table('inventory_logs')->insert([
                            'product_id' => $orderItem->product_id,
                            'quantity' => -$orderItem->quantity,
                            'type' => 'sale',
                            'notes' => "Sold {$orderItem->quantity} of {$orderItem->name}",
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        // If not enough stock, log an error (or handle it in some way)
                        $this->error("Not enough stock for product ID: {$orderItem->product_id}");
                    }
                }

                // Publish to the next queue
                $this->rabbitMQService->publishMessage('inventory_processed', [
                    'order_id' => $orderId,
                ]);

                // Acknowledge the message
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                $this->info('Inventory updated successfully for Order ID: ' . $orderId);
            } catch (\Exception $e) {
                // Log any errors to the console
                $this->error('Inventory processing error: ' . $e->getMessage());

                // Reject the message and requeue it
                if (isset($message->delivery_info)) {
                    $message->delivery_info['channel']->basic_reject($message->delivery_info['delivery_tag'], true);
                }
            }
        });
    }
}
