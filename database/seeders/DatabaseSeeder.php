<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default profile keeps rich demo data.
        // For minimal mode use:
        // php artisan db:seed --class=CleanDemoSeeder
        // For heavy mode (20+ leads/quotes/invoices) use:
        // php artisan db:seed --class=HeavyDemoSeeder
        $this->call([
            DemoDataSeeder::class,
        ]);
    }
}
