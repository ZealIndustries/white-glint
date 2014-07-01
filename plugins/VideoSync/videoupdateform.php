<?php
if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class VideoUpdateForm extends Form {
	public $v;
	
	public function __construct($out, $v) {
		parent::__construct($out);
		$this->v = $v;
	}
	
	function id() {
		return 'videosync_update-form-' . $this->v->id;
	}
	
	function formClass() {
		return 'videosync_update-form form_settings';
	}
	
    function action() {
        return common_local_url('updatevideo');
    }
	/*
    function formLegend() {
        $this->out->element('legend', null, _('Update video #' . $this->v->id));
    }*/

    function formData() {
        $this->out->hidden('video_id',
                           $this->v->id,
                           'video_id');
						   
		$this->out->elementStart('ul', 'form_data');
		
		$this->out->elementStart('li');
		$this->out->input('video_yt_id', _('Youtube ID'), $this->v->yt_id, _('YouTube video ID of the video.'));
		$this->out->elementEnd('li');
		
		$this->out->elementStart('li');
		$this->out->input('video_yt_name', _('Name'), $this->v->yt_name, _('Video name, used in various places as identification.'));
		$this->out->elementEnd('li');
		
		$this->out->elementStart('li');
		$this->out->input('video_duration', _('Duration'), $this->v->duration, _('Video length, in seconds.'));
		$this->out->elementEnd('li');
		
		$this->out->elementStart('li');
		$this->out->input('video_tag', _('Tag'), $this->v->tag, _('Tag used to connect posts about this video.'));
		$this->out->elementEnd('li');
		
		$this->out->elementEnd('ul');
    }
   
    function formActions() {
		$this->out->element('input',
			array(
				'type' => 'submit',
				'name' => 'videoupdate-submit',
				'class' => 'submit',
				'title' => _('Update'),
				'value' => _('Update'),
			));
    }
}