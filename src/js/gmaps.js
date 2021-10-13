(function ($) { 'use strict';
	$(function() {
		$( 'div.mc-gmap-fupup' ).each( function() {
			var mcmap = $( this );
			mc_map( mcmap );
		});
	});

	/*
	*  mc_map
	*
	*  This function will render a Google Map onto the selected jQuery element
	*
	*  @type	function
	*  @date	8/11/2013
	*  @since	4.3.0
	*
	*  @param	$el (jQuery element)
	*  @return	n/a
	*/

	function mc_map( $el ) {
		// var
		var $markers = $el.find('.marker');
		var zoom     = parseInt( $el.attr( 'data-zoom' ) );

		// vars
		var args = {
			zoom		: zoom,
			center		: {lng: 0.0000, lat: 0.0000},
			mapTypeId	: google.maps.MapTypeId.roadmap
		};
		
		// create map
		var plot = new google.maps.Map( $el[0], args );
		
		// add a markers reference
		plot.locations = [];

		// add markers
		$markers.each(function(){
			add_marker( $(this), plot );
		});

		// center map
		center_map( plot, zoom );

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

	function add_marker( $marker, plot ) {
		var latlng  = new google.maps.LatLng( $marker.attr('data-lat'), $marker.attr('data-lng') );
		var marker  = null;
		// Geocoder
		if ( '' == $marker.attr( 'data-lat' ) || '' == $marker.attr( 'data-lng' ) ) {
			var geocoder = new google.maps.Geocoder();
			marker = new getAddress( geocoder, $marker, plot );
		} else {
			plot.setCenter( latlng );
			// create marker
			marker = new google.maps.Marker({
				position	: latlng,
				map			: plot,
				clickable	: true,
				title		: $marker.attr( 'data-title' ),
			});

			// add to array
			plot.locations.push( marker );

			// if marker contains HTML, add it to an infoWindow
			if ( $marker.html() ) {
				// create info window
				var infowindow = new google.maps.InfoWindow({
					content		: $marker.html()
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
	 * @param zoom Zoom level
	 */
	function getAddress( geocoder, $marker, plot ) {
		var address = $marker.attr( 'data-address' );
		var marker = null;
		marker = geocoder.geocode({'address': address}, function(results, status) {
			if ( status === 'OK' ) {
				plot.setCenter( results[0].geometry.location );
				marker = new google.maps.Marker({
					map : plot,
					position : results[0].geometry.location,
					clickable : true,
					title : $marker.attr( 'data-title' ),
				});
				// add to array
				plot.locations.push( marker );

				// if marker contains HTML, add it to an infoWindow
				if ( $marker.html() ) {
					// create info window
					var infowindow = new google.maps.InfoWindow({
						content		: $marker.html()
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

	/*
	*  center_map
	*
	*  This function will re-center the map if multiple markers set.
	*
	*  @param	plot (Google Map object)
	*  @param   zoom zoom level.
	*  @return	n/a
	*/
	function center_map( plot, zoom ) {
		// set bounds.
		var bounds = new google.maps.LatLngBounds();

		// loop through all markers and create bounds
		$.each( plot.locations, function( i, marker ) {
			console.log( marker );
			var latlng = new google.maps.LatLng( marker.position.lat(), marker.position.lng() );
			bounds.extend( latlng );
		});

		// only 1 marker?
		if ( plot.locations.length == 1 ) {
			// set center of map
			plot.setCenter( bounds.getCenter() );
			plot.setZoom( zoom );
		} else {
			// fit to bounds
			plot.fitBounds( bounds );
		}
	}

})(jQuery);