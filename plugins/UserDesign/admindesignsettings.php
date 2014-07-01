<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Edit an existing group
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Group
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @author    Sarven Capadisli <csarven@status.net>
 * @author    Zach Copley <zach@status.net>
 * @copyright 2008-2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

/**
 * Add a new group
 *
 * This is the form for adding a new group
 *
 * @category Group
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @author   Zach Copley <zach@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class AdmindesignsettingsAction extends Action
{
    var $msg;

    function title()
    {
        // TRANS: Title for form to edit a group. %s is a group nickname.
        return _('Design admin panel');
    }

    /**
     * Prepare to run
     */

    function prepare($args)
    {
        parent::prepare($args);

        if (!common_logged_in()) {
            // TRANS: Client error displayed trying to edit a group while not logged in.
            $this->clientError(_('Not logged in.'));
            return false;
        }
		
        $user = common_current_user();

        if (!common_is_real_login()) {
            // Cookie theft is too easy; we require automatic
            // logins to re-authenticate before admining the site
            common_set_returnto($this->selfUrl());
            if (Event::handle('RedirectToLogin', array($this, $user))) {
                common_redirect(common_local_url('login'), 303);
            }
        }
		
        if (!$user->hasRight(Right::CONFIGURESITE)) {
            // TRANS: Client error message thrown when a user tries to change admin settings but has no access rights.
            $this->clientError(_('You cannot make changes to this site.'));
            return false;
        }

        return true;
    }

    /**
     * Handle the request
     *
     * On GET, show the form. On POST, try to save the group.
     *
     * @param array $args unused
     *
     * @return void
     */
    function handle($args)
    {
        parent::handle($args);
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->trySave();
        } else {
            $this->showForm();
        }
    }

    function showForm($msg=null)
    {
        $this->msg = $msg;
        $this->showPage();
    }

    function showContent()
    {
		$designSettings = ProfileDesign::getDesign(0);
		$settingsForm = new DesignSettingsForm($this, $designSettings, 2);
		$settingsForm->show();
    }

    function showPageNotice()
    {
        if ($this->msg) {
            $this->element('p', 'error', $this->msg);
        } else {
            $this->element('p', 'instructions',
                           // TRANS: Form instructions for group edit form.
                           _('Customize the look of the group page here.'));
        }
    }

    function trySave()
    {
        $cur = common_current_user();/*
        if (!$cur->isAdmin($this->group)) {
            // TRANS: Client error displayed trying to edit a group while not being a group admin.
            $this->clientError(_('You must be an admin to edit the group.'), 403);
            return;
        }*/

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

			$vars = ProfileDesign::staticGet('id', 0);

			if(empty($vars)) {
				$vars = new ProfileDesign();
				$vars->id = 0;
			}
			else {
				$orig = clone($vars);
			}
		if ($this->arg('defaults')) {
				$vars->delete();
		
			$this->showForm(_('Default restored.'), true);
			return;
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
				$filename = UserDesign::filenameGroup(($this->group->id),
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
			/*
			// Set banner image
			try {
				$bnimage = ImageFile::fromUpload('design_banner-image_file');
			} catch (Exception $e) {
				$bnimage = null;
			}
			if($bnimage !== null) {
				$type = $bnimage->preferredType();
				$filename = UserDesign::filenameGroup(($this->group->id),
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
			}*/

			if(isset($orig))
				$vars->update($orig);
			else
				$vars->insert();

        $config = new Config();

        $config->query('BEGIN');
				Config::save('site', 'custom-css', $this->trimmed('design_custom-css'));
        $config->query('COMMIT');
		
        $config->query('BEGIN');
				Config::save('site', 'clm-logo', $this->trimmed('design_clm-logo'));
        $config->query('COMMIT');

			$this->showForm(_('Settings saved.'), true);
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
		;
		return $new;
	}
	
	function setColor($old, $new) {
		$new = preg_replace('/[^0-9a-fA-F]/', '', $new);
		return strlen($new) == 6 || strlen($new) == 3 ? $new : $old;
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
	
    function showLocalNav()
    {
        $nav = new AdminPanelNav($this);
        $nav->show();
    }
}
