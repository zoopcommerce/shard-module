<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller;

use Zoop\ShardModule\Controller\JsonRestfulController\DoctrineSubscriber;
use Zoop\ShardModule\Exception;
use Zoop\ShardModule\Options\JsonRestfulControllerOptions;
use Zend\Http\Header\Location;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ModelInterface;
use Zend\View\Model\JsonModel;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class JsonRestfulController extends AbstractRestfulController
{
    protected $options;

    protected $doctrineSubscriber;

    public function onDispatch(MvcEvent $e)
    {
        $this->options->getDocumentManager()->getEventManager()->addEventSubscriber($this->doctrineSubscriber);
        $result = parent::onDispatch($e);

        //set the template
        if ($result instanceof ModelInterface && ! ($template = $result->getTemplate())) {
            $action = $e->getRouteMatch()->getParam('action');
            if ($action == 'get') {
                $result->setTemplate($this->options->getGetTemplate());
            } elseif ($action == 'getList') {
                $result->setTemplate($this->options->getGetListTemplate());
            }
        }

        return $result;
    }

    public function getDoctrineSubscriber()
    {
        return $this->doctrineSubscriber;
    }

    public function setDoctrineSubscriber(DoctrineSubscriber $doctrineSubscriber)
    {
        $this->doctrineSubscriber = $doctrineSubscriber;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(JsonRestfulControllerOptions $options)
    {
        $this->options = $options;
    }

    public function __construct(JsonRestfulControllerOptions $options = null)
    {
        if (!isset($options)) {
            $options = new JsonRestfulControllerOptions;
        }
        $this->setOptions($options);
    }

    public function getList()
    {
        $assistant = $this->options->getGetListAssistant();
        $assistant->setController($this);
        $list = $assistant->doGetList();

        $model = $this->acceptableViewModelSelector($this->options->getAcceptCriteria())->setVariables($list);

        if (count($list) == 0 && $model instanceof JsonModel) {
            return $this->response;
        }

        return $model;
    }

    public function get($id)
    {
        $parts = explode('/', $id);
        $id = $parts[0];

        array_shift($parts);
        $deeperResource = $parts;

        $assistant = $this->options->getGetAssistant();
        $assistant->setController($this);
        $result = $assistant->doGet($id, $deeperResource);

        if ($result instanceof ModelInterface) {
            return $result;
        }

        return $this->acceptableViewModelSelector($this->options->getAcceptCriteria())->setVariables($result);
    }

    public function create($data)
    {
        $documentManager = $this->options->getDocumentManager();
        $document = null;
        $deeperResource = [];

        if ($path = $this->getEvent()->getRouteMatch()->getParam('id')) {
            $parts = explode('/', $path);
            $document = $parts[0];
            array_shift($parts);
            $deeperResource = $parts;
        }

        $assistant = $this->options->getCreateAssistant();
        $assistant->setController($this);
        $createdDocument = $assistant->doCreate($data, $document, $deeperResource);

        if ($this->getEvent()->getRouteMatch()->getParam('surpressResponse')) {
            return $createdDocument;
        }

        $this->flush();
        $createdMetadata = $documentManager->getClassMetadata(get_class($createdDocument));

        $this->response->setStatusCode(201);
        $this->response->getHeaders()->addHeader(
            Location::fromString(
                'Location: ' .
                $this->request->getUri()->getPath() .
                '/' .
                $createdMetadata->reflFields[
                $this->options
                    ->getEndpointMap()->getEndpointsFromClass($createdMetadata->name)[0]->getProperty()
                ]->getValue($createdDocument)
            )
        );

        return $this->response;
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

    protected function flush()
    {
        $this->options->getDocumentManager()->flush();
        $flushExceptions = $this->doctrineSubscriber->getFlushExceptions();
        if (count($flushExceptions) == 1) {
            throw $flushExceptions[0];
        } elseif (count($flushExceptions) > 1) {
            $flushException = new Exception\FlushException;
            $exceptionSerializer = $this->options->getExceptionSerializer();
            foreach ($flushExceptions as $exception) {
                $exceptions[] = $exceptionSerializer->serializeException($exception);
            }
            $flushException->setInnerExceptions($exceptions);
            throw $flushException;
        }
    }
}
