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
class IdChangeListener
{
    public function update(MvcEvent $event)
    {
        if (count($event->getParam('deeperResource')) > 0) {
            return $event->getResult();
        }

        $options = $event->getTarget()->getOptions();
        $documentManager = $options->getModelManager();
        $metadata = $documentManager->getClassMetadata($options->getClass());
        $document = $event->getResult()->getModel();

        $identifier = $metadata->getIdentifierValues($document);
        $documentId = array_values($identifier)[0];
        $dataId = $event->getParam('data')[array_keys($identifier)[0]];

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

        $event->getResult()->setModel($newDocument);

        return $event->getResult();
    }
}
