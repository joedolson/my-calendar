jQuery(document).ready(function ($) {
	var r = document.querySelector('.mc-main');

	$('.mc-color-input').wpColorPicker({
		change:	function( event, ui ) {
			var color    = ui.color.toString();
			var variable = event.target.getAttribute( 'data-variable' );
			console.log( color, variable );
			r.style.setProperty( variable, color );
		},
	});
});