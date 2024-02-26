/**
 * @file Functionality for My Calendar contextual help.
 */

/* global tb_click, tb_remove, tb_position */

jQuery( function( $ ) {

	let tbWindow,
		$iframeBody,
		$tabbables,
		$firstTabbable,
		$lastTabbable,
		$focusedBefore = $(),
		$body = $( document.body );

	window.tb_position = function() {
		let width = $( window ).width(),
			H = $( window ).height() - ( ( 792 < width ) ? 240 : 160 ),
			W = ( 792 < width ) ? 772 : width - 20;

		tbWindow = $( '#TB_window' );

		if ( tbWindow.length ) {
			tbWindow.width( W ).height( H );
			$( '#TB_iframeContent' ).width( W ).height( H );
			tbWindow.css({
				'margin-left': '-' + parseInt( ( W / 2 ), 10 ) + 'px'
			});
			if ( typeof document.body.style.maxWidth !== 'undefined' ) {
				tbWindow.css({
					'top': '120px',
					'margin-top': '0'
				});
			}
		}

		return $( 'a.thickbox' ).each( function() {
			let href = $( this ).attr( 'href' );
			if ( ! href ) {
				return;
			}
			href = href.replace( /&width=[0-9]+/g, '' );
			href = href.replace( /&height=[0-9]+/g, '' );
			$(this).attr( 'href', href + '&width=' + W + '&height=' + ( H ) );
		});
	};

	$( window ).on( 'resize', function() {
		tb_position();
	});

	/*
	 * Custom events: when a Thickbox iframe has loaded and when the Thickbox
	 * modal gets removed from the DOM.
	 */
	$body
		.on( 'thickbox:iframe:loaded', tbWindow, function() {
			iframeLoaded();
		})
		.on( 'thickbox:removed', function() {
			// Set focus back to the element that opened the modal dialog.
			$focusedBefore.trigger( 'focus' );
		});

	function iframeLoaded() {
		let $iframe = tbWindow.find( '#TB_iframeContent' );

		// Get the iframe body.
		$iframeBody = $iframe.contents().find( 'body' );

		// Get the tabbable elements and handle the keydown event on first load.
		handleTabbables();

		// Set initial focus on the "Close" button.
		$firstTabbable.trigger( 'focus' );

		// Close the modal when pressing Escape.
		$iframeBody.on( 'keydown', function( event ) {
			if ( 27 !== event.which ) {
				return;
			}
			tb_remove();
		});
	}

	/*
	 * Get the tabbable elements and detach/attach the keydown event.
	 * Called after the iframe has fully loaded so we have all the elements we need.
	 * Called again each time a Tab gets clicked.
	 * @todo Consider to implement a WordPress general utility for this and don't use jQuery UI.
	 */
	function handleTabbables() {
		let $firstAndLast;
		// Get all the tabbable elements.
		$tabbables = $( ':tabbable', $iframeBody );
		// Our first tabbable element is always the "Close" button.
		$firstTabbable = tbWindow.find( '#TB_closeWindowButton' );
		// Get the last tabbable element.
		$lastTabbable = $tabbables.last();
		// Make a jQuery collection.
		$firstAndLast = $firstTabbable.add( $lastTabbable );
		// Detach any previously attached keydown event.
		$firstAndLast.off( 'keydown.my-calendar-help' );
		// Attach again the keydown event on the first and last focusable elements.
		$firstAndLast.on( 'keydown.my-calendar-help', function( event ) {
			constrainTabbing( event );
		});
	}

	// Constrain tabbing within the plugin modal dialog.
	function constrainTabbing( event ) {
		if ( 9 !== event.which ) {
			return;
		}

		if ( $lastTabbable[0] === event.target && ! event.shiftKey ) {
			event.preventDefault();
			$firstTabbable.trigger( 'focus' );
		} else if ( $firstTabbable[0] === event.target && event.shiftKey ) {
			event.preventDefault();
			$lastTabbable.trigger( 'focus' );
		}
	}

	/*
	 * Open the help modal.
	 */
	$( '.wrap' ).on( 'click', '.thickbox.my-calendar-contextual-help', function( e ) {
		let title = $( this ).data( 'title' );

		e.preventDefault();
		e.stopPropagation();

		// Store the element that has focus before opening the modal dialog, i.e. the control which opens it.
		$focusedBefore = $( this );

		tb_click.call(this);

		// Set ARIA role, ARIA label, and add a CSS class.
		tbWindow
			.attr({
				'role': 'dialog',
				'aria-label': wp.i18n.__( 'My Calendar Help' )
			})
			.addClass( 'my-calendar-help-modal' );

		// Set title attribute on the iframe.
		tbWindow.find( '#TB_iframeContent' ).attr( 'title', title );
	});

	let reset = document.querySelectorAll( '.reset-my-calendar' );
	if ( null !== reset ) {
		reset.forEach( (el) => {
			el.addEventListener( 'click', resetShortcode );
			function resetShortcode( e ) {
				let control    = e.target;
				const controls = document.querySelectorAll( '.mc-generator-inputs input, .mc-generator-inputs select' );
				for (i = 0; i < controls.length; i++) {
					switch ( controls[i].type ) {
						case 'password':
						case 'select-multiple':
						case 'select-one':
						case 'text':
						case 'email':
						case 'date':
						case 'url':
						case 'search':
						case 'textarea':
							controls[i].value = '';
							break;
						case 'checkbox':
						case 'radio':
							controls[i].checked = false;
							break;
					}
				}
				let shortcode = document.querySelectorAll( '.mc-shortcode-container' );
				shortcode.forEach( (el) => {
					el.value = '[' + control.getAttribute( 'data-type' ) + ']';
				});
			}
		});
	}
});