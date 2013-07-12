<?php
/**
 * @link       http://zoopcommerce.github.io/shard-module
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;

/**
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */

class RouteListener implements ListenerAggregateInterface
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
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, array($this, 'onRoute'), -100);
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

    public function onRoute(MvcEvent $event)
    {
        self::resolveController($event->getRouteMatch());
    }

    public static function resolveController(RouteMatch $routeMatch) {

        if (($extension = $routeMatch->getParam('extension')) &&
            ($manifestName = $routeMatch->getParam('manifestName'))
        ) {
            if ($endpoint = $routeMatch->getParam('endpoint')){
                $routeMatch->setParam('controller', implode('.', [$extension, $manifestName, $endpoint]));
            } else {
                $routeMatch->setParam('controller', implode('.', [$extension, $manifestName]));
            }
        }
    }
}
