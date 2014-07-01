<?php

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

class RDNPlusPlugin extends Plugin
{

    function onAutoload($cls)
    {
        $dir = dirname(__FILE__);

        switch ($cls)
        {
        case 'RdnrefreshsettingsAction':
            include_once $dir . '/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
        case 'Rdnrefresh':
            include_once $dir . '/'.$cls.'.php';
            return false;
        default:
            return true;
        }

    }    

    function onRouterInitialized($m)
    {
        $m->connect('settings/rdnrefresh',
            array('action' => 'rdnrefreshsettings'));
        return true;
    }

    function onEndAccountSettingsNav($action) {
        $action->menuItem(common_local_url('rdnrefreshsettings'),
            // TRANS: Menu item in settings navigation panel.
            _m('MENU','RDN Plus'),
            // TRANS: Menu item title in settings navigation panel.
            _('Change your RDN Plus settings'),
            $action instanceof RdnrefreshsettingsAction);
    }

    function onCheckSchema() {
        $schema = Schema::get();

        $schema->ensureTable('rdnrefresh',
            array(new ColumnDef('user_id', 'integer', null,
            true, 'PRI'),
            new ColumnDef('spoilertags', 'varchar', 255, true),
            new ColumnDef('usernamestags', 'varchar', 255, true),
            new ColumnDef('anyhighlightwords', 'varchar', 255, true),/*
            new ColumnDef('maincolor', 'char', 7, true),
            new ColumnDef('asidecolor', 'char', 7, true),
            new ColumnDef('pagecolor', 'char', 7, true),
            new ColumnDef('linkcolor', 'char', 7, true),
            new ColumnDef('customstyle', 'integer', 1, true),
            new ColumnDef('logo', 'varchar', 255, true),
            new ColumnDef('backgroundimage', 'varchar', 255, true),
            new ColumnDef('hideemotes', 'integer', 1, true),*/ // Gonna move this out to another plugin
            new ColumnDef('autospoil', 'integer', 1, true),
            new ColumnDef('smallfont', 'integer', 1, true),
            new ColumnDef('noclm', 'integer', 1, true),
            new ColumnDef('lastdm', 'integer', null, true),
        ));

        return true;
    }
	
	function onStartAddressData($action) {
		global $config;
		
        if(!isset($this->vars)) {
            $this->vars = Rdnrefresh::getValues();
        }
		
		if(date('N') == 1 && !($this->vars['noclm']) && common_config('site', 'clm-logo')) {
            if (common_config('singleuser', 'enabled')) {
                $user = User::singleUser();
                $url = common_local_url('showstream',
                                        array('nickname' => $user->nickname));
            } else /*if (common_logged_in()) {
                $cur = common_current_user();
                $url = common_local_url('all', array('nickname' => $cur->nickname));
            } else*/ {
                $url = common_local_url('public');
            }

            $action->elementStart('a', array('class' => 'url home bookmark',
                'href' => $url));

			$action->element('img', array('class' => 'logo photo',
				'src' => common_config('site', 'clm-logo'),
				'alt' => common_config('site', 'name')));

            $action->text(' ');
            $action->element('span', array('class' => 'fn org'), common_config('site', 'name'));
            $action->elementEnd('a');
			
			$action->element('a', array(
				'href' => common_local_url('showgroup', array('nickname' => 'clm')),
				'class' => 'clm_float'
			), sprintf(_('%s Community Logo Mondays'), common_config('site', 'name')));
			
			return false;
		}
		
		return true;
	}

    function resetInbox($action) {
        /* Reset Inbox counter */
        if($action instanceof InboxAction) {
            $user = common_current_user();
            if(!empty($user) && $action->user->id == $user->id && !$action->arg('peek')) {
                $message = new Message();

                $message->to_profile = $action->user->id;
                $message->orderBy('created DESC, id DESC');
                $message->limit(1);

                if ($message->find() && $message->fetch()) {
                    $this->vars['lastdm'] = $message->id;

                    $r = Rdnrefresh::staticGet('user_id', $user->id);

                    if(!empty($r)) {
                        $orig = clone($r);
                    }
                    else {
                        $r = new Rdnrefresh();
                        $r->user_id = $user->id;
                    }

                    $r->lastdm = $message->id;

                    if(isset($orig)) {
                        $r->update($orig);
                    }
                    else {
                        $r->insert();
                    }

                    $r->free();
                    $r = null;
                }
            }
        }
    }

    function onEndShowStyles($action)
    {
        if(!isset($this->vars)) {
            $this->vars = Rdnrefresh::getValues();
        }

        $this->resetInbox($action);

        $vars = $this->vars;
/*
        if($vars['customstyle']) {
            $d = new Design;

            $d->backgroundimage = $vars['backgroundimage'] ? "body { background-image: url(" . $vars['backgroundimage'] . "); }" : '';

            $d->contentcolor = $vars['maincolor'];
            $d->sidebarcolor = $vars['sidebarcolor'];
            $d->linkcolor = $vars['linkcolor'];
            $d->textcolor = $vars['pagecolor'];

            $d->showCSS($action);
        }*/

        $action->cssLink($this->path('css/rdnrefresh.css'), null, 'screen, tv, projection, handheld');

		if($vars['smallfont']) {
			$action->style('body {font-size: 84%} .wf-active body, .wf-inactive body {font-size: 90%}');
		}
		
        // Kill RDN Refresh
        $action->inlineScript('RDNDIE = true; ');

        return true;
    }

    function onEndShowScripts($action)
    {
        if(!isset($this->vars)) {
            $this->vars = Rdnrefresh::getValues();
        }

        $user = common_current_user();
        $nick = strtolower((!empty($user)) ? $user->nickname: '');
        $localurl = explode('?', common_local_url('public'));
        $localurl = $localurl[0];

        $action->inlineScript(
            '   var rdnrefresh_vars = ' . json_encode($this->vars) .
            ';  var currentUser     = "' . addslashes($nick) .
            '"; var siteDir         = "' . addslashes($localurl) . '";'
        );
        $action->script($this->path('js/rdnrefresh.js'));

        return true;
    }
	
	function onEndShowDirectMessageForm($out) {
        $out->raw('<ul class="bui bbTools">' .
            '<li style="width: 80px;" class="text_rot13"><r>Spoiler</r></li>' .
            '<li class="text_bold"><b>B</b></li>' .
            '<li class="text_underline"><u>U</u></li>' .
            '<li class="text_italic"><i>i</i></li>' .
            '<li class="text_strike"><s>S</s></li>' .
            '<li class="text_small"><t class="smallt">t</t></li>' .
            '</ul>');

        return true;
	}

    function onEndShowNoticeFormData($action) {
        $action->out->raw('<ul class="bui bbTools">' .
            '<li style="width: 80px;" class="text_rot13"><r>Spoiler</r></li>' .
            '<li class="text_bold"><b>B</b></li>' .
            '<li class="text_underline"><u>U</u></li>' .
            '<li class="text_italic"><i>i</i></li>' .
            '<li class="text_strike"><s>S</s></li>' .
            '<li class="text_small"><t class="smallt">t</t></li>' .
            '</ul>');

        return true;
    }

    function parseFormatting($notice) {
        // HTML code
        $bbcode = array(
            '@()\[b\](.*?)\[/b\]()@ims',
            '@()\[u\](.*?)\[/u\]()@ims',
            '@()\[i\](.*?)\[/i\]()@ims',
            '@()\[s\](.*?)\[/s\]()@ims',
            '@()\[t\](.*?)\[/t\]()@ims',
            '@()\[a\](.*?)\[/a\]()@ims',
        );

        $markdown = array(
            '@(\s|^)\*([a-z].*?[a-z])\*(\s|$)@ims',
            '@(\s|^)_([a-z].*?[a-z])_(\s|$)@ims',
            '@(\s|^)/([a-z].*?[a-z])/(\s|$)@ims',
            '@(\s|^)-([a-z].*?[a-z])-(\s|$)@ims',
            '@(\s|^)=([a-z].*?[a-z])=(\s|$)@ims',
            '@(\s|^)~([a-z].*?[a-z])~(\s|$)@ims',
        );

        $plaintext = array(
            '$1*$2*$3',
            '$1_$2_$3',
            '$1/$2/$3',
            '$1-$2-$3',
            '$1=$2=$3',
            '$1~$2~$3',
        );

        $markup = array(
            '$1<b>$2</b>$3',
            '$1<u>$2</u>$3',
            '$1<i>$2</i>$3',
            '$1<span class="striket">$2</span>$3',
            '$1<span class="smallt">$2</span>$3',
            '$1<span class="sartalic">$2</span>$3',
        );

        $markup_hybrid = array(
            '$1<b>*$2*</b>$3',
            '$1_<u>$2</u>_$3',
            '$1<i>/$2/</i>$3',
            '$1-<span class="striket">$2</span>-$3',
            '$1<span class="smallt">=$2=</span>$3',
            '$1~<span class="sartalic">$2</span>~$3',
        );

        $notice->content = preg_replace($bbcode, $plaintext, $notice->content);
        $notice->content = preg_replace($markdown, $plaintext, $notice->content);

        $notice->rendered = preg_replace($bbcode, $markup, $notice->rendered);
        $notice->rendered = preg_replace($markdown, $markup_hybrid, $notice->rendered);

        if(trim(str_replace(array('*','_','/','-','=','~'), '', $notice->content))) return true;
        else throw new ClientException('Notice cannot be blank');
    }

    function hideSpoilers($notice) {
        global $config;

        //ROT13 - WARNING. Strips previously incorporated HTML.
        $rotex = '@\[(r|sp)\](.*?)\[/(r|sp)\]@ims';
        preg_match_all($rotex, $notice->content, $matches, PREG_SET_ORDER);

        // Forgot to highlight spoiler?
        if(empty($matches) && preg_match('@#sp(oi|io)l(er?|re?)@i', $notice->content)) {

            $matches = array(array("[r]{$notice->content}[/r]", 'r', $notice->content, 'r'));
            $notice->content = "[r]$notice->content[/r]";
            $notice->rendered = "[r]$notice->rendered[/r]";
        }

        foreach($matches as $match) {
			if($match[2] == '')
				throw new ClientException(_('Notice contains empty spoiler tags. Make sure the spoilered text is located between the [r] and [/r] tags and try again.'));
            if(strtolower($match[1]) == 'r') {
                $replacematch = strtr($match[2], 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 'nopqrstuvwxyzabcdefghijklmNOPQRSTUVWXYZABCDEFGHIJKLM');
            }
            else {
                $replacematch = $match[2];
            }
            $notice->content = str_replace($match[0], "[sp]{$replacematch}[/sp]", $notice->content);
            $notice->rendered = preg_replace($rotex, '<span class="spbar" style="color:#000;background-color:#000;">$2</span>', $notice->rendered, 1);
        }

        // Prevent thumbs from being processed. This is an evil hack
        if(!empty($matches) || preg_match('@#nsfw@i', $notice->content)) {
            $config['attachments']['process_links'] = false;
        }
    }

    function onStartNoticeSave($notice) {
        $this->parseFormatting($notice);
        $this->hideSpoilers($notice);
		$notice->rendered = preg_replace('@\r?\n@', '<br />', $notice->rendered);
		$notice->rendered = preg_replace('@\[(.+?)\]\((<a.*?>)[^<\\s]+</a>\)@', '$2$1</a>', $notice->rendered);
		$notice->content = preg_replace('@\[m ([a-z]+) ([a-z]+)\](.*?)\[/m\]@ims', '$3', $notice->content);
		$notice->rendered = preg_replace('@\[m ([a-z]+) ([a-z]+)\](.*?)\[/m\]@ims', '<marquee behavior="$1" direction="$2">$3</marquee>', $notice->rendered);

        return true;
    }
	
	function onSaveNewDirectMessage($message) {
		return $this->onStartNoticeSave($message);
	}
	
	function onProcessRDNPlus($content, $rendered) {
		// At this point I'm not even trying to do things properly
		$tN = new Notice();
		$tN->content = $content;
		$tN->rendered = $rendered;
		$this->onStartNoticeSave($tN);
		$content = $tN->content;
		$rendered = $tN->rendered;
		
		return true;
	}


    function onStartShowFaveForm($action)
    {
        //$action->out->element('a', array('class' => 'addbreaks', 'title' => 'Break Lines'), 'Break Lines');

        $action->out->element('a', array('class' => 'rot13', 'title' => 'Decode Spoiler'), 'Decode Spoiler');
        /*
        $action->out->element('img', array('src' => $this->path('img/bird_16_blue.png'),
            'title' => _m('Retweet to Twitter'),
            'class' => 'retweet',
        ));
         */

        return true;
    }

    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'RDN Plus',
                            'version' => STATUSNET_VERSION,
                            'author' => 'widget+minti',
                            'homepage' => 'http://status.net/wiki/Plugin:Sample',
                            'rawdescription' =>
                          // TRANS: Plugin description.
                            _m('RDN Refresh enhancements for all.'));
        return true;
    }

}
?>
