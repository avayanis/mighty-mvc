(function(win, doc) {
	if (typeof mmdebug !== "undefined") return;
	
	var head = doc.getElementsByTagName("head")[0],
		js = doc.createElement("script"),
		$j;

	mmdebug = {
		init : function(jQuery) {
			$j = jQuery;
			
	        $j('head').append(
	            $j('<link>').attr({
	                rel: 'stylesheet',
	                type: 'text/css',
	                href: '/mm_debug/css'
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
				.append($j("<div>")
					.attr({
						id : "mmdebug_body"
					})
				)			
				.append($j("<div>")
					.append($j("<h4>")
						.attr({
							id : "mmdebug_header"
						})
						.text("Mighty MVC debug")
					)
				)
				.prependTo("body");
				
			callback();
		}
	};

	if (typeof jQuery == "undefined" || jQuery().jquery != "1.6.4") {
	    if (typeof jQuery != "undefined") {
	        var orig = jQuery;
	    }

	    js.type = "text/javascript";
	    js.src = 'https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js';
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