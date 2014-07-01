<?php
if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class VideoDeleteForm extends Form {
	public $v;
	
	public function __construct($out, $v) {
		parent::__construct($out);
		$this->v = $v;
	}
	
	function id() {
		return 'videosync_delete-form-' . $this->v->id;
	}
	
	function formClass() {
		return 'videosync_delete-form';
	}
	
    function action() {
        return common_local_url('removevideo');
    }
	
    function formData() {
        $this->out->hidden('video-id',
                           $this->v->id,
                           'video-id');
    }
   
    function formActions() {
		$this->out->element('input',
			array(
				'type' => 'submit',
				'name' => 'submit',
				'class' => 'submit',
				'title' => _('Remove this video'),
				'value' => _('Remove'),
			));
    }
}