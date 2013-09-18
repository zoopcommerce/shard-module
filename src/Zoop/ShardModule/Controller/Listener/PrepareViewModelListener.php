<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Zend\Mvc\MvcEvent;
use Zend\EventManager\EventManagerInterface;
use Zend\View\Model\ModelInterface;
use Zend\View\Model\JsonModel;
use Zoop\ShardModule\Controller\Event;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class PrepareViewModelListener extends AbstractListener
{
    /**
     * Attach to an event manager
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(Event::PREPARE_VIEW_MODEL, [$this, 'onPrepareViewModel']);
    }

    public function onPrepareViewModel(MvcEvent $event)
    {
        $result = $event->getResult();
        $controller = $event->getTarget();

        if ($result instanceof ModelInterface) {
            $viewModel = $result;
        } else {
            $viewModel = $controller->acceptableViewModelSelector($controller->getOptions()->getAcceptCriteria());
            if (isset($result)) {
                $viewModel->setVariables($result);
            }
        }

        //set the template
        if ($viewModel instanceof JsonModel && count($viewModel->getVariables()) == 0) {
            return $event->getResponse();
        } else if (!($template = $viewModel->getTemplate())) {
            $viewModel->setTemplate($controller->getOptions()->getTemplates()[$event->getRouteMatch()->getParam('action')]);
        }

        return $viewModel;
    }
}
