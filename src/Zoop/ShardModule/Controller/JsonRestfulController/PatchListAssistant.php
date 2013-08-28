<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\JsonRestfulController;

use Zoop\Shard\Serializer\Serializer;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class PatchListAssistant extends AbstractAssistant
{

    public function doPatchList(array $data, $list = [])
    {
        $metadata = $this->metadata;

        $documentManager = $this->options->getDocumentManager();

        $createAssistant = $this->options->getCreateAssistant();
        $createAssistant->setController($this->controller);

        foreach ($data as $key => $item) {
            $document = $this->unserialize($item, null, $metadata, Serializer::UNSERIALIZE_PATCH);
            if ($documentManager->contains($document)) {
                $list[$key] = $document;
            } else {
                $list[$key] = $createAssistant->doCreate($item, $document, []);
            }
        }

        return $list;
    }
}
