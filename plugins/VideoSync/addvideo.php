<?php

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once(INSTALLDIR . '/extlib/HTTP/Request2.php');

class AddvideoAction extends Action
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
		$v = new Videosync();
		$v->yt_id = Videosync::idFromUrl($this->trimmed('video_yt_id'));
		
		if(!$v->yt_id) {
            $this->clientError(_('Invalid video ID.'));
            return;
		}
		
		// Fetch info from Youtube API
        /*$request = HTTPClient::start();
        $response = $request->get('http://gdata.youtube.com/feeds/api/videos/' . $v->yt_id);
		
        $responseBody = $response->getBody();*/
		$request = new HTTP_Request2('http://gdata.youtube.com/feeds/api/videos/' . $v->yt_id);
		$response = $request->send();
        $responseBody = $response->getBody();
		
        if ($responseBody) {
			$y = @simplexml_load_string($responseBody);
			if ($y) {
				//ob_start(); // @fixme HACK HACK HACK
				$x = $y->children('http://search.yahoo.com/mrss/')->group;
				$n = $x->content[0]->attributes();
				if(isset($n['duration']))
					$v->duration = intval($n['duration']);
				$v->yt_name = $x->title . '';
				//ob_end_clean(); // @fixme I'm real sick of those warnings tho
			} else {
				common_redirect(common_local_url('managevideosync'), 303);
			};
		}
		
		if($this->trimmed('video_yt_name'))
			$v->yt_name = $this->trimmed('video_yt_name');
		//$v->duration = $this->int('video_duration');
		$v->tag = $this->trimmed('video_tag');
		$v->temporary = $this->boolean('video_temporary');
		$v->insert();
        if ($this->boolean('ajax')) {
            $this->startHTML('text/xml;charset=utf-8');
            $this->elementStart('head');
            // TRANS: Page title for page on which favorite notices can be unfavourited.
            $this->element('title', null, _('Update video.'));
            $this->elementEnd('head');
            $this->elementStart('body');
            $videoswitch = new VideoAddForm($this);
            $videoswitch->show();
            $this->elementEnd('body');
            $this->elementEnd('html');
        } else {
            //echo 'borp';
            common_redirect(common_local_url('managevideosync'), 303);
        }
    }
}
