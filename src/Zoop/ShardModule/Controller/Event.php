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
class Event
{
    const UNSERIALIZE = 'unserialize';

    const SERIALIZE = 'serialize';

    const SERIALIZE_LIST = 'serializeList';
    
    const FLUSH = 'flush';

    const PREPARE_VIEW_MODEL = 'prepareViewModel';

    const GET = 'get';

    const GET_LIST = 'getList';

    const CREATE = 'create';

    const DELETE = 'delete';

    const DELETE_LIST = 'deleteList';

    const PATCH = 'patch';

    const PATCH_LIST = 'patchList';

    const UPDATE = 'update';

    const REPLACE_LIST = 'replaceList';
}
