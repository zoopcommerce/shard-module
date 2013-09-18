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
class UnserializeListener extends AbstractListener
{
    /**
     * Attach to an event manager
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(Event::UNSERIALIZE, [$this, 'onUnserialize']);
    }

    public function onUnserialize(MvcEvent $event)
    {
        if ($document = $event->getParam('document')) {
            $class = get_class($document);
        } else {
            $class = $event->getTarget()->getOptions()->getDocumentClass();
        }

        return $event->getTarget()->getOptions()->getServiceLocator()->get('unserializer')->fromArray(
            $event->getParam('data'),
            $class,
            $document,
            $event->getParam('mode')
        );
    }
}
