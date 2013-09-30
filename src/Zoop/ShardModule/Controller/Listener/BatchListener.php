<?php
/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\ShardModule\Controller\Listener;

use Zoop\ShardModule\RouteListener;
use Zend\Http\Header\GenericHeader;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\View\Model\ModelInterface;
use Zoop\ShardModule\Controller\Result;

class BatchListener
{
    protected $exceptionViewModelPreparer;

    protected $router;

    protected $controllerLoader;

    public function getExceptionViewModelPreparer() {
        return $this->exceptionViewModelPreparer;
    }

    public function setExceptionViewModelPreparer($exceptionViewModelPreparer) {
        $this->exceptionViewModelPreparer = $exceptionViewModelPreparer;
    }

    public function getRouter() {
        return $this->router;
    }

    public function setRouter($router) {
        $this->router = $router;
    }

    public function getControllerLoader() {
        return $this->controllerLoader;
    }

    public function setControllerLoader($controllerLoader) {
        $this->controllerLoader = $controllerLoader;
    }

    public function create(MvcEvent $event)
    {
        $batchRequest = $event->getRequest();
        $router = $this->getRouter();
        $controllerLoader = $this->getControllerLoader();
        $responseModel = [];

        foreach ($event->getParam('data') as $key => $requestData) {
            $request = new Request();
            $request->setMethod($requestData['method']);
            $request->setUri($requestData['uri']);
            $queryString = $request->getUri()->getQuery();
            if ($queryString) {
                $query = [];
                parse_str($queryString, $query);
                $request->setQuery(new Parameters($query));
            }

            $requestHeaders = [
                $batchRequest->getHeaders()->get('Accept'),
                $batchRequest->getHeaders()->get('Content-Type'),
            ];
            if (isset($requestData['headers'])) {
                foreach ($requestData['headers'] as $name => $value) {
                    $requestHeaders[] = GenericHeader::fromString($name . ': ' . $value);
                }
            }
            $request->getHeaders()->addHeaders($requestHeaders);

            if (isset($requestData['content'])) {
                $request->setContent(json_encode($requestData['content']));
            }

            $response = new Response;
            $subEvent = new MvcEvent;
            $subEvent->setRequest($request);
            $subEvent->setResponse($response);
            $match = $router->match($request);
            RouteListener::resolveController($match);
            $contentModel = null;

            if (!isset($match) || ($match->getMatchedRouteName() != 'rest')) {
                $contentModel = $this->createExceptionContentModel(
                    new Exception\RuntimeException(
                        sprintf(
                            '%s uri is not a rest route, so is not supported by batch controller.',
                            $requestData['uri']
                        )
                    ),
                    $subEvent
                );
            } else {
                try {
                    $controller = $controllerLoader->get($match->getParam('controller'));
                } catch (\Zend\ServiceManager\Exception\ServiceNotFoundException $exception) {
                    $contentModel = $this->createExceptionContentModel($exception, $subEvent);
                    $response->setStatusCode(404);
                }

                $subEvent->setRouteMatch($match);
                $controller->setEvent($subEvent);

                if (!isset($contentModel)) {
                    try {
                        $contentModel = $controller->dispatch($request, $response);
                    } catch (\Exception $exception) {
                        $contentModel = $this->createExceptionContentModel($exception, $subEvent);
                    }
                }
            }

            $headers = [];
            foreach ($response->getHeaders() as $header) {
                $headers[$header->getFieldName()] = $header->getFieldValue();
            }
            $responseModel[$key] = [
                'status' => $response->getStatusCode(),
                'headers' => $headers,
            ];

            if ($contentModel instanceof ModelInterface) {
                $responseModel[$key]['content'] = $contentModel->getVariables();
            }
        }

        $result = new Result;
        $result->setSerializedModel($responseModel);
        $event->setResult($result);
        return $result;
    }

    protected function createExceptionContentModel($exception, $event)
    {
        $event->setError(Application::ERROR_EXCEPTION);
        $event->setParam('exception', $exception);
        $this->getExceptionViewModelPreparer()->prepareExceptionViewModel($event);

        return $event->getResult();
    }
}
