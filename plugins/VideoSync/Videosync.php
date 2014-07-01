<?php

if (!defined('STATUSNET')) {
    exit(1);
}

require_once INSTALLDIR . '/classes/Memcached_DataObject.php';

class Videosync extends Memcached_DataObject
{
    public $__table = 'videosync'; // table name

    public $id;    //internal id. Used for sorting purposes
    public $yt_id; // YouTube ID of the video
    public $yt_name; // Name of the video
    public $tag; // Video tag
    public $duration; // Length of the video in seconds
    public $started; // Time the video was started
    public $next; // Video to play after this one
    public $temporary; // Remove the video from the list after it plays?

    function staticGet($k, $v=null)
    {
        return Memcached_DataObject::staticGet('Videosync', $k, $v);
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
            'yt_id' => DB_DATAOBJECT_STR + DB_DATAOBJECT_NOTNULL,
            'duration' => DB_DATAOBJECT_INT + DB_DATAOBJECT_NOTNULL,
            'tag' => DB_DATAOBJECT_STR,
            'yt_name' => DB_DATAOBJECT_STR,
            'started' => DB_DATAOBJECT_INT,
            'next' => DB_DATAOBJECT_INT,
			'temporary' => DB_DATAOBJECT_BOOL,
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

    function isCurrent() {
        if($this->started + $this->duration + 10 <= time()) {
            return false;
        }
        return true;
    }

    static function getCurrent($setNext = false) {
        $v = new Videosync();
        $v->orderBy("started DESC, id ASC");
        if(!$v->find() || !$v->fetch()) {
            $v = Videosync::setCurrent(1);
        }
        else if(!$v->isCurrent() && $setNext) {
			$id = $v->id;
			$next = $v->next;
			if($next < 1)
				$next = $id;
			if($v->temporary)
				$v->delete();
            $v = Videosync::setCurrent($next);
        }

        return $v;
    }
/*
    static function setCurrent($id) {
        $new = Videosync::staticGet('id', $id);
        if(empty($new)) {
            $new = Videosync::staticGet('id', 1);
        }

        if(!empty($new)) {
            $orig = clone($new);
            $new->started = time()+6;
            $new->update($orig);
        }
        else {
            $new = new Videosync();
            $new->id = "1";
            $new->yt_id = "wmKrQBWc2U4";
            $new->duration = 856;
            $new->started = time() - 7 * 60;
        }

        return $new;
    }*/
	
	static function setCurrent($id) {
		// Pseudo-shuffle (favors new videos and those that haven't been played recently)
		$v = new Videosync();
		$v->whereAdd('started <= 0');
		$v->whereAdd('id != ' . $id);
		$v->orderBy('id ASC');
		if(!$v->find(true)) {
			$v = new Videosync();
			$v->whereAdd('id != ' . $id);
			$v->orderBy('started ASC');
			$num = $v->count('id');
			$num = intval($num/3);
			if($num == 0)
				$num = 1;
			$num = rand(1, $num);
			$v->find();
			while($num > 0) {
				$v->fetch();
				$num--;
			}
        }
		$next = $v->id;
		$v = Videosync::staticGet($id);
		
		$o = clone($v);
		$v->started = time()+6;
		$v->next = $next;
		$v->update($o);
		
		return $v;
	}

    function sequenceKey()
    {
        return array(false, false, false);
    }
	
	static function idFromUrl($url) {
		if(strlen($url) == 11)
			return $url;
		if(preg_match('/(youtu.be\/|youtube.com\/watch\?(.+&)*v=)(.{11})/i', $url, $match))
			return $match[3];
		return false;
	}

}