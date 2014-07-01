<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2011, StatusNet, Inc.
 *
 * Public stream
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
 * @category  Stream
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
 * Public stream
 *
 * @category  Stream
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

class PublicNoticeStream extends ScopingNoticeStream
{
    function __construct($profile=null, $images=false)
    {
		$check = false;
		if($profile != null) {
			$subscriptions = $profile->getSubscriptions();
			while($subscriptions->fetch()) {
				if($subscriptions->isSandboxed()) {
					$check = true;
					break;
				} else if(class_exists('Ostatus_profile') && Ostatus_profile::staticGet('profile_id', $subscriptions->id)) {
					$check = true;
					break;
				}
			}
		}
		
		if($check)
			parent::__construct(new RawPublicNoticeStream($profile),
                            $profile);
		else
			parent::__construct(new CachingNoticeStream(new RawPublicNoticeStream(false),
                                                    'public'),
                            $profile);

        $this->images = $images;
    }
}

/**
 * Raw public stream
 *
 * @category  Stream
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

class RawPublicNoticeStream extends NoticeStream
{
	var $hasRemote;
	
	function __construct($hasRemote=false) {
		$this->hasRemote = $hasRemote;
	}
	
    function getNoticeIds($offset, $limit, $since_id, $max_id)
    {
        $notice = new Notice();

        $notice->selectAdd(); // clears it
        $notice->selectAdd('id');

        $notice->orderBy('created DESC, id DESC');

        if (!is_null($offset)) {
            $notice->limit($offset, $limit);
        }

        if (common_config('public', 'localonly')) {
            $notice->whereAdd('is_local = ' . Notice::LOCAL_PUBLIC);
        } else {
            // -1 == blacklisted, -2 == gateway (i.e. Twitter)
            $notice->whereAdd('is_local !='. Notice::LOCAL_NONPUBLIC);
            $notice->whereAdd('is_local !='. Notice::GATEWAY);
        }
		
		if($this->hasRemote) {
			$profile = $this->hasRemote;
			$subscriptions = $profile->getSubscriptions();
			$subscribedIds = array();
			while($subscriptions->fetch()) {
				$subscribedIds[] = $subscriptions->id;
			}
			$subscribedIds = implode(',', $subscribedIds);
			
			$notice->whereAdd('profile_id IN (' . $subscribedIds . ')', 'OR');
		}

        Notice::addWhereSinceId($notice, $since_id);
        Notice::addWhereMaxId($notice, $max_id);

        $ids = array();

        if ($notice->find()) {
            while ($notice->fetch()) {
                $ids[] = $notice->id;
            }
        }

        $notice->free();
        $notice = NULL;

        return $ids;
    }
}
