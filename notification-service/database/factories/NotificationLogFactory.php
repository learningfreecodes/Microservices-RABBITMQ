<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\NotificationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationLog>
 */
class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'notification_id' => Notification::factory(),
            'status' => $this->faker->randomElement(['sent', 'failed']),
            'notes' => $this->faker->sentence(),
        ];
    }
}
