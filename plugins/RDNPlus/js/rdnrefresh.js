var selectedRegion = {start:0, end:0};

if(typeof currentUser == 'undefined') {
    try { currentUser = $('#nav_personal a, #nav_profile a').attr('href').replace(siteDir,'').split('/')[1].toLowerCase(); } catch(err) { }
}

$(function(){

    reProcess();
    customStyle();

    $('.rot13').live('click', function(e){
        e.preventDefault();
        var notice = $(this).closest('li');
        decodeSpoiler(notice);
    });

    $('.hideSpoilerT, .hideUserT').live('click', function() {
        $(this).removeClass('hideSpoilerT hideUserT');
        $(this).children().removeClass('hideSpoiler hideUser');
    });
/*
    $('.addbreaks').live('click', function(){
        var notice = $(this).closest('li');
        addLineBreaksToNotice(notice);
    });
*/
    $('.bbTools li').live('click', function() {

        var notice_data = $(this).closest('form').find('#notice_data-text, .notice_data-text');
        if(selectedRegion.end == 0) {
            selectedRegion.end = notice_data.val().length;
        }

        var formatElement = $(this).children()[0].nodeName.toLowerCase();
        var temp = notice_data.val();
        notice_data.val(temp.substring(0,selectedRegion.start)+'['+formatElement+']'
				+temp.substring(selectedRegion.start,selectedRegion.end)+'[/'+formatElement+']'
				+temp.substring(selectedRegion.end));
		selectedRegion.start += 2+formatElement.length;
		selectedRegion.end += 2+formatElement.length;
    });
        

    $('#notice_data-text, .notice_data-text').live('mouseup keyup', function() {
        selectedRegion = getSelected();
    });
    

	$('.notice').addClass('read_notice');
	$('.threaded_replies').live('mouseover', function() {
		$(this).find('.notice').addClass('read_notice');
	});
	$('.oldschool_stream').live('mouseover', function() {
		$('.oldschool_stream .notice').addClass('read_notice');
	});
	$('.notice').live('mouseover', function() {
		$(this).addClass('read_notice');
	});
	$('body').addClass('mark_unread_notices');
});

function getSelected(){ 
  var userSelection, ta;
    if (document.activeElement.nodeName == "TEXTAREA" || 
        (document.activeElement.nodeName == "INPUT" && 
        document.activeElement.getAttribute("type").toLowerCase() == "text")){ 
      ta = document.activeElement; 
      userSelection = getTextSelection(ta);
    } else { 
      userSelection = {start:0,end:0}; 
    } 
return userSelection; 
}

function getTextSelection(el) {
    var start = 0, end = 0, normalizedValue, range,
        textInputRange, len, endRange;

    if (typeof el.selectionStart == "number" && typeof el.selectionEnd == "number") {
        start = el.selectionStart;
        end = el.selectionEnd;
    } else {
        range = document.selection.createRange();

        if (range && range.parentElement() == el) {
            len = el.value.length;
            normalizedValue = el.value.replace(/\r\n/g, "\n");

            // Create a working TextRange that lives only in the input
            textInputRange = el.createTextRange();
            textInputRange.moveToBookmark(range.getBookmark());

            // Check if the start and end of the selection are at the very end
            // of the input, since moveStart/moveEnd doesn't return what we want
            // in those cases
            endRange = el.createTextRange();
            endRange.collapse(false);

            if (textInputRange.compareEndPoints("StartToEnd", endRange) > -1) {
                start = end = len;
            } else {
                start = -textInputRange.moveStart("character", -len);
                start += normalizedValue.slice(0, start).split("\n").length - 1;

                if (textInputRange.compareEndPoints("EndToEnd", endRange) > -1) {
                    end = len;
                } else {
                    end = -textInputRange.moveEnd("character", -len);
                    end += normalizedValue.slice(0, end).split("\n").length - 1;
                }
            }
        }
    }
    return {'start':start,'end':end};
}

function hideUsers(newPosts) {
    // Remove users
    if(rdnrefresh_vars.usernamestags && rdnrefresh_vars.usernamestags.replace(/W+/,'') != '') {
        usernamesTags = rdnrefresh_vars.usernamestags.split(' ');
        $(newPosts).find(".vcard.author .nickname.fn, .vcard.author .url").each(
                function(){
                    tag = $(this);
                    $.each(usernamesTags, function() {
                        try {
                            if($(tag).text().toLowerCase() == this || ( $(tag).attr('href') && $(tag).attr('href').replace(siteDir,'').split('/')[0].toLowerCase() == this )) {
                                var target = tag.closest('li'); 
                                $.fx.off = true;
                                target.children().addClass('hideUser');
                                target.addClass('hideUserT'); //select LI
                                $.fx.off = false;
                            }
                        }
                        catch(e) {}
                    })
                })
    }
}

function hideSpoilers(newPosts) {
    // Remove spoilers
    if(rdnrefresh_vars.spoilertags && rdnrefresh_vars.spoilertags.replace(/W+/,'') != '') {
        spoilerTags = rdnrefresh_vars.spoilertags.toLowerCase().split(' ');
        $(newPosts).find(".tag a").each(
                function(){
                    tag = $(this);
                    $.each(spoilerTags, function() {
                        if(this == tag.html().toLowerCase()) {
                            var target = tag.closest('li'); 
                            $.fx.off = true;
                            target.children().addClass('hideSpoiler'); //select subelements
                            target.addClass('hideSpoilerT'); //select LI
                            $.fx.off = false;
                        }
                    });
                });
    }
}

/* Removes emoticons *
function delEmotes(newPosts) {
    if(rdnrefresh_vars.hideemotes == '1') {
        $(newPosts).find('img.emote').each(function() {
            $(this).before($(this).attr('alt'));
            $(this).remove();
        });
    }
}*/

function addLineBreaksToNotice(notice) {
    var noticeText = $(notice).find('p.entry-content').filter(':first');
    noticeText.html(noticeText.html().replace(/\n/g, '<br />'));
}

/* Reprocesses the page and/or post */
function reProcess(newPosts) {
    //if($('#mobile-toggle-disable').length) return;
    setTimeout(reProcess, 1000);
    if(!newPosts) { var newPosts = $('.hentry.notice')/*.not('.rdnrefresh_done')*/ }
	
	if(!newPosts.length) return;

    if(rdnrefresh_vars.autospoil == '1') newPosts.each(function() {decodeSpoiler($(this), true)});

    hideSpoilers(newPosts);
    hideUsers(newPosts);
    //delEmotes(newPosts);
    highlightUsername(newPosts);
    highlightAny(newPosts);

    var notice_options = $(newPosts).find('.notice-options');
    if(notice_options.length) {
        delButton(notice_options);
    }

    //$(newPosts).addClass('rdnrefresh_done');
    $(newPosts)//.find('.hentry.notice')//.addClass('rdnrefresh_done');
	.removeClass('hentry');
}

function delButton(newPosts) {
    $(newPosts).find('.notice_delete').each(function() {
        var notice_id = $(this).parent().parent().attr('id').split('-')[1];
        var delTitle = $(this).attr('title');
        var container = document.createElement('div');
        var token = $(this).parent().find('.form_favor [name*="token"]').val()
        $(container).html(('<form action="' + siteDir + '/notice/delete" method="post" class="notice_delete" id="delete-%%%"> <fieldset> <legend>Delete this notice?</legend> <input type="hidden" value="' + token + '" id="token-%%%" name="token"> <input type="hidden" value="%%%" id="notice-d%%%" name="notice"> <input title="' + delTitle + '" value="Yes" class="submit submit_delete" name="yes" id="delete-submit-%%%" type="submit" /> </fieldset> </form>').replace(/%%%/g,notice_id));
        $(container).bind('click', function(event) {

            event.preventDefault();
            event.stopPropagation();

            var form = $(this).find('form');
            var submit_i = form.find('.submit');

            var close = function(){
                form.find('.close').remove();

                form
                    .removeClass('dialogbox')
                    .closest('.notice-options')
                        .removeClass('opaque');

                form.find('.submit_dialogbox').remove();
                form.find('.submit').show();

                return false;
            }


            var submit = submit_i.clone();
            submit
                .addClass('submit_dialogbox')
                .removeClass('submit');
            form.append(submit);
            submit.bind('click', function() {
                $.ajax({
                    type: 'POST',
                    dataType: 'text',
                    url: form.attr('action'),
                    data: form.serialize() + '&ajax=1&yes=yes',
                    beforeSend: function(xhr) {
                        close();
                    },
                    error: function (xhr, textStatus, errorThrown) {
                    },
                    success: function(data, textStatus) {
                        form.closest('li').remove();
                    }
                });

                return false;
            });

            submit_i.hide();

            form
                .addClass('dialogbox')
                .append('<button class="close">&#215;</button>')
                .closest('.notice-options')
                    .addClass('opaque');

            form.find('button.close').click(close);
        }, false, true);
        $(this).replaceWith(container);
    });
}

/* Highlights any word the user has typed into the highlight any box. SLOW */
function highlightAny(newPosts) {
    if(rdnrefresh_vars.anyhighlightwords && rdnrefresh_vars.anyhighlightwords.replace(/W+/,'') != ''){
        var words = rdnrefresh_vars.anyhighlightwords.split(' ');
        var posts = $(newPosts).find('p.entry-content');
        $.each(words, function() {
            var wordex = new RegExp('(' + this + ')', 'gi');
            posts.each(function(){
                $(this).textWalk(wordex, '<span class="anyHighlight">$1</span>');
            });
        });
    }
}

/* Scans the page for the current user's name, then applys a highlight */
function highlightUsername(newPosts) {
    var mentionCounter = 0; 
    $(newPosts).find('.entry-content .vcard .url, .author .addressee').each(function(){
        try {
            if($(this).text().toLowerCase() == currentUser || $(this).attr('href').replace(siteDir,'').split('/')[0].toLowerCase() == currentUser) {
                $(this).addClass('userHighlight');
                mentionCounter++
            }
        }
        catch(e){}
    });
}

function customStyle() {
    if(rdnrefresh_vars.customstyle == '1') {
    if(rdnrefresh_vars.logo) {$('.logo.photo').attr('src', logo)}
    }
}

function rot13(text){ 
    return text.replace(/[a-zA-Z]/g, function(c){ 
        return String.fromCharCode((c <= "Z" ? 90 : 122) >= (c = c.charCodeAt(0) + 13) ? c : c - 26); 
    }); 
}

function decodeSpoiler(notice, onlyTagged) {
    var noticetext = notice.find('p.entry-content').filter(':first');
    var rotd = noticetext.find('.rotd, .spbar');
    if(rotd.length) {
        rotd.each(function() {
            if($(this).hasClass('rotd')) {
                $(this).text(rot13($(this).text()));
            }
            if($(this).hasClass('spbar')) {
                $(this).toggleClass('decoded');
                $(this).attr('style', '');
            }
        });
    }
    else if(!onlyTagged) {
        noticetext.text(rot13(noticetext.text()));
    }
}

jQuery.fn.textWalk = function( fn, str ) {
    var func = jQuery.isFunction( fn );
    this.contents().each( jwalk );

    function jwalk() {
        var nn = this.nodeName.toLowerCase();
        if( nn === '#text' ) {
            if( func ) {
                fn.call( this );
            } else {
                if(this.data.search(fn) != -1) {
                    var data = document.createElement('span');
                    data.innerHTML = this.data.replace(fn, str);
                    this.parentNode.replaceChild(data, this);
                }
            }
        } else if( this.nodeType === 1 && this.childNodes && this.childNodes[0] && nn !== 'script' && nn !== 'textarea' ) {
            $(this).contents().each( jwalk );
        }
    }
    return this;
};

