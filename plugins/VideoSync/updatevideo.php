<?php

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class UpdatevideoAction extends Action
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
		$v = Videosync::staticGet('id', $this->trimmed('video_id'));
		if($v) {
			$o = clone($v);
		} else {
            $this->clientError(_('Video does not exist.'));
            return;
		}
		$v->yt_id = Videosync::idFromUrl($this->trimmed('video_yt_id'));
		$v->yt_name = $this->trimmed('video_yt_name');
		$v->duration = $this->trimmed('video_duration');
		$v->tag = $this->trimmed('video_tag');
		$v->update($o);
        if ($this->boolean('ajax')) {
            $this->startHTML('text/xml;charset=utf-8');
            $this->elementStart('head');
            // TRANS: Page title for page on which favorite notices can be unfavourited.
            $this->element('title', null, _('Update video.'));
            $this->elementEnd('head');
            $this->elementStart('body');
            $videoswitch = new VideoUpdateForm($this, $v);
            $videoswitch->show();
            $this->elementEnd('body');
            $this->elementEnd('html');
        } else {
            //echo 'borp';
            common_redirect(common_local_url('managevideosync'), 303);
        }
    }
}
