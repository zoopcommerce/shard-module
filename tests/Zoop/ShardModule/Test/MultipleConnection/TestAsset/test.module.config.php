<?php
return [
    'zoop' => [
        'shard' => [
            'rest' => [
                'cache_control' => [
                    'no_cache' => true
                ],
                'rest' => [
                    'country' => [
                        'manifest' => 'country',
                        'class' => 'Zoop\ShardModule\Test\MultipleConnection\TestAsset\Document1\Country',
                        'property' => 'name',
                    ],
                    'user'    => [
                        'manifest' => 'user',
                        'class' => 'Zoop\ShardModule\Test\MultipleConnection\TestAsset\Document2\User',
                        'property' => 'username',
                    ]
                ]
            ],

            'manifest' => [
                'country' => [
                    'model_manager' => 'doctrine.odm.documentmanager.country',
                    'extension_configs' => [
                        'extension.odmcore' => true,
                        'extension.serializer' => true,
                    ],
                    'models' => [
                        'Zoop\ShardModule\Test\MultipleConnection\TestAsset\Document1' => __DIR__.'/TestAsset/Document1'
                    ],
                    'service_manager_config' => [
                        'factories' => [
                            'modelmanager' => 'Zoop\ShardModule\Test\MultipleConnection\TestAsset\CountryModelManagerFactory',
                            'eventmanager' => 'Zoop\ShardModule\Service\EventManagerFactory'
                        ],
                        'abstract_factories' => [
                            'Zoop\ShardModule\Service\UserAbstractFactory'
                        ]
                    ]
                ],
                'user' => [
                    'model_manager' => 'doctrine.odm.documentmanager.user',
                    'extension_configs' => [
                        'extension.odmcore' => true,
                        'extension.serializer' => true,
                    ],
                    'models' => [
                        'Zoop\ShardModule\Test\MultipleConnection\TestAsset\Document2' => __DIR__.'/TestAsset/Document2'
                    ],
                    'service_manager_config' => [
                        'factories' => [
                            'modelmanager' => 'Zoop\ShardModule\Test\MultipleConnection\TestAsset\UserModelManagerFactory',
                            'eventmanager' => 'Zoop\ShardModule\Service\EventManagerFactory'
                        ],
                        'abstract_factories' => [
                            'Zoop\ShardModule\Service\UserAbstractFactory'
                        ]
                    ]
                ]
            ],
        ],
    ],

    'doctrine' => [
        'odm' => [
            'documentmanager' => [
                'country' => [
                    'connection'    => 'doctrine.odm.connection.country',
                    'configuration' => 'doctrine.odm.configuration.country',
                    'eventmanager'  => 'doctrine.eventmanager.country'
                ],
                'user' => [
                    'connection'    => 'doctrine.odm.connection.user',
                    'configuration' => 'doctrine.odm.configuration.user',
                    'eventmanager'  => 'doctrine.eventmanager.user'
                ]
            ],
            'connection' => [
                'country' => [
                    'server'    => 'localhost',
                    'port'      => '27017',
                    'user'      => null,
                    'password'  => null,
                    'dbname'    => null,
                    'options'   => []
                ],
                'user' => [
                    'server'    => 'localhost',
                    'port'      => '27017',
                    'user'      => null,
                    'password'  => null,
                    'dbname'    => null,
                    'options'   => []
                ],
            ],
            'configuration' => [
                'country' => [
                    'class_metadata_factory_name' => 'Zoop\Shard\ODMCore\ClassMetadataFactory',
                    'proxy_dir'          => __DIR__ . '/../../../../../Proxy',
                    'hydrator_dir'       => __DIR__ . '/../../../../../Hydrator',
                    'metadata_cache'     => 'doctrine.cache.array',
                    'driver'             => 'doctrine.driver.default',
                    'generate_proxies'   => true,
                    'proxy_namespace'    => 'DoctrineMongoODMModule\Proxy',
                    'generate_hydrators' => true,
                    'hydrator_namespace' => 'DoctrineMongoODMModule\Hydrator',
                    'default_db'         => 'shard-module-phpunit-country',
                    'filters'            => []
                ],
                'user' => [
                    'class_metadata_factory_name' => 'Zoop\Shard\ODMCore\ClassMetadataFactory',
                    'proxy_dir'          => __DIR__ . '/../../../../../Proxy',
                    'hydrator_dir'       => __DIR__ . '/../../../../../Hydrator',
                    'metadata_cache'     => 'doctrine.cache.array',
                    'driver'             => 'doctrine.driver.default',
                    'generate_proxies'   => true,
                    'proxy_namespace'    => 'DoctrineMongoODMModule\Proxy',
                    'generate_hydrators' => true,
                    'hydrator_namespace' => 'DoctrineMongoODMModule\Hydrator',
                    'default_db'         => 'shard-module-phpunit-user',
                    'filters'            => []
                ],
            ],
        ],
        'eventmanager' => [
            'country' => [],
            'user' => [],
        ],
    ],

    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions' => true,
    ),
];
