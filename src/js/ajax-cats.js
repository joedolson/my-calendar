(function ($) {
	$(function () {
		$( 'button.add-category' ).on( 'click', function() {
			var event_category = $( '#event_category_name' ).val();

			var data    = {
				'action': mc_data.add_category,
				'event_category': event_category,
				'security': mc_data.security
			};
			$.post( ajaxurl, data, function (response) {
				if ( response.success == 1 ) {
					var event_id = response.event_id;
					$( '.categories .checkboxes' ).append( '<li class="new"><input type="checkbox" name="event_category[]" id="mc_cat_' + event_id + '" value="' + event_id + '" checked> <label for="mc_cat_' + event_id + '">' + event_category + '</label></li>' );
				}
			}, "json" );
		});

	});
}(jQuery));