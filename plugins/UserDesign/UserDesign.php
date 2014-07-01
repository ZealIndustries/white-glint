<?php

if (!defined('STATUSNET')) {
    exit(1);
}

class UserDesign {
	// A quick-n-dirty class to grab background image locations
	static function url($filename) {
		$path = common_config('background', 'path');

        if ($path[strlen($path)-1] != '/') {
            $path .= '/';
        }

        if ($path[0] != '/') {
            $path = '/'.$path;
        }

        $server = common_config('background', 'server');

        if (empty($server)) {
            $server = common_config('site', 'server');
        }

        $ssl = common_config('background', 'ssl');

        if (is_null($ssl)) { // null -> guess
            if (common_config('site', 'ssl') == 'always' &&
                !common_config('background', 'server')) {
                $ssl = true;
            } else {
                $ssl = false;
            }
        }

        $protocol = ($ssl) ? 'https' : 'http';

        return $protocol.'://'.$server.$path.$filename;

	}
	
	static function path($imageLink) {
		$dir = common_config('background', 'dir');

        if ($dir[strlen($dir)-1] != '/') {
            $dir .= '/';
        }
		
		return $dir.$imageLink;
	}
	
    static function filename($id, $extension, $type, $extra=null)
    {
		return $id . '-' . $type . (($extra) ? ('-' . $extra) : '') . $extension;
    }
	
	static function filenameGroup($id, $extension, $type, $extra=null) {
		return 'group' . UserDesign::filename($id, $extension, $type, $extra);
	}
}