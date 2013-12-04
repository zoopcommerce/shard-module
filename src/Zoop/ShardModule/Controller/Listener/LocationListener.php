<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Zend\Http\Header\Location;
use Zend\Mvc\MvcEvent;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class LocationListener
{
    public function __call($name, $args)
    {
        return $this->addLocationHeader($args[0], $name);
    }

    protected function addLocationHeader(MvcEvent $event)
    {
        $options = $event->getTarget()->getOptions();
        if ($property = $options->getProperty()) {
            $result = $event->getResult();
            $createdDocument = $result->getModel();
            $result->addHeader(Location::fromString(
                'Location: ' .
                $event->getRequest()->getUri()->getPath() .
                '/' .
                $options->getModelManager()
                    ->getClassMetadata(get_class($createdDocument))
                    ->getFieldValue($createdDocument, $property)
            ));
        }
        return $event->getResult();
    }
}
