<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Menu for personal group of actions
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
 * @category  Menu
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @author    Sarven Capadisli <csarven@status.net>
 * @copyright 2008-2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

/**
 * Menu for personal group of actions
 *
 * @category Menu
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @author   Sarven Capadisli <csarven@status.net>
 * @copyright 2008-2011 StatusNet, Inc.
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class OtherGroupNav extends Menu
{
    /**
     * Show the menu
     *
     * @return void
     */
    function show()
    {
        $user         = $this->action->user;

        $user_profile = $user->getProfile();
        $nickname = $user_profile->nickname;
        $name     = $user_profile->getBestName();

        $action = $this->action->trimmed('action');
        if($action == 'showstream' && $this->action->arg('images')) {
            $action = 'showstream_media';
        }

        $action = $this->actionName;
        $mine = ($this->action->arg('nickname') == $nickname); // @fixme kinda vague

        $this->out->elementStart('ul', array('class' => 'nav'));

        if (Event::handle('StartOtherGroupNav', array($this))) {
            $this->out->menuItem(common_local_url('all', array('nickname' =>
                                                               $nickname)),
                                 // TRANS: Menu item in personal group navigation menu.
                                 _m('MENU', $name),
                                 // TRANS: Menu item title in personal group navigation menu.
                                 // TRANS: %s is a username.
                                 sprintf(_('%s and friends'), $name),
                                 $mine && $action =='all', 'othernav_timeline_personal');
            $this->out->menuItem(common_local_url('showstream', array('nickname' =>
                                                                      $nickname)),
                                 // TRANS: Menu item in personal group navigation menu.
                                 _m('MENU','Profile'),
                                 // TRANS: Menu item title in personal group navigation menu.
                                 _('Your profile'),
                                 $mine && $action =='showstream',
                                 'nav_profile');
            $this->out->menuItem(common_local_url('replies', array('nickname' =>
                                                                   $nickname)),
                                 // TRANS: Menu item in personal group navigation menu.
                                 _m('MENU','Replies'),
                                 // TRANS: Menu item title in personal group navigation menu.
                                 // TRANS: %s is a username.
                                 sprintf(_('Replies to %s'), $name),
                                 $mine && $action =='replies', 'othernav_timeline_replies');
            $this->out->menuItem(common_local_url('showfavorites', array('nickname' =>
                                                                         $nickname)),
                                 // TRANS: Menu item in personal group navigation menu.
                                 _m('MENU','Favorites'),
                                 // @todo i18n FIXME: Need to make this two messages.
                                 // TRANS: Menu item title in personal group navigation menu.
                                 // TRANS: %s is a username.
                                 sprintf(_('%s\'s favorite notices'),
                                         // TRANS: Replaces %s in '%s\'s favorite notices'. (Yes, we know we need to fix this.)
                                         ($user_profile) ? $name : _m('FIXME','User')),
                                 $mine && $action =='showfavorites', 'othernav_timeline_favorites');
            $this->out->menuItem(common_local_url('showstream', array('nickname' =>
                                                                  $nickname)) . '?images=1',
                             // TRANS: Personal group navigation menu option when logged in for viewing own favourited notices.
                             _m('MENU','Media'),
                             // TRANS: Tooltip for personal group navigation menu option when logged in for viewing own favourited notices.
                             sprintf(_('%s\'s media'), ($user_profile) ? $name : _('User')),
                             $mine && $action == 'showstream_media', 'othernav_timeline_media');

            $cur = common_current_user();

            Event::handle('EndOtherGroupNav', array($this));
        }
        $this->out->elementEnd('ul');
    }
}
