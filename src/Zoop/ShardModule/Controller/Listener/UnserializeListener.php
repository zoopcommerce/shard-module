<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Zend\Mvc\MvcEvent;
use Zoop\Shard\Serializer\Unserializer;
use Zoop\ShardModule\Controller\Result;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class UnserializeListener
{

    public function create(MvcEvent $event)
    {
        if (count($event->getParam('deeperResource')) > 0) {
            return;
        }

        $result = new Result(
            $event->getTarget()->getOptions()->getManifest()->getServiceManager()->get('unserializer')->fromArray(
                $event->getParam('data'),
                $event->getTarget()->getOptions()->getClass(),
                null,
                Unserializer::UNSERIALIZE_PATCH
            )
        );
        $event->setResult($result);
        return $result;
    }
}
