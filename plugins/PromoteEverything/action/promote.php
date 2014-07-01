<?php
/**
 * Favor action.
 *
 * PHP version 5
 *
 * @category Action
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @author   Robin Millette <millette@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://status.net/
 *
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2008, 2009, StatusNet, Inc.
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
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

/**
 * Promote class.
 *
 * @category Action
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @author   Robin Millette <millette@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://status.net/
 */
class PromoteAction extends Action
{
    /**
     * Class handler.
     *
     * @param array $args query arguments
     *
     * @return void
     */
    function handle($args)
    {
        parent::handle($args);
        if (!common_logged_in()) {
            // TRANS: Client error displayed when trying to promote a notice without being logged in.
            $this->clientError(_('Not logged in.'));
            return;
        }
        $user = common_current_user();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            common_redirect(common_local_url('promoted'));
            return;
        }
        if (!$user->hasRight(Right::CONFIGURESITE)) {
            $this->clientError(_('You must be an administrator to promote dashes.'));
            return;
        }
        $id     = $this->trimmed('notice');
        $notice = Notice::staticGet($id);
        $token  = $this->trimmed('token-'.$notice->id);
        if (!$token || $token != common_session_token()) {
            $this->clientError(_('There was a problem with your session token. Try again, please.'));
            return;
        }

        $promote = new Promote();
        $promote->type = 'notice';
        $promote->item_id = $notice->id;

        if ($promote->find()) {
            // TRANS: Client error displayed when trying to promote a notice that is already.
            $this->clientError(_('This dash is already promoted!'));
            return;
        }

        if (!$promote->insert()) {
            // TRANS: Server error displayed when trying to promote a notice fails in the database.
            $this->serverError(_('Could not create promotion.'));
            return;
        }

        Promote::blow('promote');
        Promote::blow('promote;last');
        if ($this->boolean('ajax')) {
            $this->startHTML('text/xml;charset=utf-8');
            $this->elementStart('head');
            // TRANS: Page title for page on which favorite notices can be unfavourited.
            $this->element('title', null, _('Unpromote dash.'));
            $this->elementEnd('head');
            $this->elementStart('body');
            $disfavor = new UnpromoteForm($this, $notice);
            $disfavor->show();
            $this->elementEnd('body');
            $this->elementEnd('html');
        } else {
            common_redirect(common_local_url('promoted'), 303);
        }
    }
}
