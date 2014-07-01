<?php

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

include_once(INSTALLDIR . '/plugins/Realtime/RealtimePlugin.php');
include_once(INSTALLDIR . '/plugins/Meteor/MeteorPlugin.php');

/* At first I was going to extend MeteorPlugin (wouldn't work because of the complications with Realtime), but then I realized instantiating it and calling its functions would probably work. */
class VideoSyncPlugin extends Plugin
{
    public $webserver     = null;
    public $webport       = null;
    public $controlport   = null;
    public $controlserver = null;
    public $channelbase   = null;
    public $persistent    = true;
    public $tag = 'livestream';
	
	var $tagMatch = false;

    function __construct($webserver=null, $webport=4670, $controlport=4671, $controlserver=null, $channelbase='')
    {  
        global $config;

        $this->webserver     = (empty($webserver)) ? $config['site']['server'] : $webserver;
        $this->webport       = $webport;
        $this->controlport   = $controlport;
        $this->controlserver = (empty($controlserver)) ? $webserver : $controlserver;
        $this->channelbase   = $channelbase;

        parent::__construct();
    }

    function onRouterInitialized($m) {
        $m->connect('main/switchvideo',
            array('action' => 'switchvideo')
        );
		$m->connect('main/updatestream',
			array('action' => 'updatestream')
		);
		$m->connect('main/videosync',
			array('action' => 'managevideosync')
		);
		$m->connect('main/videosync/update',
			array('action' => 'updatevideo')
		);
		$m->connect('main/videosync/add',
			array('action' => 'addvideo')
		);
		$m->connect('main/videosync/delete',
			array('action' => 'removevideo')
		);
		$m->connect('main/videosync/promote',
			array('action' => 'makevideosyncadmin')
		);
		$m->connect('main/videosync/demote',
			array('action' => 'removevideosyncadmin')
		);

        return true;
    }

    function onAutoload($cls) {
        $dir = dirname(__FILE__);

        switch ($cls) {
        case 'SwitchvideoAction':
        case 'AddvideoAction':
        case 'RemovevideoAction':
        case 'UpdatevideoAction':
        case 'ManagevideosyncAction':
        case 'MakevideosyncadminAction':
        case 'RemovevideosyncadminAction':
            require_once $dir . '/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
        case 'Videosync':
        case 'VideosyncAdmin':
            require_once $dir . '/' . $cls . '.php';
            return false;
		case 'UpdatestreamAction':
			
            $m = $this->getMeteor();

            $m->_connect();
			$position = time() - $this->v->started;
			if($position < 0)
				$position = 0;
            $m->_publish($this->channelbase . '-videosync', array('yt_id' => $this->v->yt_id, 'pos' => $position, 'started' => strtotime($this->v->started), 'tag' => $this->getFullTag()));
            $m->_disconnect();
			
			exit(0);
			return false;
			
        case 'SwitchForm':
        case 'VideoUpdateForm':
        case 'VideoSetPlayingForm':
        case 'VideoDeleteForm':
        case 'VideoDeleteConfirmForm':
        case 'VideoAddForm':
        case 'VideosyncPromoteForm':
        case 'VideosyncDemoteForm':
            require_once $dir . '/' . strtolower($cls) . '.php';
			return false;
        default:
            return true;
        }
    }

    function initialize() {
        $this->v = Videosync::getCurrent(true);

    }

    function onCheckSchema() {
        $schema = Schema::get();

        $schema->ensureTable('videosync',
            array(new ColumnDef('id', 'integer', null,
            true, 'PRI', null, null, true),
            new ColumnDef('yt_id', 'varchar', 11, true),
            new ColumnDef('duration', 'integer', 4, true),
            new ColumnDef('tag', 'varchar', 50, true),
            new ColumnDef('yt_name', 'varchar', 255, true),
            new ColumnDef('started', 'int',  null, false),
            new ColumnDef('next', 'int',  null, false),
            new ColumnDef('temporary', 'integer', 1, true, null, false),
        ));
		
		$schema->ensureTable('videosyncadmin',
			array(new ColumnDef('id', 'integer', null, false, 'PRI'))
		);

        return true;
    }

    function getMeteor() {
        return new MeteorPlugin(
            $this->webserver,
            $this->webport,
            $this->controlport,
            $this->controlserver,
            $this->channelbase
        );
    }

    function getFullTag() {
        return $this->tag . ((!empty($this->v->tag)) ? ' #' . $this->v->tag : '');
    }

    function onEndShowScripts($action) {
        if($action instanceof PublicAction
			|| $this->tagMatch) {
            //$action->script($this->path('videosync.min.js'));
			if($action instanceof ManagevideosyncAction)
				$action->script('http://'.$this->webserver.(($this->webport == 80) ? '':':'.$this->webport).'/meteor.js');
            $action->script($this->path('videosync.js'));
            $action->inlineScript('Videosync.init(' . json_encode(array(
                'yt_id' => $this->v->yt_id, 
                'started' => $this->v->started,
                'tag' => $this->getFullTag(),
                'channel' => $this->channelbase . '-videosync',
            )) . ');');
        }

        return true;
    }
	
	function onStartTagShowContent($action) {
		$tag = $action->tag;
		$v = Videosync::staticGet('tag', $tag);
		if($v) {
			$action->elementStart('div', 'videosync_tag_info');
			
			$action->elementStart('a', array('href' => '//youtu.be/' . $v->yt_id, 'rel' => 'external nofollow'));
			$action->element('img', array(
				'src' => '//img.youtube.com/vi/'.$v->yt_id.'/mqdefault.jpg',
				'width' => '160',
				'height' => '90',
				'style' => 'float:left;margin-right: 8px'
			), null);
			$action->elementEnd('a');
			
			$action->elementStart('h2');
			$action->element('a', array('href' => '//youtu.be/' . $v->yt_id, 'rel' => 'external nofollow'), $v->yt_name);
			$action->elementEnd('h2');
			
			$action->elementStart('div');
			
			$length = intval($v->duration/60) . ':' . ($v->duration%60 < 10 ? '0' : '') . ($v->duration%60);
			$action->text($length);
			
			if($v->started > 10) {
				$dateStr = common_date_string(date('d F Y H:i:s', $v->started));
				$action->text(' - ' . sprintf(_('Last played %s'), $dateStr));
			} else {
				$action->text(' - ' . _('Not yet played'));
			}
			if($v->isCurrent())
				$action->raw(' - <b>' . _('Now Playing') . '</b>');
			if($v->temporary)
				$action->raw(' - <i>' . _('Temporary') . '</i>');
			$action->elementEnd('div');
			
			$action->elementEnd('div');
		}
		return true;
	}

    //function onEndShowHeader($action) {
    function onStartShowSiteNotice($action) {
        $user = common_current_user();
		
		$this->tagMatch = false;
		
		if($action instanceof TagAction) {
			$tag = $action->tag;
			$this->tagMatch = $tag == strtolower($this->tag);
		}

        if(($action instanceof PublicAction
			|| $this->tagMatch) && $user) {
            $action->elementStart('div', array('id' => 'videosync'));
            $action->element('input', array(
                'type' => 'button', 
                'id' => 'videosync_btn', 
                'value' => "Watch videos on the #{$this->tag}!")
            );
            if(!empty($user) && VideosyncAdmin::isAdmin($user)) {
                $action->elementStart('div', array('id' => 'videosync_aside'));
                $v = new Videosync();
                $v->find();
                $s = new SwitchForm($action, $v);
                $s->show();
                $action->elementEnd('div');
            }
            $action->element('div', array('id' => 'videosync_box'));
            $action->elementEnd('div');
        }

        return true;
    }
	
	function onEndUserRoleBlock($action) {
		if($action->user->hasRight(Right::CONFIGURESITE))
			return true;
        list($act, $r2args) = $action->returnToArgs();
        $r2args['action'] = $act;

        $action->elementStart('li', "entity_role_stream_manager");
        if (VideosyncAdmin::isAdmin($action->user)) {
            $rf = new VideosyncDemoteForm($action, $action->profile, $r2args);
            $rf->show();
        } else {
            $rf = new VideosyncPromoteForm($action, $action->profile, $r2args);
            $rf->show();
        }
        $action->elementEnd('li');
	}
	
	var $shownMenuOpt = false;
	
	function onEndAdminDropdown($nav) {
		if(common_logged_in() && VideosyncAdmin::isAdmin(common_current_user()))
			$nav->menuItem(common_local_url('managevideosync'),
				// TRANS: Main menu option when logged in and site admin for access to site configuration.
				_m('MENU', 'Videosync'), _('Manage videosync playlist'), false, 'nav_videosync');
		
		$this->shownMenuOpt = true;
		return true;
	}
	
	function onEndPrimaryNav($nav) {
		if($this->shownMenuOpt)
			return true;
		if(common_logged_in() && VideosyncAdmin::isAdmin(common_current_user()))
			$nav->menuItem(common_local_url('managevideosync'),
				// TRANS: Main menu option when logged in and site admin for access to site configuration.
				_m('MENU', 'Videosync'), _('Manage videosync playlist'), false, 'nav_videosync');
		
		return true;
	}
}
