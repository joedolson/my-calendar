(function ($) {
	$(function () {
		// Delete single instances of recurring events.
		$( '.mc_response' ).hide();
		$('.instance-list').on( 'click', 'button.delete_occurrence', function () {
			let value  = $(this).attr( 'data-value' );
			let id     = $(this).attr( 'data-event' );
			let begin  = $(this).attr( 'data-begin' );
			let end    = $(this).attr( 'data-end' );
			const data = {
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
				}
				$('.mc_response').text( response.response ).show( 300 );
			}, "json" );
		});

		$( '.mc_add_new' ).hide();

		$( 'button.add-occurrence').on( 'click', function() {
			let expanded = $( this ).attr( 'aria-expanded' );
			if ( expanded == 'true' ) {
				$( this ).attr( 'aria-expanded', 'false' ).find( '.dashicons' ).addClass( 'dashicons-plus' ).removeClass( 'dashicons-minus' );
				$( this ).attr( 'data-action', 'shiftback' );
			} else {
				$( this ).attr( 'aria-expanded', 'true' ).find( '.dashicons' ).addClass( 'dashicons-minus' ).removeClass( 'dashicons-plus' );
				$( this ).attr( 'data-action', '' );
			}
			$( '.mc_add_new' ).toggle();
		});

		/**
		 * Save additional date.
		 */
		$( 'button.save-occurrence').on( 'click', function() {
			let date    = $( '#r_begin' ).val();
			let begin   = $( '#r_time' ).val();
			let end     = $( '#r_endtime' ).val();
			let enddate = $( '#r_enddate' ).val();
			let event_id = $( 'input[name="event_id"]' ).val();
			let group_id = $( 'input[name="event_group_id"]' ).val();

			const data    = {
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
					let time     = begin.split( ':' );
					let display  = time[0] + ':' + time[1];
					let edit_url = mc_data.url + response.id;
					let dateEnd  = ( typeof( enddate ) === 'undefined' ) ? date : enddate;
					$( '.instance-list' ).append( '<li class="new"><p><span id="occur_date_' + response.id + '"><strong>Added:</strong> ' + date + ' @ ' + display + '</span></p><p class="instance-buttons"><button class="button delete_occurrence" type="button" data-event="' + event_id + '" data-begin="' + date + ' ' + begin + '" data-end="' + dateEnd + ' ' + end + '" data-value="' + response.id + '" aria-describedby="occur_date_' + response.id + '">Delete</button> <a href="' + edit_url + '" class="button">Edit</a></p></li>' );
				}
				$('.mc_response').text( response.response ).show( 300 );
			}, "json" );
		});

		/**
		 * Display human-readable event repetition pattern when making changes.
		 */
		$( '#e_recur, #e_every' ).on( 'change', function() {
			let recur = $( '#e_recur' ).val();
			let every = $( '#e_every' ).val();
			let until = $( 'duet-date-picker[identifier=e_repeats]' ).val();

			const data  = {
				'action': mc_recur.action,
				'until' : until,
				'every' : every,
				'recur' : recur,
				'security' : mc_recur.security
			};

			$.post( ajaxurl, data, function (response) {
				if ( '' === response.response ) {
					$( '.mc_recur_string' ).removeClass( 'active' );
					$('.mc_recur_string p').text( '' );
				} else {
					$( '.mc_recur_string' ).addClass( 'active' );
					$('.mc_recur_string p').text( response.response ).show( 300 );
				}
			}, "json" );
		});

		/**
		 * Human-readable event repetition for duet date picker.
		 */
		const repeats = document.querySelector( 'duet-date-picker[identifier=e_repeats]' );
		if ( null !== repeats ) {
			repeats.addEventListener( 'duetChange', function(e) {
				let until = e.detail.value;
				let recur = $( '#e_recur' ).val();
				let every = $( '#e_every' ).val();

				const data  = {
					'action': mc_recur.action,
					'until' : until,
					'every' : every,
					'recur' : recur,
					'security' : mc_recur.security
				};

				$.post( ajaxurl, data, function (response) {
					$( '.mc_recur_string' ).addClass( 'active' );
					$('.mc_recur_string p').text( response.response ).show( 300 );
				}, "json" );
			});
		}
	});
	$(function () {
		$( 'button.add-category' ).on( 'click', function() {
			let category_name = $( '#event_category_name' ).val();
			if ( '' !== category_name ) {
				const data = {
					'action': mc_cats.action,
					'category_name': category_name,
					'security': mc_cats.security
				};
				$.post( ajaxurl, data, function (response) {
					if ( response.success == 1 ) {
						let category_id = response.category_id;
						$( '#event_category_name' ).val( '' );
						$( '<li class="new"><input type="checkbox" name="event_category[]" id="mc_cat_' + category_id + '" value="' + category_id + '" checked> <label for="mc_cat_' + category_id + '">' + category_name + '</label></li>' ).insertBefore( '.categories .event-new-category' );
						let primary = $( '#e_category' );
						primary.append( '<option value="' + category_id + '">' + category_name + '</option>' );
					}
				}, "json" );
			}
		});

	});
}(jQuery));