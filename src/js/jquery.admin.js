jQuery(document).ready(function ($) {
	$('#e_schedule').on( 'click', '.add_field', function() {
		$('#event_span').show();
		var num = $('.datetime-template').length; // how many sets of input fields we have.
		var newNum = new Number(num + 1);   // the numeric ID of the new input field being added.
		// create the new element via clone(), and manipulate it's ID using newNum value.
		var newElem = $('#event' + num ).clone().attr('id', 'event' + newNum);
		var oldElem = $('#event' + num );
		oldElem.find( '.buttons' ).hide();
		newElem.find( '.number_of' ).text( num );
		// insert the new element after the last "duplicatable" input field.
		$( '#event' + num ).after(newElem);
		// Update id & for relationships.
		var inputs = newElem.find( 'input' );
		var firstInput = newElem.find( '.event-begin' ).trigger( 'focus' );
		var labels = newElem.find( 'label' );
		inputs.each(function() {
			var id = $(this).attr('id');
			newId = id + newNum;
			$(this).attr( 'id', newId ).prop( 'disabled', false );
		});
		labels.each(function() {
			var forVal = $(this).attr('for');
			newFor = forVal + newNum;
			$(this).attr( 'for', newFor );
		});
		// enable the "remove" button.
		$('.del_field').removeAttr('disabled');
		// business rule: you can only add 40 occurrences.
		if ( newNum == 40 ) {
			$('.add_field').attr('disabled', 'disabled');
		}
	});

	$('#e_schedule').on( 'click', '.del_field', function() {
		var num = $('.datetime-template').length; // how many "duplicatable" input fields we currently have.
		$('#event' + num).remove(); // remove the last element.
		$('#event' + (num - 1) ).find( '.buttons' ).show();
		$('#event' + (num - 1) ).find( 'input' ).trigger( 'focus' );
		// enable the "add" button.
		$('.add_field').removeAttr('disabled');
		// if only one element remains, disable the "remove" button.
		if ( num - 1 == 1 ) {
			$('.del_field').attr('disabled', 'disabled');
		}
		$('#event_span').hide();
	});

	// Set default conditions.
	$( '#del_field' ).attr('disabled', 'disabled');
	$( '#event_span' ).hide();
	$( '.mc-actions input[type="submit"]' ).attr( 'disabled', 'disabled' );

	$(".selectall").on( 'click', function() {
		var checked_status = $(this).prop('checked');
		if ( checked_status ) {
			// Activate actions on bulk checked.
			$( '.mc-actions input[type="submit"]' ).removeAttr( 'disabled' );
		} else {
			$( '.mc-actions input[type="submit"]' ).attr( 'disabled', 'disabled' );
		}
		var checkbox_name  = $(this).attr('data-action');
		$('input[name="' + checkbox_name + '[]"]').each( function() {
			$(this).prop('checked', checked_status);
		});
	});


	$( '.row-actions' ).on( 'focus', 'a', function() {
		$( this ).parent( '.row-actions' ).css( { 'left' : 'auto' } );
	});

	$( '.row-actions' ).on( 'blur', 'a', function() {
		$( this ).parent( '.row-actions' ).css( { 'left' : '-999em' } );
	});

	$( '#my-calendar-admin-table input' ).on( 'change', function (e) {
		var checked_status = $(this).prop('checked');
		if ( checked_status ) {
			$( '.mc-actions input[type="submit"]' ).removeAttr( 'disabled' );
		} else {
			checkboxes = $( '#my-calendar-admin-table input:checked' );
			if ( checkboxes.length == 0 ) {
				$( '.mc-actions input[type="submit"]' ).attr( 'disabled', 'disabled' );
			}
		}
	});

	var publishText = $( 'input[name=save]' ).val();
	$( '#e_approved' ).on( 'change', function (e) {
		var event_status = $(this).val();
		if ( event_status == 0 ) {
			$( 'input[name=save]' ).val( mcAdmin.draftText );
		} else {
			$( 'input[name=save]' ).val( publishText );
		}
	});

	var firstItem = window.location.hash;
	if ( firstItem ) {
		showPanel( firstItem );
	} else {
		firstItem = $( '.mc-tabs .tabs' ).attr( 'data-default' );
		if ( 'undefined' !== typeof( firstItem ) ) {
			showPanel( firstItem );
		}
	}
	var tabs = document.querySelectorAll('.mc-tabs [role=tab]'); //get all role=tab elements as a variable
	for (i = 0; i < tabs.length; i++) {
		tabs[i].addEventListener('click', showTabPanel);
	} //add click event to each tab to run the showTabPanel function
	/**
	 * Activate a panel from the click event.
	 *
	 * @param event Click event.
	 */
	function showTabPanel(e) {
		var tabs2 = document.querySelectorAll('.mc-tabs [role=tab]'); //get tabs
		for (i = 0; i < tabs2.length; i++) {
			tabs2[i].setAttribute('aria-selected', 'false');
			tabs2[i].setAttribute('style', 'font-weight:normal');
		} // reset all tabs to aria-selected=false and normal font weight
		e.target.setAttribute('aria-selected', 'true'); //set aria-selected=true for clicked tab
		var tabPanelToOpen = e.target.getAttribute('aria-controls');
		var tabPanels = document.querySelectorAll('[role=tabpanel]'); //get all tabpanels
		for (i = 0; i < tabPanels.length; i++) {
			tabPanels[i].style.display = "none";
		} // hide all tabpanels
		window.location.hash = tabPanelToOpen;
		document.getElementById(tabPanelToOpen).style.display = "block"; //show tabpanel
	}

	/**
	 * Activate a panel from panel ID.
	 *
	 * @param string hash Item ID.
	 */
	function showPanel(hash) {
		var id = hash.replace( '#', '' );
		var control = $( 'button[aria-controls=' + id + ']' );
		var tabs2 = document.querySelectorAll('.mc-tabs [role=tab]'); //get tabs
		for (i = 0; i < tabs2.length; i++) {
			tabs2[i].setAttribute('aria-selected', 'false');
			tabs2[i].setAttribute('style', 'font-weight:normal');
		} //reset all tabs to aria-selected=false and normal font weight
		control.attr('aria-selected', 'true'); //set aria-selected=true for clicked tab
		var tabPanels = document.querySelectorAll('[role=tabpanel]'); //get all tabpanels
		for (i = 0; i < tabPanels.length; i++) {
			tabPanels[i].style.display = "none";
		}
		document.getElementById(id).style.display = "block"; //show tabpanel
	}
	// Arrow key handlers.
	$('.mc-tabs [role=tablist]').keydown(function(e) {
		if (e.keyCode == 37) {
			$("[aria-selected=true]").prev().trigger('click').trigger('focus');
			e.preventDefault();
		}
		if (e.keyCode == 38) {
			$("[aria-selected=true]").prev().trigger('click').trigger('focus');
			e.preventDefault();
		}
		if (e.keyCode == 39) {
			$("[aria-selected=true]").next().trigger('click').trigger('focus');
			e.preventDefault();
		}
		if (e.keyCode == 40) {
			$("[aria-selected=true]").next().trigger('click').trigger('focus');
			e.preventDefault();
		}
	});

	$( '#mc-generator .custom' ).hide();
	$( '#mc-generator select[name=type]' ).on( 'change', function () {
		var selected = $( this ).val();
		if ( selected == 'custom' ) {
			$( '#mc-generator .custom' ).show();
		} else {
			$( '#mc-generator .custom' ).hide();
		}
	});

	$( '#my-calendar-generate select[name=ltype]' ).on( 'change', function(e) {
		var ltype = $( this ).val();
		if ( ltype != '' ) {
			$( '#mc-generator input[name=lvalue]' ).prop( 'disabled', false ).prop( 'required', true );
		} else {
			$( '#mc-generator input[name=lvalue]' ).prop( 'disabled', true ).prop( 'required', false );
		}
	});


	$('#mc-sortable').sortable({
		placeholder: 'mc-ui-state-highlight',
		update: function (event, ui) {
			$('#mc-sortable-update').html( 'Submit form to save changes' );
		}
	});
	$('#mc-sortable .up').on('click', function (e) {
		var parentEls = $( this ).parents().map(function() { return this.tagName; } ).get();
		var parentLi  = $.inArray( 'LI', parentEls );
		if ( 1 == parentLi ) {
			$(this).parents('li').insertBefore($(this).parents('li').prev());
			$( '#mc-sortable li' ).removeClass( 'mc-updated' );
			$(this).parents('li').addClass( 'mc-updated' );
		} else {
			$(this).parents('tr').insertBefore($(this).parents('tr').prev());
			$( '#mc-sortable tr' ).removeClass( 'mc-updated' );
			$(this).parents('tr').addClass( 'mc-updated' );
		}
	});
	$('#mc-sortable .down').on('click', function (e) {
		var parentEls = $( this ).parents().map(function() { return this.tagName; } ).get();
		var parentLi  = $.inArray( 'LI', parentEls );
		if ( 1 == parentLi ) {
			$(this).parents('li').insertAfter($(this).parents('li').next());
			$( '#mc-sortable li' ).removeClass( 'mc-updated' );
			$(this).parents('li').addClass( 'mc-updated' );
		} else {
			$(this).parents('tr').insertAfter($(this).parents('tr').next());
			$( '#mc-sortable tr' ).removeClass( 'mc-updated' );
			$(this).parents('tr').addClass( 'mc-updated' );
		}
	});
});

var mediaPopup = '';
(function ($) {
	"use strict";
	$(function () {
		/**
		 * Clears any existing Media Manager instances
		 *
		 * @author Gabe Shackle <gabe@hereswhatidid.com>
		 * @modified Joe Dolson <plugins@joedolson.com>
		 * @return void
		 */
		function clear_existing() {
			if (typeof mediaPopup !== 'string') {
				mediaPopup.detach();
				mediaPopup = '';
			}
		}

		$('.mc-image-upload')
			.on('click', '.textfield-field', function (e) {
				e.preventDefault();
				var $self = $(this),
					$inpField = $self.parent('.field-holder').find('#e_image'),
					$idField = $self.parent('.field-holder').find('#e_image_id'),
					$displayField = $self.parent('.field-holder').find('.event_image');
				clear_existing();
				mediaPopup = wp.media({
					multiple: false, // add, reset, false.
					title: 'Choose an Uploaded Document',
					button: {
						text: 'Select'
					}
				});

				mediaPopup.on('select', function () {
					var selection = mediaPopup.state().get('selection'),
						id = '',
						img = '',
						height = '',
						width = '';
					if (selection) {
						id = selection.first().attributes.id;
						height = mcAdmin.thumbHeight;
						width = ( ( selection.first().attributes.width ) / ( selection.first().attributes.height ) ) * height;
						img = "<img src='" + selection.first().attributes.url + "' width='" + width + "' height='" + height + "' />";
						$inpField.val(selection.first().attributes.url);
						$idField.val(id);
						$displayField.html(img);
					}
				});
				mediaPopup.open();
			})
	});
})(jQuery);