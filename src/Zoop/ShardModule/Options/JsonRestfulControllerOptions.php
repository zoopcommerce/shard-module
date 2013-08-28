<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Options;

use Zoop\Shard\Rest\EndpointMap;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class JsonRestfulControllerOptions extends AbstractControllerOptions
{

    protected $acceptCriteria = [
        'Zend\View\Model\JsonModel' => [
            'application/json',
        ],
        'Zend\View\Model\ViewModel' => [
            '*/*',
        ],
    ];

    protected $endpoint;

    protected $serializer = 'serializer';

    protected $referenceMap = 'referenceMap';

    protected $documentClass;

    protected $limit = '30';

    protected $exceptionSerializer = 'Zoop\MaggottModule\JsonExceptionStrategy';

    protected $surpressFlush;

    protected $endpointMap;

    protected $getTemplate = 'zoop/rest/get';

    protected $getListTemplate = 'zoop/rest/get-list';

    protected $createAssistant = 'zoop.shardmodule.assistant.create';

    protected $deleteAssistant = 'zoop.shardmodule.assistant.delete';

    protected $deleteListAssistant = 'zoop.shardmodule.assistant.deletelist';

    protected $getAssistant = 'zoop.shardmodule.assistant.get';

    protected $getListAssistant = 'zoop.shardmodule.assistant.getlist';

    protected $patchAssistant = 'zoop.shardmodule.assistant.patch';

    protected $patchListAssistant = 'zoop.shardmodule.assistant.patchlist';

    protected $replaceListAssistant = 'zoop.shardmodule.assistant.replacelist';

    protected $updateAssistant = 'zoop.shardmodule.assistant.update';

    public function getAcceptCriteria()
    {
        return $this->acceptCriteria;
    }

    public function setAcceptCriteria(array $acceptCriteria)
    {
        $this->acceptCriteria = $acceptCriteria;
    }

    public function getEndpoint()
    {
        return $this->endpoint;
    }

    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
    }

    /**
     *
     * @param \Zoop\Common\Serializer\SerializerInterface | string $serializer
     */
    public function setSerializer($serializer)
    {
        $this->serializer = $serializer;
    }

    public function getSerializer()
    {
        if (is_string($this->serializer)) {
            $this->serializer = $this->serviceLocator->get($this->serializer);
        }

        return $this->serializer;
    }

    public function setReferenceMap($referenceMap)
    {
        $this->referenceMap = $referenceMap;
    }

    public function getReferenceMap()
    {
        if (is_string($this->referenceMap)) {
            $this->referenceMap = $this->serviceLocator->get($this->referenceMap);
        }

        return $this->referenceMap;
    }

    public function getDocumentClass()
    {
        return $this->documentClass;
    }

    public function setDocumentClass($documentClass)
    {
        $this->documentClass = (string) $documentClass;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function setLimit($limit)
    {
        $this->limit = (int) $limit;
    }

    public function getExceptionSerializer()
    {
        if (is_string($this->exceptionSerializer)) {
            $this->exceptionSerializer = $this->serviceLocator->get($this->exceptionSerializer);
        }

        return $this->exceptionSerializer;
    }

    public function setExceptionSerializer($exceptionSerializer)
    {
        $this->exceptionSerializer = $exceptionSerializer;
    }

    public function getSurpressFlush()
    {
        return $this->surpressFlush;
    }

    public function setSurpressFlush($surpressFlush)
    {
        $this->surpressFlush = (boolean) $surpressFlush;
    }

    public function getEndpointMap()
    {
        return $this->endpointMap;
    }

    public function setEndpointMap(EndpointMap $endpointMap)
    {
        $this->endpointMap = $endpointMap;
    }

    public function getGetTemplate()
    {
        return $this->getTemplate;
    }

    public function setGetTemplate($getTemplate)
    {
        $this->getTemplate = (string) $getTemplate;
    }

    public function getGetListTemplate()
    {
        return $this->getListTemplate;
    }

    public function setGetListTemplate($getListTemplate)
    {
        $this->getListTemplate = (string) $getListTemplate;
    }

    public function getCreateAssistant()
    {
        if (is_string($this->createAssistant)) {
            $this->createAssistant = $this->serviceLocator->get($this->createAssistant);
        }

        return $this->createAssistant;
    }

    public function setCreateAssistant($createAssistant)
    {
        $this->createAssistant = $createAssistant;
    }

    public function getDeleteAssistant()
    {
        if (is_string($this->deleteAssistant)) {
            $this->deleteAssistant = $this->serviceLocator->get($this->deleteAssistant);
        }

        return $this->deleteAssistant;
    }

    public function setDeleteAssistant($deleteAssistant)
    {
        $this->deleteAssistant = $deleteAssistant;
    }

    public function getDeleteListAssistant()
    {
        if (is_string($this->deleteListAssistant)) {
            $this->deleteListAssistant = $this->serviceLocator->get($this->deleteListAssistant);
        }

        return $this->deleteListAssistant;
    }

    public function setDeleteListAssistant($deleteListAssistant)
    {
        $this->deleteListAssistant = $deleteListAssistant;
    }

    public function getGetAssistant()
    {
        if (is_string($this->getAssistant)) {
            $this->getAssistant = $this->serviceLocator->get($this->getAssistant);
        }

        return $this->getAssistant;
    }

    public function setGetAssistant($getAssistant)
    {
        $this->getAssistant = $getAssistant;
    }

    public function getGetListAssistant()
    {
        if (is_string($this->getListAssistant)) {
            $this->getListAssistant = $this->serviceLocator->get($this->getListAssistant);
        }

        return $this->getListAssistant;
    }

    public function setGetListAssistant($getListAssistant)
    {
        $this->getListAssistant = $getListAssistant;
    }

    public function getPatchAssistant()
    {
        if (is_string($this->patchAssistant)) {
            $this->patchAssistant = $this->serviceLocator->get($this->patchAssistant);
        }

        return $this->patchAssistant;
    }

    public function setPatchAssistant($patchAssistant)
    {
        $this->patchAssistant = $patchAssistant;
    }

    public function getPatchListAssistant()
    {
        if (is_string($this->patchListAssistant)) {
            $this->patchListAssistant = $this->serviceLocator->get($this->patchListAssistant);
        }

        return $this->patchListAssistant;
    }

    public function setPatchListAssistant($patchListAssistant)
    {
        $this->patchListAssistant = $patchListAssistant;
    }

    public function getReplaceListAssistant()
    {
        if (is_string($this->replaceListAssistant)) {
            $this->replaceListAssistant = $this->serviceLocator->get($this->replaceListAssistant);
        }

        return $this->replaceListAssistant;
    }

    public function setReplaceListAssistant($replaceListAssistant)
    {
        $this->replaceListAssistant = $replaceListAssistant;
    }

    public function getUpdateAssistant()
    {
        if (is_string($this->updateAssistant)) {
            $this->updateAssistant = $this->serviceLocator->get($this->updateAssistant);
        }

        return $this->updateAssistant;
    }

    public function setUpdateAssistant($updateAssistant)
    {
        $this->updateAssistant = $updateAssistant;
    }
}
