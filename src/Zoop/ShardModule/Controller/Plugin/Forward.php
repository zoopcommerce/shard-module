<?php

namespace Zoop\ShardModule\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\Forward as ZendForward;
use Zend\Mvc\Exception;
use Zend\Mvc\InjectApplicationEventInterface;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;

class Forward extends ZendForward
{
    /**
     * @var int
     */
    protected $numNestedForwards = 0;

    /**
     * Returns how deep forward nesting currently is
     *
     * @return int
     */
    public function getNumNestedForwards()
    {
        return $this->numNestedForwards;
    }

    /**
     * Dispatch another controller
     *
     * @param  string $name Controller name; either a class name or an alias used in the controller manager
     * @param  null|array $params Parameters with which to seed a custom RouteMatch object for the new controller
     * @return mixed
     * @throws Exception\DomainException if composed controller does not define InjectApplicationEventInterface
     *         or Locator aware; or if the discovered controller is not dispatchable
     */
    public function dispatch($name, array $params = null)
    {
        $event = $this->getEvent();

        $controller = $this->controllers->get($name);
        if ($controller instanceof InjectApplicationEventInterface) {
            $controller->setEvent($event);
        }

        // Allow passing parameters to seed the RouteMatch with & copy matched route name
        if (isset($params)) {
            $routeMatch = $event->getRouteMatch();
            foreach ($params as $key => $value) {
                $routeMatch->setParam($key, $value);
            }
        }

        if ($this->numNestedForwards > $this->maxNestedForwards) {
            throw new Exception\DomainException("Circular forwarding detected: greater than $this->maxNestedForwards nested forwards");
        }
        $this->numNestedForwards++;

        // Detach listeners that may cause problems during dispatch:
        $sharedEvents = $event->getApplication()->getEventManager()->getSharedManager();
        $listeners = $this->detachProblemListeners($sharedEvents);

        $return = $controller->dispatch($event->getRequest(), $event->getResponse());

        // If we detached any listeners, reattach them now:
        $this->reattachProblemListeners($sharedEvents, $listeners);

        $this->numNestedForwards--;

        return $return;
    }

    /**
     * Get the event
     *
     * @return MvcEvent
     * @throws Exception\DomainException if unable to find event
     */
    protected function getEvent()
    {
        $controller = $this->getController();
        if (!$controller instanceof InjectApplicationEventInterface) {
            throw new Exception\DomainException('Forward plugin requires a controller that implements InjectApplicationEventInterface');
        }

        $event = $controller->getEvent();
        if (!$event instanceof MvcEvent) {
            $params = array();
            if ($event) {
                $params = $event->getParams();
            }
            $event  = new MvcEvent();
            $event->setParams($params);
        }

        return $event;
    }
}
