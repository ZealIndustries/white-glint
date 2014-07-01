<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Base class for sections showing lists of notices
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
 * @category  Widget
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

/**
 * Base class for sections showing lists of notices
 *
 * These are the widgets that show interesting data about a person
 * group, or site.
 *
 * @category Widget
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class PromotedNoticeSection extends NoticeSection
{
	var $promotedHeaders;
	
    function getNotices()
    {
        return Promote::getStream(0, NOTICES_PER_SECTION + 1);
    }

    function title()
    {
        // TRANS: Title for favourited notices section.
        return _('Promoted');
    }

    function divId()
    {
        return 'promoted_notices';
    }

    function moreUrl()
    {
        return common_local_url('promoted');
    }
	
	function showContent() {
        $promote = new Promote();        
        $promote->orderBy("FIELD(type, 'tag', 'group', 'profile', 'notice'), created DESC, id DESC");
		
		$promote->find();
		$this->promotedHeaders = $promote;
		
		return parent::showContent();
	}

    function showNotice($notice)
    {
        $profile = $notice->getProfile();
        if (empty($profile)) {
            common_log(LOG_WARNING, sprintf("Notice %d has no profile",
                                            $notice->id));
            return;
        }
		
		$this->promotedHeaders->fetch();
		if($this->promotedHeaders->type != 'notice' && $this->promotedHeaders->type != 'profile') {
        $this->out->elementStart('li', 'hentry notice has-promoted-header');
			$this->out->elementStart('div', 'promoted-'.$this->promotedHeaders->type);
			if($this->promotedHeaders->type == 'tag') {
				$this->out->element('a', 
					array('href' => common_local_url('tag', array('tag' => $this->promotedHeaders->item_id))),
					$this->promotedHeaders->item_id);
			} else {
				$group = new User_group();
				$group->id = $this->promotedHeaders->item_id;
				if($group->find(true))
					$this->out->element('a',
						array('href' => $group->uri),
						$group->fullname);
			}
			$this->out->elementEnd('div');
		} else {
			$this->out->elementStart('li', 'hentry notice');
		}
		
        $this->out->elementStart('div', 'entry-title');
        $avatar = $profile->getAvatar(AVATAR_MINI_SIZE);
        $this->out->elementStart('span', 'vcard author');
        $this->out->elementStart('a', array('title' => ($profile->fullname) ?
                                            $profile->fullname :
                                            $profile->nickname,
                                            'href' => $profile->profileurl,
                                            'class' => 'url'));
        $this->out->element('img', array('src' => (($avatar) ? $avatar->displayUrl() :  Avatar::defaultImage(AVATAR_MINI_SIZE)),
                                         'width' => AVATAR_MINI_SIZE,
                                         'height' => AVATAR_MINI_SIZE,
                                         'class' => 'avatar photo',
                                         'alt' =>  ($profile->fullname) ?
                                         $profile->fullname :
                                         $profile->nickname));
        $this->out->text(' ');
        $this->out->element('span', 'fn nickname', $profile->nickname);
        $this->out->elementEnd('a');
        $this->out->elementEnd('span');

        $this->out->elementStart('p', 'entry-content');
        $this->out->raw($notice->rendered);
        $this->out->elementEnd('p');

        $this->out->elementStart('div', 'entry_content');
        $nli = new NoticeListItem($notice, $this->out);
        $nli->showNoticeLink();
        $this->out->elementEnd('div');

        if (!empty($notice->value)) {
            $this->out->elementStart('p');
            $this->out->text($notice->value);
            $this->out->elementEnd('p');
        }
        $this->out->elementEnd('div');
        $this->out->elementEnd('li');
    }
}
