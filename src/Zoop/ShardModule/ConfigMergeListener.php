<?php
/**
 * @link       http://zoopcommerce.github.io/shard-module
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\ModuleManager\ModuleEvent;
use Zend\StdLib\ArrayUtils;
use Zoop\Shard\Manifest;

/**
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */

class ConfigMergeListener implements ListenerAggregateInterface
{
    /**
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $listeners = array();

    /**
     * Attach to an event manager
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(ModuleEvent::EVENT_MERGE_CONFIG, array($this, 'onConfigMerge'), 1);
    }

    /**
     * Detach all our listeners from the event manager
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     *
     * @param \Zend\ModuleManager\ModuleEvent $event
     */
    public function onConfigMerge(ModuleEvent $event)
    {
        $config = $event->getConfigListener()->getMergedConfig(false);

        foreach ($config['zoop']['shard']['manifest'] as $name => $manifestConfig) {
            if (!isset($manifestConfig['initalized']) || !$manifestConfig['initalized']) {

                $modelManager = $manifestConfig['model_manager'];
                unset($manifestConfig['model_manager']);

                $manifest = new Manifest($manifestConfig);
                $manifestConfig = $manifest->toArray();
                $manifestConfig['model_manager'] = $modelManager;
                $config['zoop']['shard']['manifest'][$name] = $manifestConfig;

                $dmConfig = &$this->getSubConfig($config, $modelManager);;

                //inject filter config
                $configurationConfig = &$this->getSubConfig($config, $dmConfig['configuration']);
                foreach ($manifest->getServiceManager()->get('extension.odmcore')->getFilters() as $filterName => $filterClass) {
                    $configurationConfig['filters'][$filterName] = $filterClass;
                }

                //inject models
                $driverConfig = &$this->getSubConfig($config, $configurationConfig['driver']);
                $count = 0;
                foreach ($manifest->getModels() as $namespace => $path) {
                    $driverConfig['drivers'][$namespace] = 'doctrine.driver.shard' . $name . $count;
                    $config['doctrine']['driver']['shard' . $name . $count] = [
                        'class' => 'Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver',
                        'paths' => [$path]
                    ];
                    $count++;
                }

                //inject subscribers
                $eventManagerConfig = &$this->getSubConfig($config, $dmConfig['eventmanager']);
                foreach ($manifest->getSubscribers() as $subscriber) {
                    $eventManagerConfig['subscribers'][] = 'shard.' . $name . '.' . $subscriber;
                }

                //make sure the Zoop/Shard/Core/ModuleManagerDelegator gets called
                $delegatorConfig = [
                    'delegators' => [
                        $modelManager => ['shard.' . $name . '.modelmanager.delegator.factory'],
                    ]
                ];
                $config['service_manager'] = ArrayUtils::merge($config['service_manager'], $delegatorConfig);
            }
        }

        $event->getConfigListener()->setMergedConfig($config);
    }

    protected function &getSubConfig(&$config, $name)
    {
        $subConfig = &$config;
        foreach (explode('.', $name) as $key) {
            $subConfig = &$subConfig[$key];
        }

        return $subConfig;
    }
}
