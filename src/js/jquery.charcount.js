/*
 * 	Character Count Plugin - jQuery plugin
 * 	Dynamic character count for text areas and input fields
 *	written by Alen Grakalic	
 *	http://cssglobe.com/
 *
 *	Copyright (c) 2009 Alen Grakalic (http://cssglobe.com)
 *	Dual licensed under the MIT (MIT-LICENSE.txt)
 *	and GPL (GPL-LICENSE.txt) licenses.
 *
 *	Built for jQuery library
 *	http://jquery.com
 *
 */

 (function ($) {

    $.fn.charCount = function (options) {
        // default configuration properties
        var defaults = {
            allowed: 140,
            warning: 25,
            css: 'counter',
            counterElement: 'span',
            cssWarning: 'warning',
            cssExceeded: 'exceeded',
            counterText: ''
        };
        var options = $.extend(defaults, options);

        function calculate(obj) {
            var count = $(obj).val().length;
            // supported shortcodes
            var urlcount     = $(obj).val().indexOf('#url#') > -1 ? 18 : 0;
            var longurlcount = $(obj).val().indexOf('#longurl#') > -1 ? 14 : 0;
			if ( $( '#title' ).length ) {
				var titlecount = $(obj).val().indexOf('#title#') > - 1 ? ( $('#title').val().length - 7 ) : 0;
			} else {
				var titlecount = 0;
			}
            var namecount = $(obj).val().indexOf('#blog#') > -1 ? ($('#wp-admin-bar-site-name a').val().length - 6) : 0;
			var imgcount  = ( $('#wpt_image_yes:checked').length && $( '#remove-post-thumbnail' ).length ) ? 22 : 0;
            var available = options.allowed - ( count + urlcount + longurlcount + titlecount + namecount + imgcount );

            if ( available <= options.warning && available >= 0 ) {
                $(obj).next().addClass(options.cssWarning);
            } else {
                $(obj).next().removeClass(options.cssWarning);
            }
            if ( available < 0 ) {
                $(obj).next().addClass(options.cssExceeded);
            } else {
                $(obj).next().removeClass(options.cssExceeded);
            }
            $(obj).next().html(options.counterText + available);
        };

        this.each(function () {
            $(this).after('<' + options.counterElement + ' aria-live="polite" aria-atomic="true" class="' + options.css + '">' + options.counterText + '</' + options.counterElement + '>');
            calculate(this);
			$(this).on( 'keyup', function(e) {
				setTimeout( calculate(this), 200 );
			});
			$(this).on( 'change', function(e) {
				setTimeout( calculate(this), 200 );
			});
        });

    };

})(jQuery);