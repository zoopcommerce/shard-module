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
class ReplaceListAssistant extends AbstractAssistant
{

    public function doReplaceList(array $data, $list = []){

        $metadata = $this->metadata;

        $deleteListAssistant = new DeleteListAssistant(
            $metadata,
            $this->endpoint,
            $this->controller
        );
        $deleteListAssistant->doDeleteList($list);

        $createAssistant = new CreateAssistant(
            $metadata,
            $this->endpoint,
            $this->controller
        );

        $documentManager = $this->options->getDocumentManager();
        foreach ($data as $key => $item){
            $document = $this->unserialize($item, null, $metadata, Serializer::UNSERIALIZE_UPDATE);
            if ($documentManager->contains($document)){
                $list[$key] = $document;
            } else {
                $list[$key] = $createAssistant->doCreate($item, $document, []);
            }
        }

        return $list;
    }
}
