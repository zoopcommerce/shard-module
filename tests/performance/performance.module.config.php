<?php

return [

    'zoop' => [
        'shard' => [
            'extensionConfigs' => [
                'extension.freeze' => false,
                'extension.softdelete' => false,
                'extension.state' => false,
                'extension.zone' => false,
            ],
        ],
        'juggernaut' => [
            'file_system' => [
                'directory' => __DIR__ . '/cache/doctrine'
            ]
        ]
    ],

    'doctrine' => [
        'odm' => [
            'configuration' => [
                'default' => [
                    'metadata_cache'     => 'doctrine.cache.juggernaut.filesystem',
                    //'metadata_cache'     => 'doctrine.cache.array',
                    'generate_proxies'   => false,
                    'generate_hydrators' => false,
                ],
            ],
        ],
    ],

    'controllers' => [
        'invokables' => [
            'TestData' => 'Zoop\ShardModule\Test\TestAsset\TestDataController'
        ],
    ],

    'router' => array(
        'routes' => array(
        ),
    ),

    'console' => array(
        'router' => array(
            'routes' => array(
                'ShardModule\TestData\create' => array(
                    'options' => array(
                        'route'    => 'create',
                        'defaults' => array(
                            '__NAMESPACE__' => 'Zoop\ShardModule\Test\TestAsset',
                            'controller' => 'TestData',
                            'action'     => 'create'
                        )
                    )
                ),
                'ShardModule\TestData\remove' => array(
                    'options' => array(
                        'route'    => 'remove',
                        'defaults' => array(
                            '__NAMESPACE__' => 'Zoop\ShardModule\Test\TestAsset',
                            'controller' => 'TestData',
                            'action'     => 'remove'
                        )
                    )
                ),
            )
        )
    ),
];
