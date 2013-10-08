<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class Result
{
    protected $model;

    protected $serializedModel;

    protected $statusCode = 200;

    protected $headers = [];

    public function getModel()
    {
        return $this->model;
    }

    public function setModel($model)
    {
        $this->model = $model;
    }

    public function getSerializedModel()
    {
        return $this->serializedModel;
    }

    public function setSerializedModel(array $serializedModel)
    {
        $this->serializedModel = $serializedModel;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    public function addHeader($header)
    {
        $this->headers[] = $header;
    }

    public function __construct($model = null)
    {
        $this->model = $model;
    }
}
