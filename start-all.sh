#!/bin/bash

echo "============================================"
echo "  IAE Final Project — Start All Services"
echo "============================================"

echo ""
echo "📡 [1/6] Membuat Docker Network..."
docker network create laravel-net 2>/dev/null || echo "  Network sudah ada, skip."

echo ""
echo "🐇 [2/6] Menjalankan RabbitMQ..."
cd rabbitmq && docker compose up -d && cd ..
sleep 5

echo ""
echo "👤 [3/6] Menjalankan User Service..."
cd user-service && docker compose up -d --build && cd ..

echo ""
echo "📦 [4/6] Menjalankan Product Service..."
cd product-service && docker compose up -d --build && cd ..

echo ""
echo "🛒 [5/6] Menjalankan Order Service..."
cd order-service && docker compose up -d --build && cd ..

echo ""
echo "🔷 [6/6] Menjalankan GraphQL Service..."
cd graphql-service && docker compose up -d --build && cd ..

echo ""
echo "⏳ Menunggu semua container siap (15 detik)..."
sleep 15

echo ""
echo "🗃️  Menjalankan Migrasi Database..."
docker exec user-service-app    php artisan migrate --force
docker exec product-service-app php artisan migrate --force
docker exec order-service-app   php artisan migrate --force

echo ""
echo "🔄 Menjalankan Queue Worker (Product Service — RabbitMQ Consumer)..."
docker exec -d product-service-app php artisan queue:work \
  --queue=product-stock-update \
  --verbose \
  --tries=3 \
  --timeout=90

echo ""
echo "✅ Selesai! Buka Docker Desktop → tab Containers untuk verifikasi."
echo "   Harus terlihat 5 grup: rabbitmq, user-service, product-service,"
echo "   order-service, graphql-service — masing-masing dengan"
echo "   beberapa container di dalamnya."
echo ""
echo "============================================"
echo "  ✅ Semua service berjalan!"
echo "============================================"
echo ""
echo "  User Service    → http://localhost:8000/api/users"
echo "  Product Service → http://localhost:8001/api/products"
echo "  Order Service   → http://localhost:8002/api/orders"
echo "  GraphQL Manual  → http://localhost:8003/graphql"
echo "  RabbitMQ UI     → http://localhost:15672 (guest/guest)"
echo ""
echo "  ⚡ Hasura Cloud (online, terpisah dari Docker):"
echo "      https://<nama-project>.hasura.app/v1/graphql"
echo "      (database: Neon Postgres — lihat Langkah 4.12 di README)"
echo ""
