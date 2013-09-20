<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Zend\Mvc\MvcEvent;
use Zend\Http\Header\Location;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class IdChangeListener
{
    public function update(MvcEvent $event)
    {
        return $this->idChange($event);
    }

    public function patch(MvcEvent $event)
    {
        return $this->idChange($event);
    }

    public function idChange(MvcEvent $event)
    {
        if (count($event->getParam('deeperResource')) > 0) {
            return $event->getResult();
        }

        $options = $event->getTarget()->getOptions();
        $documentManager = $options->getModelManager();
        $metadata = $documentManager->getClassMetadata($options->getClass());
        $document = $event->getResult()->getModel();
        $data = $event->getParam('data');

        $identifier = $metadata->identifier;

        if (!isset($identifier) || !isset($data[$identifier])) {
            return $event->getResult();
        }

        $documentId = $metadata->getFieldValue($document, $identifier);
        $dataId = $data[$identifier];

        if ($dataId == $documentId) {
            return $event->getResult();
        }

        //the update is asking to change the doucment Id

        //first delete the current document
        $event->getTarget()->delete($event);

        //clone the old document into the new document
        $newDocument = $metadata->newInstance();
        foreach ($metadata->reflFields as $refl) {
            $refl->setValue($newDocument, $refl->getValue($document));
        }
        $metadata->reflFields[$metadata->identifier]->setValue($newDocument, $dataId);

        //update all the references (could be lots)
        $referenceMap = $options
            ->getManifest()
            ->getServiceManager()
            ->get('referenceMap')->getMap();

        if (isset($referenceMap[$metadata->name])) {
            $identityMap = $documentManager->getUnitOfWork()->getIdentityMap();
            foreach ($referenceMap[$metadata->name] as $mapping) {
                //update all references in the db
                $documentManager
                    ->createQueryBuilder($mapping['class'])
                    ->update()
                    ->multiple(true)
                    ->field($mapping['field'])->equals($documentId)
                    ->field($mapping['field'])->set($dataId)
                    ->getQuery()
                    ->execute();
            }

            //update all references for docs currently loaded in the uow
            if (isset($identityMap[$mapping['class']])) {
                $doucmentUsingRefMetadata = $documentManager->getClassMetadata($mapping['class']);
                foreach ($identityMap[$mapping['class']] as $documentUsingRef) {
                    if ($mapping['type'] == 'one' &&
                        $documentId == $metadata->reflFields[$metadata->identifier]->getValue(
                            $doucmentUsingRefMetadata->reflFields[$mapping['field']]->getValue(
                                $documentUsingRef
                            )
                        )
                    ) {
                        $doucmentUsingRefMetadata
                            ->reflFields[$mapping['field']]
                            ->setValue($documentUsingRef, $newDocument);
                    } else {
                        //TODO: mapping type == many
                    }
                }
            }
        }

        //finally, persist the new doc
        if (! $metadata->isEmbeddedDocument) {
            $documentManager->persist($newDocument);
        }

        $result = $event->getResult();
        $result->setModel($newDocument);
        $result->addHeader($this->getLocationHeader($event, $metadata, $newDocument));

        return $result;
    }

    protected function getLocationHeader($event, $metadata, $document){

        if ($property = $event->getTarget()->getOptions()->getProperty()) {
            $pieces = explode('/', $event->getRequest()->getUri()->getPath());
            array_pop($pieces);
            return Location::fromString(
                'Location: ' .
                implode('/', $pieces) .
                '/' .
                $metadata->getFieldValue($document, $property)
            );
        }
    }
}
