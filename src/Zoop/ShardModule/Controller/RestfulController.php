<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zoop\ShardModule\Exception;
use Zoop\ShardModule\Options\RestfulControllerOptions;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class RestfulController extends AbstractRestfulController
{
    protected $options;

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(RestfulControllerOptions $options)
    {
        $this->options = $options;
    }

    public function __construct(RestfulControllerOptions $options = null)
    {
        if (!isset($options)) {
            $options = new RestfulControllerOptions;
        }
        $this->setOptions($options);

        //attach listeners for shard/doctrine events
        $this->options->getModelManager()
            ->getEventManager()
            ->addEventSubscriber($this->options->getDoctrineSubscriber());
    }

    public function trigger($name, $event)
    {
        //first lazy load the listeners for the event
        //this means that instances of all listeners for all events don't have to be created every request
        $eventManager = $this->getEventManager();
        $listeners = $this->options->getListenersForEvent($name);

        if (count($listeners) == 0) {
            //If there are no listeners, then the method can't do anything - so throw an exception
            throw new Exception\MethodNotAllowedException;
        }

        foreach ($listeners as $listener) {
            $eventManager->attach($name, [$listener, $name]);
        }

        //then trigger the event
        return $eventManager->trigger($name, $event);
    }

    public function getList()
    {
        //trigger event
        return $this->trigger(Event::GET_LIST, $this->getEvent())->last();
    }

    public function get($id)
    {
        $event = $this->getEvent();

        if ($event->getParam('id', null) == null) {
            $parts = explode('/', $id);
            $id = $parts[0];
            array_shift($parts);
            $event->setParam('id', $id);
            $event->setParam('deeperResource', $parts);
        }

        //trigger event
        return $this->trigger(Event::GET, $this->getEvent())->last();
    }

    public function create($data)
    {
        $event = $this->getEvent();

        if ($event->getParam('id', null) == null && $path = $this->getEvent()->getRouteMatch()->getParam('id')) {
            $parts = explode('/', $path);
            $id = $parts[0];
            array_shift($parts);
            $event->setParam('id', $id);
            $event->setParam('deeperResource', $parts);
        }

        $event->setParam('data', $data);

        //trigger event
        return $this->trigger(Event::CREATE, $event)->last();
    }

    public function update($id, $data)
    {
        $event = $this->getEvent();

        if ($event->getParam('id', null) == null) {
            $parts = explode('/', $id);
            $id = $parts[0];
            array_shift($parts);
            $event->setParam('id', $id);
            $event->setParam('deeperResource', $parts);
        }

        $event->setParam('data', $data);

        //trigger event
        return $this->trigger(Event::UPDATE, $event)->last();
    }

    public function replaceList($data)
    {
        $event = $this->getEvent();
        $event->setParam('data', $data);

        //trigger event
        return $this->trigger(Event::REPLACE_LIST, $event)->last();
    }

    public function patch($id, $data)
    {
        $event = $this->getEvent();

        if ($event->getParam('id', null) == null) {
            $parts = explode('/', $id);
            $id = $parts[0];
            array_shift($parts);
            $event->setParam('id', $id);
            $event->setParam('deeperResource', $parts);
        }

        $event->setParam('data', $data);

        //trigger event
        return $this->trigger(Event::PATCH, $event)->last();
    }

    public function patchList($data)
    {
        $event = $this->getEvent();
        $event->setParam('data', $data);

        //trigger event
        return $this->trigger(Event::PATCH_LIST, $event)->last();
    }

    public function delete($id)
    {
        $event = $this->getEvent();

        if ($event->getParam('id', null) == null) {
            $parts = explode('/', $id);
            $id = $parts[0];
            array_shift($parts);
            $event->setParam('id', $id);
            $event->setParam('deeperResource', $parts);
        }

        //trigger event
        return $this->trigger(Event::DELETE, $this->getEvent())->last();
    }

    public function deleteList()
    {
        //trigger event
        return $this->trigger(Event::DELETE_LIST, $this->getEvent())->last();
    }
}
