<?php
return [
    'zoop' => [
        'shard' => [
            'rest' => [
                'cache_control' => [
                    'no_cache' => true
                ],
                'property' => 'name',
                'class' => 'Zoop\ShardModule\Test\TestAsset\Document\Author',
                'rest' => [
                    'game' => [
                        'property' => 'name',
                        'class' => 'Zoop\ShardModule\Test\TestAsset\Document\Game',
                        'rest' => [
                            'components' => [
                                'rest' => [
                                    'manufacturers' => []
                                ]
                            ]
                        ]
                    ],
                    'author'  => [
                        'class' => 'Zoop\ShardModule\Test\TestAsset\Document\Author',
                    ],
                    'country' => [
                        'class' => 'Zoop\ShardModule\Test\TestAsset\Document\Country',
                    ],
                    'review'  => [
                        'class' => 'Zoop\ShardModule\Test\TestAsset\Document\Review',
                        'property' => 'title',
                        'limit' => 2,
                    ],
                    'user'    => [
                        'class' => 'Zoop\ShardModule\Test\TestAsset\Document\User',
                        'property' => 'username',
                    ]
                ]
            ],

            'manifest' => [
                'default' => [
                    'extension_configs' => [
                        'extension.odmcore' => true,
                        'extension.accesscontrol' => true,
                        'extension.annotation' => true,
                        'extension.crypt' => true,
                        'extension.freeze' => true,
                        'extension.owner' => true,
                        'extension.serializer' => [
                            'maxNestingDepth' => 2
                        ],
                        'extension.softdelete' => true,
                        'extension.stamp' => true,
                        'extension.state' => true,
                        'extension.validator' => true,
                        'extension.zone' => true,
                    ],
                    'models' => [
                        'Zoop\ShardModule\Test\TestAsset\Document' => __DIR__.'/Zoop/ShardModule/Test/TestAsset/Document'
                    ]
                ]
            ],
        ],
    ],

    'doctrine' => [
        'odm' => [
            'configuration' => [
                'default' => [
                    'default_db'   => 'shard-module-phpunit',
                    'proxy_dir'    => __DIR__ . '/Proxy',
                    'hydrator_dir' => __DIR__ . '/Hydrator',
                ],
            ],
        ],
    ],

    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions' => true,
    ),
];
