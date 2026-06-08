<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateProductStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $productId,
        public int $quantity
    ) {
        // Arahkan ke antrian yang sama dengan product service
        $this->onQueue('product-stock-update');
    }

    public function handle(): void
    {
        // Job ini di-dispatch dari Order Service ke RabbitMQ.
        // Handle-nya ada di Product Service.
    }
}