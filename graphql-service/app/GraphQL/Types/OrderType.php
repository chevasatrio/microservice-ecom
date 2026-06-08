<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class OrderType extends GraphQLType
{
    protected $attributes = ['name' => 'Order', 'description' => 'An order'];

    public function fields(): array
    {
        return [
            'id' => ['type' => Type::string()],
            'code' => ['type' => Type::string()],
            'product_id' => ['type' => Type::string()],
            'user_id' => ['type' => Type::string()],
            'status' => ['type' => Type::string()],
            'total_price' => ['type' => Type::float()],
            'quantity' => ['type' => Type::int()],
        ];
    }
}