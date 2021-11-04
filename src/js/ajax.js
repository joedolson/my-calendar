(function ($) {
	$(function () {
		// Delete single instances of recurring events.
		$( '.mc_response' ).hide();
		$('.instance-list').on( 'click', 'button.delete_occurrence', function () {
			var value = $(this).attr( 'data-value' );
			var id    = $(this).attr( 'data-event' );
			var begin = $(this).attr( 'data-begin' );
			var end   = $(this).attr( 'data-end' );
			var data = {
				'action': mc_data.action,
				'occur_id': value,
				'event_id': id,
				'occur_begin': begin,
				'occur_end': end,
				'security': mc_data.security
			};
			$.post( ajaxurl, data, function (response) {
				if ( response.success == 1 ) {
					$( "button[data-value='"+value+"']" ).parents( 'li' ).hide();
				} else {
					console.log( response );
				}
				$('.mc_response').text( response.response ).show( 300 );
			}, "json" );
		});

		$( '.mc_add_new' ).hide();

		$( 'button.add-occurrence').on( 'click', function() {
			var expanded = $( this ).attr( 'aria-expanded' );
			if ( expanded == 'true' ) {
				$( this ).attr( 'aria-expanded', 'false' ).find( '.dashicons' ).addClass( 'dashicons-plus' ).removeClass( 'dashicons-minus' );
			} else {
				$( this ).attr( 'aria-expanded', 'true' ).find( '.dashicons' ).addClass( 'dashicons-minus' ).removeClass( 'dashicons-plus' );
			}
			$( '.mc_add_new' ).toggle();
		});

		$( 'button.save-occurrence').on( 'click', function() {
			var date    = $( '#r_begin' ).val();
			var begin   = $( '#r_time' ).val();
			var end     = $( '#r_endtime' ).val();
			var enddate = $( '#r_enddate' ).val();
			var event_id = $( 'input[name="event_id"]' ).val();
			var group_id = $( 'input[name="event_group_id"]' ).val();

			var data    = {
				'action': mc_data.recur,
				'event_id': event_id,
				'group_id': group_id,
				'event_date' : date,
				'event_time' : begin,
				'event_endtime' : end,
				'event_enddate' : enddate,
				'security': mc_data.security
			};
			$.post( ajaxurl, data, function (response) {
				if ( response.success == 1 ) {
					var time     = begin.split( ':' );
					var display  = time[0] + ':' + time[1];
					var edit_url = mc_data.url + response.id;
					var dateEnd  = ( typeof( enddate ) === 'undefined' ) ? date : enddate;
					$( '.instance-list' ).append( '<li class="new"><p><span id="occur_date_' + response.id + '"><strong>Added:</strong> ' + date + ' @ ' + display + '</span></p><p class="instance-buttons"><button class="button delete_occurrence" type="button" data-event="' + event_id + '" data-begin="' + date + ' ' + begin + '" data-end="' + dateEnd + ' ' + end + '" data-value="' + response.id + '" aria-describedby="occur_date_' + response.id + '">Delete</button> <a href="' + edit_url + '" class="button">Edit</a></p></li>' );
				}
				$('.mc_response').text( response.response ).show( 300 );
			}, "json" );
		});
	});
}(jQuery));