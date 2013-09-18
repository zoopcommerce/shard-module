<?php
return [
    'zoop' => [
        'shard' => [
            'rest' => [
                'game' => [
                    'class' => 'Zoop\ShardModule\Test\TestAsset\Document\Game',
                    'property' => 'name',
                    'manifest' => 'default',
                    'cache_control' => [
                        'no_cache' => true
                    ],
                    'rest' => [
                        'components' => [
                            'class' => 'Zoop\ShardModule\Test\TestAsset\Document\Component',
                            'rest' => [
                                'manufacturers' => [
                                    'property' => 'name'
                                ]
                            ]
                        ]
                    ]
                ],
                'author'  => [
                    'class' => 'Zoop\ShardModule\Test\TestAsset\Document\Author',
                    'property' => 'name',
                    'manifest' => 'default',
                ],
                'country' => [
                    'class' => 'Zoop\ShardModule\Test\TestAsset\Document\Country',
                    'property' => 'name',
                    'manifest' => 'default',
                ],
                'review'  => [
                    'class' => 'Zoop\ShardModule\Test\TestAsset\Document\Review',
                    'property' => 'title',
                    'limit' => 2,
                    'manifest' => 'default',
                ],
                'user'    => [
                    'class' => 'Zoop\ShardModule\Test\TestAsset\Document\User',
                    'property' => 'username',
                    'manifest' => 'default',
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
                        'extension.reference' => true,
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
                    'default_db' => 'shardModuleTest',
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
