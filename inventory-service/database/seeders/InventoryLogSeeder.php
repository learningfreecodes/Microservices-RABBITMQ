<?php

namespace Database\Seeders;

use App\Models\InventoryLog;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InventoryLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();

        foreach ($products as $product) {
            InventoryLog::create([
                'product_id' => $product->id,
                'quantity' => rand(1, 10),
                'type' => 'restock',
                'notes' => 'Restocked with new batch',
            ]);

            InventoryLog::create([
                'product_id' => $product->id,
                'quantity' => rand(1, 5),
                'type' => 'sale',
                'notes' => 'Sold products',
            ]);
        }
    }
}
