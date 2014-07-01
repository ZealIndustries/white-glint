<?php

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

class UserDesignPlugin extends Plugin
{

    function onAutoload($cls)
    {
        $dir = dirname(__FILE__);

        switch ($cls)
        {
        case 'ProfiledesignsettingsAction':
        case 'GroupdesignsettingsAction':
        case 'AdmindesignsettingsAction':
            include_once $dir . '/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
        case 'UserDesign':
        case 'ProfileDesign':
            include_once $dir . '/'.$cls.'.php';
            return false;
        case 'DesignSettingsForm':
            include_once $dir . '/' . strtolower($cls) . '.php';
            return false;
        default:
            return true;
        }

    }    

    function onRouterInitialized($m)
    {
        $m->connect('settings/userdesign',
            array('action' => 'profiledesignsettings'));
        $m->connect('group/:nickname/design',
                    array('action' => 'groupdesignsettings'),
                    array('nickname' => '[0-9a-z]+'));
        $m->connect('panel/design',
            array('action' => 'admindesignsettings'));
        return true;
    }

    function onEndAccountSettingsNav($action) {
        $action->menuItem(common_local_url('profiledesignsettings'),
            // TRANS: Menu item in settings navigation panel.
            _m('MENU','Design'),
            // TRANS: Menu item title in settings navigation panel.
            _('Design your profile'),
            $action instanceof ProfiledesignsettingsAction);
    }

    function onEndAdminPanelNav($action) {
        $action->menuItem(common_local_url('admindesignsettings'),
            // TRANS: Menu item in settings navigation panel.
            _m('MENU','Design'),
            // TRANS: Menu item title in settings navigation panel.
            _('Design the site'),
            $action instanceof AdmindesignsettingsAction);
    }

    function onCheckSchema() {
        $schema = Schema::get();

        $schema->ensureTable('profiledesign',
            array(new ColumnDef('id', 'integer', null,
            true, 'PRI'),
            new ColumnDef('bgcolor', 'char', 7, true),
            new ColumnDef('contentcolor', 'char', 7, true),
            new ColumnDef('asidecolor', 'char', 7, true),
            new ColumnDef('textcolor', 'char', 7, true),
            new ColumnDef('linkcolor', 'char', 7, true),
            new ColumnDef('infocolor', 'char', 7, true),
            new ColumnDef('bgimage', 'varchar', 255, true),
            new ColumnDef('infoimage', 'varchar', 255, true),
            new ColumnDef('designoptions', 'integer', null, true),
        ));

        return true;
    }

    function onEndShowStyles($action)
    {
		if(common_config('site', 'custom-css')) {
			$action->style(common_config('site', 'custom-css'));
		}
		
		$design = false;
		if($action instanceof SettingsAction || in_array(strtolower($action->trimmed('action')), array(
			'openidsettings', 'oauthconnectionssettings', 'editpeopletag'))) {
			$user = common_current_user();
			if($user)
				$design = ProfileDesign::getDesign($user->id);
		}
		
		if($action instanceof ProfileAction
			|| $action instanceof MailboxAction
			|| $action instanceof RepliesAction
			|| $action instanceof ShowfavoritesAction
			|| $action instanceof ShownoticeAction
			|| $action instanceof PeopletagsbyuserAction
			|| $action instanceof PeopletagsforuserAction) {
			$user = $action->user;
			$design = ProfileDesign::getDesign($user->id);
		}
		
		if($action instanceof PeopletagsubscriptionsAction) {
			$prof = $action->profile;
			$design = ProfileDesign::getDesign($prof->id);
		}
		
		if($action instanceof ShowprofiletagAction
			|| $action instanceof PeopletaggedAction
			|| $action instanceof PeopletagsubscribersAction) {
			$prof = $action->tagger;
			$design = ProfileDesign::getDesign($prof->id);
		}
		
		if($action instanceof GroupAction) {
			$design = ProfileDesign::getDesign(-($action->group->id));
		}
		
		if($design === false) {
			$user = common_current_user();
			if($user)
				$design = ProfileDesign::getDesign($user->id);
			if(!($design['designoptions'] & 512))
				$design = false;
			else
				$design['designoptions'] -= $design['designoptions'] & (64+1024);
		}
		
		if($design === false) {
			$design = ProfileDesign::getDesign(0);
		}
		
		if($design !== false)
			$action->style($this->renderDesign($design));
		
		return true;
    }
	
	function renderDesign($design) {
		$css = 
			'body{background-color:#' . $design['bgcolor'] . '}'
			. '#content,#core:before,#site_nav_local_views li.current a,#site_nav_local_views a:hover{background-color:#' . $design['contentcolor'] . '}'
			. '#core{background-color:#' . $design['asidecolor'] . '}'
			. '#core,#core #site_nav_local_views a,#core .input_form_nav_tab a{color:#' . $design['textcolor'] . '}'
			. '.input_form_nav_tab.current,.input_form_nav_tab:hover{border-bottom-color:#' . $design['textcolor'] . '}'
			. $this->_getIconsCss()
			. '#core a{color:#' . $design['linkcolor'] . '}'
			. '.profile_block{background-color:#' . $design['infocolor'] . '}'
		;
		
		if($design['designoptions'] & 1 && $design['bgimage'] != null) {
			$css .= 'body{background-image:url(' . UserDesign::url($design['bgimage']) . ');background-repeat:'
				. ($design['designoptions'] & 2 ? ($design['designoptions'] & 4 ? 'repeat' : 'repeat-x') : ($design['designoptions'] & 4 ? 'repeat-y' : 'no-repeat'))
				. ';background-position:';
			switch($design['designoptions'] & 24) {
			case 8:
				$css .= 'top';
				break;
				
			case 16:
				$css .= 'center';
				break;
				
			default:
				$css .= 'bottom';
			}
			$css .= ' center;background-attachment:' . ($design['designoptions'] & 32 ? 'scroll' : 'fixed') . '}';
		}
		
		if($design['designoptions'] & 64 && $design['infoimage'] != null) {
			$css .= '.profile_block{background-image:url(' . UserDesign::url($design['infoimage']) . ')'
				. ($design['designoptions'] & 128 ? ';background-position:top left' : '')
				. ($design['designoptions'] & 1024 ? ';background-size: cover' : '') . '}';
		}
		
		if($design['designoptions'] & 256)
			$css .= '#core .profile_block .profile_block_name a,#core .profile_block_nickname,#core .profile_block_location,#core .profile_block_homepage{color:#FFF;text-shadow:1px 1px 3px black}';
		
		return $css;
	}
	
	private function _getIconsCss() {
		return <<<PORK
#new_group a:before,
#pagination .nav_prev a:before,
#pagination .nav_next a:after,
button.close:before,
button.minimize:before,
.form_reset_key .submit:before,
.entity_clear .submit:before,
#realtime_play:before,
#realtime_pause:before,
#realtime_popup:before,.notice-options form:before, .notice-options a:before, .notice-options .repeated:before,
.user_profile_tags button,
.dm_sent_to_multiple:before,
PORK;
	}

    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'User Designs',
                            'version' => STATUSNET_VERSION,
                            'author' => 'RedEnchilada',
                            'homepage' => 'http://rainbowdash.net/user/798',
                            'rawdescription' =>
                          // TRANS: Plugin description.
                            _m('Design customizability, taken from 0.9.x and expanded.'));
        return true;
    }

}
?>
