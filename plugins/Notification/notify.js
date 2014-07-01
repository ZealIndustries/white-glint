// Notify.js from https://github.com/alexgibson/notify.js
(function (root, factory) {
    'use strict';
    if (typeof define === 'function' && define.amd) {
        // AMD environment
        define('notify', [], function () {
            return factory(root, document);
        });
    } else {
        // Browser environment
        root.Notify = factory(root, document);
    }
}(this, function (w, d) {
    'use strict';
    function Notify(title, options) {
        this.title = typeof title === 'string' ? title : null;
        this.options = {
            icon: '',
            body: '',
            tag: '',
            notifyShow: null,
            notifyClose: null,
            notifyClick: null,
            notifyError: null,
            permissionGranted: null,
            permissionDenied: null
        };
        this.permission = null;
        if (!this.isSupported()) {
            return;
        }
        if (!this.title) {
            throw new Error('Notify(): first arg (title) must be a string.');
        }
        //User defined options for notification content
        if (typeof options === 'object') {
            for (var i in options) {
                if (options.hasOwnProperty(i)) {
                    this.options[i] = options[i];
                }
            }
            //callback when notification is displayed
            if (typeof this.options.notifyShow === 'function') {
                this.onShowCallback = this.options.notifyShow;
            }
            //callback when notification is closed
            if (typeof this.options.notifyClose === 'function') {
                this.onCloseCallback = this.options.notifyClose;
            }
            //callback when notification is clicked
            if (typeof this.options.notifyClick === 'function') {
                this.onClickCallback = this.options.notifyClick;
            }
            //callback when notification throws error
            if (typeof this.options.notifyError === 'function') {
                this.onErrorCallback = this.options.notifyError;
            }
            //callback user grants permission for notification
            if (typeof this.options.permissionGranted === 'function') {
                this.onPermissionGrantedCallback = this.options.permissionGranted;
            }
            //callback user denies permission for notification
            if (typeof this.options.permissionDenied === 'function') {
                this.onPermissionDeniedCallback = this.options.permissionDenied;
            }
        }
    }
    Notify.prototype.needsPermission = function () {
        if (('webkitNotifications' in window && webkitNotifications.checkPermission() == 0)
			|| ('Notification' in w && Notification.permission === 'granted')) {
            return false;
        }
        return true;
    };
    Notify.prototype.requestPermission = function () {
        var that = this;
        w.Notification.requestPermission(function (perm) {
            that.permission = perm;
            switch (that.permission) {
            case 'granted':
                that.onPermissionGranted();
                break;
            case 'denied':
                that.onPermissionDenied();
                break;
            }
        });
    };
    Notify.prototype.show = function () {
        if (!this.isSupported()) {
            return;
        }
		var opts = {
            'body': this.options.body,
			'tag': this.options.tag,
            'icon' : this.options.icon
        };
        this.myNotify = new Notification(this.title, opts);
        this.myNotify.addEventListener('show', this, false);
        this.myNotify.addEventListener('error', this, false);
        this.myNotify.addEventListener('close', this, false);
        this.myNotify.addEventListener('click', this, false);
    };
    Notify.prototype.onShowNotification = function () {
        if (this.onShowCallback) {
            this.onShowCallback();
        }
    };
    Notify.prototype.onCloseNotification = function () {
        if (this.onCloseCallback) {
            this.onCloseCallback();
        }
        this.destroy();
    };
    Notify.prototype.onClickNotification = function () {
        if (this.onClickCallback) {
            this.onClickCallback();
        }
    };
    Notify.prototype.onErrorNotification = function () {
        if (this.onErrorCallback) {
            this.onErrorCallback();
        }
        this.destroy();
    };
    Notify.prototype.onPermissionGranted = function () {
        if (this.onPermissionGrantedCallback) {
            this.onPermissionGrantedCallback();
        }
    };
    Notify.prototype.onPermissionDenied = function () {
        if (this.onPermissionDeniedCallback) {
            this.onPermissionDeniedCallback();
        }
    };
    Notify.prototype.destroy = function () {
        this.myNotify.removeEventListener('show', this, false);
        this.myNotify.removeEventListener('error', this, false);
        this.myNotify.removeEventListener('close', this, false);
        this.myNotify.removeEventListener('click', this, false);
    };
    Notify.prototype.isSupported = function () {
        if ('Notification' in w) {
            return true;
        }
        return false;
    };
    Notify.prototype.handleEvent = function (e) {
        switch (e.type) {
        case 'show':
            this.onShowNotification(e);
            break;
        case 'close':
            this.onCloseNotification(e);
            break;
        case 'click':
            this.onClickNotification(e);
            break;
        case 'error':
            this.onErrorNotification(e);
            break;
        }
    };
	Notify.prototype.close = function () {
		this.myNotify.close();
	}
    return Notify;
}));

// Helper functions (thanks SO!)
if (!String.format) {
  String.format = function (format) {
    var args = Array.prototype.slice.call(arguments, 1);
    var sprintfRegex = /%(\d+)\$s/g;
    var sprintf = function (match, number) {
      return number-1 in args ? args[number-1] : match;
    };
    return format.replace(sprintfRegex, sprintf);
  };
}

var indexOf = function(needle) {
    if(typeof Array.prototype.indexOf === 'function') {
        indexOf = Array.prototype.indexOf;
    } else {
        indexOf = function(needle) {
            var i = -1, index = -1;

            for(i = 0; i < this.length; i++) {
                if(this[i] === needle) {
                    index = i;
                    break;
                }
            }

            return index;
        };
    }

    return indexOf.call(this, needle);
};

// Custom plugin JS starts here
SNNote = { //StatusNetNotification
	bN: { //browserNotifications
		// Debug toggle for single-notification mode. TODO add a toggle for this in settings
		singleMode: true,
		
		// Path to icon
		icon: '',
		
		// Counter for things
		counter: 0,
		
		// The set of JSON notifications used to generate the last browser notification. Used to determine whether a new browser notification needs to be spawned.
		lastJson: null,
		
		// Whether browser notifications are supported and enabled.
		enabled: false,
		
		// Initialize the browser notification system
		init: function() {
			var a = new Notify('a', {
				permissionGranted: function() {
					SNNote.bN.enabled = true;
				}
			});
			if(a.isSupported()) {
				if(a.needsPermission()) {
					if('webkitNotifications' in window)
						SNNote.bN.chrome();
					else
						a.requestPermission();
				}
				else
					SNNote.bN.enabled = true;
			}
		},
		
		// Spawn link to request permission in Chrome and other Webkit-based browsers that don't allow for permission checks on page load
		chrome: function() {
			// TODO
			SNNote.dom.append($('<a class="notification_permission" href="#">'+SN.msg('notification_chrome')
				+'</a>').bind('click', function(e) {
					var a = new Notify('a', {
						permissionGranted: function() {
							SNNote.bN.enabled = true;
						}
					});
					a.requestPermission();
					$(this).remove();
				}));
		},
		
		// Check to keep notifications to one browser window at a time
		master: false,
		
		// Process notification JSON, determine if a new browser notification needs to be sent out
		process: function(notifications) {
			if(SNNote.bN.master || typeof(localStorage) == 'undefined'
				|| localStorage.getItem('SNNotes_mastercheck') + SNNote.update*2 < new Date().getTime()) {
				SNNote.bN.master = true;
				if(typeof(localStorage) != 'undefined')
					localStorage.setItem('SNNotes_masterCheck', new Date().getTime());
			} else
				return; // Limit desktop notifications to one window at a time
			
			if(!SNNote.bN.enabled || (SNNote.windowActive && SNNote.update < 20000)) // Don't create desktop notification if window is active
				return; // Always create notification on mobile though
			
			SNNote.bN.lastJson = notifications;
			
			var check = SNNote.bN.newestTimestamp;
			
			var newNotifications = SNNote.bN.getNew(notifications);
			
			if(check == SNNote.bN.newestTimestamp)
				return;
			
			var types = ['message','mention','subscribe','favorite','repeat','grouppost','groupjoin','grouprequest'];
			var typeFound = 0;
			for(var i = 0; i < types.length; i++)
				if(types[i] in newNotifications)
					typeFound++;
			
			if(typeFound == 0) { // No new notifications found
				return;
			}
			
			if(typeFound > 1) { // Notifications of multiple types found
				var total = [];
				for(var i = 0; i < types.length; i++)
					if(types[i] in newNotifications)
						total = total.concat(newNotifications[types[i]]);
				
				var message = String.format(SN.msg('notification_multiple'), total.length);
				var link = '#';
				
				SNNote.bN.push(message, link);
				return;
			}
			
			for(var i = 0; i < types.length; i++)
				if(types[i] in newNotifications)
					typeFound = types[i];
			
			var info = SNNote.bN.processType[typeFound](newNotifications[typeFound]);
			
			SNNote.bN.push(info.message, info.link);
		},
		
		// Processing library for different singular types of notifications. Pass notifications, get message and link in JSON
		processType: {
			message: function(notes) {
				var link = notes[0].inboxlink;
				var message = 'This function is written improperly.';
				if(notes.length == 1) {
					message = String.format(SN.msg('notification_message'), notes[0].user.fullname);
				} else {
					var names = [];
					for(var i = 0; i < notes.length; i++) {
						if(indexOf.call(names, notes[i].user.fullname) == -1)
							names[names.length] = notes[i].user.fullname;
					}
					names = SNNote.mergeNames(names);
					
					message = String.format(SN.msg('notification_message_multiple'), names, notes.length);
				}
				
				return {"message": message, "link": link};
			},
			
			mention: function(notes) {
				var link = notes[0].notice.url;
				var message = 'Stiv, I hope you can read this now that the chat has died down.';
				if(notes.length == 1) {
					message = String.format(SN.msg('notification_mention'), notes[0].user.fullname);
					message += ': "';
					message += notes[0].notice.content + '"';
				} else {
					var names = [];
					for(var i = 0; i < notes.length; i++) {
						if(indexOf.call(names, notes[i].user.fullname) == -1)
							names[names.length] = notes[i].user.fullname;
					}
					names = SNNote.mergeNames(names);
					
					message = String.format(SN.msg('notification_mention_multiple'), names, notes.length);
					link = notes[0].notice.replieslink;
				}
				
				return {"message": message, "link": link};
			},
			
			subscribe: function(notes) {
				var link = notes[0].subscriberslist;
				var user = 'Pls no capy pasterino.';
				if(notes.length == 1) {
					user = notes[0].user.fullname;
				} else {
					var names = [];
					for(var i = 0; i < notes.length; i++) {
						if(indexOf.call(names, notes[i].user.fullname) == -1)
							names[names.length] = notes[i].user.fullname;
					}
					user = SNNote.mergeNames(names);
				}
				var message = String.format(SN.msg('notification_subscribe'), user);
				
				return {"message": message, "link": link};
			},
			
			favorite: function(notes) {
				var sorted = SNNote.sortByNotice(notes);
				if(sorted.count > 1) {
					var names = [];
					for(var i = 0; i < notes.length; i++) {
						if(indexOf.call(names, notes[i].user.fullname) == -1)
							names[names.length] = notes[i].user.fullname;
					}
					names = SNNote.mergeNames(names);
					var message = String.format(SN.msg('notification_favorite_multiple'), names, sorted.count);
					return {"message": message, "link": '#'};
				}
				// If we reached this point, all notes are for the same notice
				var user = 'Great job stiv.';
				if(notes.length == 1) {
					user = notes[0].user.fullname;
				} else {
					var names = [];
					for(var i = 0; i < notes.length; i++) {
						if(indexOf.call(names, notes[i].user.fullname) == -1)
							names[names.length] = notes[i].user.fullname;
					}
					user = SNNote.mergeNames(names);
				}
				var message = String.format(SN.msg('notification_favorite'), user);
				message += ': "';
				message += notes[0].notice.content + '"';
				
				return {"message": message, "link": notes[0].notice.url};
			},
			
			repeat: function(notes) {
				var sorted = SNNote.sortByNotice(notes);
				if(sorted.count > 1) {
					var names = [];
					for(var i = 0; i < notes.length; i++) {
						if(indexOf.call(names, notes[i].user.fullname) == -1)
							names[names.length] = notes[i].user.fullname;
					}
					names = SNNote.mergeNames(names);
					var message = String.format(SN.msg('notification_repeat_multiple'), names, sorted.count);
					return {"message": message, "link": '#'};
				}
				// If we reached this point, all notes are for the same notice
				var user = 'Great job stiv.';
				if(notes.length == 1) {
					user = notes[0].user.fullname;
				} else {
					var names = [];
					for(var i = 0; i < notes.length; i++) {
						if(indexOf.call(names, notes[i].user.fullname) == -1)
							names[names.length] = notes[i].user.fullname;
					}
					user = SNNote.mergeNames(names);
				}
				var message = String.format(SN.msg('notification_repeat'), user);
				message += ': "';
				message += notes[0].notice.content + '"';
				
				return {"message": message, "link": notes[0].notice.url};
			},
			
			grouppost: function(notes) {
				var sorted = SNNote.sortByGroup(notes);
				if(sorted.count > 1) {
					var message = String.format(SN.msg('notification_grouppost_multiple_condensed'), 
						notes.length, sorted.count);
					return {"message": message, "link": '#'};
				}
				// If we reached this point, all notes are for the same group
				var message = 'Softlock in the cheese wedge';
				if(notes.length == 1) {
					message = String.format(SN.msg('notification_grouppost'), notes[0].user.fullname, notes[0].group.name);
					message += ': "';
					message += notes[0].notice.content + '"';
				} else {
					var names = [];
					for(var i = 0; i < notes.length; i++) {
						if(indexOf.call(names, notes[i].user.fullname) == -1)
							names[names.length] = notes[i].user.fullname;
					}
					names = SNNote.mergeNames(names);
					message = String.format(SN.msg('notification_grouppost_multiple'), names, notes.length, notes[0].group.name);
				}
				
				return {"message": message, "link": notes[0].group.url};
			},
			
			groupjoin: function(notes) {
				var sorted = SNNote.sortByGroup(notes);
				if(sorted.count > 1) {
					var names = [];
					for(var i = 0; i < notes.length; i++) {
						if(indexOf.call(names, notes[i].user.fullname) == -1)
							names[names.length] = notes[i].user.fullname;
					}
					names = SNNote.mergeNames(names);
					var message = String.format(SN.msg('notification_groupjoin_multiple'), 
						names, sorted.count);
					return {"message": message, "link": '#'};
				}
				// If we reached this point, all notes are for the same group
				var message = 'Animal birth-induced PTSD';
				if(notes.length == 1) {
					message = String.format(SN.msg('notification_groupjoin'), notes[0].user.fullname, notes[0].group.name);
				} else {
					var names = [];
					for(var i = 0; i < notes.length; i++) {
						if(indexOf.call(names, notes[i].user.fullname) == -1)
							names[names.length] = notes[i].user.fullname;
					}
					names = SNNote.mergeNames(names);
					message = String.format(SN.msg('notification_groupjoin'), names, notes[0].group.name);
				}
				
				return {"message": message, "link": notes[0].group.url};
			},
			
			grouprequest: function(notes) {
				var sorted = SNNote.sortByGroup(notes);
				if(sorted.count > 1) {
					var names = [];
					for(var i = 0; i < notes.length; i++) {
						if(indexOf.call(names, notes[i].user.fullname) == -1)
							names[names.length] = notes[i].user.fullname;
					}
					names = SNNote.mergeNames(names);
					var message = String.format(SN.msg('notification_grouprequest_multiple'), 
						names, sorted.count);
					return {"message": message, "link": '#'};
				}
				// If we reached this point, all notes are for the same group
				var message = 'Animal birth-induced PTSD';
				if(notes.length == 1) {
					message = String.format(SN.msg('notification_grouprequest'), notes[0].user.fullname, notes[0].group.name);
				} else {
					var names = [];
					for(var i = 0; i < notes.length; i++) {
						if(indexOf.call(names, notes[i].user.fullname) == -1)
							names[names.length] = notes[i].user.fullname;
					}
					names = SNNote.mergeNames(names);
					message = String.format(SN.msg('notification_grouprequest'), names, notes[0].group.name);
				}
				
				return {"message": message, "link": notes[0].group.url};
			}
		},
		
		// Push a new desktop notification. Pass content and link
		push: function(message, link) {
			var clickCallback = function() {
				if(link == '#')
					return true;
				window.open(link, '_blank');
				return true;
			};
			
			var noteTag = 'RDN';
			if(!SNNote.bN.singleMode) {
				noteTag += SNNote.bN.counter;
				SNNote.bN.counter++;
			}
			
			var notify = new Notify(SN.msg('notification_title'), {
				body: message,
				notifyClick: clickCallback,
				icon: SNNote.bN.icon,
				tag: noteTag
			});
			notify.show();
		},
		
		// Timestamp of newest notification seen
		newestTimestamp: 0,
		
		// Figure out which notifications are new
		getNew: function(nots) {
			var notes = jQuery.parseJSON(JSON.stringify(nots)); // Create a cloned object so as not to affect the original
			var threshold = SNNote.bN.newestTimestamp;
			
			for(type in notes) {
				var count = 0;
				for(var i = 0; i < notes[type].length; i++) {
					if(notes[type][i].created <= threshold && !SNNote.bN.singleMode) {
						delete notes[type][i];
						count++;
					}
					else if(notes[type][i].created > SNNote.bN.newestTimestamp)
						SNNote.bN.newestTimestamp = notes[type][i].created;
				}
				if(count == notes[type].length) // All notifications of that type were old
					delete notes[type];
				else if(count > 0) { // At least one notification was old, so condense array
					var b = []; 
					for(var i = 0;i < notes[type].length;i++) { 
						if (notes[type][i] !== undefined && notes[type][i] != null) { 
							b.push(notes[type][i]); 
						}
					}
					notes[type] = b;
				}
			}
			
			return notes;
		}
	},
	
	// URL to hit when notifications need updating
	updateUrl: '',
	
	// URL to hit when notifications need removed
	removeUrl: '',
	
	// Open pages in new tabs when notifications are clicked?
	openInNewWindow: false,
	
	// Notification holder element
	dom: null,
	
	// Array of "processed" notifications
	notifications: [],
	
	// Last notification JSON processed
	last: null,
	
	// Is the tab active?
	windowActive: true,
	
	// Interval of time in which to check notices
	update: 60000,
	
	// Initialize script, pass initial notification JSON to work with as well as options
	init: function(notifications, options) {
		SNNote.dom = $('<div id="notification_popup"></div>');
		$('body').append(SNNote.dom);
		$('body').append($('<a href="#" class="notification_toggle"></a>').bind('click', function() {
			SNNote.dom.toggleClass('visible');
		}));
		SNNote.updateUrl = options.updateUrl;
		SNNote.removeUrl = options.removeUrl;
		SNNote.openInNewWindow = options.openInNewWindow;
		SNNote.update = options.update;
		SNNote.bN.icon = options.icon;
		SNNote.bN.init();
		window.setTimeout(SNNote.refresh, SNNote.update);
		$(window).bind('focus', function() {
			SNNote.windowActive = true;
		});
		$(window).bind('blur', function() {
			SNNote.windowActive = false;
		});
		
		$('.notification_close').live('click', SNNote.closeNotification);
		SNNote.process(notifications);
	},
	
	// Update notification JSON and reprocess
	refresh: function() {
		$.ajax({
			type: 'GET',
			url: SNNote.updateUrl,
			cache: false,
			error: function(response) {
				window.setTimeout(SNNote.refresh, SNNote.update);
			},
			success: function(response) {
				window.setTimeout(SNNote.refresh, SNNote.update);
				var json = jQuery.parseJSON(response);
				SNNote.process(json);
			}
		});
	},
	
	// Process notification JSON into site notifications
	process: function(notifications) {
		var displayedNotes = [];
		
		if(notifications === false)
			notifications = {empty:1};

		// Private messages
		if("message" in notifications) {
			var messages = notifications.message;
			var msgNotes = [];
			for(var i = 0; i < messages.length; i++) {
				msgNotes[i] = SNNote.createNotification(messages[i]);
			}
			if(messages.length > 1) {
				var ids = [];
				var names = [];
				
				for(var i = 0; i < messages.length; i++) {
					msgNotes[i].active = false;
					ids[i] = msgNotes[i].id;
					if(indexOf.call(names, messages[i].user.fullname) == -1)
						names[names.length] = messages[i].user.fullname;
				}
				
				names = SNNote.mergeNames(names);
				var message = String.format(SN.msg('notification_message_multiple'), names, ids.length);
				var link = messages[0].inboxlink;
				
				var element = $('<div class="notification notification-message" id="notification-'+ids.join('-')
					+'"><a class="notification_close" href="#"></a><span>'+message+'</span></div>');
				element.append($('<a class="notification_link"></a>').attr('href', link));
				if(SNNote.openInNewWindow)
					element.find('.notification_link').attr('target', '_blank');
				
				var result = {
					"id": ids.join('-'),
					"dom": element,
					"link": link,
					"type": 'mention',
					"notifications": ids,
					"data": null,
					"active": true
				};
				SNNote.notifications[SNNote.notifications.length] = result;
				msgNotes = [result];
			}
			displayedNotes = displayedNotes.concat(msgNotes);
		}
		
		// Do replies
		if("mention" in notifications) { 
			var replies = notifications.mention;
			var replyNotes = [];
			for(var i = 0; i < replies.length; i++) {
				replyNotes[i] = SNNote.createNotification(replies[i]);
			}
			if(replies.length > 3) {
				var ids = [];
				var names = [];
				
				for(var i = 0; i < replies.length; i++) {
					replyNotes[i].active = false;
					ids[i] = replyNotes[i].id;
					if(indexOf.call(names, replies[i].user.fullname) == -1)
						names[names.length] = replies[i].user.fullname;
				}
				
				names = SNNote.mergeNames(names);
				var message = String.format(SN.msg('notification_mention_multiple'), names, ids.length);
				var link = replies[0].notice.replieslink;
				
				var element = $('<div class="notification notification-mention" id="notification-'+ids.join('-')
					+'"><a class="notification_close" href="#"></a><span>'+message+'</span></div>');
				element.append($('<a class="notification_link"></a>').attr('href', link));
				if(SNNote.openInNewWindow)
					element.find('.notification_link').attr('target', '_blank');
				
				var result = {
					"id": ids.join('-'),
					"dom": element,
					"link": link,
					"type": 'mention',
					"notifications": ids,
					"data": null,
					"active": true
				};
				SNNote.notifications[SNNote.notifications.length] = result;
				replyNotes = [result];
			}
			displayedNotes = displayedNotes.concat(replyNotes);
		}
		
		// Group requests
		if("grouprequest" in notifications) {
			var groupReqs = notifications.grouprequest;
			var groupReqNotes = [];
			var organizedGroupReqs = SNNote.sortByGroup(groupReqs);
			
			for(group in organizedGroupReqs) {
				var notes = [];
				
				if(group == 'count')
					continue;
				
				group = organizedGroupReqs[group];
				
				for(var i = 0; i < group.length; i++) {
					notes[i] = SNNote.createNotification(group[i]);
				}
				
				if(notes.length > 1) {
					var ids = [];
					var names = [];
					
					for(var i = 0; i < notes.length; i++) {
						notes[i].active = false;
						ids[i] = group[i].id;
						if(indexOf.call(names, group[i].user.fullname) == -1)
							names[names.length] = group[i].user.fullname;
					}
					
					names = SNNote.mergeNames(names);
					var message = String.format(SN.msg('notification_grouprequest'), names, group[0].group.name);
					var link = group[0].group.url+'/members/pending';
					
					var element = $('<div class="notification notification-grouprequest" id="notification-'+ids.join('-')
						+'"><a class="notification_close" href="#"></a><span>'+message+'</span></div>');
					element.append($('<a class="notification_link"></a>').attr('href', link));
					if(SNNote.openInNewWindow)
						element.find('.notification_link').attr('target', '_blank');
					
					var result = {
						"id": ids.join('-'),
						"dom": element,
						"link": link,
						"type": 'mention',
						"notifications": ids,
						"data": null,
						"active": true
					};
					SNNote.notifications[SNNote.notifications.length] = result;
					notes = [result];
				}
				groupReqNotes = groupReqNotes.concat(notes);
			}
			displayedNotes = displayedNotes.concat(groupReqNotes);
		}
		
		// Group joins
		if("groupjoin" in notifications) {
			var groupJoins = notifications.groupjoin;
			var groupJoinNotes = [];
			var organizedGroupJoins = SNNote.sortByGroup(groupJoins);
			
			for(group in organizedGroupJoins) {
				var notes = [];
				
				if(group == 'count')
					continue;
				
				group = organizedGroupJoins[group];
				
				for(var i = 0; i < group.length; i++) {
					notes[i] = SNNote.createNotification(group[i]);
				}
				
				if(notes.length > 1) {
					var ids = [];
					var names = [];
					
					for(var i = 0; i < notes.length; i++) {
						notes[i].active = false;
						ids[i] = group[i].id;
						if(indexOf.call(names, group[i].user.fullname) == -1)
							names[names.length] = group[i].user.fullname;
					}
					
					names = SNNote.mergeNames(names);
					var message = String.format(SN.msg('notification_groupjoin'), names, group[0].group.name);
					var link = group[0].group.url+'/members';
					
					var element = $('<div class="notification notification-groupjoin" id="notification-'+ids.join('-')
						+'"><a class="notification_close" href="#"></a><span>'+message+'</span></div>');
					element.append($('<a class="notification_link"></a>').attr('href', link));
					if(SNNote.openInNewWindow)
						element.find('.notification_link').attr('target', '_blank');
					
					var result = {
						"id": ids.join('-'),
						"dom": element,
						"link": link,
						"type": 'mention',
						"notifications": ids,
						"data": null,
						"active": true
					};
					SNNote.notifications[SNNote.notifications.length] = result;
					notes = [result];
				}
				groupJoinNotes = groupJoinNotes.concat(notes);
			}
			displayedNotes = displayedNotes.concat(groupJoinNotes);
		}
		
		// Now figure out subscriptions
		if("subscribe" in notifications) {
			var subscribes = notifications.subscribe;
			var subNotes = [];
			for(var i = 0; i < subscribes.length; i++) {
				subNotes[i] = SNNote.createNotification(subscribes[i]);
			}
			if(subscribes.length > 2) {
				var ids = [];
				var names = [];
				
				for(var i = 0; i < subscribes.length; i++) {
					subNotes[i].active = false;
					ids[i] = subNotes[i].id;
					if(indexOf.call(names, subscribes[i].user.fullname) == -1)
						names[names.length] = subscribes[i].user.fullname;
				}
				
				names = SNNote.mergeNames(names);
				var message = String.format(SN.msg('notification_subscribe'), names);
				var link = subscribes[0].subscriberslist;
				
				var element = $('<div class="notification notification-subscribe" id="notification-'+ids.join('-')
					+'"><a class="notification_close" href="#"></a><span>'+message+'</span></div>');
				element.append($('<a class="notification_link"></a>').attr('href', link));
				if(SNNote.openInNewWindow)
					element.find('.notification_link').attr('target', '_blank');
				
				var result = {
					"id": ids.join('-'),
					"dom": element,
					"link": link,
					"type": 'mention',
					"notifications": ids,
					"data": null,
					"active": true
				};
				SNNote.notifications[SNNote.notifications.length] = result;
				subNotes = [result];
			}
			displayedNotes = displayedNotes.concat(subNotes);
		}
		
		// Group posts
		if("grouppost" in notifications) {
			var groupPosts = notifications.grouppost;
			var groupPostNotes = [];
			var organizedGroupPosts = SNNote.sortByGroup(groupPosts);
			
			for(group in organizedGroupPosts) {
				var notes = [];
				
				if(group == 'count')
					continue;
				
				group = organizedGroupPosts[group];
				
				for(var i = 0; i < group.length; i++) {
					notes[i] = SNNote.createNotification(group[i]);
				}
				
				if(notes.length > 3 || (organizedGroupPosts.count+notes.length > 4 && notes.length > 1)) {
					var ids = [];
					var names = [];
					
					for(var i = 0; i < notes.length; i++) {
						notes[i].active = false;
						ids[i] = group[i].id;
						if(indexOf.call(names, group[i].user.fullname) == -1)
							names[names.length] = group[i].user.fullname;
					}
					
					names = SNNote.mergeNames(names);
					var message = String.format(SN.msg('notification_grouppost_multiple'), names, ids.length, group[0].group.name);
					var link = group[0].group.url;
					
					var element = $('<div class="notification notification-grouppost" id="notification-'+ids.join('-')
						+'"><a class="notification_close" href="#"></a><span>'+message+'</span></div>');
					element.append($('<a class="notification_link"></a>').attr('href', link));
					if(SNNote.openInNewWindow)
						element.find('.notification_link').attr('target', '_blank');
					
					var result = {
						"id": ids.join('-'),
						"dom": element,
						"link": link,
						"type": 'mention',
						"notifications": ids,
						"data": null,
						"active": true
					};
					SNNote.notifications[SNNote.notifications.length] = result;
					notes = [result];
				}
				groupPostNotes = groupPostNotes.concat(notes);
			}
			displayedNotes = displayedNotes.concat(groupPostNotes);
		}
		
		// Favorites
		if("favorite" in notifications) {
			var favs = notifications.favorite;
			var favNotes = [];
			var organizedFavs = SNNote.sortByNotice(favs);
			
			for(group in organizedFavs) {
				var notes = [];
				
				if(group == 'count')
					continue;
				
				group = organizedFavs[group];
				
				for(var i = 0; i < group.length; i++) {
					notes[i] = SNNote.createNotification(group[i]);
				}
				
				if(notes.length > 1) {
					var ids = [];
					var names = [];
					
					for(var i = 0; i < notes.length; i++) {
						notes[i].active = false;
						ids[i] = group[i].id;
						if(indexOf.call(names, group[i].user.fullname) == -1)
							names[names.length] = group[i].user.fullname;
					}
					
					names = SNNote.mergeNames(names);
					var message = String.format(SN.msg('notification_favorite'), names);
					var link = group[0].notice.url;
					
					var element = $('<div class="notification notification-favorite" id="notification-'+ids.join('-')
						+'"><a class="notification_close" href="#"></a><span>'+message+'</span></div>');
					element.append($('<p></p>').text(group[0].notice.content));
					element.append($('<a class="notification_link"></a>').attr('href', link));
					if(SNNote.openInNewWindow)
						element.find('.notification_link').attr('target', '_blank');
					
					var result = {
						"id": ids.join('-'),
						"dom": element,
						"link": link,
						"type": 'mention',
						"notifications": ids,
						"data": null,
						"active": true
					};
					SNNote.notifications[SNNote.notifications.length] = result;
					notes = [result];
				}
				favNotes = favNotes.concat(notes);
			}
			displayedNotes = displayedNotes.concat(favNotes);
		}
		
		// Repeats
		if("repeat" in notifications) {
			var repeats = notifications.repeat;
			var repeatNotes = [];
			var organizedRepeats = SNNote.sortByNotice(repeats);
			
			for(group in organizedRepeats) {
				var notes = [];
				
				if(group == 'count')
					continue;
				
				group = organizedRepeats[group];
				
				for(var i = 0; i < group.length; i++) {
					notes[i] = SNNote.createNotification(group[i]);
				}
				
				if(notes.length > 1) {
					var ids = [];
					var names = [];
					
					for(var i = 0; i < notes.length; i++) {
						notes[i].active = false;
						ids[i] = group[i].id;
						if(indexOf.call(names, group[i].user.fullname) == -1)
							names[names.length] = group[i].user.fullname;
					}
					
					names = SNNote.mergeNames(names);
					var message = String.format(SN.msg('notification_repeat'), names);
					var link = group[0].notice.url;
					
					var element = $('<div class="notification notification-repeat" id="notification-'+ids.join('-')
						+'"><a class="notification_close" href="#"></a><span>'+message+'</span></div>');
					element.append($('<p></p>').text(group[0].notice.content));
					element.append($('<a class="notification_link"></a>').attr('href', link));
					if(SNNote.openInNewWindow)
						element.find('.notification_link').attr('target', '_blank');
					
					var result = {
						"id": ids.join('-'),
						"dom": element,
						"link": link,
						"type": 'mention',
						"notifications": ids,
						"data": null,
						"active": true
					};
					SNNote.notifications[SNNote.notifications.length] = result;
					notes = [result];
				}
				repeatNotes = repeatNotes.concat(notes);
			}
			displayedNotes = displayedNotes.concat(repeatNotes);
		}
		
		// Display notifications
		SNNote.dom.find('.notification').remove();
		for(var i = 0; i < displayedNotes.length; i++) {
			SNNote.dom.append(displayedNotes[i].dom);
		}
		
		// End by processing the desktop notification
		SNNote.bN.process(notifications);
	},
	
	// Turn a JSON notification into a processed one, then return it
	createNotification: function(notification) {
		var id = notification.id;
		
		var already = SNNote.findNotification(id);
		if(already)
			return already;
		
		var message = SN.msg('notification_'+notification.type);
		var username = notification.user.fullname;
		var group = '';
		if('group' in notification)
			group = notification.group.name;
		
		message = String.format(message, username, group);
		
		var link = '';
		if('notice' in notification)
			link = notification.notice.url;
		else if('group' in notification) {
			link = notification.group.url;
			if(notification.type == 'groupjoin')
				link += '/members';
			else
				link += '/members/pending';
		}
		else if(notification.type == 'message')
			link = notification.inboxlink;
		else
			link = notification.subscriberslist;
		
		var element = $('<div class="notification notification-'+notification.type+'" id="notification-'+id
			+'"><a class="notification_close" href="#"></a><span>'+message+'</span></div>');
		if('notice' in notification)
			element.append($('<p>'+notification.notice.rendered+'</p>')/*.text(notification.notice.content)*/);
		element.append($('<a class="notification_link"></a>').attr('href', link));
		if(SNNote.openInNewWindow)
			element.find('.notification_link').attr('target', '_blank');
		
		var result = {
			"id": id,
			"dom": element,
			"link": link,
			"type": notification.type,
			"notifications": [id],
			"data": notification,
			"active": true
		};
		
		SNNote.notifications[SNNote.notifications.length] = result;
		return result;
	},
	
	// Find notification if it already exists. Return false if not found
	findNotification: function(id) {
		for (key in SNNote.notifications) {
			if (SNNote.notifications.hasOwnProperty(key) &&
				/^0$|^[1-9]\d*$/.test(key) &&
				key <= 4294967294 &&
				SNNote.notifications[key].id == id
				) {
				return SNNote.notifications[key];
			}
		}
		return false;
	},
	
	// Organize posts to individual groups
	sortByGroup: function(notes) {
		var sorted = {};
		var count = 0;
		for(var i = 0; i < notes.length; i++) {
			var post = notes[i];
			var id = post.group.id;
			if(!(id in sorted)) {
				sorted[id] = [];
				count++
			}
			sorted[id][sorted[id].length] = post;
		}
		sorted.count = count;
		return sorted;
	},
	
	// Organize posts to individual notices
	sortByNotice: function(notes) {
		var sorted = {};
		var count = 0;
		for(var i = 0; i < notes.length; i++) {
			var post = notes[i];
			var id = post.notice.id;
			if(!(id in sorted)) {
				sorted[id] = [];
				count++
			}
			sorted[id][sorted[id].length] = post;
		}
		sorted.count = count;
		return sorted;
	},
	
	// String array of names together easily
	mergeNames: function(names) {
		if(names.length < 5) {
			if(names.length > 1)
				names[names.length-1] = String.format(SN.msg('notification_andx'), names[names.length-1]);
			if(names.length == 2)
				return names.join(' ');
			else
				return names.join(', ');
		} else {
			var others = names.length-3;
			return names[0]+', '+names[1]+', '+names[2]+', '
				+String.format(SN.msg('notification_andothers'), others);
		}
	},
	
	// Close a notification, removing all IDs associated with it in a site request
	closeNotification: function(e) {
		var element = $(this).closest('.notification');
		var id = element.attr('id').substring(13);
		id = id.split('-').join(',');
		element.addClass('closing');
		
		$.ajax({
			type: 'POST',
			url: SNNote.removeUrl,
			data: 'notifications='+id,
			error: function(response) {
				element.removeClass('closing');
			},
			success: function(response) {
				element.remove();
			}
		});
		
		return false;
	}
};