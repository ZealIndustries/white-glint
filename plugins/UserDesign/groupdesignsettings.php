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
class GroupdesignsettingsAction extends GroupAction
{
    var $msg;

    function title()
    {
        // TRANS: Title for form to edit a group. %s is a group nickname.
        return sprintf(_('%s group design'), $this->group->nickname);
    }

    /**
     * Prepare to run
     */

    function prepare($args)
    {
        parent::prepare($args);

        if (!common_logged_in()) {
            // TRANS: Client error displayed trying to edit a group while not logged in.
            $this->clientError(_('You must be logged in to create a group.'));
            return false;
        }

        $nickname_arg = $this->trimmed('nickname');
        $nickname = common_canonical_nickname($nickname_arg);

        // Permanent redirect on non-canonical nickname

        if ($nickname_arg != $nickname) {
            $args = array('nickname' => $nickname);
            common_redirect(common_local_url('groupdesignsettings', $args), 301);
            return false;
        }

        if (!$nickname) {
            // TRANS: Client error displayed trying to edit a group while not proving a nickname for the group to edit.
            $this->clientError(_('No nickname.'), 404);
            return false;
        }

        $groupid = $this->trimmed('groupid');

        if ($groupid) {
            $this->group = User_group::staticGet('id', $groupid);
        } else {
            $local = Local_group::staticGet('nickname', $nickname);
            if ($local) {
                $this->group = User_group::staticGet('id', $local->group_id);
            }
        }

        if (!$this->group) {
            // TRANS: Client error displayed trying to edit a non-existing group.
            $this->clientError(_('No such group.'), 404);
            return false;
        }

        $cur = common_current_user();

        if (!$cur->isAdmin($this->group)) {
            // TRANS: Client error displayed trying to edit a group while not being a group admin.
            $this->clientError(_('You must be an admin to edit the group.'), 403);
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
		$designSettings = ProfileDesign::getDesign(-($this->group->id));
		$settingsForm = new DesignSettingsForm($this, $designSettings, false);
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
        $cur = common_current_user();
        if (!$cur->isAdmin($this->group)) {
            // TRANS: Client error displayed trying to edit a group while not being a group admin.
            $this->clientError(_('You must be an admin to edit the group.'), 403);
            return;
        }

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

			$vars = ProfileDesign::staticGet('id', -($this->group->id));

			if(empty($vars)) {
				$vars = new ProfileDesign();
				$vars->id = -($this->group->id);
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
			}

			if(isset($orig))
				$vars->update($orig);
			else
				$vars->insert();

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
		$bnSize = $this->boolean('design_banner-resize');
		
		
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
			+ ($bnSize ? 1024 : 0)
		;
		return $new;
	}
	
	function setColor($old, $new) {
		$new = preg_replace('/[^0-9a-fA-F]/', '', $new);
		return strlen($new) == 6 || strlen($new) == 3 ? $new : $old;
	}

    function nicknameExists($nickname)
    {
        $group = Local_group::staticGet('nickname', $nickname);

        if (!empty($group) &&
            $group->group_id != $this->group->id) {
            return true;
        }

        $alias = Group_alias::staticGet('alias', $nickname);

        if (!empty($alias) &&
            $alias->group_id != $this->group->id) {
            return true;
        }

        return false;
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
}
