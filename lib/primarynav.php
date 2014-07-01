<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2011, StatusNet, Inc.
 *
 * Primary nav, show on all pages
 *
 * PHP version 5
 *
 * This program is free software: you can redistribute it and/or modify
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
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

/**
 * Primary, top-level menu
 *
 * @category  General
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

class PrimaryNav extends Menu
{
    function show()
    {
        $user = common_current_user();
        $tmpisadmin = 0;
        if ($user) {
            if ($user->hasRight(Right::CONFIGURESITE)) {
                $tmpisadmin = 1;
            }
        }

        // TRANS: DT element for primary navigation menu. String is hidden in default CSS.
        $this->elementStart('ul', array('class' => 'nav'));

        if (Event::handle('StartPrimaryNav', array($this->action))) {

            // TRANS: Tooltip for main menu option "Home".
            $tooltip = _m('TOOLTIP', 'Frequently asked questions');
            $this->menuItem(common_local_url('doc', array('title' => 'faq')),
                _m('MENU', 'FAQ'), $tooltip, false, 'nav_faq');

            // TRANS: Tooltip for main menu option "Rules".
            $tooltip = _m('TOOLTIP', 'Site rules');
            $this->menuItem(common_local_url('doc', array('title' => 'rules')),
                _m('MENU', 'Rules'), $tooltip, false, 'nav_rules');

            // TRANS: Tooltip for main menu option "Rules".
            $tooltip = _m('TOOLTIP', 'List of site staff');
            $this->menuItem(common_local_url('staff'),
                _m('MENU', 'Staff'), $tooltip, false, 'nav_admins');
/* Replace this with a dropdown by the search button
            if ($user || !common_config('site', 'private')) {
                $this->startDropdown(_m('MENU', 'Search'), 'nav_search');

                // TRANS: Tooltip for main menu option "Search People".
                $tooltip = _m('TOOLTIP', 'Find people on this site');
                $this->menuItem(common_local_url('peoplesearch'),
                    // TRANS: Main menu option when logged in or when the StatusNet instance is not private.
                    _m('People'), $tooltip, false, 'nav_peoplesearch');

                // TRANS: Tooltip for main menu option "Search People".
                $tooltip = _m('TOOLTIP', 'Find content of notices');
                $this->menuItem(common_local_url('noticesearch'),
                    // TRANS: Main menu option when logged in or when the StatusNet instance is not private.
                    _m('Notices'), $tooltip, false, 'nav_noticesearch');

                // TRANS: Tooltip for main menu option "Search People".
                $tooltip = _m('TOOLTIP', 'Find groups on this site');
                $this->menuItem(common_local_url('groupsearch'),
                    // TRANS: Main menu option when logged in or when the StatusNet instance is not private.
                    _m('Groups'), $tooltip, false, 'nav_groupsearch');

                $this->endDropdown();
            }*/
/*
            if (Event::handle('StartLinkDropdown', array($this))) {
                $this->startDropdown(_m('Links'), 'nav_links');

                // TRANS: Tooltip for main menu option "Roleplay".
                $tooltip = _m('TOOLTIP', 'Go to Equestria RP for any roleplaying! (Not affiliated with Rainbow Dash Network)');

                $this->menuItem('http://equestriarp.net/',
                    _m('MENU', 'Equestria RP'), $tooltip, false, 'nav_roleplay');

                // TRANS: Tooltip for main menu option "Meetups".
                $tooltip = _m('TOOLTIP', 'Find a pony meetup or group near you');
                $this->menuItem('http://www.bronies.com/map/',
                    // TRANS: Main menu option when logged in for access to personal profile and friends timeline.
                    _m('MENU', 'Meetups'), $tooltip, false, 'nav_meetups');

                Event::handle('EndLinkDropdown', array($this));

                $this->endDropdown();
            }*/

            if($user) {
                if(($user->hasRole(Profile_role::ADMINISTRATOR) || $user->hasRole(Profile_role::MODERATOR)) &&
                    Event::handle('StartAdminDropdown', array($this))) {

                    $this->startDropdown(_m('MENU', 'Mod tools'), 'nav_modtools');

                    if ($user->hasRight(Right::CONFIGURESITE)) {
                        // TRANS: Tooltip for menu option "Admin".
                        $tooltip = _m('TOOLTIP', 'Change site configuration');
                        $this->menuItem(common_local_url('siteadminpanel'),
                            // TRANS: Main menu option when logged in and site admin for access to site configuration.
                            _m('MENU', 'Admin'), $tooltip, false, 'nav_admin');
                    }

                    Event::handle('EndAdminDropdown', array($this));

                    $this->endDropdown();
                }
            }
/*
            if ($user) {

                if (Event::handle('StartUserDropdown', array($this))) {
                    $this->startDropdown($user->nickname, 'nav_userlinks');
                    // TRANS: Tooltip for main menu option "Personal".
                    $tooltip = _m('TOOLTIP', 'Personal profile and friends timeline');
                    $this->menuItem(common_local_url('all', array('nickname' => $user->nickname)),
                        // TRANS: Main menu option when logged in for access to personal profile and friends timeline.
                        _m('MENU', 'Personal'), $tooltip, false, 'nav_personal');

                    $tooltip = _m('TOOLTIP', 'Your incoming messages');
                    $this->menuItem(common_local_url('inbox', array('nickname' => $user->nickname)),
                        _('Inbox'), $tooltip, false, 'nav_dmcounter');

                    $tooltip = _m('TOOLTIP', 'View replies');
                    $this->menuItem(common_local_url('replies', array('nickname' => $user->nickname)),
                        _('Replies'), $tooltip, false, 'nav_replies');

                    // TRANS: Tooltip for main menu option "Services".
                    $tooltip = _m('TOOLTIP', 'Connect to services');
                    $this->menuItem(common_local_url('oauthconnectionssettings'),
                        // TRANS: Main menu option when logged in and connection are possible for access to options to connect to other services.
                        _('Connect'), $tooltip, false, 'nav_connect');

                    if(common_config('invite', 'enabled')) {
                        // TRANS: Tooltip for main menu option "Invite".
                        $tooltip = _m('TOOLTIP', 'Invite friends and colleagues to join you on %s');
                        $this->menuItem(common_local_url('invite'),
                            _m('MENU', 'Invite'),
                            sprintf($tooltip,
                            common_config('site', 'name')),
                            false, 'nav_invitecontact');
                    }

                    // TRANS: Tooltip for main menu option "Account".
                    $tooltip = _m('TOOLTIP', 'Change your email, avatar, password, profile');
                    $this->menuItem(common_local_url('profilesettings'),
                        // TRANS: Main menu option when logged in for access to user settings.
                        _('Account'), $tooltip, false, 'nav_account');

                    Event::handle('EndUserDropdown', array($this));

                    $this->endDropdown();
                }

                // TRANS: Tooltip for main menu option "Logout"
                $tooltip = _m('TOOLTIP', 'Logout from the site');
                $this->menuItem(common_local_url('logout'),
                    // TRANS: Main menu option when logged in to log out the current user.
                    _m('MENU', 'Logout'), $tooltip, false, 'nav_logout');
            }
            else {
                if (!common_config('site', 'closed') && !common_config('site', 'inviteonly')) {
                    // TRANS: Tooltip for main menu option "Register".
                    $tooltip = _m('TOOLTIP', 'Create an account');
                    $this->menuItem(common_local_url('register'),
                        // TRANS: Main menu option when not logged in to register a new account.
                        _m('MENU', 'Register'), $tooltip, false, 'nav_register');
                }
                // TRANS: Tooltip for main menu option "Login".
                $tooltip = _m('TOOLTIP', 'Login to the site');
                $this->menuItem(common_local_url('login'),
                    // TRANS: Main menu option when not logged in to log in.
                    _m('MENU', 'Login'), $tooltip, false, 'nav_login');
            }*/

            Event::handle('EndPrimaryNav', array($this->action));
        }
        $this->elementEnd('ul');
    }
}
