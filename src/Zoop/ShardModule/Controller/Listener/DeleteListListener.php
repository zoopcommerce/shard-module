<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Zend\Mvc\MvcEvent;
use Zoop\ShardModule\Controller\Result;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class DeleteListListener
{
    public function deleteList(MvcEvent $event)
    {
        if ($list = $event->getParam('list')) {
            foreach ($list->getKeys() as $key) {
                $list->remove($key);
            }
        } else {
            $options = $event->getTarget()->getOptions();
            $options->getModelManager()
                ->createQueryBuilder($options->getClass())
                ->remove()
                ->getQuery()
                ->execute();
        }

        if (!($result = $event->getResult())) {
            $result = new Result([]);
            $result->setStatusCode(204);
            $event->setResult($result);
        }

        return $result;
    }
}
