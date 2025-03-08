<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Модель работающая с таблицей товаров в Базе данных
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
    ];

    /**
     * Ссылка на модель инвентаризации
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inventoryLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }
}
