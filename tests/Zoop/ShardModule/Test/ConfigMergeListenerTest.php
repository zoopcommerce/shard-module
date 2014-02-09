<?php

namespace Zoop\ShardModule\Test;

use PHPUnit_Framework_TestCase;
use Zend\ModuleManager\Listener\ConfigListener;
use Zend\ModuleManager\ModuleEvent;
use Zoop\ShardModule\ConfigMergeListener;

class ConfigMergeListenerTest extends PHPUnit_Framework_TestCase
{
    public function testShardDefaultsArePresentInMergedConfig()
    {

        $configListener = new ConfigListener;
        $configListener->setMergedConfig([
            'zoop' => [
                'shard' => [
                    'manifest' => [
                        'default' => [
                            'model_manager' => 'doctrine.odm.documentmanager.default',
                            'extension_configs' => [
                                'extension.odmcore'       => true,
                                'extension.serializer' => true,
                            ]
                        ]
                     ]
                 ]
             ],
            'doctrine' => [
                'odm' => [
                    'documentmanager' => [
                        'default' => [
                            'connection'    => 'doctrine.odm.connection.default',
                            'configuration' => 'doctrine.odm.configuration.default',
                            'eventmanager'  => 'doctrine.eventmanager.default'
                        ]
                    ],
                    'configuration' => [
                        'default' => [
                            'class_metadata_factory_name' => 'Zoop\Shard\ODMCore\ClassMetadataFactory',
                            'driver'                      => 'doctrine.driver.default',
                        ]
                    ],
                ],
                'driver' => [
                    'default' => []
                ],
                'eventmanager' => [
                    'default' => []
                ]
            ]
        ]);

        $event = new ModuleEvent();
        $event->setConfigListener($configListener);

        $configMergeListener = new ConfigMergeListener();
        $configMergeListener->onConfigMerge($event);

        $config = $configListener->getMergedConfig(false);

        $this->assertEquals('Zoop\Shard\Serializer\Type\Collection', $config['zoop']['shard']['manifest']['default']['service_manager_config']['invokables']['serializer.type.collection']);
    }
}
