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
        return $this->unserialize($event, null, Unserializer::UNSERIALIZE_PATCH);
    }

    public function update(MvcEvent $event)
    {
        return $this->unserialize($event, $event->getParam('document'), Unserializer::UNSERIALIZE_UPDATE);
    }

    public function unserialize(MvcEvent $event, $document, $mode)
    {
        if ($result = $event->getResult()) {
            return $result;
        }

        $result = new Result(
            $event->getTarget()->getOptions()->getManifest()->getServiceManager()->get('unserializer')->fromArray(
                $event->getParam('data'),
                $event->getTarget()->getOptions()->getClass(),
                $document,
                $mode
            )
        );
        $event->setResult($result);
        return $result;
    }
}
