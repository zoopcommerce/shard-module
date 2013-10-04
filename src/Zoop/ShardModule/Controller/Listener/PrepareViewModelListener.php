<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class PrepareViewModelListener
{
    public function __call($name, $args)
    {
        return $this->prepareViewModel($args[0], $name);
    }

    public function prepareViewModel(MvcEvent $event, $action)
    {
        if ($event->getTarget()->forward()->getNumNestedForwards() > 0) {
            return $event->getResult();
        }

        $result = $event->getResult();

        $response = $event->getResponse();
        $response->setStatusCode($result->getStatusCode());
        $response->getHeaders()->addHeaders($result->getHeaders());

        $controller = $event->getTarget();

        $viewModel = $controller->acceptableViewModelSelector($controller->getOptions()->getAcceptCriteria());
        if ($vars = $result->getSerializedModel()) {
            $viewModel->setVariables($vars);
        }

        //set the template
        if ($viewModel instanceof JsonModel && count($viewModel->getVariables()) == 0) {
            if ($response->getStatusCode() == 200) {
                $response->setStatusCode(204);
            }
            return $response;
        } elseif ($viewModel->getTemplate() == null) {
            $viewModel->setTemplate($controller->getOptions()->getTemplates()[$action]);
        }

        $event->setResult($viewModel);

        return $viewModel;
    }
}
