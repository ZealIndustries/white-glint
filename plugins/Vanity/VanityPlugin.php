<?php

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

class VanityPlugin extends Plugin
{

    function onAutoload($cls)
    {
        $dir = dirname(__FILE__);

        switch ($cls)
        {
        case 'VanityAction':
            include_once $dir . '/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
        default:
            return true;
        }

    }    

    function onStartInitializeRouter($m)
    {
        $m->connect(':nickname/favorited',
            array('action' => 'vanity'),
            array('nickname' => Nickname::DISPLAY_FMT));
        return true;
    }

    function onEndPersonalGroupNav($action) {
        $action->out->menuItem(common_local_url('vanity', array('nickname' => $action->action->user->nickname)),
            _m('MENU','Popular'),
            _('Posts that other people liked'),
            $action->action->trimmed('action') == 'vanity');

        return true;
    }

    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'Vanity',
                            'version' => STATUSNET_VERSION,
                            'author' => 'widget',
                            'homepage' => 'http://status.net/wiki/Plugin:Sample',
                            'rawdescription' =>
                          // TRANS: Plugin description.
                            _m('Have fun looking at your own popular notices'));
        return true;
    }

}
?>
