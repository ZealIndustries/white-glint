<?php

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class DesignSettingsForm extends Form
{
	var $enctype = 'multipart/form-data';
    var $settings;
    var $isUserForm;

    function __construct($out, $settings, $isUserForm)
    {
        parent::__construct($out);
        $this->settings = $settings;
		if(!$this->settings)
			$this->settings = ProfileDesign::getEmptyDesign();
		$this->isUserForm = $isUserForm;
    }

    /**
     * Visible or invisible data elements
     *
     * Display the form fields that make up the data of the form.
     * Sub-classes should overload this to show their data.
     *
     * @return void
     */

    function formData()
    {
		$this->out->elementStart('fieldset', array('id' =>
            'settings_design_background-image'));
        // TRANS: Fieldset legend on profile design page.
        $this->out->element('legend', null, _('Change background image'));
        $this->out->elementStart('ul', 'form_data');
        $this->out->elementStart('li');
        $this->out->element('input', array('name' => 'MAX_FILE_SIZE',
                                      'type' => 'hidden',
                                      'id' => 'MAX_FILE_SIZE',
                                      'value' => ImageFile::maxFileSizeInt()));
		//$this->element('div', 'profile_block');
        
		if (!empty($this->settings['bgimage']))
            $this->out->element('img', array('src' =>
                UserDesign::url($this->settings['bgimage'])));
		
		$this->out->element('label', array('for' => 'design_background-image_file'),
                                // TRANS: Label in form on profile design page.
                                // TRANS: Field contains file name on user's computer that could be that user's custom profile background image.
                                _('Upload file'));
        $this->out->element('input', array('name' => 'design_background-image_file',
                                      'type' => 'file',
                                      'id' => 'design_background-image_file'));
        $this->out->elementEnd('li');

        
            $this->out->elementStart('li', array('id' =>
                'design_background-image_onoff'));
		
            $this->out->checkbox('design_background-image_enable',
                            // TRANS: Checkbox label on profile design page that will cause the profile image to be tiled.
                            _('Enable background image'),
                            ($this->settings['designoptions'] & 1) ? true : false);
            $this->out->elementEnd('li');

			$this->out->elementStart('li');
            $this->out->checkbox('design_background-image_scroll',
                            // TRANS: Checkbox label on profile design page that will cause the profile image to be tiled.
                            _('Scroll background image with page'),
                            ($this->settings['designoptions'] & 32) ? true : false);
            $this->out->elementEnd('li');

			$this->out->element('p', null, _('Tile background image'));
            $this->out->elementStart('li');
            $this->out->checkbox('design_background-image_repeat-horizontal',
                            // TRANS: Checkbox label on profile design page that will cause the profile image to be tiled.
                            _('Horizontally'),
                            ($this->settings['designoptions'] & 2) ? true : false);
            $this->out->elementEnd('li');
            $this->out->elementStart('li');
            $this->out->checkbox('design_background-image_repeat-vertical',
                            // TRANS: Checkbox label on profile design page that will cause the profile image to be tiled.
                            _('Vertically'),
                            ($this->settings['designoptions'] & 4) ? true : false);
            $this->out->elementEnd('li');
			
			$this->out->elementStart('li');
            $this->out->dropdown('design_background-image_anchor',
                            // TRANS: Dropdown field label on profile settings, for what policies to apply when someone else tries to subscribe to your updates.
                            _('Position'),
                            // TRANS: Dropdown field option for following policy.
                            array(
								'top' => _('Top'),
								'center' => _('Center'),
								'bottom' => _('Bottom'),
							),
								  
                            _('The screen position the background will anchor itself to.'),
                            false,
                            (($this->settings['designoptions'] & 8) ? 'top' : (($this->settings['designoptions'] & 16) ? 'center' : 'bottom')));
			$this->out->elementEnd('li');

        $this->out->elementEnd('ul');
        $this->out->elementEnd('fieldset');

		$this->out->elementStart('fieldset', array('id' =>
            'settings_design_banner-image'));
        // TRANS: Fieldset legend on profile design page.
        $this->out->element('legend', null, _('Change banner image'));
        $this->out->elementStart('ul', 'form_data');
        $this->out->elementStart('li');
        //$this->out->element('input', array('name' => 'MAX_FILE_SIZE',
          //                            'type' => 'hidden',
            //                          'id' => 'MAX_FILE_SIZE',
              //                        'value' => ImageFile::maxFileSizeInt()));
		if($this->isUserForm)
		$this->element('div', 'profile_block');
        
		//if (!empty($this->settings['bgimage']))
          //  $this->out->element('img', array('src' =>
            //    UserDesign::url($this->settings['bgimage'])));
		if($this->isUserForm !== 2) {
		$this->out->element('label', array('for' => 'design_banner-image_file'),
                                // TRANS: Label in form on profile design page.
                                // TRANS: Field contains file name on user's computer that could be that user's custom profile background image.
                                _('Upload file'));
        $this->out->element('input', array('name' => 'design_banner-image_file',
                                      'type' => 'file',
                                      'id' => 'design_banner-image_file'));
        $this->out->elementEnd('li');

            $this->out->elementStart('li', array('id' =>
                'design_banner-image_onoff'));
            $this->out->checkbox('design_banner-image_enable',
                            // TRANS: Checkbox label on profile design page that will cause the profile image to be tiled.
                            _('Enable banner image'),
                            ($this->settings['designoptions'] & 64) ? true : false);
			}
            $this->out->elementEnd('li');

            $this->out->elementStart('li');
            $this->out->checkbox('design_banner-light',
                            // TRANS: Checkbox label on profile design page that will cause the profile image to be tiled.
                            _('Light text over banner'),
                            ($this->settings['designoptions'] & 256) ? true : false);
            $this->out->elementEnd('li');
			if($this->isUserForm !== 2) {

            $this->out->elementStart('li');
            $this->out->checkbox('design_banner-resize',
                            // TRANS: Checkbox label on profile design page that will cause the profile image to be tiled.
                            _('Resize image to fit'),
                            ($this->settings['designoptions'] & 1024) ? true : false);
            $this->out->elementEnd('li');
			$this->out->elementStart('li');
            $this->out->dropdown('design_banner-image_anchor',
                            // TRANS: Dropdown field label on profile settings, for what policies to apply when someone else tries to subscribe to your updates.
                            _('Position'),
                            // TRANS: Dropdown field option for following policy.
                            array(
								'left' => _('Left'),
								'not-left' => _('Right'),
							),
								  
                            _('The position the banner will anchor itself to.'),
                            false,
                            (($this->settings['designoptions'] & 128) ? 'left' : 'not-left'));
			$this->out->elementEnd('li');
			}

        $this->out->elementEnd('ul');
        $this->out->elementEnd('fieldset');

        $this->out->elementStart('fieldset', array('id' => 'settings_design_color'));
        // TRANS: Fieldset legend on profile design page to change profile page colours.
        $this->out->element('legend', null, _('Change colors'));
        $this->out->elementStart('ul', 'form_data');

            $this->out->elementStart('li');
            // TRANS: Label on profile design page for setting a profile page background colour.
            $this->out->element('label', array('for' => 'swatch-1'), _('Background'));
            $this->out->element('input', array('name' => 'design_background',
                                          'type' => 'text',
                                          'id' => 'swatch-1',
                                          'class' => 'swatch',
                                          'maxlength' => '7',
                                          'size' => '7',
                                          'value' => ''));
            $this->out->elementEnd('li');

            $this->out->elementStart('li');
            // TRANS: Label on profile design page for setting a profile page content colour.
            $this->out->element('label', array('for' => 'swatch-2'), _('Content'));
            $this->out->element('input', array('name' => 'design_content',
                                          'type' => 'text',
                                          'id' => 'swatch-2',
                                          'class' => 'swatch',
                                          'maxlength' => '7',
                                          'size' => '7',
                                          'value' => ''));
            $this->out->elementEnd('li');

            $this->out->elementStart('li');
            // TRANS: Label on profile design page for setting a profile page sidebar colour.
            $this->out->element('label', array('for' => 'swatch-3'), _('Sidebar'));
            $this->out->element('input', array('name' => 'design_sidebar',
                                        'type' => 'text',
                                        'id' => 'swatch-3',
                                        'class' => 'swatch',
                                        'maxlength' => '7',
                                        'size' => '7',
                                        'value' => ''));
            $this->out->elementEnd('li');

            $this->out->elementStart('li');
            // TRANS: Label on profile design page for setting a profile page text colour.
            $this->out->element('label', array('for' => 'swatch-4'), _('Text'));
            $this->out->element('input', array('name' => 'design_text',
                                        'type' => 'text',
                                        'id' => 'swatch-4',
                                        'class' => 'swatch',
                                        'maxlength' => '7',
                                        'size' => '7',
                                        'value' => ''));
            $this->out->elementEnd('li');

            $this->out->elementStart('li');
            // TRANS: Label on profile design page for setting a profile page links colour.
            $this->out->element('label', array('for' => 'swatch-5'), _('Links'));
            $this->out->element('input', array('name' => 'design_links',
                                         'type' => 'text',
                                         'id' => 'swatch-5',
                                         'class' => 'swatch',
                                         'maxlength' => '7',
                                         'size' => '7',
                                         'value' => ''));
            $this->out->elementEnd('li');

            $this->out->elementStart('li');
            // TRANS: Label on profile design page for setting a profile page links colour.
            $this->out->element('label', array('for' => 'swatch-6'), _('Banner'));
            $this->out->element('input', array('name' => 'design_banner',
                                         'type' => 'text',
                                         'id' => 'swatch-6',
                                         'class' => 'swatch',
                                         'maxlength' => '7',
                                         'size' => '7',
                                         'value' => ''));
            $this->out->elementEnd('li');

        $this->out->elementEnd('ul');
        $this->out->elementEnd('fieldset');
		
		if($this->isUserForm) {
			if($this->isUserForm !== 2) {
			$this->out->elementStart('fieldset');
			$this->out->elementStart('ul', 'form_data');
			$this->out->elementStart('li');
            $this->out->checkbox('design_use-everywhere',
                            // TRANS: Checkbox label on profile design page that will cause the profile image to be tiled.
                            _('Use design everywhere'),
                            ($this->settings['designoptions'] & 512) ? true : false);
			$this->out->element('p', 'form_guide', _('Whether to load your profile design throughout the site.'));
			$this->out->elementEnd('li');
			$this->out->elementEnd('ul');
			$this->out->elementEnd('fieldset');
			} else {
			$this->out->elementStart('fieldset');
			$this->out->elementStart('ul', 'form_data');
			$this->out->elementStart('li');
            $this->out->textarea('design_custom-css',
                            // TRANS: Checkbox label on profile design page that will cause the profile image to be tiled.
                            _('Custom CSS'),
                            common_config('site', 'custom-css'));
			$this->out->elementEnd('li');

            $this->out->elementStart('li');
            // TRANS: Label on profile design page for setting a profile page links colour.
            $this->out->element('label', array('for' => 'design_clm-logo'), _('CLM logo'));
            $this->out->element('input', array('name' => 'design_clm-logo',
                                         'type' => 'text',
                                         'id' => 'design_clm-logo',
                                         'value' => common_config('site', 'clm-logo')));
            $this->out->elementEnd('li');
			$this->out->elementEnd('ul');
			$this->out->elementEnd('fieldset');
			}
		}
    }

    /**
     * Buttons for form actions
     *
     * Submit and cancel buttons (or whatever)
     * Sub-classes should overload this to show their own buttons.
     *
     * @return void
     */

    function formActions()
    {
		$this->submit('defaults', _('Use defaults'), 'submit form_action-default',
		// TRANS: Title for button on profile design page to reset all colour settings to default.
        'defaults', _('Restore default designs'));

        $this->submit('submit', _('Save'));
    }

    /**
     * ID of the form
     *
     * Should be unique on the page. Sub-classes should overload this
     * to show their own IDs.
     *
     * @return int ID of the form
     */

    function id()
    {
        return 'form_design';
    }

    /**
     * Action of the form.
     *
     * URL to post to. Should be overloaded by subclasses to give
     * somewhere to post to.
     *
     * @return string URL to post to
     */

    function action()
    {
        return $this->isUserForm ? ($this->isUserForm === 2 ? common_local_url('admindesignsettings') : common_local_url('profiledesignsettings')) : common_local_url('groupdesignsettings', array('nickname' => $this->out->group->nickname));
    }

    /**
     * Class of the form. May include space-separated list of multiple classes.
     *
     * @return string the form's class
     */

    function formClass()
    {
        return 'form_settings';
    }
}
