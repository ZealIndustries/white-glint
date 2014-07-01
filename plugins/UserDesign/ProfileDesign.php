<?php

if (!defined('STATUSNET')) {
    exit(1);
}
require_once INSTALLDIR.'/classes/Memcached_DataObject.php';

class ProfileDesign extends Memcached_DataObject
{
    public $__table = 'profiledesign'; // table name

    public $id;                         // int(4)  primary_key not_null
    public $bgcolor;
    public $contentcolor;
    public $asidecolor;
    public $textcolor;
    public $linkcolor;
    public $infocolor;
    public $bgimage;
    public $infoimage;
    public $designoptions;

    function staticGet($k, $v=null)
    {
        return Memcached_DataObject::staticGet('ProfileDesign', $k, $v);
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
			// All colors should be represented as 6-digit hex strings w/o preceeding #
            'bgcolor' => DB_DATAOBJECT_STR,
            'contentcolor' => DB_DATAOBJECT_STR,
            'asidecolor' => DB_DATAOBJECT_STR,
            'textcolor' => DB_DATAOBJECT_STR,
            'linkcolor' => DB_DATAOBJECT_STR,
            'infocolor' => DB_DATAOBJECT_STR,
			// Strings pointing to image file in /background folder?
            'bgimage' => DB_DATAOBJECT_STR, // id-bg-timestamp.ext
            'infoimage' => DB_DATAOBJECT_STR, // id-bn-timestamp.ext
			/* Design options flags:
			 *   1 - Use background image
			 *   2 - Tile background image horizontally
			 *   4 - Tile background image vertically
			 *   8 - Anchor background to top
			 *  16 - Anchor background to center (setting neither anchors background to bottom)
			 *  32 - background-attachment: scroll (else fixed)
			 *  64 - Use infobox image
			 * 128 - Attach infobox image to top-left (else top-right)
			 * 256 - Light infobox text (#EEE w/ shadow, else black w/o shadow)
			 * 512 - Use design everywhere (profile design only)
			 */ 'designoptions' => DB_DATAOBJECT_INT + DB_DATAOBJECT_NOTNULL,
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

    function sequenceKey()
    {
        return array(false, false, false);
    }

    function getDesign($id) {
	    $database = ProfileDesign::staticGet('id', $id);
        if($database) {
			$vars = array();

			$fields = array(
				'id',
				'bgcolor',
				'contentcolor',
				'asidecolor',
				'textcolor',
				'linkcolor',
				'infocolor',
				'bgimage',
				'infoimage',
				'designoptions',
			);
		
            foreach($fields as $key) {
                $dbvar = $database->$key;
                $vars[$key] = $dbvar;
            }

            // Prevent leaks.
            $database->free();
            unset($database);
			return $vars;
        }
		return false;
    }
	
	function getEmptyDesign() {
		return array(
				'id' => 0,
				'bgcolor' => '',
				'contentcolor' => '',
				'asidecolor' => '',
				'textcolor' => '',
				'linkcolor' => '',
				'infocolor' => '',
				'bgimage' => '',
				'infoimage' => '',
				'designoptions' => 0,
			);
	}
}