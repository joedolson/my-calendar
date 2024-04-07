jQuery(document).ready(function ($) {
	let r = document.querySelector('.mc-main');

	$('.mc-color-input').wpColorPicker({
		change:	function( event, ui ) {
			if ( null !== r ) {
				let color    = ui.color.toString();
				let variable = event.target.getAttribute( 'data-variable' );
				r.style.setProperty( variable, color );
				wp.a11y.speak( 'Preview updated' );
			}
		},
	});

	$('.mc-text-input').on( 'keyup', function(event) {
		let el = $( this );
		setTimeout( function() {
			let text = el.val();
			let variable = event.target.getAttribute( 'data-variable' );
			r.style.setProperty( variable, text );
			wp.a11y.speak( 'Preview updated' );
		}, 500, el, event );
	});

	var el = $(".my-calendar-style-preview");
	el.css("height", el.outerHeight()).find(".mc-main").stickOnScroll({
		footerElement: $(".mc-contrast-table"),
		bottomOffset: 20,
		topOffset: 34,
		setParentOnStick: true,
		stickClass: 'fix',
		setWidthOnStick: true,
	});
});