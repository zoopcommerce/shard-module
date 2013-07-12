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

    public function getAcceptCriteria() {
        return $this->acceptCriteria;
    }

    public function setAcceptCriteria(array $acceptCriteria) {
        $this->acceptCriteria = $acceptCriteria;
    }

    public function getEndpoint() {
        return $this->endpoint;
    }

    public function setEndpoint($endpoint) {
        $this->endpoint = $endpoint;
    }

    /**
     *
     * @param \Zoop\Common\Serializer\SerializerInterface | string $serializer
     */
    public function setSerializer($serializer) {
        $this->serializer = $serializer;
    }

    public function getSerializer() {
        if (is_string($this->serializer)) {
            $this->serializer = $this->serviceLocator->get($this->serializer);
        }
        return $this->serializer;
    }

    public function setReferenceMap($referenceMap) {
        $this->referenceMap = $referenceMap;
    }

    public function getReferenceMap() {
        if (is_string($this->referenceMap)) {
            $this->referenceMap = $this->serviceLocator->get($this->referenceMap);
        }
        return $this->referenceMap;
    }

    public function getDocumentClass() {
        return $this->documentClass;
    }

    public function setDocumentClass($documentClass) {
        $this->documentClass = (string) $documentClass;
    }

    public function getLimit() {
        return $this->limit;
    }

    public function setLimit($limit) {
        $this->limit = (int) $limit;
    }

    public function getExceptionSerializer() {
        if (is_string($this->exceptionSerializer)) {
            $this->exceptionSerializer = $this->serviceLocator->get($this->exceptionSerializer);
        }
        return $this->exceptionSerializer;
    }

    public function setExceptionSerializer($exceptionSerializer) {
        $this->exceptionSerializer = $exceptionSerializer;
    }

    public function getSurpressFlush() {
        return $this->surpressFlush;
    }

    public function setSurpressFlush($surpressFlush) {
        $this->surpressFlush = (boolean) $surpressFlush;
    }

    public function getEndpointMap() {
        return $this->endpointMap;
    }

    public function setEndpointMap(EndpointMap $endpointMap) {
        $this->endpointMap = $endpointMap;
    }
}
