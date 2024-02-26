(function () {
	(function() {
		var containers = document.querySelectorAll( 'div.mc-gmap-markers' );
		containers.forEach((el) => {
			mc_map( el );
		});

		var location_list = document.querySelectorAll( 'div.mc-gmap-location-list .mc-location-details' );
		location_list.forEach((el) => {
			var address = el.querySelector( '.sub-address' );
			var heading = el.querySelector( '.adr .org' );
			var id      = el.getAttribute( 'id' );
			address.style.display = 'none';
			address.setAttribute( 'id', 'control-' + id );

			var button = document.createElement( 'button' );
			button.type = 'button';
			button.innerHTML = gmaps.toggle;
			button.className =  'toggle-locations';
			button.setAttribute( 'aria-controls', 'control-' + id );
			button.setAttribute( 'aria-expanded', false );
			heading.insertAdjacentElement( 'afterbegin', button );
		});

		var toggles = document.querySelectorAll( 'button.toggle-locations' );
		toggles.forEach((el) => {
			el.addEventListener( 'click', function() {
				var target  = el.getAttribute( 'aria-controls' );
				var address = document.getElementById( target );
				var visible = ( address.style['display'] == 'none' ) ? false : true;
				if ( ! visible ) {
					address.style.display = 'block';
					this.setAttribute( 'aria-expanded', true );
					this.querySelector( '.dashicons' ).classList.add( 'dashicons-arrow-down' );
					this.querySelector( '.dashicons' ).classList.remove( 'dashicons-arrow-right' );
				} else {
					address.style.display = 'none';
					this.setAttribute( 'aria-expanded', false );
					this.querySelector( '.dashicons' ).classList.remove( 'dashicons-arrow-down' );
					this.querySelector( '.dashicons' ).classList.add( 'dashicons-arrow-right' );
				}
			});
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
		const $markers = el.querySelectorAll( '.marker' );
		const count    = $markers.length;
		let mapType  = el.getAttribute( 'data-maptype' );
		// vars
		const args = {
			center    : {lng: 0.0000, lat: 0.0000},
			mapTypeId : mapType
		};
	
		// create map
		const plot   = new google.maps.Map( el, args );
		const bounds = new google.maps.LatLngBounds();

		// add markers
		$markers.forEach((marker) => {
			add_marker( marker, plot, bounds );
		});

		if ( count > 25 ) {
			// If there's a large number of locations, allow the bounds to extend outside the minimum area.
			const extendPoint = new google.maps.LatLng( bounds.getNorthEast().lat() - 0.01, bounds.getNorthEast().lng() - 0.01 );
			bounds.extend( extendPoint );
		}

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
		let latlng = new google.maps.LatLng( $marker.getAttribute('data-lat'), $marker.getAttribute('data-lng') );
		let marker  = null;
		// Geocoder
		if ( '' == $marker.getAttribute( 'data-lat' ) || '' == $marker.getAttribute( 'data-lng' ) ) {
			const geocoder = new google.maps.Geocoder();
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

			latlng = new google.maps.LatLng( marker.position.lat(), marker.position.lng() );
			bounds.extend( latlng );
			// If current bounds are too tight, add .005 degrees and zoom out. (~1/2 mile).
			if ( bounds.getNorthEast().equals( bounds.getSouthWest() ) ) {
				const extendPoint = new google.maps.LatLng( bounds.getNorthEast().lat() + 0.005, bounds.getNorthEast().lng() + 0.005 );
				bounds.extend( extendPoint );
			}
			plot.fitBounds(bounds);

			// if marker contains HTML, add it to an infoWindow
			let content = $marker.innerHTML;
			if ( content ) {
				// create info window
				const infowindow = new google.maps.InfoWindow({
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
		let address = $marker.getAttribute( 'data-address' );
		let marker = null;
		marker = geocoder.geocode({'address': address}, function(results, status) {
			if ( status === 'OK' ) {
				plot.setCenter( results[0].geometry.location );
				marker = new google.maps.Marker({
					map : plot,
					position : results[0].geometry.location,
					clickable : true,
					title : $marker.getAttribute( 'data-title' ),
				});

				let latlng = new google.maps.LatLng( marker.position.lat(), marker.position.lng() );
				bounds.extend( latlng );
				// If current bounds are too tight, add .005 degrees and zoom out. (~1/2 mile).
				if ( bounds.getNorthEast().equals( bounds.getSouthWest() ) ) {
					const extendPoint = new google.maps.LatLng( bounds.getNorthEast().lat() + 0.005, bounds.getNorthEast().lng() + 0.005 );
					bounds.extend( extendPoint );
				}
				plot.fitBounds(bounds);

				// if marker contains HTML, add it to an infoWindow
				let content = $marker.innerHTML;
				if ( content ) {
					// create info window
					const infowindow = new google.maps.InfoWindow({
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