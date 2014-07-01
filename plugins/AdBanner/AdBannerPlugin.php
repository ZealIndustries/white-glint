<?php

/**

 * StatusNet, the distributed open-source microblogging tool

 *

 * Plugin to add additional awesomenss to StatusNet

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

 * @category  Plugin

 * @package   StatusNet

 * @author    Jeroen De Dauw <jeroendedauw@gmail.com>

 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0

 * @link      http://status.net/

 */



if (!defined('STATUSNET')) {

    exit(1);

}



/**

 * Fun sample plugin: tweaks input data and adds a 'Cornify' widget to sidebar.

 *

 * @category Plugin

 * @package  StatusNet

 * @author   Jeroen De Dauw <jeroendedauw@gmail.com>

 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0

 * @link     http://status.net/

 */



class AdBannerPlugin extends Plugin

{

    public $adsenseCode;

         function onEndShowInsideFooter($action){



                        $action->elementStart('div', array('id' => 'adbanner'));

                        $action->raw($this->adsenseCode);

                        $action->elementEnd('div');

        }



} 
