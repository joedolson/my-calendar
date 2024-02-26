jQuery(document).ready(function ($) {
	let r = document.querySelector('.mc-main');

	$('.mc-color-input').wpColorPicker({
		change:	function( event, ui ) {
			let color    = ui.color.toString();
			let variable = event.target.getAttribute( 'data-variable' );
			r.style.setProperty( variable, color );
		},
	});
});