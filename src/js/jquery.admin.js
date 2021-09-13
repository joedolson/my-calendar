jQuery(document).ready(function ($) {
	$( '.preview-link' ).hide();
	var active = $( '.preview-link' ).attr( 'data-css' );
	$( '#mc_css_file' ).on( 'change', function(e) {
		var current = $( this ).val();
		if ( current !== active ) {
			var current_url = $( '.preview-link' ).prop( 'href' );
			var current_css = $( '.preview-link' ).attr( 'data-css' );
			var new_url     = current_url.replace( current_css, current );
			$( '.preview-link' ).prop( 'href', new_url ).attr( 'data-css', current ).show();
		}
	});

	$('#e_schedule').on( 'click', '.add_field', function() {
		$('#event_span').show();
		var num    = $('.datetime-template').length; // how many sets of input fields we have.
		var newNum = new Number(num + 1);   // the numeric ID of the new input field being added.
		// create the new element via clone(), and manipulate it's ID using newNum value.
		var newElem = $('#event' + num ).clone().attr('id', 'event' + newNum);
		$( newElem ).find( 'input' ).prop( 'disabled', false );
		$( newElem ).find( 'button.restore_field' ).removeClass( 'restore_field' ).addClass( 'del_field' ).text( mcAdmin.deleteButton );
		var oldElem = $('#event' + num );
		oldElem.find( '.buttons' ).hide();
		$( '#event1' ).hide();
		newElem.find( '.number_of' ).text( num );
		// insert the new element after the last "duplicatable" input field.
		$( '#event' + num ).after(newElem);
		// Update id & for relationships.
		var inputs      = newElem.find( 'input' );
		var timeControl = newElem.find( '.event-time' );
		var startControl = newElem.find( '.event-begin' );
		var endControl  = newElem.find( '.event-end' );
		var initialTime = $( '#mc_event_time' ).val();
		var initialEnd  = $( '#mc_event_endtime' ).val();
		var initialStart = oldElem.find( '.event-begin' ).val();
		if ( ! initialStart ) {
			initialStart = document.querySelector( '[identifier="mc_event_enddate"]' ).value;
		}
		endControl.val( initialEnd );
		startControl.val( initialStart );
		timeControl.val( initialTime ).trigger( 'focus' );
		var labels     = newElem.find( 'label' );
		inputs.each(function() {
			var id = $(this).attr('id');
			newId  = id + newNum;
			$(this).attr( 'id', newId ).prop( 'disabled', false );
		});
		labels.each(function() {
			var forVal = $(this).attr('for');
			newFor     = forVal + newNum;
			$(this).attr( 'for', newFor );
		});
		// You can only add up to 40 occurrences at a time.
		if ( newNum == 40 ) {
			$('.add_field').attr('disabled', 'disabled');
		}
	});

	$('#e_schedule').on( 'click', '.del_field', function() {
		var id = $( this ).parents( 'li' ).attr( 'id' );
		$( '#' + id + ' input' ).prop( 'disabled', true ).removeClass( 'enabled' ).addClass( 'disabled' );
		$('.add_field').prop( 'disabled', false );
		$( this ).removeClass( 'del_field' ).addClass( 'restore_field' ).removeClass( 'button-delete' ).text( mcAdmin.restoreButton );
		$( '#' + id ).find( '.remove_field' ).removeClass( 'hidden' );
		$( this ).parents( 'div' ).addClass( 'multiple' );
	});

	$( '#e_schedule' ).on( 'click', '.remove_field', function() {
		var num = $('.datetime-template.enabled').length;
		$( this ).parents( 'div' ).removeClass( 'multiple' );
		$( this ).parents( 'li' ).remove();
		// if only one element left, hide event span checkbox & show original add occurrence button.
		if ( num - 1 <= 1 ) {
			$('#event_span').hide();
			$('#event1, #event1 .buttons' ).show();
		}
	});

	$('#e_schedule').on( 'click', '.restore_field', function() {
		var id  = $( this ).parents( 'li' ).attr( 'id' );
		$( this ).parents( 'div' ).removeClass( 'multiple' );
		$( this ).removeClass( 'restore_field' ).addClass( 'del_field button-delete' ).text( mcAdmin.deleteButton );
		$( '#' + id ).find( '.remove_field' ).addClass( 'hidden' );
		$( '#' + id + ' input' ).prop( 'disabled', false ).removeClass( 'disabled' ).addClass( 'emabled' );
	});

	var recurrences = $( '.disable-recurrences' );
	recurrences.find( 'fieldset' ).hide();
	recurrences.find( 'fieldset input, fieldset select, fieldset duet-date-picker' ).prop( 'disabled', true );
	$( '.enable-repetition' ).on( 'click', function() {
		var expanded = $( this ).attr( 'aria-expanded' );
		if ( 'false' !== expanded ) {
			$( this ).attr( 'aria-expanded', 'false' );
			$( this ).find( '.dashicons' ).removeClass( 'dashicons-arrow-down' ).addClass( 'dashicons-arrow-right' );
			recurrences.find( 'fieldset' ).hide();
			recurrences.find( 'fieldset input, fieldset select, fieldset duet-date-picker' ).prop( 'disabled', true );
		} else {
			$( this ).attr( 'aria-expanded', 'true' );
			$( this ).find( '.dashicons' ).removeClass( 'dashicons-arrow-right' ).addClass( 'dashicons-arrow-down' );
			recurrences.find( 'fieldset' ).show();
			recurrences.find( 'fieldset input, fieldset select, fieldset duet-date-picker' ).prop( 'disabled', false );
		}
	});

	// Set default conditions.
	$( '#event_span' ).hide();
	$( '.mc-actions input[type="submit"]' ).attr( 'disabled', 'disabled' );

	$( '.selectall' ).on( 'click', function() {
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

	$( '#mc_bulk_actions' ).on( 'change', function (e) {
		var value = $( this ).val();
		$( '#mc_bulk_actions_footer' ).val( value );
	});

	$( '#mc_bulk_actions_footer' ).on( 'change', function (e) {
		var value = $( this ).val();
		$( '#mc_bulk_actions' ).val( value );
	});

	$( '#my-calendar-admin-table input, .mc-actions input' ).on( 'change', function (e) {
		var checked_status = $(this).prop('checked');
		var groups_table   = $(this).parents( 'table' ).hasClass( 'mc-groups-table' );
		var checkboxes     = $( '#my-calendar-admin-table input:checked' );
		var checked        = checkboxes.length;
		if ( checked_status ) {
			if ( ( groups_table && checked > 1 ) || ! groups_table ) {
				$( '.mc-actions input[type="submit"]' ).removeAttr( 'disabled' );
			}
		} else {
			if ( ( groups_table && checked < 2 ) || ( ! groups_table && checked == 0 ) ) {
				$( '.mc-actions input[type="submit"]' ).attr( 'disabled', 'disabled' );
			}
		}
	});

	var publishText = $( 'input[name=save]' ).val();
	$( '#e_approved' ).on( 'change', function (e) {
		var event_status = $(this).val();
		if ( publishText == mcAdmin.publishText ) {
			if ( event_status == 0 ) {
				$( 'input[name=save]' ).val( mcAdmin.draftText );
			} else {
				$( 'input[name=save]' ).val( publishText );
			}
		}
	});

	$( '.new-event-category' ).hide();
	$( '#event_category_new' ).on( 'click', function() {
		var checked_status = $(this).prop('checked');
		if ( checked_status ) {
			$( '.new-event-category' ).show();
			$( '#event_category_name' ).prop( 'disabled', false );
		} else {
			$( '.new-event-category' ).hide();
			$( '#event_category_name' ).prop( 'disabled', true );
		}
	});

	// display notice informing users of lack of support for recur month by day.
	$( '#e_recur' ).on( 'change', function (e) {
		var recur = $(this).val();
		if ( recur != 'S' ) {
			$( 'duet-date-picker[identifier=e_repeats]' ).attr( 'required', 'true' );
		} else {
			$( 'duet-date-picker[identifier=e_repeats]' ).removeAttr( 'required' );
		}
		if ( recur == 'U' ) {
			$( '#e_every' ).attr( 'max', 1 ).val( 1 );
		} else {
			$( '#e_every' ).attr( 'max', 99 );
		}
	});

	var is_checked = $( 'input[id="e_allday"]' ).prop( "checked" );
	if ( ! is_checked ) {
		$( '.event_time_label' ).hide();
	}

	$( 'input[id="e_allday"]' ).change( function() {
		var checked = $(this).prop( "checked" );
		if ( checked ) {
			$( '.event_time_label' ).show();
		} else {
			$( '.event_time_label' ).hide();
		}
	});

	var hide_end_checked = $( 'input[id="e_hide_end"]' ).prop( "checked" );
	if ( hide_end_checked ) {
		$( 'label[for=mc_event_endtime] span' ).show();
	}

	$( 'input[id="e_hide_end"]' ).change( function() {
		var checked = $(this).prop( "checked" );
		if ( checked ) {
			$( 'label[for=mc_event_endtime] span' ).show();
		} else {
			$( 'label[for=mc_event_endtime] span' ).hide();
		}
	});
	var firstItem = window.location.hash;
	var tabGroups = document.querySelectorAll( '.mc-tabs' );

	for ( var i = 0; i < tabGroups.length; i++ ) {
		var panel = $( tabGroups[i] ).find( firstItem );
		if ( panel.length !== 0 ) {
			showPanel( firstItem );
		} else {
			firstItem = $( tabGroups[i] ).find( '[role=tablist]' ).attr( 'data-default' );
			showPanel( firstItem );
		}
	}
	var tabs = document.querySelectorAll('.mc-tabs [role=tab]'); //get all role=tab elements as a variable
	for ( var i = 0; i < tabs.length; i++) {
		tabs[i].addEventListener( 'click', showTabPanel );
		tabs[i].addEventListener( 'keydown', handleKeyPress );
	} //add click event to each tab to run the showTabPanel function
	/**
	 * Activate a panel from the click event.
	 *
	 * @param event Click event.
	 */
	function showTabPanel(e) {
		var tabContainer = $( e.currentTarget ).closest( '.tabs' );
		var tabs         = tabContainer.find( '[role=tab]' );
		var container    = $( e.currentTarget ).closest( '.mc-tabs' );
		var inside       = $( e.currentTarget ).parents( '.inside' );
		if ( inside.length == 0 ) {
			var tabPanels = container.find( '.ui-sortable > [role=tabpanel]' );
		} else {
			var tabPanels = container.find( '[role=tabpanel]' );
		}
		for ( var i = 0; i < tabs.length; i++) {
			tabs[i].setAttribute('aria-selected', 'false');
		} // reset all tabs to aria-selected=false and normal font weight
		e.target.setAttribute('aria-selected', 'true'); //set aria-selected=true for clicked tab
		var tabPanelToOpen = e.target.getAttribute('aria-controls');
		for ( var i = 0; i < tabPanels.length; i++) {
			tabPanels[i].style.display = "none";
		} // hide all tabpanels
		// If this is an inner tab panel, don't set the window location.
		if ( inside.length == 0 ) {
			window.location.hash = tabPanelToOpen;
		}
		document.getElementById(tabPanelToOpen).style.display = "block"; //show tabpanel
		$( '#' + tabPanelToOpen ).attr( 'tabindex', '-1' ).trigger( 'focus' );
	}

	/**
	 * Activate a panel from panel ID.
	 *
	 * @param string hash Item ID.
	 */
	function showPanel(hash) {
		var id           = hash.replace( '#', '' );
		var control      = $( 'button[aria-controls=' + id + ']' );
		var tabContainer = $( hash ).closest( '.tabs' );
		var tabs         = tabContainer.find( '[role=tab]' );
		var container    = $( hash ).closest( '.mc-tabs' );
		var tabPanels    = container.find( '[role=tabpanel]' );
		for ( var i = 0; i < tabs.length; i++) {
			tabs[i].setAttribute('aria-selected', 'false');
		} //reset all tabs to aria-selected=false and normal font weight
		control.attr('aria-selected', 'true'); //set aria-selected=true for clicked tab
		for ( var i = 0; i < tabPanels.length; i++) {
			tabPanels[i].style.display = "none";
		}
		var currentPanel = document.getElementById(id);
		if ( null !== currentPanel ) {
			currentPanel.style.display = "block"; //show tabpanel
		}
	}

	// Arrow key handlers.
	function handleKeyPress(e) {
		if (e.keyCode == 37) { // left arrow
			$( e.currentTarget ).prev().trigger('click').trigger('focus');
			e.preventDefault();
		}
		if (e.keyCode == 38) { // up arrow
			$( e.currentTarget ).prev().trigger('click').trigger('focus');
			e.preventDefault();
		}
		if (e.keyCode == 39) { // right arrow
			$( e.currentTarget ).next().trigger('click').trigger('focus');
			e.preventDefault();
		}
		if (e.keyCode == 40) { // down arrow.
			$( e.currentTarget ).next().trigger('click').trigger('focus');
			e.preventDefault();
		}
	};

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

	$('#mc-sortable .hide').on('click', function (e) {
		var disabled = $( this ).find( '.dashicons' ).hasClass( 'dashicons-hidden' );
		var current  = $( this ).parents( 'li' );
		if ( disabled ) {
			current.removeClass( 'mc-hidden' ).addClass( 'mc-visible' );
			current.find( 'input[type=hidden]' ).prop( 'disabled', false );
			$( this ).find( '.dashicons' ).removeClass( 'dashicons-hidden' ).addClass( 'dashicons-visibility' );
			wp.a11y.speak( 'Item shown' );
		} else {
			current.addClass( 'mc-hidden' ).removeClass( 'mc-visible' );
			current.find( 'input[type=hidden]' ).prop( 'disabled', true );
			$( this ).find( '.dashicons' ).removeClass( 'dashicons-visibility' ).addClass( 'dashicons-hidden' );
			wp.a11y.speak( 'Item hidden' );
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
		$( this ).trigger( 'focus' );
		wp.a11y.speak( 'Item moved up' );
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
		$( this ).trigger( 'focus' );
		wp.a11y.speak( 'Item moved down' );
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
					$inpField = document.querySelector('#e_image'),
					$idField = document.querySelector('#e_image_id'),
					$displayField = document.querySelector('.event_image');
				clear_existing();
				mediaPopup = wp.media({
					multiple: false, // add, reset, false.
					title: 'Choose an Image',
					button: {
						text: 'Select'
					}
				});

				mediaPopup.on('select', function () {
					var selection = mediaPopup.state().get('selection'),
						id = '',
						img = '',
						height = '',
						width = '',
						alt = '';
					if (selection) {
						id                      = selection.first().attributes.id;
						height                  = mcAdmin.thumbHeight;
						width                   = Math.round( ( ( selection.first().attributes.width ) / ( selection.first().attributes.height ) ) * height );
						alt                     = selection.first().attributes.alt;
						img                     = "<img id='event_image' src='" + selection.first().attributes.url + "' width='" + width + "' height='" + height + "' alt='" + alt + "' />";
						$inpField.value         = selection.first().attributes.url;
						$idField.value          = id;
						$displayField.innerHTML = img;
					}
				});
				mediaPopup.open();
			})
	});
})(jQuery);