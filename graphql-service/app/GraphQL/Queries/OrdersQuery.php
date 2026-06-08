<?php

namespace App\GraphQL\Queries;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class OrdersQuery extends Query
{
    protected $attributes = [
        'name' => 'orders',
        'description' => 'Ambil semua data pesanan dari Order Service',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Order'));
    }

    public function args(): array
    {
        return [];
    }

    public function resolve($root, array $args): array
    {
        try {
            $client = new Client(['timeout' => 5.0]);
            $response = $client->get(env('ORDER_SERVICE_URL') . '/api/orders');
            $body = json_decode($response->getBody()->getContents(), true);
            return $body['data'] ?? [];
        } catch (RequestException $e) {
            return [];
        }
    }
}