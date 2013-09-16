<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Http\Header\CacheControl;
use Zend\Http\Header\LastModified;
use Zend\Mvc\MvcEvent;
use Zoop\ShardModule\Exception;
use Zoop\ShardModule\Controller\Event;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class CreateListener implements ListenerAggregateInterface
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
        $this->listeners[] = $events->attach(Event::CREATE, [$this, 'onCreate']);
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

    public function onCreate(MvcEvent $event)
    {
        $deeperResource = $event->getParam('deeperResource');
        $options = $event->getTarget()->getOptions();
        $documentManager = $options->getObjectManager();

        if (! ($endpoint = $event->getParam('endpoint'))) {
            $endpoint = $options->getEndpoint();
        }

        if ($document = $event->getParam('document')) {
            $metadata = $documentManager->getClassMetadata(get_class($document));
        } else {
            $metadata = $documentManager->getClassMetadata($options->getDocumentClass());
        }

        if (count($deeperResource) == 0) {
            $createdDocument = $this->unserialize($data, $document, $metadata, Unserializer::UNSERIALIZE_PATCH);
            if ($documentManager->contains($createdDocument)) {
                $exception = new Exception\DocumentAlreadyExistsException();
                $exception->setDocument($createdDocument);
                throw $exception;
            }
            if (! $metadata->isEmbeddedDocument) {
                $documentManager->persist($createdDocument);
            }

            return $createdDocument;
        }
    }

    protected function getSingleObject($field, $metadata, $documentManager, $endpoint, $event)
    {

    }

    protected function getCollection($field, $metadata, $documentManager, $endpoint, $event)
    {

    }
}
