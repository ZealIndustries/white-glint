
/*!
// Infinite Scroll jQuery plugin
// copyright Paul Irish, licensed GPL & MIT
// version 1.2.090804

// home and docs: http://www.infinite-scroll.com
*/

// todo: add preloading option.
 
;(function($){
    
  $.fn.infinitescroll = function(options,callback){
    
    // console log wrapper.
    function debug(){
      if (opts.debug) { window.console && console.log.call(console,arguments)}
    }
    
    // grab each selector option and see if any fail.
    function areSelectorsValid(opts){
      for (var key in opts){
        if (key.indexOf && (key.indexOf('Selector') != -1) && $(opts[key]).length === 0){
            debug('Your ' + key + ' found no elements.');    
            return false;
        } 
        return true;
      }
    }


    // find the number to increment in the path.
    function determinePath(path){
      
      path.match(relurl) ? path.match(relurl)[2] : path; 

      if ( opts.customRegex && path.match(opts.customRegex) ) {
          path = path.match(opts.customRegex).slice(1);
      }
      // there is a 2 in the url surrounded by slashes, e.g. /page/2/
      else if ( path.match(/^(.*?)\b(2)\b(.*?$)/) ){  
          path = path.match(/^(.*?)\b(2)\b(.*?$)/).slice(1);
      } else 
        // if there is any 2 in the url at all.
        if (path.match(/^(.*?)(2)(.*?$)/)){
          debug('Trying backup next selector parse technique. Treacherous waters here, matey.');
          path = path.match(/^(.*?)(2)(.*?$)/).slice(1);
      } else {
        debug('Sorry, we couldn\'t parse your Next (Previous Posts) URL. Verify your the css selector points to the correct A tag. If you still get this error: yell, scream, and kindly ask for help at infinite-scroll.com.');    
        props.isInvalidPage = true;  //prevent it from running on this page.
      }
      
      return path;
    }


    // 'document' means the full document usually, but sometimes the content of the overflow'd div in local mode
    function getDocumentHeight(){
      // weird doubletouch of scrollheight because http://soulpass.com/2006/07/24/ie-and-scrollheight/
      return opts.localMode ? ($(props.container)[0].scrollHeight && $(props.container)[0].scrollHeight) 
                                // needs to be document's height. (not props.container's) html's height is wrong in IE.
                                : $(document).height()
    }
    
    function isNearBottom(opts,props){
      
      // distance remaining in the scroll
      // computed as: document height - distance already scroll - viewport height - buffer
      var pixelsFromWindowBottomToBottom = getDocumentHeight()  -
                                            (opts.localMode ? $(props.container).scrollTop() : 
                                              // have to do this bs because safari doesnt report a scrollTop on the html element
                                              ($(props.container).scrollTop() || $(props.container.ownerDocument.body).scrollTop())) - 
                                            $(opts.localMode ? props.container : window).height();
      
      debug('math:',pixelsFromWindowBottomToBottom, props.pixelsFromNavToBottom);
      
      // if distance remaining in the scroll (including buffer) is less than the orignal nav to bottom....
      return (pixelsFromWindowBottomToBottom  - opts.bufferPx < props.pixelsFromNavToBottom);    
    }    
    
    function showDoneMsg(){
      props.loadingMsg
        .find('img').hide()
        .parent()
          .find('div').html(opts.donetext).animate({opacity: 1},2000).fadeOut('normal');
      
      // user provided callback when done    
      opts.errorCallback();
    }
    
    function infscrSetup(path,opts,props,callback){
    
        if (props.isDuringAjax || props.isInvalidPage || props.isDone) return; 
    
    		if ( opts.infiniteScroll && !isNearBottom(opts,props) ) return;
    		  
    		// we dont want to fire the ajax multiple times
    		props.isDuringAjax = true; 
    		
    		// show the loading message and hide the previous/next links
    		props.loadingMsg.appendTo( opts.contentSelector ).show();
    		if(opts.infiniteScroll) $( opts.navSelector ).hide(); 
    		
    		// increment the URL bit. e.g. /page/3/
    		props.currPage++;

    		debug('heading into ajax',path);
    		
    		// if we're dealing with a table we can't use DIVs
    		var box = $(opts.contentSelector).is('table') ? $('<tbody/>') : $('<div/>');  
    		
    		box
    		  .attr('id','infscr-page-'+props.currPage)
    		  .addClass('infscr-pages')
    		  .load( path.join( props.currPage ) + ' ' + opts.itemSelector,null,function(){
    		    
    		        // if we've hit the last page...
    		        if (props.isDone){ 
                    showDoneMsg();
        			      return false;    
        			      
  	            } else {
  	              
  	                // if it didn't return anything
  	                if (box.children().length == 0){
  	                  // fake an ajaxError so we can quit.
  	                  $.event.trigger( "ajaxError", [{status:404}] ); 
  	                } 
  	                
  	                // fadeout currently makes the <em>'d text ugly in IE6
    		            props.loadingMsg.fadeOut('normal' ); 
  
    		            // smooth scroll to ease in the new content
    		            if (opts.animate){ 
      		            var scrollTo = $(window).scrollTop() + $('#infscr-loading').height() + opts.extraScrollPx + 'px';
                      $('html,body').animate({scrollTop: scrollTo}, 800,function(){ props.isDuringAjax = false; }); 
    		            }

                    // check to make sure the posts don't already exist
                    box.children().each(function() {
                        if($('#' + $(this).attr('id')).length) {
                            $(this).remove();
                        }
                    });
                    
                    // pass in the new DOM element as context for the callback
                    callback.call( box[0] );
                    box.appendTo( opts.contentSelector );

                    // pushState (if supported)
                    if (typeof history.pushState !== 'undefined') {
                        index = (props.currPage - 1) * $(opts.itemSelector).length / props.currPage;
                        pos = $(opts.itemSelector).eq(index).offset().top;
    
                        history.pushState({'page': props.currPage}, '', path.join( props.currPage ) );
                    }

    		            if (!opts.animate) props.isDuringAjax = false; // once the call is done, we can allow it again.
  	            }
              }); // end of load()
    			
    		    
      }  // end of infscrSetup()
          
  
    
      
    // lets get started.
    
    var opts    = $.extend({}, $.infinitescroll.defaults, options);
    var props   = $.infinitescroll; // shorthand
    callback    = callback || function(){};
    
    if (!areSelectorsValid(opts)){ return false;  }
    
     // we doing this on an overflow:auto div?
    props.container   =  opts.localMode ? this : document.documentElement;
                          
    // contentSelector we'll use for our .load()
    opts.contentSelector = opts.contentSelector || this; 
    
    
    // get the relative URL - everything past the domain name.
    var relurl        = /(.*?\/\/).*?(\/.*)/;
    var path          = $(opts.nextSelector).attr('href');
    
    
    if (!path) { debug('Navigation selector not found'); return; }
    
    // set the path to be a relative URL from root.
    path           = determinePath(path, opts);
    props.currPage = path.splice(1, 1)[0] - 1;

    // reset scrollTop in case of page refresh:
    if (opts.localMode) $(props.container)[0].scrollTop = 0;

    // distance from nav links to bottom
    // computed as: height of the document + top offset of container - top offset of nav link
    props.pixelsFromNavToBottom =  getDocumentHeight()  +
                                     $(props.container).offset().top - 
                                     $(opts.navSelector).offset().top;
    
    // define loading msg
    props.loadingMsg = $('<div id="infscr-loading" style="text-align: center;"><img alt="Loading..." src="'+
                                  opts.loadingImg+'" /><div>'+opts.loadingText+'</div></div>');    
     // preload the image
    (new Image()).src    = opts.loadingImg;
  		      

  
    // set up our bindings
    $(document).ajaxError(function(e,xhr,opt){
      debug('Page not found. Self-destructing...');    
      
      // die if we're out of pages.
      if (xhr.status == 404){ 
        showDoneMsg();
        props.isDone = true; 
        $(opts.localMode ? this : window).unbind('scroll.infscr');
      } 
    });

    if(opts.infiniteScroll){
      // bind scroll handler to element (if its a local scroll) or window  
      $(opts.localMode ? this : window)
        .bind('scroll.infscr', function(){ infscrSetup(path,opts,props,callback); } )
        .trigger('scroll.infscr'); // trigger the event, in case it's a short page
    }else{
      $(opts.nextSelector).click(
        function(){
          infscrSetup(path,opts,props,callback);
          return false;
        }
      );
    }
    
    
    return this;
  
  }  // end of $.fn.infinitescroll()
  

  
  // options and read-only properties object
  
  $.infinitescroll = {     
        defaults      : {
                          debug           : false,
                          infiniteScroll  : true,
                          preload         : false,
                          nextSelector    : "div.navigation a:first",
                          loadingImg      : "http://www.infinite-scroll.com/loading.gif",
                          loadingText     : "<em>Loading the next set of posts...</em>",
                          donetext        : "<em>Congratulations, you've reached the end of the internet.</em>",
                          navSelector     : "div.navigation",
                          contentSelector : null,           // not really a selector. :) it's whatever the method was called on..
                          customRegex     : null,
                          extraScrollPx   : 150,
                          itemSelector    : "div.post",
                          animate         : false,
                          localMode      : false,
                          bufferPx        : 40,
                          errorCallback   : function(){}
                        }, 
        loadingImg    : undefined,
        loadingMsg    : undefined,
        container     : undefined,
        currPage      : 1,
        currDOMChunk  : null,  // defined in setup()'s load()
        isDuringAjax  : false,
        isInvalidPage : false,
        isDone        : false  // for when it goes all the way through the archive.
  };
  


})(jQuery);