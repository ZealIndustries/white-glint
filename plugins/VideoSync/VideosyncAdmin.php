<?php

if (!defined('STATUSNET')) {
    exit(1);
}

require_once INSTALLDIR . '/classes/Memcached_DataObject.php';

class VideosyncAdmin extends Memcached_DataObject
{
    public $__table = 'videosyncadmin'; // table name

    public $id;    // User id

    function staticGet($k, $v=null)
    {
        return Memcached_DataObject::staticGet('VideosyncAdmin', $k, $v);
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

    static function isAdmin($user) {
		if ($user->hasRight(Right::CONFIGURESITE))
			return true;
        return (self::staticGet('id', $user->id)) ? true : false;
    }
	
	static function promoteUser($user) {
		$orig = self::staticGet('id', $user->id);
		if($orig)
			return false;
		$orig = new VideosyncAdmin();
		$orig->id = $user->id;
		$orig->insert();
		return true;
	}
	
	static function demoteUser($user) {
		$orig = self::staticGet('id', $user->id);
		if(!$orig)
			return false;
		$orig->delete();
		return true;
	}

    function sequenceKey()
    {
        return array(false, false, false);
    }

}
