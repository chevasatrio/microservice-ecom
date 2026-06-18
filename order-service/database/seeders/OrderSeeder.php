<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $orders = [
            [
                'id'          => 'c1111111-1111-1111-1111-111111111111',
                'code'        => 'OR-AAAAAAAA',
                'product_id'  => 'b1111111-1111-1111-1111-111111111111', // Kaos Polos Premium
                'user_id'     => 'a1111111-1111-1111-1111-111111111111', // Budi Santoso
                'status'      => 'completed',
                'total_price' => 150000, // 2 x 75000
                'quantity'    => 2,
            ],
            [
                'id'          => 'c2222222-2222-2222-2222-222222222222',
                'code'        => 'OR-BBBBBBBB',
                'product_id'  => 'b2222222-2222-2222-2222-222222222222', // Sepatu Sneakers Casual
                'user_id'     => 'a2222222-2222-2222-2222-222222222222', // Siti Aminah
                'status'      => 'completed',
                'total_price' => 250000, // 1 x 250000
                'quantity'    => 1,
            ],
            [
                'id'          => 'c3333333-3333-3333-3333-333333333333',
                'code'        => 'OR-CCCCCCCC',
                'product_id'  => 'b5555555-5555-5555-5555-555555555555', // Jaket Hoodie Oversize
                'user_id'     => 'a3333333-3333-3333-3333-333333333333', // Andi Wijaya
                'status'      => 'pending',
                'total_price' => 360000, // 2 x 180000
                'quantity'    => 2,
            ],
            [
                'id'          => 'c4444444-4444-4444-4444-444444444444',
                'code'        => 'OR-DDDDDDDD',
                'product_id'  => 'b3333333-3333-3333-3333-333333333333', // Tas Selempang Kanvas
                'user_id'     => 'a4444444-4444-4444-4444-444444444444', // Dewi Lestari
                'status'      => 'pending',
                'total_price' => 120000, // 1 x 120000
                'quantity'    => 1,
            ],
            [
                'id'          => 'c5555555-5555-5555-5555-555555555555',
                'code'        => 'OR-EEEEEEEE',
                'product_id'  => 'b7777777-7777-7777-7777-777777777777', // Dompet Kulit Pria
                'user_id'     => 'a5555555-5555-5555-5555-555555555555', // Rian Pratama
                'status'      => 'completed',
                'total_price' => 190000, // 2 x 95000
                'quantity'    => 2,
            ],
        ];

        foreach ($orders as $order) {
            Order::updateOrCreate(['id' => $order['id']], $order);
        }
    }
}
