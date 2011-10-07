(function(win, doc) {
	if (typeof mmdebug !== "undefined") return;
	
	var head = doc.getElementsByTagName("head")[0],
		js = doc.createElement("script"),
		$j;

	mmdebug = {
		init : function(jQuery) {
			$j = jQuery;
			console.log(mmdebug_stats);
	        $j('head').append(
	            $j('<link>').attr({
	                rel: 'stylesheet',
	                type: 'text/css',
	                href: '/mm_debug/debug.css'
	            }).ready(function(e) {
	                mmdebug.buildInterface(mmdebug.bindEvents);
	            })
	        );
		},
		
		bindEvents : function() {

			$j("#mmdebug_header").bind("click", function(e) {
				$j(e.target).trigger("mmdebug_toggle_debug");
			});
			
			$j("#mmdebug_container").bind("mmdebug_toggle_debug", function(e) {
				$j("#mmdebug_body").slideToggle(250);
			});
			
		},
		
		buildInterface : function(callback) {

			$j("<div>")
				.attr({
					id : "mmdebug_container"
				})
				.appendTo("body");
				
			callback();
		}
	};

	if (typeof jQuery == "undefined" || jQuery().jquery != "1.6.4") {
	    if (typeof jQuery != "undefined") {
	        var orig = jQuery;
	    }

	    js.type = "text/javascript";
	    js.src = '/mm_debug/jquery-1.6.4.min.js';
	    js.onload = function() {
	        $.noConflict();
	        mmdebug.init(jQuery);
	
			if (typeof orig !== "undefined") {
	        	jQuery = orig;	
			}
	    };

	    head.appendChild(js);
	} else {
		mmdebug.init(jQuery);
	}

})(window, document);