<?php
/**
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\ShardModule\Controller;

use Zoop\ShardModule\Exception\DocumentNotFoundException;
use Zoop\ShardModule\Options\DojoControllerOptions;
use Zend\Http\Header\ContentType;
use Zend\Mvc\Controller\AbstractActionController;

class DojoController extends AbstractActionController
{

    protected $options;

    public function getOptions() {
        return $this->options;
    }

    public function setOptions(DojoControllerOptions $options) {
        $this->options = $options;
    }

    public function __construct(DojoControllerOptions $options = null) {
        if (!isset($options)){
            $options = new DojoControllerOptions;
        }
        $this->setOptions($options);
    }

    public function indexAction()
    {
        $module = $this->getEvent()->getRouteMatch()->getParam('module');

        $resourceMap = $this->options->getResourceMap();

        if ( ! $resourceMap->has($module)){
            throw new DocumentNotFoundException();
        }

        $response = $this->getResponse();
        $response->setContent($resourceMap->get($module));
        $response->getHeaders()->addHeader(ContentType::fromString('Content-type: application/javascript'));
        $response->setStatusCode(200);
        return $response;
    }
}