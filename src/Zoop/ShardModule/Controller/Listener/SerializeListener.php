<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Zend\Mvc\MvcEvent;
use Zend\EventManager\EventManagerInterface;
use Zoop\ShardModule\Controller\Event;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class SerializeListener extends AbstractListener
{
    /**
     * Attach to an event manager
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(Event::SERIALIZE, [$this, 'onSerialize']);
        $this->listeners[] = $events->attach(Event::SERIALIZE_LIST, [$this, 'onSerializeList']);
    }

    public function onSerialize(MvcEvent $event)
    {
        return $event->getTarget()->getOptions()->getManifest()->getServiceManager()->get('serializer')->toArray($event->getParam('document'));
    }

    public function onSerializeList(MvcEvent $event)
    {
        $serializer = $event->getTarget()->getOptions()->getManifest()->getServiceManager()->get('serializer');

        $items = [];
        foreach ($event->getParam('list') as $item) {
            $items[] = $serializer->toArray($item);
        }

        return $items;
    }
}
