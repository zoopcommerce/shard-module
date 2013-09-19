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

    protected $unserializer;

    public function create(MvcEvent $event)
    {
        if (count($event->getParam('deeperResource')) > 0 || $result = $event->getResult()) {
            return $event->getResult();
        }

        return $this->unserialize($event, null, Unserializer::UNSERIALIZE_PATCH);
    }

    public function update(MvcEvent $event)
    {
        if (count($event->getParam('deeperResource')) > 0 || $result = $event->getResult()) {
            return $event->getResult();
        }

        $data = $event->getParam('data');
        $id = $event->getParam('id');
        $options = $event->getTarget()->getOptions();

        $data[$options->getProperty()] = $id;
        $event->setParam('data', $data);

        return $this->unserialize($event, $event->getParam('document'), Unserializer::UNSERIALIZE_UPDATE);
    }

    public function unserialize(MvcEvent $event, $document, $mode)
    {
        if (!isset($this->unserializer)) {
            $this->unserializer = $event->getTarget()->getOptions()->getManifest()->getServiceManager()->get('unserializer');
        }

        $result = new Result(
            $this->unserializer->fromArray(
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
