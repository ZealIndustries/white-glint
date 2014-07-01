<?php

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

class NotificationPlugin extends Plugin {
	public $mobileCheckFrequency = 600000; // every 10 minutes
	public $desktopCheckFrequency = 15000; // every 15 seconds
	
	function onEndShowHeader($action) {
		$this->mobileCheckFrequency = $this->desktopCheckFrequency;
		return true; // hack to have separate timeout on mobile
	}

    function onAutoload($cls)
    {
        $dir = dirname(__FILE__);

        switch ($cls)
        {
        case 'GetnotificationjsonAction':
        case 'RemovenotificationsAction':
        case 'UsernotificationsettingsAction':
        case 'YesnotificationsAction':
        case 'NonotificationsAction':
            include_once $dir . '/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
        case 'User_notification':
        case 'User_notification_settings':
        case 'User_notification_optout':
            include_once $dir . '/'.$cls.'.php';
            return false;
        case 'YesNotificationsForm':
        case 'NoNotificationsForm':
            include_once $dir . '/' . strtolower($cls) . '.php';
            return false;
        default:
            return true;
        }
    }    
	
    function onCheckSchema() {
        $schema = Schema::get();

        $schema->ensureTable('user_notification',
            array(new ColumnDef('id', 'integer', null,
            true, 'PRI'),
            new ColumnDef('user_id', 'integer', null, true),
            new ColumnDef('from_user_id', 'integer', null, true),
            new ColumnDef('type', 'varchar', 16, true),
            new ColumnDef('arg', 'integer', null, true),
            new ColumnDef('arg2', 'integer', null, true),
            new ColumnDef('created', 'integer', null, true),
        ));

        $schema->ensureTable('user_notification_settings',
            array(new ColumnDef('user_id', 'integer', null,
            true, 'PRI'),
            new ColumnDef('enabled', 'integer', 1, true),
            new ColumnDef('newwindow', 'integer', 1, true),
            new ColumnDef('messages', 'integer', 1, true),
            new ColumnDef('mentions', 'integer', 1, true),
            new ColumnDef('subscribes', 'integer', 1, true),
            new ColumnDef('groups', 'integer', 1, true),
            new ColumnDef('faves', 'integer', 1, true),
            new ColumnDef('repeats', 'integer', 1, true),
            new ColumnDef('groupposts', 'integer', 1, true),
        ));

        $schema->ensureTable('user_notification_optout',
            array(new ColumnDef('user_id', 'integer', null,
				true, 'PRI'),
			new ColumnDef('group_id', 'integer', null,
				true, 'PRI'),
        ));

        return true;
    }

    function onRouterInitialized($m)
    {
        $m->connect('main/notifications/json',
            array('action' => 'getnotificationjson'));
        $m->connect('main/notifications/remove',
            array('action' => 'removenotifications'));
        $m->connect('settings/notifications',
            array('action' => 'usernotificationsettings'));
        $m->connect('group/:id/yesnotifications',
            array('action' => 'yesnotifications', 'id' => '[0-9]+'));
        $m->connect('group/:id/nonotifications',
            array('action' => 'nonotifications', 'id' => '[0-9]+'));
        return true;
    }

    function onEndAccountSettingsNav($action) {
        $action->menuItem(common_local_url('usernotificationsettings'),
            // TRANS: Menu item in settings navigation panel.
            _m('MENU','Notifications'),
            // TRANS: Menu item title in settings navigation panel.
            _('Adjust your notification settings'),
            $action instanceof UsernotificationsettingsAction);
    }
	
	function onEndGroupActionsList($form, $group) {
		if(!common_logged_in())
			return true;
			
		$user = common_current_user();
		$user = $user->getProfile();
		if(!$user->isMember($group))
			return true;
		
		$form->out->elementStart('li');
		
		$opt = new User_notification_optout();
		$opt->user_id = $user->id;
		$opt->group_id = $group->id;
		if($opt->find(true)) {
			// Show button to enable group notifications
			$lf = new YesNotificationsForm($form->out, $group);
			$lf->show();
		} else {
			// Show button to disable group notifications
			$lf = new NoNotificationsForm($form->out, $group);
			$lf->show();
		}
		$form->out->elementEnd('li');
		return true;
	}
	
	function onEndNoticeSave($notice) {
        if (empty($notice->repeat_of)) {
			$this->_sendReplyNotifications($notice);
			$this->_sendGroupPostNotifications($notice);
        } else {
			$original = Notice::staticGet('id', $notice->repeat_of);
			$profile = Profile::staticGet('id', $original->profile_id);
			if($notice->getProfile()->id == $original->profile_id)
				return true;
			if(User_notification_settings::getsRepeats($profile->id)) {
				User_notification::saveNew($notice->getProfile(), $profile,
					'repeat', $original->id);
			}
		}
		
		return true;
	}
	
	private function _sendReplyNotifications($notice) {
        $recipientIds = $notice->getReplies();

        foreach ($recipientIds as $recipientId) {
			if($notice->profile_id == $recipientId)
				continue;
			if(User_notification_settings::getsReplies($recipientId)) {
				User_notification::saveNew(Profile::staticGet('id', $notice->profile_id), Profile::staticGet('id', $recipientId),
					'mention', $notice->id);
			}
        }
	}
	
	private function _sendGroupPostNotifications($notice) {
        $groups = $notice->getGroups();
		
		foreach($groups as $group) {
			$members = $group->getUserMembers();
			foreach($members as $member) {		
				if($notice->profile_id == $member)
					continue;
				if(User_notification_settings::getsGroupPosts($member, $group->id)) {
					User_notification::saveNew(Profile::staticGet('id', $notice->profile_id), Profile::staticGet('id', $member),
						'grouppost', $group->id, $notice->id);
				}
			}
		}
	}
	
	function onEndRequestJoinGroup($user, $group) {
		$groupId = $group->id;
		$admins = $group->getAdmins();
		while($admins->fetch()) {
			$adminId = $admins->id;
			if(User_notification_settings::getsGroupJoins($adminId)) {
				User_notification::saveNew($user, $admins,
					'grouprequest', $group->id);
			}
		}
		
		return true;
	}
	
	function onEndJoinGroup($group, $profile) {
		$groupId = $group->id;
		$admins = @$group->getAdmins();
		while(@$admins->fetch()) {
			$adminId = $admins->id;
			if(User_notification_settings::getsGroupJoins($adminId)) {
				@User_notification::saveNew($profile, $admins,
					'groupjoin', $group->id);
			}
		}
		
		// Delete notifications for join requests, in case they exist
		$notify = new User_notification();
		$notify->from_user_id = $profile->id;
		$notify->arg = $groupId;
		$notify->type = 'grouprequest';
		$notify->delete();
		
		return true;
	}
	
    function onEndFavorNotice($profile, $notice) {
		if($profile->id == $notice->profile_id)
			return true;
		$user = @Profile::staticGet('id', $notice->profile_id);
		
		if(User_notification_settings::getsFavorites($user->id)) {
			@User_notification::saveNew($profile, $user,
				'favorite', $notice->id);
		}
		
		return true;
	}
	
    function onEndSubscribe($subscriber, $other) {
		if(User_notification_settings::getsSubscribes($other->id)) {
			User_notification::saveNew($subscriber, $other,
				'subscribe');
		}
		
		return true;
	}
	
	function onEndSendPrivateMessage($message) {
		$from = Profile::staticGet('id', $message->from_profile);
		$to = Profile::staticGet('id', $message->to_profile);
		
		if(User_notification_settings::getsPrivateMessages($to->id)) {
			User_notification::saveNew($from, $to,
				'message');
		}
		
		return true;
	}
	
	function onEndShowFooter($action) {
		if(!common_logged_in())
			return true;
		$user = common_current_user();
		if(!User_notification_settings::isEnabled($user->id))
			return true;
		
		if($action instanceof ShowgroupAction) {
			$note = new User_notification();
			$note->user_id = $user->id;
			$note->type = 'grouppost';
			$note->arg = $action->group->id;
			$note->delete();
			
			return true;
		}
		
		if($action instanceof GroupmembersAction) {
			$note = new User_notification();
			$note->user_id = $user->id;
			$note->type = 'groupjoin';
			$note->arg = $action->group->id;
			$note->delete();
			
			return true;
		}
		
		if($action instanceof GroupqueueAction) {
			$note = new User_notification();
			$note->user_id = $user->id;
			$note->type = 'grouprequest';
			$note->arg = $action->group->id;
			$note->delete();
			
			return true;
		}
		
		if($action instanceof SubscribersAction && $action->profile->id == $user->id) {
			$note = new User_notification();
			$note->user_id = $user->id;
			$note->type = 'subscribe';
			$note->delete();
			
			return true;
		}
		
		if($action instanceof InboxAction) {
			$note = new User_notification();
			$note->user_id = $user->id;
			$note->type = 'message';
			$note->delete();
			
			return true;
		}
		
		if($action instanceof RepliesAction && $action->user->id == $user->id) {
			$note = new User_notification();
			$note->user_id = $user->id;
			$note->type = 'mention';
			$note->delete();
			
			return true;
		}
		
		if($action instanceof ShownoticeAction) {
			$note = new User_notification();
			$note->user_id = $user->id;
			$note->type = 'mention';
			$note->arg = $action->notice->id;
			$note->delete();
			$note->type = 'favorite';
			$note->delete();
			$note->type = 'repeat';
			$note->delete();

			$note = new User_notification();
			$note->user_id = $user->id;
			$note->type = 'grouppost';
			$note->arg2 = $action->notice->id;
			$note->delete();
			
			return true;
		}
		
		return true;
	}

    function onEndShowStatusNetStyles($action)
    {
        $action->cssLink(Plugin::staticPath('Notification', 'notify.css'),
                         null,
                         'screen, projection, tv');
        return true;
    }
	
    function onEndScriptMessages($action, &$messages){
		if(!common_logged_in())
			return true;
		$user = common_current_user();
		if(!User_notification_settings::isEnabled($user->id))
			return true;
        // TRANS: Text label for realtime view "play" button, usually replaced by an icon.
        $messages['notification_mention'] = _m('%1$s mentioned you in a notice');
        $messages['notification_mention_multiple'] = _m('%1$s mentioned you in %2$s notices');
        $messages['notification_favorite'] = _m('%1$s added your notice as a favorite');
        $messages['notification_favorite_multiple'] = _m('%1$s added %2$s of your notices as favorites');
        $messages['notification_repeat'] = _m('%1$s repeated your notice');
        $messages['notification_repeat_multiple'] = _m('%1$s repeated %2$s of your notices');
        $messages['notification_message'] = _m('%1$s sent you a direct message');
        $messages['notification_message_multiple'] = _m('%1$s sent you %2$s direct messages');
        $messages['notification_subscribe'] = _m('%1$s started following you');
        $messages['notification_grouppost'] = _m('%1$s posted a notice to %2$s');
        $messages['notification_grouppost_multiple'] = _m('%1$s posted %2$s notices to %3$s');
        $messages['notification_grouppost_multiple_condensed'] = _m('%1$s posts to %2$s groups');
        $messages['notification_groupjoin'] = _m('%1$s joined the group %2$s');
        $messages['notification_groupjoin_multiple'] = _m('%1$s joined %2$s of your groups');
        $messages['notification_grouprequest'] = _m('%1$s requested to join the group %2$s');
        $messages['notification_grouprequest_multiple'] = _m('%1$s requested to join %2$s of your groups');
        $messages['notification_andx'] = _m('and %1$s');
        $messages['notification_andothers'] = _m('and %1$s others');
        $messages['notification_chrome'] = _m('Enable desktop notifications');
        $messages['notification_multiple'] = _m('%1$s new notifications');
        $messages['notification_title'] = common_config('site', 'name'); // Maybe not the *proper* place to put it, but eh

        return true;
    }
	
	function onEndShowScripts($action) {
		if(!common_logged_in())
			return true;
		$user = common_current_user();
		if(!User_notification_settings::isEnabled($user->id))
			return true;
        $action->script($this->path('notify.js'));
        $action->inlineScript('SNNote.init('.json_encode(User_notification::getAllForUser($user))
			.', '.json_encode(array('updateUrl' => common_local_url('getnotificationjson'),
			'removeUrl' => common_local_url('removenotifications'),
			'openInNewWindow' => (User_notification_settings::openInNewWindow($user->id) ? true : false),
			'icon' => $this->path('icon.png'), 'update' => $this->mobileCheckFrequency
			))
			.');');
	}
	
    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'Notifications',
                            'version' => STATUSNET_VERSION,
                            'author' => 'RedEnchilada',
                            'homepage' => 'http://status.net/wiki/Plugin:Sample',
                            'rawdescription' =>
                          // TRANS: Plugin description.
                            _m('Site notifications for some interactions, using browser desktop notifications if available.'));
        return true;
    }
}
?>
