<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Zend\Http\Header\LastModified;
use Zend\Mvc\MvcEvent;
use Zoop\ShardModule\Exception;
use Zoop\ShardModule\Controller\Result;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class GetListener extends AbstractActionListener
{
    public function get(MvcEvent $event)
    {
        return $this->route($event);
    }

    protected function doAction(MvcEvent $event, $metadata, $documentManager)
    {
        $options = $event->getTarget()->getOptions();

        if (!($document = $event->getParam('document'))) {
            // document not set, so load it
            $document = $documentManager
                ->createQueryBuilder()
                ->find($metadata->name)
                ->field($options->getProperty())->equals($event->getParam('id'))
                ->getQuery()
                ->getSingleResult();

            if (! $document) {
                throw new Exception\DocumentNotFoundException();
            }
        }

        $result = new Result($document);

        if (isset($metadata->stamp['updatedOn'])) {
            $lastModified = new LastModified;
            $lastModified->setDate($metadata->getFieldValue($document, $metadata->stamp['updatedOn']));
            $result->addHeader($lastModified);
        }
        $result->addHeader($options->getCacheControl());

        $event->setResult($result);

        return $result;
    }
}
