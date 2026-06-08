<?php

namespace App\GraphQL\Queries;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class ProductsQuery extends Query
{
    protected $attributes = [
        'name' => 'products',
        'description' => 'Ambil semua data produk dari Product Service',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Product'));
    }

    public function args(): array
    {
        return [];
    }

    public function resolve($root, array $args): array
    {
        try {
            $client = new Client(['timeout' => 5.0]);
            $response = $client->get(env('PRODUCT_SERVICE_URL') . '/api/products');
            $body = json_decode($response->getBody()->getContents(), true);
            return $body['data'] ?? [];
        } catch (RequestException $e) {
            return [];
        }
    }
}