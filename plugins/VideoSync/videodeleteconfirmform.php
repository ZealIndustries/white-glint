<?php

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/form.php';

class VideoDeleteConfirmForm extends Form
{
    /**
     * Notice to favor
     */
    var $id = null;

    /**
     * Constructor
     *
     * @param HTMLOutputter $out    output channel
     * @param Notice        $notice notice to favor
     */
    function __construct($out=null, $id=null)
    {
        parent::__construct($out);

        $this->id = $id;
    }

    /**
     * ID of the form
     *
     * @return int ID of the form
     */
    function id()
    {
        return 'videosync_deleteconfirm';
    }

    /**
     * Action of the form
     *
     * @return string URL of the action
     */
    function action()
    {
        return common_local_url('removevideo');
    }

    /**
     * Include a session token for CSRF protection
     *
     * @return void
     *//*
    function sessionToken()
    {
        $this->out->hidden('token-videoswitch',
                           common_session_token());
    }*/

    /**
     * Legend of the Form
     *
     * @return void
     */
    function formLegend()
    {
        $this->out->element('legend', null, _('Switch video'));
    }
	
	function formData() {
		$this->out->hidden('video-id', $this->id);
        $this->element('p', null, _('Are you sure you want to delete this video?'));
	}

    /**
     * Action elements
     *
     * @return void
     */
    function formActions()
    {
        // TRANS: Message for the delete notice form.
        $this->submit('form_action-no',
                      // TRANS: Button label on the delete notice form.
                      _m('BUTTON','No'),
                      'submit',
                      'no',
                      // TRANS: Submit button title for 'No' when deleting a notice.
                      _('Do not delete this video.'));
        $this->submit('form_action-yes',
                      // TRANS: Button label on the delete notice form.
                      _m('BUTTON','Yes'),
                      'submit',
                      'yes',
                      // TRANS: Submit button title for 'Yes' when deleting a notice.
                      _('Delete this video.'));

    }

    /**
     * Class of the form.
     *
     * @return string the form's class
     */
    function formClass()
    {
        return 'form_settings';
    }
}
