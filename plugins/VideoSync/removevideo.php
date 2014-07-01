<?php

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once(INSTALLDIR . '/extlib/HTTP/Request2.php');

class RemovevideoAction extends Action
{
    /**
     * Class handler.
     *
     * @param array $args query arguments
     *
     * @return void
     */
    function handle($args)
    {
        parent::handle($args);
        if (!common_logged_in()) {
            $this->clientError(_('Not logged in.'));
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            //echo 'berp';
            common_redirect(common_local_url('managevideosync'));
            return;
        }
        $user = common_current_user();
        if(!VideosyncAdmin::isAdmin($user)) {
            $this->clientError(_('Not authorized'));
            return;
        }
        $token  = $this->trimmed('token');
        if (!$token || $token != common_session_token()) {
            $this->clientError(_('There was a problem with your session token. Try again, please.'));
            return;
        }
        if ($this->boolean('yes')) {
			$v = Videosync::staticGet('id', $this->trimmed('video-id'));
			if($v->isCurrent())
				Videosync::setCurrent($v->next);
			$v->delete();
            common_redirect(common_local_url('managevideosync'), 303);
		}
		if($this->boolean('no'))
            common_redirect(common_local_url('managevideosync'), 303);
		$this->showPage();
    }
	
	
    function title()
    {
        return _('Remove video');
    }
	
	function showContent() {
		$f = new VideoDeleteConfirmForm($this, $this->trimmed('video-id'));
		$f->show();
	}
}
