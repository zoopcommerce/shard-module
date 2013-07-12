<?php
/**
 * @link       http://zoopcommerce.github.io/shard-module
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule;

use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\SharedListenerAggregateInterface;
use Zend\EventManager\EventInterface;

/**
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */

class InitalizeConsoleListener implements SharedListenerAggregateInterface
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
    public function attachShared(SharedEventManagerInterface $events)
    {
        $this->listeners[] = $events->attach('doctrine', 'loadCli.post', array($this, 'initializeConsole'));
    }

    /**
     * Detach all our listeners from the event manager
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function detachShared(SharedEventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * Initializes the console with additional commands from the ODM
     *
     * @param \Zend\EventManager\EventInterface $event
     *
     * @return void
     */
    public function initializeConsole(EventInterface $event)
    {
        /* @var $cli \Symfony\Component\Console\Application */
        $cli = $event->getTarget();

        $serviceLocator = $event->getParam('ServiceManager')->get('shard.default.servicemanager');
        $manifest = $serviceLocator->get('manifest');

        foreach ($manifest->getCliCommands() as $command){
            if (is_string($command)){
                $command = $serviceLocator->get($command);
            }
            $cli->addCommands([$command]);
        }

        $helperSet = $cli->getHelperSet();
        foreach ($manifest->getCliHelpers() as $key => $helper) {
            if (is_string($helper)){
                $helper = $serviceLocator->get($helper);
            }
            $helperSet->set($helper, $key);
        }
    }
}
