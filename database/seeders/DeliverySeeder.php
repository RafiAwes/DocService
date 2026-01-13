<?php

namespace Database\Seeders;

use App\Models\Delivery;
use Illuminate\Database\Seeder;

class DeliverySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $deliveries = [
            [
                'title' => 'Standard Delivery',
                'description' => '5-7 business days',
                'price' => 29.99,
            ],
            [
                'title' => 'Express Delivery',
                'description' => '2-3 business days',
                'price' => 49.99,
            ],
            [
                'title' => 'Overnight Delivery',
                'description' => 'Next business day',
                'price' => 99.99,
            ],
            [
                'title' => 'Local Pickup',
                'description' => 'Pick up from our office',
                'price' => 0.00,
            ],
        ];

        foreach ($deliveries as $delivery) {
            Delivery::firstOrCreate(
                ['title' => $delivery['title']],
                $delivery
            );
        }
    }
}
