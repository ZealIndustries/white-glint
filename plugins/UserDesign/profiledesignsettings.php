<?php

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class ProfiledesignsettingsAction extends SettingsAction
{
    /**
     * Title of the page
     *
     * @return string Title of the page
     */
    function title()
    {
        return _('Profile design');
    }

    /**
     * Instructions for use
     *
     * @return instructions for use
     */
    function getInstructions()
    {
        return _('Customize the look of your profile page here.');
    }
	
    function showStylesheets()
    {
        parent::showStylesheets();
        $this->cssLink(common_path('js/farbtastic/farbtastic.css',null,'screen, projection, tv'));
    }

    function showScripts()
    {
        parent::showScripts();
		$this->script('farbtastic/farbtastic.js');
		$this->script('userdesign.go.js');
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
		if(!$user) {
			// @fixme Show a proper error message?
			return;
		}
		
		$designSettings = ProfileDesign::getDesign($user->id);
		$settingsForm = new DesignSettingsForm($this, $designSettings, true);
		$settingsForm->show();
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

        if (empty($_FILES)
            && empty($_POST)
            && ($_SERVER['CONTENT_LENGTH'] > 0)
        ) {
            // TRANS: Client error displayed when the number of bytes in a POST request exceeds a limit.
            // TRANS: %s is the number of bytes of the CONTENT_LENGTH.
            $msg = _m('The server was unable to handle that much POST data (%s byte) due to its current configuration.',
                      'The server was unable to handle that much POST data (%s bytes) due to its current configuration.',
                      intval($_SERVER['CONTENT_LENGTH']));
            $this->showForm(sprintf($msg, $_SERVER['CONTENT_LENGTH']));
            return;
        }
		
        $user = common_current_user();
		
		if ($this->arg('defaults')) {
			$vars = ProfileDesign::staticGet('id', $user->id);
			if(!empty($vars))
				$vars->delete();
		
			$this->showForm(_('Default restored.'), true);
			return;
		}
		if(!empty($user)) {

			$vars = ProfileDesign::staticGet('id', $user->id);

			if(empty($vars)) {
				$vars = new ProfileDesign();
				$vars->id = $user->id;
			}
			else {
				$orig = clone($vars);
			}
			
			$vars->designoptions = $this->postDesignOptions(isset($orig) ? $orig->designoptions : 0);
			$vars->bgcolor = $this->setColor($vars->bgcolor, $this->trimmed('design_background'));
			$vars->contentcolor = $this->setColor($vars->contentcolor, $this->trimmed('design_content'));
			$vars->asidecolor = $this->setColor($vars->asidecolor, $this->trimmed('design_sidebar'));
			$vars->textcolor = $this->setColor($vars->textcolor, $this->trimmed('design_text'));
			$vars->linkcolor = $this->setColor($vars->linkcolor, $this->trimmed('design_links'));
			$vars->infocolor = $this->setColor($vars->infocolor, $this->trimmed('design_banner'));
			
			// Set background image
			try {
				$bgimage = ImageFile::fromUpload('design_background-image_file');
			} catch (Exception $e) {
				$bgimage = null;
			}
			if($bgimage !== null) {
				$type = $bgimage->preferredType();
				$filename = UserDesign::filename($user->id,
											 image_type_to_extension($type),
											 'bg',
											 common_timestamp());
				$filepath = UserDesign::path($filename);
				$bgimage->copyTo($filepath);
				
				$vars->bgimage = $filename;
				if(isset($orig->bgimage)) {
					$old = UserDesign::path($orig->bgimage);
					@unlink($old);
				}
			}
			
			// Set banner image
			try {
				$bnimage = ImageFile::fromUpload('design_banner-image_file');
			} catch (Exception $e) {
				$bnimage = null;
			}
			if($bnimage !== null) {
				$type = $bnimage->preferredType();
				$filename = UserDesign::filename($user->id,
											 image_type_to_extension($type),
											 'bn',
											 common_timestamp());
				$filepath = UserDesign::path($filename);
				$bnimage->copyTo($filepath);
				
				$vars->infoimage = $filename;
				if(isset($orig->bnimage)) {
					$old = UserDesign::path($orig->infoimage);
					@unlink($old);
				}
			}
/*
			$vars->spoilertags = substr($this->trimmed('spoilertags'),0,255);
			$vars->usernamestags = substr($this->trimmed('usernamestags'),0,255);
			$vars->anyhighlightwords = substr($this->trimmed('anyhighlightwords'),0,255);
			$vars->autospoil = $this->boolean('autospoil');
*/
			if(isset($orig))
				$vars->update($orig);
			else {        
				$vars->insert();
			}

			$this->showForm(_('Settings saved.'), true);
        }
    }
	
	function postDesignOptions($original) {
		$bgEnabled = $this->boolean('design_background-image_enable');
		$tileX = $this->boolean('design_background-image_repeat-horizontal');
		$tileY = $this->boolean('design_background-image_repeat-vertical');
		$bgAnchor = $this->trimmed('design_background-image_anchor');
		$scroll = $this->boolean('design_background-image_scroll');
		
		$bnEnabled = $this->boolean('design_banner-image_enable');
		$bnAnchor = $this->trimmed('design_banner-image_anchor');
		$bnLight = $this->boolean('design_banner-light');
		$bnSize = $this->boolean('design_banner-resize');
		
		$useEverywhere = $this->boolean('design_use-everywhere');
		
		
		$mask = 0;
		
		if($bgAnchor !== null) {
			$bgAnchor = ($bgAnchor == 'top' ? 8 : ($bgAnchor == 'center' ? 16 : 0));
		} else {
			$bgAnchor = 0;
			$mask += 24;
		}
			
		if($bnAnchor !== null) {
			$bnAnchor = ($bnAnchor == 'left' ? 128 : 0);
		} else {
			$bnAnchor = 0;
			$mask += 128;
		}
		
		
		$new = $original & $mask;
		$new += $bgAnchor+$bnAnchor
			+ ($bgEnabled ? 1 : 0)
			+ ($tileX ? 2 : 0)
			+ ($tileY ? 4 : 0)
			+ ($scroll ? 32 : 0)
			+ ($bnEnabled ? 64 : 0)
			+ ($bnLight ? 256 : 0)
			+ ($useEverywhere ? 512 : 0)
			+ ($bnSize ? 1024 : 0)
		;
		return $new;
	}
	
	function setColor($old, $new) {
		$new = preg_replace('/[^0-9a-fA-F]/', '', $new);
		return strlen($new) == 6 || strlen($new) == 3 ? $new : $old;
	}
}
