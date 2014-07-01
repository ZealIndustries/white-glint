<?php

if (!defined('STATUSNET')) {
    exit(1);
}

require_once INSTALLDIR . '/classes/Memcached_DataObject.php';

class User_notification extends Memcached_DataObject
{
    public $__table = 'user_notification'; // table name

    public $id;    // internal id. Used for sorting purposes
    public $user_id; // User who received the notification
	public $from_user_id; // User who triggered the notification
    public $type; // Type of notification
	public $arg; // ID of object that triggered the notification; may refer to a notice, group, or message depending on $type
	public $arg2; // For posts to a group, the notice ID
	public $created; // When the notification was created

    function staticGet($k, $v=null)
    {
        return Memcached_DataObject::staticGet('User_notification', $k, $v);
    }

    /**
     * return table definition for DB_DataObject
     *
     * DB_DataObject needs to know something about the table to manipulate
     * instances. This method provides all the DB_DataObject needs to know.
     *
     * @return array array of column definitions
     */
    function table()
    {
        return array('id' => DB_DATAOBJECT_INT + DB_DATAOBJECT_NOTNULL,
            'user_id' => DB_DATAOBJECT_INT + DB_DATAOBJECT_NOTNULL,
            'from_user_id' => DB_DATAOBJECT_INT + DB_DATAOBJECT_NOTNULL,
            'type' => DB_DATAOBJECT_STR + DB_DATAOBJECT_NOTNULL,
            'arg' => DB_DATAOBJECT_INT,
            'arg2' => DB_DATAOBJECT_INT,
			'created' => DB_DATAOBJECT_INT + DB_DATAOBJECT_NOTNULL,
        );
    }

    /**
     * return key definitions for DB_DataObject
     *
     * DB_DataObject needs to know about keys that the table has, since it
     * won't appear in StatusNet's own keys list. In most cases, this will
     * simply reference your keyTypes() function.
     *
     * @return array list of key field names
     */
    function keys()
    {
        return array_keys($this->keyTypes());
    }

    /**
     * return key definitions for Memcached_DataObject
     *
     * Our caching system uses the same key definitions, but uses a different
     * method to get them. This key information is used to store and clear
     * cached data, so be sure to list any key that will be used for static
     * lookups.
     *
     * @return array associative array of key definitions, field name to type:
     *         'K' for primary key: for compound keys, add an entry for each component;
     *         'U' for unique keys: compound keys are not well supported here.
     */
    function keyTypes()
    {
        return array('id' => 'K');
    }

	// Return an array of notification information, ready to be JSON-encoded and sent to the user
	// Return false if no notifications
	static function getAllForUser($user) {
		$notify = new User_notification();
		$notify->user_id = $user->id;
		if(!$notify->find())
			return false;
			
		$return = array();
		while($notify->fetch()) {
			if(!isset($return[$notify->type]))
				$return[$notify->type] = array();
			$item = array();
			
			$item['type'] = $notify->type;
			$item['id'] = $notify->id;
			$item['created'] = $notify->created;
				
			$other = Profile::staticGet('id', $notify->from_user_id);
			if($other == false)
				continue;
			$item['user'] = array(
				'id' => $other->id,
				'nickname' => $other->nickname,
				'fullname' => $other->getBestName(),
			);
			
			switch($notify->type) {
			case 'mention':
			case 'favorite':
			case 'repeat':
				$notice = Notice::staticGet('id', $notify->arg);
				if($notice == false) {
					$item = null;
					break;
				}
				$item['notice'] = array(
					'id' => $notice->id,
					'content' => $notice->content,
					'rendered' => $notice->rendered,
					'url' => $notice->bestUrl(),
					'replieslink' => common_local_url('replies', array('nickname' => $user->nickname)),
				);
				break;
				
			case 'grouppost':
				$notice = Notice::staticGet('id', $notify->arg2);
				if($notice == false) {
					$item = null;
					break;
				}
				$item['notice'] = array(
					'id' => $notice->id,
					'content' => $notice->content,
					'rendered' => $notice->rendered,
					'url' => $notice->bestUrl(),
				);
				
			case 'groupjoin':
			case 'grouprequest';
				$group = User_group::staticGet('id', $notify->arg);
				if($group == false) {
					$item = null;
					break;
				}
				$item['group'] = array(
					'id' => $group->id,
					'name' => $group->getFancyName(),
					'url' => $group->permalink(),
				);
				break;
				
			case 'message':
				$item['inboxlink'] = common_local_url('inbox', array('nickname' => $user->nickname));
				break;
				
			case 'subscribe':
				$item['subscriberslist'] = common_local_url('subscribers', array('nickname' => $user->nickname));
				break;
				
			default:
				break;
			}
			
			if($item != null)
				$return[$notify->type][] = $item;
		}
		return $return;
	}
	
	static function saveNew($from, $to, $type, $arg1 = null, $arg2 = null) {
		if(!$from || !$to || $from->id == null || $to->id == null)
			return false;
		$notify = new User_notification();
		$notify->user_id = $to->id;
		$notify->from_user_id = $from->id;
		$notify->type = $type;
		$notify->arg = $arg1;
		$notify->arg2 = $arg2;
		$notify->created = time();
		$notify->insert();
		return $notify;
	}
}
