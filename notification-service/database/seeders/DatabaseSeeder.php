<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed Notifications
        Notification::factory(15)->create();

        // Seed NotificationLogs
        NotificationLog::factory(30)->create();
    }
}
