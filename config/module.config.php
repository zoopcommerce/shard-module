<?php
return [
    'zoop' => [
        'shard' => [
            //shard supports multiple manifest-objectManager pairs.
            //each manifest should be configured with it's own objectManager.
            //a default manifest is pre-configured with the default documentManager
            'manifest' => [
                'default' => [
                    'object_manager' => 'doctrine.odm.documentmanager.default',
                    'extension_configs' => [
//                        'extension.accessControl' => true,
//                        'extension.annotation' => true,
//                        'extension.crypt' => true,
//                        'extension.freeze' => true,
//                        'extension.generator' => true,
//                        'extension.owner' => true,
//                        'extension.reference' => true,
//                        'extension.rest' => true,
//                        'extension.serializer' => true,
//                        'extension.softdelete' => true,
//                        'extension.stamp' => true,
//                        'extension.state' => true,
//                        'extension.validator' => true,
//                        'extension.zone' => true,
                    ],
                    'service_manager_config' => [
                        'invokables' => [
                            'eventmanager.delegator.factory' => 'Zoop\ShardModule\Delegator\EventManagerDelegatorFactory',
                            'configuration.delegator.factory' => 'Zoop\ShardModule\Delegator\ConfigurationDelegatorFactory'
                        ],
                        'factories' => [
                            'objectmanager' => 'Zoop\ShardModule\Service\DefaultObjectManagerFactory'
                        ],
                        'abstract_factories' => [
                            'Zoop\ShardModule\Service\UserAbstractFactory'
                        ]
                    ]
                ]
            ]
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
            'rest.default' => [
                //this route will look to load a controller
                //service called `rest.default.<endpoint>`
                'type' => 'Zend\Mvc\Router\Http\Segment',
                'options' => [
                    'route' => '/rest/:endpoint[/:id]',
                    'constraints' => [
                        'endpoint' => '[a-zA-Z][a-zA-Z0-9_-]+',
                        'id'       => '[a-zA-Z][a-zA-Z0-9/_-]+',
                    ],
                    'defaults' => [
                        'extension'    => 'rest',
                        'manifestName' => 'default',
                    ]
                ],
            ],
        ]
    ],

    'controllers' => [
        'abstract_factories' => [
            'Zoop\ShardModule\Service\BatchRestControllerAbstractFactory',
            'Zoop\ShardModule\Service\RestControllerAbstractFactory'
        ]
    ],

    'service_manager' => [
        'invokables' => [
            'zoop.shardmodule.assistant.create' => 'Zoop\ShardModule\Controller\JsonRestfulController\CreateAssistant',
            'zoop.shardmodule.assistant.delete' => 'Zoop\ShardModule\Controller\JsonRestfulController\DeleteAssistant',
            'zoop.shardmodule.assistant.deletelist' => 'Zoop\ShardModule\Controller\JsonRestfulController\DeleteListAssistant',
            'zoop.shardmodule.assistant.get' => 'Zoop\ShardModule\Controller\JsonRestfulController\GetAssistant',
            'zoop.shardmodule.assistant.getlist' => 'Zoop\ShardModule\Controller\JsonRestfulController\GetListAssistant',
            'zoop.shardmodule.assistant.patch' => 'Zoop\ShardModule\Controller\JsonRestfulController\PatchAssistant',
            'zoop.shardmodule.assistant.patchlist' => 'Zoop\ShardModule\Controller\JsonRestfulController\PatchListAssistant',
            'zoop.shardmodule.assistant.replacelist' => 'Zoop\ShardModule\Controller\JsonRestfulController\ReplaceListAssistant',
            'zoop.shardmodule.assistant.update' => 'Zoop\ShardModule\Controller\JsonRestfulController\UpdateAssistant',
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
    )
];
