<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\JsonRestfulController;

use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Zoop\ShardModule\Exception;
use Zend\Http\Header\CacheControl;
use Zend\Http\Header\LastModified;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class GetAssistant extends AbstractAssistant
{

    public function doGet($document, $deeperResource = [])
    {
        $documentManager = $this->options->getDocumentManager();
        $serializer = $this->options->getSerializer();
        $metadata = $this->metadata;
        $endpoint = $this->endpoint;

        if (count($deeperResource) == 0) {
            if (is_string($document)) {
                $document = $documentManager
                    ->createQueryBuilder()
                    ->find($metadata->name)
                    ->field($endpoint->getProperty())->equals($document)
                    ->hydrate(false)
                    ->getQuery();
                $document = $document->getSingleResult();

                if (! $document) {
                    throw new Exception\DocumentNotFoundException();
                }
            }

            if (isset($metadata->stamp['updatedOn'])) {
                $lastModified = new LastModified;
                $sec = $document[$metadata->stamp['updatedOn']]->sec;
                $lastModified->setDate(new \DateTime("@$sec"));
                $this->controller->getResponse()->getHeaders()->addHeader($lastModified);
            }

            $cacheControlOptions = $endpoint->getCacheControl();
            $cacheControl = new CacheControl;
            if ($cacheControlOptions->getPublic()) {
                $cacheControl->addDirective('public', true);
            }
            if ($cacheControlOptions->getPrivate()) {
                $cacheControl->addDirective('private', true);
            }
            if ($cacheControlOptions->getNoCache()) {
                $cacheControl->addDirective('no-cache', true);
            }
            if ($cacheControlOptions->getMaxAge()) {
                $cacheControl->addDirective('max-age', $cacheControlOptions->getMaxAge());
            }
            $this->controller->getResponse()->getHeaders()->addHeader($cacheControl);

            if ($select = $this->getSelect()) {
                $document = array_intersect_key($document, array_fill_keys($select, 0));
            }

            return $serializer->applySerializeMetadataToArray($document, $metadata->name);
        }

        $field = $deeperResource[0];
        array_shift($deeperResource);

        //check if field can be returned
        if (! $serializer->isSerializableField($field, $metadata)) {
            throw new Exception\DocumentNotFoundException();
        }

        $mapping = $metadata->fieldMappings[$field];

        if (isset($mapping['reference']) && $mapping['reference'] && $mapping['type'] == 'many') {
            $this->controller->getRequest()->getQuery()->set($metadata->fieldMappings[$field]['mappedBy'], $document);
            $referenceMetadata = $this->options
                ->getDocumentManager()
                ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);
            $referenceEndpoint = $this->options
                ->getEndpointMap()
                ->getEndpointsFromMetadata($referenceMetadata)[0];

            return $this->forward()->dispatch(
                'rest.' . $this->options->getManifestName() . '.' . $referenceEndpoint->getName(),
                ['id' => implode('/', $deeperResource)]
            );
        }

        if (is_string($document)) {
            $document = $documentManager
                ->createQueryBuilder()
                ->find($metadata->name)
                ->field($endpoint->getProperty())->equals($document)
                ->field($field)->exists(true)
                ->select($field)
                ->hydrate(false)
                ->getQuery()
                ->getSingleResult();

            if (! $document) {
                throw new Exception\DocumentNotFoundException();
            }
        }

        if (isset($mapping['reference']) && $mapping['reference'] && $mapping['type'] == 'one') {
            if (! $referencedDocument = $document[$field]) {
                throw new Exception\DocumentNotFoundException;
            }
            $referencedMetadata = $documentManager
                ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);
            $referencedEndpoint = $this->options
                ->getEndpointMap()
                ->getEndpointsFromMetadata($referencedMetadata)[0];
            if (is_string($referencedDocument)) {
                $referencedDocument = $this->options
                    ->getDocumentManager()->getRepository($referencedMetadata->name)->find($referencedDocument);
            }
            if ($referencedDocument instanceof Proxy) {
                $referencedDocument->__load();
            }
            array_unshift(
                $deeperResource,
                $referencedMetadata->reflFields[$referencedEndpoint->getProperty()]->getValue($referencedDocument)
            );

            return $this->forward()->dispatch(
                'rest.' . $this->options->getManifestName() . '.' . $referencedEndpoint->getName(),
                ['id' => implode('/', $deeperResource)]
            );
        }

        if (isset($mapping['embedded']) && $mapping['embedded'] && $mapping['type'] == 'many') {
            $this->metadata = $this
                ->options
                ->getDocumentManager()
                ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);
            if (count($deeperResource) > 0) {
                $collection = $document[$field];
                $embeddedEndpoint = $endpoint->getEmbeddedLists()[$field];
                $embeddedEndpointProperty = $embeddedEndpoint->getProperty();

                if ($embeddedEndpointProperty == '$set') {
                    if (isset($collection[$deeperResource[0]])) {
                        $embeddedDocument = $collection[$deeperResource[0]];
                        $this->endpoint = $embeddedEndpoint;
                        array_shift($deeperResource);
                        return $this->doGet($embeddedDocument, $deeperResource);
                    }
                } else {
                    foreach ($collection as $embeddedDocument) {
                        //this iteration is slow. Should be replaced when upgrade to new version of mongo happens
                        if ($embeddedDocument[$embeddedEndpointProperty] == $deeperResource[0]) {
                            array_shift($deeperResource);
                            $this->endpoint = $embeddedEndpoint;

                            return $this->doGet($embeddedDocument, $deeperResource);
                        }
                    }
                }
                //embedded document not found in collection
                throw new Exception\DocumentNotFoundException();
            } else {
                $getListAssistant = $this->options->getGetListAssistant();
                $getListAssistant->setController($this->controller);

                return $getListAssistant->doGetList($document[$field]);
            }
        }

        if (isset($mapping['embedded']) && $mapping['embedded'] && $mapping['type'] == 'one') {
            $this->metadata = $this
                ->options
                ->getDocumentManager()
                ->getClassMetadata($metadata->fieldMappings[$field]['targetDocument']);

            return $this->doGet($document[$field], $deeperResource);
        }

        throw new Exception\DocumentNotFoundException();
    }
}
