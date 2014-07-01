<?php

if (!defined('STATUSNET')) {
    exit(1);
}

require_once INSTALLDIR . '/classes/Memcached_DataObject.php';

class User_notification_settings extends Memcached_DataObject
{
    public $__table = 'user_notification_settings'; // table name

    public $user_id; // User who these settings belong to
	public $enabled; // Are notifications enabled?
	public $newwindow; // Open all links in a new window?
	public $messages; // Private messages
	public $mentions; // Replies
	public $subscribes; // When users subscribe to you
	public $groups; // Group joins/requests
	public $faves; // When someone favorites your notice
	public $repeats; // When someone repeats your notice
	public $groupposts; // When someone posts to a group you're in (still must be enabled at the group level)

    function staticGet($k, $v=null)
    {
        return Memcached_DataObject::staticGet('User_notification_settings', $k, $v);
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
        return array('user_id' => DB_DATAOBJECT_INT + DB_DATAOBJECT_NOTNULL,
            'enabled' => DB_DATAOBJECT_BOOL + DB_DATAOBJECT_NOTNULL,
            'newwindow' => DB_DATAOBJECT_BOOL + DB_DATAOBJECT_NOTNULL,
            'messages' => DB_DATAOBJECT_BOOL + DB_DATAOBJECT_NOTNULL,
            'mentions' => DB_DATAOBJECT_BOOL + DB_DATAOBJECT_NOTNULL,
            'subscribes' => DB_DATAOBJECT_BOOL + DB_DATAOBJECT_NOTNULL,
            'groups' => DB_DATAOBJECT_BOOL + DB_DATAOBJECT_NOTNULL,
            'faves' => DB_DATAOBJECT_BOOL + DB_DATAOBJECT_NOTNULL,
            'repeats' => DB_DATAOBJECT_BOOL + DB_DATAOBJECT_NOTNULL,
            'groupposts' => DB_DATAOBJECT_BOOL + DB_DATAOBJECT_NOTNULL,
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
        return array('user_id' => 'K');
    }
	
	// Return values for a user, with reasonable defaults
	static function getValues($user) {
		$values = array(
			'enabled' => true,
			'newwindow' => false,
			'messages' => true,
			'mentions' => true,
			'subscribes' => true,
			'groups' => true,
			'faves' => true,
			'repeats' => true,
			'groupposts' => true,
		);
		
		$settings = User_notification_settings::staticGet('user_id', $user);
		if($settings) {
			foreach($values as $key => $unused)
				$values[$key] = $settings->$key;
		}
		
		return $values;
	}
	
	// Helper functions to simplify things
	static function isEnabled($user) {
		$vals = self::getValues($user);
		return $vals['enabled'];
	}
	
	static function getsRepeats($user) {
		$vals = self::getValues($user);
		return $vals['enabled'] && $vals['repeats'];
	}
	
	static function getsReplies($user) {
		$vals = self::getValues($user);
		return $vals['enabled'] && $vals['mentions'];
	}
	
	static function getsGroupPosts($user, $group) {
		$vals = self::getValues($user);
		if($vals['enabled'] && $vals['groupposts']) {
			$not = new User_notification_optout();
			$not->user_id = $user;
			$not->group_id = $group;
			return !($not->find(true));
		}
		return false;
	}
	
	static function getsGroupJoins($user) {
		$vals = self::getValues($user);
		return $vals['enabled'] && $vals['groups'];
	}
	
	static function getsFavorites($user) {
		$vals = self::getValues($user);
		return $vals['enabled'] && $vals['faves'];
	}
	
	static function getsSubscribes($user) {
		$vals = self::getValues($user);
		return $vals['enabled'] && $vals['subscribes'];
	}
	
	static function getsPrivateMessages($user) {
		$vals = self::getValues($user);
		return $vals['enabled'] && $vals['messages'];
	}
	
	static function openInNewWindow($user) {
		$vals = self::getValues($user);
		return $vals['newwindow'];
	}

    function sequenceKey()
    {
        return array(false, false, false);
    }
}
