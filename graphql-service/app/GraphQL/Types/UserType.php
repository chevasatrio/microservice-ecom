<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class UserType extends GraphQLType
{
    protected $attributes = [
        'name'        => 'User',
        'description' => 'A user in the system',
    ];

    public function fields(): array
    {
        return [
            'id'    => ['type' => Type::string(), 'description' => 'UUID'],
            'name'  => ['type' => Type::string()],
            'email' => ['type' => Type::string()],
        ];
    }
}
