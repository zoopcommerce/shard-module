<?php
/**
 * @link       http://zoopcommerce.github.io/shard-module
 * @package    Zoop
 * @license    MIT
 */

namespace Zoop\ShardModule\Exception;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class FlushException extends \Exception implements ExceptionInterface
{
    protected $innerExceptions;

    protected $statusCode = 500;

    public function getInnerExceptions()
    {
        return $this->innerExceptions;
    }

    public function setInnerExceptions($innerExceptions)
    {
        $this->innerExceptions = $innerExceptions;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }
}
