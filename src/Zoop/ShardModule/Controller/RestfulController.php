<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller;

use Zend\Http\Header\Location;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\Mvc\MvcEvent;
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
        $this->options->getModelManager()->getEventManager()->addEventSubscriber($this->options->getDoctrineSubscriber());
    }

    public function trigger($name, $event)
    {
        //first lazy load the listeners for the event
        //this means that instances of all listeners for all events don't have to be created every request
        $eventManager = $this->getEventManager();
        foreach ($this->options->getListenersForEvent($name) as $listener) {
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

//        $this->trigger(Event::FLUSH, $event);

//        $this->response->setStatusCode(201);
//        $this->response->getHeaders()->addHeader($event->getParam('locationHeader'));
    }

    public function update($id, $data)
    {
        $documentManager = $this->options->getDocumentManager();

        $parts = explode('/', $id);
        $document = $parts[0];
        array_shift($parts);
        $deeperResource = $parts;

        $assistant = $this->options->getUpdateAssistant();
        $assistant->setController($this);
        $updatedDocument = $assistant->doUpdate($data, $document, $deeperResource);

        if ($this->getEvent()->getRouteMatch()->getParam('surpressResponse')) {
            return $updatedDocument;
        }

        $this->flush();

        $updatedMetadata = $documentManager->getClassMetadata(get_class($updatedDocument));
        $newEndpoint = $updatedMetadata
            ->reflFields[$this->options->getEndpoint()->getProperty()]->getValue($updatedDocument);

        if ($newEndpoint != $id) {
            $parts = explode('/', $this->request->getUri()->getPath());
            array_pop($parts);
            $location = implode('/', $parts) . '/' . $newEndpoint;
            $this->response->getHeaders()->addHeader(
                Location::fromString('Location: ' . $location)
            );
        }

        $this->response->setStatusCode(204);
        $this->response->setContent(null);

        return $this->response;
    }

    public function patch($id, $data)
    {
        $documentManager = $this->options->getDocumentManager();

        $parts = explode('/', $id);
        $document = $parts[0];
        array_shift($parts);
        $deeperResource = $parts;

        $assistant = $this->options->getPatchAssistant();
        $assistant->setController($this);
        $patchedDocument = $assistant->doPatch($data, $document, $deeperResource);

        if ($this->getEvent()->getRouteMatch()->getParam('surpressResponse')) {
            return $patchedDocument;
        }

        $this->flush();

        $patchedMetadata = $documentManager->getClassMetadata(get_class($patchedDocument));
        $newId = $patchedMetadata->reflFields[$patchedMetadata->identifier]->getValue($patchedDocument);
        if ($newId != $id) {
            $parts = explode('/', $this->request->getUri()->getPath());
            array_pop($parts);
            $location = implode('/', $parts) . '/' . $newId;
            $this->response->getHeaders()->addHeader(
                Location::fromString('Location: ' . $location)
            );
        }

        $this->response->setStatusCode(204);

        return $this->response;
    }

    public function patchList($data)
    {
        $assistant = $this->options->getPatchListAssistant();
        $assistant->setController($this);
        $collection = $assistant->doPatchList($data);

        if ($this->getEvent()->getRouteMatch()->getParam('surpressResponse')) {
            return $collection;
        }

        $this->flush();
        $this->response->setStatusCode(204);

        return $this->response;
    }

    public function replaceList($data)
    {
        $assistant = $this->options->getReplaceListAssistant();
        $assistant->setController($this);
        $collection = $assistant->doReplaceList($data);

        if ($this->getEvent()->getRouteMatch()->getParam('surpressResponse')) {
            return $collection;
        }

        $this->flush();
        $this->response->setStatusCode(204);

        return $this->response;
    }

    public function delete($id)
    {
        $documentManager = $this->options->getDocumentManager();

        $parts = explode('/', $id);
        $document = $parts[0];
        array_shift($parts);
        $deeperResource = $parts;

        $assistant = $this->options->getDeleteAssistant();
        $assistant->setController($this);
        $assistant->doDelete($document, $deeperResource);

        $this->flush();

        $this->response->setStatusCode(204);

        return $this->response;
    }

    public function deleteList()
    {
        $assistant = $this->options->getDeleteListAssistant();
        $assistant->setController($this);
        $assistant->doDeleteList();

        $this->response->setStatusCode(204);

        return $this->response;
    }

    protected function getIdentifier($routeMatch, $request)
    {
        $id = $routeMatch->getParam('id', false);
        if ($id) {
            return $id;
        }

        return false;
    }

//    protected function flush()
//    {
//        $this->options->getDocumentManager()->flush();
//        $flushExceptions = $this->doctrineSubscriber->getFlushExceptions();
//        if (count($flushExceptions) == 1) {
//            throw $flushExceptions[0];
//        } elseif (count($flushExceptions) > 1) {
//            $flushException = new Exception\FlushException;
//            $exceptionSerializer = $this->options->getExceptionSerializer();
//            $identicalStatusCodes = true;
//            $exceptions = [];
//            foreach ($flushExceptions as $exception) {
//                $exception = $exceptionSerializer->serializeException($exception);
//                if (! isset($statusCode) && isset($exception['statusCode'])) {
//                    $statusCode = $exception['statusCode'];
//                } elseif (isset($exception['statusCode']) && $statusCode != $exception['statusCode']) {
//                    $identicalStatusCodes = false;
//                }
//                $exceptions[] = $exception;
//            }
//            if (isset($statusCode) && $identicalStatusCodes) {
//                $flushException->setStatusCode($statusCode);
//            }
//            $flushException->setInnerExceptions($exceptions);
//            throw $flushException;
//        }
//    }
}
