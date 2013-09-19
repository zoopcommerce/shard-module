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
class DocumentAlreadyExistsException extends \Exception implements ExceptionInterface
{
    protected $document;

    protected $location;

    public function getDocument()
    {
        return $this->document;
    }

    public function setDocument($document)
    {
        $this->document = $document;
    }

    public function getLocation() {
        return $this->location;
    }

    public function setLocation($location) {
        $this->location = $location;
    }
}
