#!/bin/bash

echo "============================================"
echo "  IAE Final Project — Stop All Services"
echo "============================================"

echo ""
echo "🔷 Stopping GraphQL Service..."
cd graphql-service && docker compose down && cd ..

echo ""
echo "🛒 Stopping Order Service..."
cd order-service && docker compose down && cd ..

echo ""
echo "📦 Stopping Product Service..."
cd product-service && docker compose down && cd ..

echo ""
echo "👤 Stopping User Service..."
cd user-service && docker compose down && cd ..

echo ""
echo "🐇 Stopping RabbitMQ..."
cd rabbitmq && docker compose down && cd ..

echo ""
echo "✅ Semua service dihentikan."
echo ""
