(function( $ ) { 'use strict';
	/* https://autocomplete.trevoreyre.com/#/javascript-component?id=getresultvalue */
	if ( typeof( mclocations ) !== 'undefined' ) {
		new Autocomplete( '#mc-locations-autocomplete', {
			search: input => {
				const url = mclocations.ajaxurl;
				return new Promise( resolve => {
					if (input.length < 3) {
						return resolve([])
					}

					const data = new FormData();
					data.append( 'action', mclocations.action );
					data.append( 'security', mclocations.security );
					data.append( 'data', input );
					const response = fetch(url, {
						method: 'POST',
						credentials: 'same-origin',
						body: data
					}).then(response => response.json())
					.then(data => {
						resolve(data.response)
					})
				})
			},
			onSubmit: result => {
				let location_field = document.getElementById( 'mc_event_location_value' );

				location_field.value = result.location_id;
				$( location_field ).trigger( 'change' );
			},
			getResultValue: result => ( '' !== result.location_label ) ? result.location_label : '#' + result.location_id
		});
	}

	if ( typeof( mcpages ) !== 'undefined' ) {
		/* https://autocomplete.trevoreyre.com/#/javascript-component?id=getresultvalue */
		new Autocomplete( '#mc-pages-autocomplete', {
			search: input => {
				const url = mcpages.ajaxurl;
				return new Promise( resolve => {
					if (input.length < 3) {
						return resolve([])
					}

					const data = new FormData();
					data.append( 'action', mcpages.action );
					data.append( 'security', mcpages.security );
					data.append( 'data', input );
					const response = fetch(url, {
						method: 'POST',
						credentials: 'same-origin',
						body: data
					}).then(response => response.json())
					.then(data => {
						resolve(data.response)
					})
				})
			},
			onSubmit: result => {
				let pages_field = document.getElementById( 'mc_uri_id' );

				pages_field.value = result.post_id;
				$( pages_field ).trigger( 'change' );
			},
			getResultValue: result => ( '' !== result.post_title ) ? result.post_title : '#' + result.post_id
		});
	}

	if ( typeof( mcicons ) !== 'undefined' ) {
		/* https://autocomplete.trevoreyre.com/#/javascript-component?id=getresultvalue */
		new Autocomplete( '#mc-icons-autocomplete', {
			search: input => {
				const url = mcicons.ajaxurl;
				return new Promise( resolve => {
					if (input.length < 2) {
						return resolve([])
					}

					const data = new FormData();
					data.append( 'action', mcicons.action );
					data.append( 'security', mcicons.security );
					data.append( 'data', input );
					const response = fetch(url, {
						method: 'POST',
						credentials: 'same-origin',
						body: data
					}).then(response => response.json())
					.then(data => {
						resolve(data.response)
					})
				})
			},
			onSubmit: result => {
				let icon_field = document.getElementById( 'mc_category_icon' );

				icon_field.value = result.filename;
				$( icon_field ).trigger( 'change' );
			},
			renderResult: (result, props) => `
				<li ${props}>${result.svg} ${result.filename}</li>
			`,
			getResultValue: result => result.filename
		});
	}

	if ( typeof( mccountries ) !== 'undefined' ) {
		/* https://autocomplete.trevoreyre.com/#/javascript-component?id=getresultvalue */
		new Autocomplete( '#mc-countries-autocomplete', {
			search: input => {
				return new Promise( resolve => {
					if (input.length < 2) {
						return resolve([])
					}

					const data = new FormData();
					data.append( 'action', mccountries.action );
					data.append( 'security', mccountries.security );
					data.append( 'data', input );
					const response = fetch(mccountries.ajaxurl, {
						method: 'POST',
						credentials: 'same-origin',
						body: data
					}).then(response => response.json())
					.then(data => {
						resolve(data.response)
					})
				})
			},
			onSubmit: result => {
				let location_field = document.getElementById( 'e_country' );

				location_field.value = result.country;
				$( location_field ).trigger( 'change' );
			},
			renderResult: (result, props) => `
				<li ${props}>${result.country}</li>
			`,
			getResultValue: result => result.country
		});
	}

}(jQuery));