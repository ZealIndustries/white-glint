<?php

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once(INSTALLDIR . '/extlib/HTTP/Request2.php');

class RemovevideosyncadminAction extends Action
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
        if(!$user->hasRight(Right::CONFIGURESITE)) {
            $this->clientError(_('Not authorized'));
            return;
        }
        $token  = $this->trimmed('token');
        if (!$token || $token != common_session_token()) {
            $this->clientError(_('There was a problem with your session token. Try again, please.'));
            return;
        }
		$usr = User::staticGet('id', $this->trimmed('profileid'));
		VideosyncAdmin::demoteUser($usr);
		common_redirect(common_local_url($this->trimmed('returnto-action'), array('nickname' => $this->trimmed('returnto-nickname'))), 303);
    }
}
