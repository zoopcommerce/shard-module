<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Zend\Mvc\MvcEvent;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class ReplaceListListener
{
    public function replaceList(MvcEvent $event)
    {
        $event->getTarget()->deleteList($event);

        $options = $event->getTarget()->getOptions();
        $documentManager = $options->getModelManager();

        $list = $event->getParam('list');
        $result = $event->getResult();
        $newList = $result->getModel();
        foreach ($newList as $key => $item) {
            if (!$documentManager->contains($item)) {
                $result->setModel($item);
                $event->getTarget()->create($event);
            }
            if (isset($list)) {
                $list[$key] = $item;
            }
        }

        $result->setModel($newList);
        $result->setStatusCode(204);
        return $event->getResult();
    }
}
