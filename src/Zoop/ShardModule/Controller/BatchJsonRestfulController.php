<?php
/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\ShardModule\Controller;

use Zoop\ShardModule\Exception;
use Zoop\ShardModule\Options\BatchJsonRestfulControllerOptions;
use Zoop\ShardModule\RouteListener;
use Zend\Http\Header\GenericHeader;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ModelInterface;

class BatchJsonRestfulController extends AbstractRestfulController
{

    protected $options;

    public function getOptions() {
        return $this->options;
    }

    public function setOptions(BatchJsonRestfulControllerOptions $options) {
        $this->options = $options;
    }

    public function __construct(BatchJsonRestfulControllerOptions $options = null) {
        if (!isset($options)){
            $options = new BatchJsonRestfulControllerOptions;
        }
        $this->setOptions($options);
    }

    public function create($data) {
        $router = $this->serviceLocator->get('router');
        $controllerLoader = $this->serviceLocator->get('controllerLoader');

        foreach ($data as $key => $requestData){
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
                $this->request->getHeaders()->get('Accept'),
                $this->request->getHeaders()->get('Content-Type'),
            ];
            if (isset($requestData['headers'])){
                foreach ($requestData['headers'] as $name => $value){
                    $requestHeaders[] = GenericHeader::fromString($name . ': ' . $value);
                }
            }
            $request->getHeaders()->addHeaders($requestHeaders);

            if (isset($requestData['content'])){
                $request->setContent(json_encode($requestData['content']));
            }

            $response = new Response;
            $event = new MvcEvent;
            $event->setRequest($request);
            $event->setResponse($response);
            $match = $router->match($request);
            RouteListener::resolveController($match);
            $contentModel = null;

            if (!isset($match) || ($match->getMatchedRouteName() != 'rest.' . $this->options->getManifestName())){
                $contentModel = $this->createExceptionContentModel(
                    new Exception\RuntimeException(sprintf(
                        '%s uri is not a rest route, so is not supported by batch controller.', $requestData['uri']
                    )),
                    $event
                );
            } else {
                try {
                    $controller = $controllerLoader->get($match->getParam('controller'));
                } catch (\Zend\ServiceManager\Exception\ServiceNotFoundException $exception) {
                    $contentModel = $this->createExceptionContentModel($exception, $event);
                    $response->setStatusCode(404);
                }

                $event->setRouteMatch($match);
                $controller->setEvent($event);

                if (!isset($contentModel)){
                    try {
                        $contentModel = $controller->dispatch($request, $response);
                    } catch (\Exception $exception) {
                        $contentModel = $this->createExceptionContentModel($exception, $event);
                    }
                }
            }

            $headers = [];
            foreach ($response->getHeaders() as $header){
                $headers[$header->getFieldName()] = $header->getFieldValue();
            }
            $responseModel = new JsonModel([
                'status' => $response->getStatusCode(),
                'headers' => $headers
            ]);
            if ($contentModel instanceof ModelInterface){
                $responseModel->addChild($contentModel, 'content');
            }
            $this->model->addChild($responseModel, $key);
        }

        return $this->model;
    }

    protected function createExceptionContentModel($exception, $event) {
        $event->setError(Application::ERROR_EXCEPTION);
        $event->setParam('exception', $exception);
        $this->options->getExceptionViewModelPreparer()->prepareExceptionViewModel($event);
        return $event->getResult();
    }

    public function onDispatch(MvcEvent $e) {
        $this->model = $this->acceptableViewModelSelector($this->options->getAcceptCriteria());
        return parent::onDispatch($e);
    }

    public function getList(){
        throw new Exception\RuntimeException(sprintf(
            '%s is not supported', __METHOD__
        ));
    }

    public function get($id){
        throw new Exception\RuntimeException(sprintf(
            '%s is not supported', __METHOD__
        ));
    }

    public function delete($id) {
        throw new Exception\RuntimeException(sprintf(
            '%s is not supported', __METHOD__
        ));
    }

    public function deleteList() {
        throw new Exception\RuntimeException(sprintf(
            '%s is not supported', __METHOD__
        ));
    }

    public function update($id, $data) {
        throw new Exception\RuntimeException(sprintf(
            '%s is not supported', __METHOD__
        ));
    }
}