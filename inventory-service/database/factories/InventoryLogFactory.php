<?php

namespace Database\Factories;

use App\Models\InventoryLog;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryLog>
 */
class InventoryLogFactory extends Factory
{
    protected $model = InventoryLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(-50, 50),
            'type' => $this->faker->randomElement(['restock', 'sale']),
            'notes' => $this->faker->sentence(),
        ];
    }
}
