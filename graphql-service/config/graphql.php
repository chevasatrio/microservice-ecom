<?php

return [
    'route' => [
        'prefix' => 'graphql',
        'middleware' => ['api'],
    ],

    'default_schema' => 'default',

    'schemas' => [
        'default' => [
            'query' => [
                'users' => \App\GraphQL\Queries\UsersQuery::class,
                'products' => \App\GraphQL\Queries\ProductsQuery::class,
                'orders' => \App\GraphQL\Queries\OrdersQuery::class,
            ],
            'mutation' => [],
            'middleware' => [],
            'method' => ['GET', 'POST'],
        ],
    ],

    'types' => [
        'User' => \App\GraphQL\Types\UserType::class,
        'Product' => \App\GraphQL\Types\ProductType::class,
        'Order' => \App\GraphQL\Types\OrderType::class,
    ],

    'error_formatter' => ['\Rebing\GraphQL\GraphQL', 'formatError'],
    'errors_handler' => ['\Rebing\GraphQL\GraphQL', 'handleErrors'],
    'security' => [
        'query_max_complexity' => null,
        'query_max_depth' => null,
        'disable_introspection' => false,
    ],
    'pagination_type' => \Rebing\GraphQL\Support\PaginationType::class,
    'simple_pagination_type' => \Rebing\GraphQL\Support\SimplePaginationType::class,
    'defaultFieldResolver' => null,
    'headers' => [],
    'json_encoding_options' => 0,
    'apq' => false,
];