(function ($) {
	$(function () {
		$( 'button.add-category' ).on( 'click', function() {
			var category_name = $( '#event_category_name' ).val();
			if ( '' !== category_name ) {
				var data    = {
					'action': mc_cats.action,
					'category_name': category_name,
					'security': mc_cats.security
				};
				$.post( ajaxurl, data, function (response) {
					if ( response.success == 1 ) {
						var category_id = response.category_id;
						$( '#event_category_name' ).val( '' );
						$( '.categories .checkboxes' ).append( '<li class="new"><input type="checkbox" name="event_category[]" id="mc_cat_' + category_id + '" value="' + category_id + '" checked> <label for="mc_cat_' + category_id + '">' + category_name + '</label></li>' );
					}
					console.log( response );
				}, "json" );
			}
		});

	});
}(jQuery));