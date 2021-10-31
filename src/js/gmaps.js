(function () {
	(function() {
		var containers = document.querySelectorAll( 'div.mc-gmap-markers' );
		containers.forEach((el) => {
			mc_map( el );
		});
	})();

	/*
	*  mc_map
	*
	*  This function will render a Google Map onto the selected jQuery element
	*
	*  @type	function
	*  @date	8/11/2013
	*  @since	4.3.0
	*
	*  @param	el DOM element containing marker references.
	*  @return	n/a
	*/

	function mc_map( el ) {
		// var
		var $markers = el.querySelectorAll( '.marker' );
		//var $markers = $el.find('.marker');

		// vars
		var args = {
			center		: {lng: 0.0000, lat: 0.0000},
			mapTypeId	: google.maps.MapTypeId.roadmap
		};
		
		// create map
		var plot = new google.maps.Map( el, args );
		var bounds = new google.maps.LatLngBounds();

		console.log( el );
		// add markers
		$markers.forEach((marker) => {
			add_marker( marker, plot, bounds );
		});

		// return
		return plot;
	}

	/*
	*  add_marker
	*
	*  This function will add a marker to the selected Google Map
	*
	*  @type	function
	*  @date	8/11/2013
	*  @since	4.3.0
	*
	*  @param	$marker (jQuery element)
	*  @param	map (Google Map object)
	*  @return	n/a
	*/

	function add_marker( $marker, plot, bounds ) {
		var latlng  = new google.maps.LatLng( $marker.getAttribute('data-lat'), $marker.getAttribute('data-lng') );
		var marker  = null;
		// Geocoder
		if ( '' == $marker.getAttribute( 'data-lat' ) || '' == $marker.getAttribute( 'data-lng' ) ) {
			var geocoder = new google.maps.Geocoder();
			marker = new getAddress( geocoder, $marker, plot, bounds );
		} else {
			plot.setCenter( latlng );
			// create marker
			marker = new google.maps.Marker({
				position	: latlng,
				map			: plot,
				clickable	: true,
				title		: $marker.getAttribute( 'data-title' ),
			});

			var latlng = new google.maps.LatLng( marker.position.lat(), marker.position.lng() );
			bounds.extend( latlng );
			// If current bounds are too tight, add .005 degrees and zoom out. (~1/2 mile).
			if ( bounds.getNorthEast().equals( bounds.getSouthWest() ) ) {
				var extendPoint = new google.maps.LatLng( bounds.getNorthEast().lat() + 0.005, bounds.getNorthEast().lng() + 0.005 );
				bounds.extend( extendPoint );
			}
			plot.fitBounds(bounds);

			// if marker contains HTML, add it to an infoWindow
			var content = $marker.innerHTML;
			if ( content ) {
				// create info window
				var infowindow = new google.maps.InfoWindow({
					content		: content
				});

				// show info window when marker is clicked
				google.maps.event.addListener( marker, 'click', function() {
					infowindow.open( plot, marker );
				});
			}
		}
	}

	/*
	 * Geocode an address.
	 *
	 * @param geocoder
	 * @param $marker
	 * @param plot Google map object.
	 * @param bounds Google boundary object.
	 */
	function getAddress( geocoder, $marker, plot, bounds ) {
		var address = $marker.getAttribute( 'data-address' );
		var marker = null;
		marker = geocoder.geocode({'address': address}, function(results, status) {
			if ( status === 'OK' ) {
				plot.setCenter( results[0].geometry.location );
				marker = new google.maps.Marker({
					map : plot,
					position : results[0].geometry.location,
					clickable : true,
					title : $marker.getAttribute( 'data-title' ),
				});

				var latlng = new google.maps.LatLng( marker.position.lat(), marker.position.lng() );
				bounds.extend( latlng );
				// If current bounds are too tight, add .005 degrees and zoom out. (~1/2 mile).
				if ( bounds.getNorthEast().equals( bounds.getSouthWest() ) ) {
					var extendPoint = new google.maps.LatLng( bounds.getNorthEast().lat() + 0.005, bounds.getNorthEast().lng() + 0.005 );
					bounds.extend( extendPoint );
				}
				plot.fitBounds(bounds);

				// if marker contains HTML, add it to an infoWindow
				var content = $marker.innerHTML;
				if ( content ) {
					// create info window
					var infowindow = new google.maps.InfoWindow({
						content		: content
					});

					// show info window when marker is clicked
					marker.addListener( 'click', () => {
						infowindow.open( { anchor: marker, plot } );
					});
				}
			} else {
				console.log( 'Geocode was not successful for the following reason: ' + status );
				console.log( 'Address used to Geocode: ' + address );
			}
		});
	}
})();