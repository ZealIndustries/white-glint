<?php

if (!defined('STATUSNET')) {
    exit(1);
}

class IpconflictsAction extends Action
{

    /**
     * Take arguments for running
     *
     * This method is called first, and it lets the action class get
     * all its arguments and validate them. It's also the time
     * to fetch any relevant data from the database.
     *
     * Action classes should run parent::prepare($args) as the first
     * line of this method to make sure the default argument-processing
     * happens.
     *
     * @param array $args $_REQUEST args
     *
     * @return boolean success flag
     */
    function prepare($args)
    {
        parent::prepare($args);

        $user = common_current_user();

        if(empty($user) || (!$user->hasRole(Profile_role::ADMINISTRATOR) && !$user->hasRole(Profile_role::MODERATOR))) {
            $this->clientError(_("You are not permitted to access this page."), 403);
            return false;
        }

        $this->ip = new Profile();
        $this->ip->query("SELECT profile.* FROM profile JOIN ip_login as ip ON profile.id = ip.user_id JOIN ip_login AS ip2 ON ip2.ipaddress = ip.ipaddress JOIN profile_role ON ip2.user_id = profile_role.profile_id WHERE (role='silenced' OR role='permaban') GROUP BY profile.id;");

        return true;
    }

    /**
     * Handle request
     *
     * This is the main method for handling a request. Note that
     * most preparation should be done in the prepare() method;
     * by the time handle() is called the action should be
     * more or less ready to go.
     *
     * @param array $args $_REQUEST args; handled in prepare()
     *
     * @return void
     */
    function handle($args)
    {
        parent::handle($args);

        $this->showPage();
    }

    /**
     * Title of this page
     *
     * Override this method to show a custom title.
     *
     * @return string Title of the page
     */
    function title()
    {
        return _m('IP Conflicts');
    }

    /**
     * Show content in the content area
     *
     * The default StatusNet page has a lot of decorations: menus,
     * logos, tabs, all that jazz. This method is used to show
     * content in the content area of the page; it's the main
     * thing you want to overload.
     *
     * This method also demonstrates use of a plural localized string.
     *
     * @return void
     */
    function showContent()
    {
        $this->element('p', null, 'Users whose IP currently conflicts with banned users.');
        foreach(range(1, $this->ip->N / PROFILES_PER_PAGE) as $dummy) {
            $pl = new ProfileList($this->ip, $this);
            $pl->show();
        }
    }

    /**
     * Return true if read only.
     *
     * Some actions only read from the database; others read and write.
     * The simple database load-balancer built into StatusNet will
     * direct read-only actions to database mirrors (if they are configured),
     * and read-write actions to the master database.
     *
     * This defaults to false to avoid data integrity issues, but you
     * should make sure to overload it for performance gains.
     *
     * @param array $args other arguments, if RO/RW status depends on them.
     *
     * @return boolean is read only action?
     */
    function isReadOnly($args)
    {
        return true;
    }
}
?>
