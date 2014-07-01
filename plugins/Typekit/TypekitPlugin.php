<?php
if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class TypekitPlugin extends Plugin {
	public $key;
	
	function onEndShowStyles($action) {
		$action->raw('<script type="text/javascript" src="//use.typekit.net/' . $this->key . '.js"></script>');
		$action->inlineScript('try{Typekit.load();' . ((file_exists(Theme::file('css/typekit.css')))
			? 'document.write(\'<link rel="stylesheet" type="text/css" href="'
			. Theme::path('css/typekit.css', null, 'handheld') . '"/>\');' : '') . '}catch(e){}');
		return true;
	}

    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'Typekit',
                            'version' => '0.0.01',
                            'author' => 'Cerulean Spark & Adobe',
                            'homepage' => '',
                            'rawdescription' =>
                            // TRANS: Plugin description.
                            _m('We\'re up all night to get pretty.'));
        return true;
    }
}