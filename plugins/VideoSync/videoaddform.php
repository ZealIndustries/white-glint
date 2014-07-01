<?php
if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class VideoAddForm extends Form {
	
	function id() {
		return 'videosync_add-form';
	}
	
	function formClass() {
		return 'videosync_add-form form_settings';
	}
	
    function action() {
        return common_local_url('addvideo');
    }
	
    function formLegend() {
        $this->out->element('legend', null, _('Add new video'));
    }

    function formData() {						   
		$this->out->elementStart('ul', 'form_data');
		
		$this->out->elementStart('li');
		$this->out->input('video_yt_id', _('Youtube ID'), 'XXXXXXXXXXX', _('YouTube video ID of the video.'));
		$this->out->elementEnd('li');
		
		$this->out->elementStart('li');
		$this->out->input('video_yt_name', _('Name'), null, _('Video name, used in various places as identification. (optional)'));
		$this->out->elementEnd('li');
	/*	
		$this->out->elementStart('li');
		$this->out->input('video_duration', _('Duration'), $this->v->duration, _('Video length, in seconds.'));
		$this->out->elementEnd('li');
		*/
		$this->out->elementStart('li');
		$this->out->input('video_tag', _('Tag'), null, _('Tag used to connect posts about this video.'));
		$this->out->elementEnd('li');
		
		$this->out->elementStart('li');
		$this->out->checkbox('video_temporary', _('Remove video from listing after playback'), false);
		$this->out->elementEnd('li');
		
		$this->out->elementEnd('ul');
    }
   
    function formActions() {
		$this->out->element('input',
			array(
				'type' => 'submit',
				'name' => 'videoadd-submit',
				'class' => 'submit',
				'title' => _('Add'),
				'value' => _('Add'),
			));
    }
}