<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Zend\Mvc\MvcEvent;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\View\Model\ModelInterface;
use Zend\View\Model\JsonModel;
use Zoop\ShardModule\Controller\Event;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class PrepareViewModelListener implements ListenerAggregateInterface
{

    protected $listeners = array();

    /**
     * Attach to an event manager
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(Event::PREPARE_VIEW_MODEL, [$this, 'onPrepareViewModel']);
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

    public function onPrepareViewModel(MvcEvent $event)
    {
        $result = $event->getResult();
        $controller = $event->getTarget();

        if (! ($result instanceof ModelInterface)) {
            $result = $controller->acceptableViewModelSelector($controller->getOptions()->getAcceptCriteria())->setVariables($result);
        }

        if (count($result->getVariables()) == 0) {
            $event->getResponse()->setStatusCode(204);
            return $event->getResponse();
        }

        //set the template
        if (! ($template = $result->getTemplate())) {
            $result->setTemplate($controller->getOptions()->getTemplates()[$event->getRouteMatch()->getParam('action')]);
        }

        return $result;
    }
}
