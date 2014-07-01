<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * List of favorites
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
 * @category  Personal
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2008-2009 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/personalgroupnav.php';
require_once INSTALLDIR.'/lib/noticelist.php';
require_once INSTALLDIR.'/lib/feedlist.php';

/**
 * List of favorites
 *
 * @category Personal
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class VanityAction extends OwnerDesignAction
{
    /** User we're getting the faves of */
    var $user = null;
    /** Page of the faves we're on */
    var $page = null;

    /**
     * Is this a read-only page?
     *
     * @return boolean true
     */
    function isReadOnly($args)
    {
        return true;
    }

    /**
     * Title of the page
     *
     * Includes name of user and page number.
     *
     * @return string title of page
     */
    function title()
    {
        if ($this->page == 1) {
            // TRANS: Title for first page of favourite notices of a user.
            // TRANS: %s is the user for whom the favourite notices are displayed.
            return sprintf(_('%s\'s popular notices'), $this->user->nickname);
        } else {
            // TRANS: Title for all but the first page of favourite notices of a user.
            // TRANS: %1$s is the user for whom the favourite notices are displayed, %2$d is the page number.
            return sprintf(_('%1$s\'s popular notices, page %2$d'),
                           $this->user->nickname,
                           $this->page);
        }
    }

    /**
     * Prepare the object
     *
     * Check the input values and initialize the object.
     * Shows an error page on bad input.
     *
     * @param array $args $_REQUEST data
     *
     * @return boolean success flag
     */
    function prepare($args)
    {
        parent::prepare($args);

        $nickname = common_canonical_nickname($this->arg('nickname'));

        $this->user = User::staticGet('nickname', $nickname);

        if (!$this->user) {
            // TRANS: Client error displayed when trying to display favourite notices for a non-existing user.
            $this->clientError(_('No such user.'));
            return false;
        }

        $this->page = $this->trimmed('page');

        if (!$this->page) {
            $this->page = 1;
        }

        common_set_returnto($this->selfUrl());

        return true;
    }

    /**
     * Handle a request
     *
     * Just show the page. All args already handled.
     *
     * @param array $args $_REQUEST data
     *
     * @return void
     */
    function handle($args)
    {
        parent::handle($args);
        $this->showPage();
    }

    /**
     * show the personal group nav
     *
     * @return void
     */
    function showLocalNav()
    {
        $nav = new PersonalGroupNav($this);
        $nav->show();
    }

    function showEmptyListMessage()
    {
        if (common_logged_in()) {
            $current_user = common_current_user();
            if ($this->user->id === $current_user->id) {
                // TRANS: Text displayed instead of favourite notices for the current logged in user that has no favourites.
                $message = _('No one has favorited any of your notices yet. You should try posting something useful.');
            } else {
                // TRANS: Text displayed instead of favourite notices for a user that has no favourites while logged in.
                // TRANS: %s is a username.
                $message = sprintf(_('%s doesn\'t have any popular notices. Try favoriting something they\'ve said.'), $this->user->nickname);
            }
        }
        else {
                // TRANS: Text displayed instead of favourite notices for a user that has no favourites while not logged in.
                // TRANS: %s is a username, %%%%action.register%%%% is a link to the user registration page.
                // TRANS: (link text)[link] is a Mark Down link.
            $message = sprintf(_('%s hasn\'t added any favorite notices yet. Why not [register an account](%%%%action.register%%%%) and then post something interesting they would add to their favorites :)'), $this->user->nickname);
        }

        $this->elementStart('div', 'guide');
        $this->raw(common_markup_to_html($message));
        $this->elementEnd('div');
    }

    /**
     * Content area
     *
     * Shows the list of popular notices
     *
     * @return void
     */
    function showContent()
    {
        $pop = new Popularity();
        $pop->offset = ($this->page - 1) * NOTICES_PER_PAGE;
        $pop->limit  = NOTICES_PER_PAGE;
        $pop->user = $this->user;
        $pop->expiry = 600;
        $notice = $pop->getNotices();

        $nl = new NoticeList($notice, $this);

        $cnt = $nl->show();

        if ($cnt == 0) {
            $this->showEmptyListMessage();
        }

        $this->pagination($this->page > 1, $cnt > NOTICES_PER_PAGE,
                          $this->page, 'favorited');
    }

    function showPageNotice() {
        // TRANS: Page notice for show favourites page.
        $this->element('p', 'instructions', _('This is a way to see what others like.'));
    }
}

class FavoritesNoticeList extends NoticeList
{
    function newListItem($notice)
    {
        return new FavoritesNoticeListItem($notice, $this->out);
    }
}

// All handled by superclass
class FavoritesNoticeListItem extends DoFollowListItem
{
}
