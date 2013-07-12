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
class InvalidDocumentException extends \Exception implements ExceptionInterface
{
    protected $validatorMessages = [];

    protected $document;

    public function getValidatorMessages() {
        return $this->validatorMessages;
    }

    public function setValidatorMessages(array $validatorMessages) {
        $this->validatorMessages = $validatorMessages;
    }

    public function getDocument() {
        return $this->document;
    }

    public function setDocument($document) {
        $this->document = $document;
    }
}
