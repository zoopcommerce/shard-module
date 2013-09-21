<?php
return [
    'zoop' => [
        'shard' => [
            //a default manifest is pre-configured with the default documentManager
            'manifest' => [
                'default' => [
                    'model_manager' => 'doctrine.odm.documentmanager.default',
                    'extension_configs' => [
//                        'extension.accessControl' => true,
//                        'extension.annotation' => true,
//                        'extension.crypt' => true,
//                        'extension.freeze' => true,
//                        'extension.generator' => true,
//                        'extension.owner' => true,
//                        'extension.reference' => true,
//                        'extension.serializer' => true,
//                        'extension.softdelete' => true,
//                        'extension.stamp' => true,
//                        'extension.state' => true,
//                        'extension.validator' => true,
//                        'extension.zone' => true,
                    ],
                    'service_manager_config' => [
                        'factories' => [
                            'modelmanager' => 'Zoop\ShardModule\Service\DefaultModelManagerFactory'
                        ],
                        'abstract_factories' => [
                            'Zoop\ShardModule\Service\UserAbstractFactory'
                        ]
                    ]
                ]
            ],
        ],
        'maggott' => [
            'exception_map' => [
                'Zoop\ShardModule\Exception\FlushException' => [
                    'described_by' => 'flush-exception',
                    'title' => 'Exception occured when writing data to the database',
                    'extra_fields' => ['statusCode'],
                    'restricted_extra_fields' => ['innerExceptions']
                ],
                'Zoop\ShardModule\Exception\DocumentNotFoundException' => [
                    'described_by' => 'document-not-found',
                    'title' => 'Document not found',
                    'status_code' => 404
                ],
                'Zoop\ShardModule\Exception\BadRangeException' => [
                    'described_by' => 'bad-range',
                    'title' => 'Requested range cannot be returned',
                    'status_code' => 416
                ],
                'Zoop\ShardModule\Exception\InvalidDocumentException' => [
                    'described_by' => 'document-validation-failed',
                    'title' => 'Document validation failed',
                    'extra_fields' => ['validatorMessages']
                ],
                'Zoop\ShardModule\Exception\DocumentAlreadyExistsException' => [
                    'described_by' => 'document-already-exists',
                    'title' => 'Document already exists'
                ],
                'Zoop\ShardModule\Exception\AccessControlException' => [
                    'described_by' => 'access-control-exception',
                    'title' => 'Access denied',
                    'status_code' => 403,
                    'extra_fields' => ['action', 'documentClass']
                ],
                'Zoop\ShardModule\Exception\MethodNotAllowedException' => [
                    'described_by' => 'method-not-allowed-exception',
                    'title' => 'Method Not Allowed',
                    'status_code' => 405
                ]
            ]
        ],
        'juggernaut' => [
            'file_system' => [
                'directory' => 'data/Juggernaut/cache'
            ]
        ]
    ],

    'doctrine' => [
        'odm' => [
            'configuration' => [
                'default' => [
                    'class_metadata_factory_name' => 'Zoop\Shard\ODMCore\ClassMetadataFactory'
                ]
            ],
        ],
    ],

    'router' => [
        'routes' => [
            'rest' => [
                //this route will look to load a controller
                //service called `shard.rest.<endpoint>`
                'type' => 'Zend\Mvc\Router\Http\Segment',
                'options' => [
                    'route' => '/rest/:endpoint[/:id]',
                    'constraints' => [
                        'endpoint' => '[a-zA-Z][a-zA-Z0-9_-]+',
                        'id'       => '[a-zA-Z][a-zA-Z0-9/_-]+',
                    ],
                ],
            ],
        ]
    ],

    'controllers' => [
        'factories' => [
            'shard.rest.batch' => 'Zoop\ShardModule\Service\BatchRestControllerFactory',
        ],
        'abstract_factories' => [
            'Zoop\ShardModule\Service\RestControllerAbstractFactory'
        ]
    ],

    'service_manager' => [
        'invokables' => [
            'zoop.shardmodule.listener.serialize'        => 'Zoop\ShardModule\Controller\Listener\SerializeListener',
            'zoop.shardmodule.listener.unserialize'      => 'Zoop\ShardModule\Controller\Listener\UnserializeListener',
            'zoop.shardmodule.listener.idchange'         => 'Zoop\ShardModule\Controller\Listener\IdChangeListener',
            'zoop.shardmodule.listener.flush'            => 'Zoop\ShardModule\Controller\Listener\FlushListener',
            'zoop.shardmodule.listener.prepareviewmodel' => 'Zoop\ShardModule\Controller\Listener\PrepareViewModelListener',
            'zoop.shardmodule.listener.create'           => 'Zoop\ShardModule\Controller\Listener\CreateListener',
            'zoop.shardmodule.listener.delete'           => 'Zoop\ShardModule\Controller\Listener\DeleteListener',
            'zoop.shardmodule.listener.deletelist'       => 'Zoop\ShardModule\Controller\Listener\DeleteListListener',
            'zoop.shardmodule.listener.get'              => 'Zoop\ShardModule\Controller\Listener\GetListener',
            'zoop.shardmodule.listener.getlist'          => 'Zoop\ShardModule\Controller\Listener\GetListListener',
            'zoop.shardmodule.listener.patch'            => 'Zoop\ShardModule\Controller\Listener\PatchListener',
            'zoop.shardmodule.listener.patchlist'        => 'Zoop\ShardModule\Controller\Listener\PatchListListener',
            'zoop.shardmodule.listener.replacelist'      => 'Zoop\ShardModule\Controller\Listener\ReplaceListListener',
            'zoop.shardmodule.listener.update'           => 'Zoop\ShardModule\Controller\Listener\UpdateListener',
            'zoop.shardmodule.restcontrollermap'         => 'Zoop\ShardModule\RestControllerMap',
            'doctrine.builder.odm.documentmanager'       => 'Zoop\ShardModule\Builder\ModelManagerBuilder',
        ],
        'factories' => [
            'doctrine.cache.juggernaut.filesystem' => 'Zoop\ShardModule\Service\JuggernautFileSystemCacheFactory',
        ],
        'abstract_factories' => [
            'Zoop\ShardModule\Service\ShardServiceAbstractFactory'
        ]
    ],

    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
        'strategies' => array(
            'ViewJsonStrategy',
        ),
    ),

    'controller_plugins' => [
        'factories' => [
            'forward' => 'Zoop\ShardModule\Service\ForwardFactory'
        ]
    ],
];
