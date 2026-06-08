<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateProductStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $productId,
        public int    $quantity
    ) {}

    public function handle(): void
    {
        $product = Product::find($this->productId);

        if (!$product) {
            Log::warning("Product not found: {$this->productId}");
            return;
        }

        // Simulasi proses lambat (untuk demo asinkron)
        sleep(3);

        $product->decrement('stock', $this->quantity);

        Log::info("Stock updated for product {$this->productId}: -{$this->quantity}");
    }
}