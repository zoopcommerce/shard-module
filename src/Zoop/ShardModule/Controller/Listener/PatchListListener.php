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
class PatchListListener
{
    public function patchList(MvcEvent $event)
    {
        $options = $event->getTarget()->getOptions();
        $documentManager = $options->getModelManager();

        $list = $event->getParam('list');
        $result = $event->getResult();
        $patchList = $result->getModel();
        foreach ($patchList as $key => $item) {
            if (!$documentManager->contains($item)) {
                if (! $documentManager->getClassMetadata(get_class($item))->isEmbeddedDocument) {
                    $documentManager->persist($item);
                }
            }
            if (isset($list)) {
                $list[$key] = $item;
            }
        }

        $result->setModel($patchList);
        $result->setStatusCode(204);

        return $event->getResult();
    }
}
