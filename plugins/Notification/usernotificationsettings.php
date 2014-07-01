<?php

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/settingsaction.php';

class UsernotificationsettingsAction extends SettingsAction
{
    /**
     * Title of the page
     *
     * @return string Title of the page
     */
    function title()
    {
        return _('Notification settings');
    }

    /**
     * Instructions for use
     *
     * @return instructions for use
     */
    function getInstructions()
    {
        return _('Update your notification settings here');
    }
	
    function showScripts()
    {
        parent::showScripts();
		$this->inlineScript(<<<HONK
$('enabled').bind('click', function(e) {
	w.Notification.requestPermission();
})
HONK
		);
    }

    /**
     * Content area of the page
     *
     * Shows a form for uploading an avatar.
     *
     * @return void
     */
    function showContent()
    {
		$user = common_current_user();
        $vars = User_notification_settings::getValues($user->id);

        $this->elementStart('form', array('method' => 'post',
                                          'id' => 'form_settings_notification',
                                          'class' => 'form_settings',
                                          'action' => common_local_url('usernotificationsettings')));
        $this->elementStart('fieldset');
        $this->hidden('token', common_session_token());

        // too much common patterns here... abstractable?
        $this->elementStart('ul', 'form_data');

            $this->elementStart('li');
            $this->checkbox('enabled', _('Enable site notifications'),
                            ($this->arg('enabled')) ?
                            $this->boolean('enabled') : $vars['enabled']);
            $this->elementEnd('li');

            $this->elementStart('li');
            $this->checkbox('newwindow', _('Open notification links in a new window'),
                            ($this->arg('newwindow')) ?
                            $this->boolean('newwindow') : $vars['newwindow']);
            $this->elementEnd('li');

        $this->elementEnd('ul');
		
		$this->elementStart('fieldset');
		$this->element('legend', array(), _('Notifications to receive'));
        $this->elementStart('ul', 'form_data');
		
            $this->elementStart('li');
            $this->checkbox('messages', _('Private messages'),
                            ($this->arg('messages')) ?
                            $this->boolean('messages') : $vars['messages']);
            $this->elementEnd('li');
		
            $this->elementStart('li');
            $this->checkbox('mentions', _('Replies and mentions'),
                            ($this->arg('mentions')) ?
                            $this->boolean('mentions') : $vars['mentions']);
            $this->elementEnd('li');
		
            $this->elementStart('li');
            $this->checkbox('subscribes', _('Subscribers'),
                            ($this->arg('subscribes')) ?
                            $this->boolean('subscribes') : $vars['subscribes']);
            $this->elementEnd('li');
		
            $this->elementStart('li');
            $this->checkbox('groups', _('New group members'),
                            ($this->arg('groups')) ?
                            $this->boolean('groups') : $vars['groups']);
            $this->elementEnd('li');
		
            $this->elementStart('li');
            $this->checkbox('faves', _('Favorited notices'),
                            ($this->arg('faves')) ?
                            $this->boolean('faves') : $vars['faves']);
            $this->elementEnd('li');
		
            $this->elementStart('li');
            $this->checkbox('repeats', _('Repeated notices'),
                            ($this->arg('repeats')) ?
                            $this->boolean('repeats') : $vars['repeats']);
            $this->elementEnd('li');
		
            $this->elementStart('li');
            $this->checkbox('groupposts', _('Posts to groups'),
                            ($this->arg('groupposts')) ?
                            $this->boolean('groupposts') : $vars['groupposts']);
			$this->element('p', 'form_guide', _('Adjust settings for individual groups from each group page.'));
            $this->elementEnd('li');
		
        $this->elementEnd('ul');
		$this->elementEnd('fieldset');
        $this->submit('save', _m('BUTTON','Save'));

        $this->elementEnd('fieldset');
        $this->elementEnd('form');
    }

    /**
     * Handle a post
     *
     * Validate input and save changes. Reload the form with a success
     * or error message.
     *
     * @return void
     */
    function handlePost()
    {
        // CSRF protection
        $token = $this->trimmed('token');
        if (!$token || $token != common_session_token()) {
            $this->showForm(_('There was a problem with your session token. '.
                              'Try again, please.'));
            return;
        }

            $user = common_current_user();

            if(!empty($user)) {

                $vars = @User_notification_settings::staticGet('user_id', $user->id);

                if(empty($vars)) {
                    $vars = @new User_notification_settings();
                    $vars->user_id = $user->id;
                }
                else {
                    $orig = clone($vars);
                }
				
                $vars->enabled = $this->boolean('enabled');
                $vars->newwindow = $this->boolean('newwindow');
                $vars->messages = $this->boolean('messages');
                $vars->mentions = $this->boolean('mentions');
                $vars->subscribes = $this->boolean('subscribes');
                $vars->groups = $this->boolean('groups');
                $vars->faves = $this->boolean('faves');
                $vars->repeats = $this->boolean('repeats');
                $vars->groupposts = $this->boolean('groupposts');

                if(isset($orig))
                    @$vars->update($orig);
                else
                    @$vars->insert();

                $this->showForm(_('Settings saved.'), true);
            }
    }
}
