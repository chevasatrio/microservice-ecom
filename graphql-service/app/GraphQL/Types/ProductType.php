<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ProductType extends GraphQLType
{
    protected $attributes = ['name' => 'Product', 'description' => 'A product'];

    public function fields(): array
    {
        return [
            'id' => ['type' => Type::string()],
            'code' => ['type' => Type::string()],
            'name' => ['type' => Type::string()],
            'description' => ['type' => Type::string()],
            'price' => ['type' => Type::float()],
            'stock' => ['type' => Type::int()],
        ];
    }
}
