#!/bin/bash

# Resolve the directory where this script lives
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "============================================"
echo "  IAE Final Project — Stop All Services"
echo "============================================"

echo ""
echo "🔷 Stopping GraphQL Service..."
(cd "$SCRIPT_DIR/graphql-service" && docker compose down)

echo ""
echo "🛒 Stopping Order Service..."
(cd "$SCRIPT_DIR/order-service" && docker compose down)

echo ""
echo "📦 Stopping Product Service..."
(cd "$SCRIPT_DIR/product-service" && docker compose down)

echo ""
echo "👤 Stopping User Service..."
(cd "$SCRIPT_DIR/user-service" && docker compose down)

echo ""
echo "🐇 Stopping RabbitMQ..."
(cd "$SCRIPT_DIR/rabbitmq" && docker compose down)

echo ""
echo "✅ Semua service dihentikan."
echo ""
