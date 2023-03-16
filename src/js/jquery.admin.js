function resizeIframe(iframe) {
	iframe.height = iframe.contentWindow.document.body.scrollHeight + "px";
}

jQuery(document).ready(function ($) {
	$( '#my-calendar' ).on( 'submit', function(e) {
		var unsubmitted = $( '#mc_unsubmitted' );
		unsubmitted.remove();
	});
	var form  = document.getElementById( 'my-calendar' );
	if ( form ) {
		var clean = [];
		var dirty = [];
		var elems = form.querySelectorAll( 'input, select' );
		elems.forEach((el) => {
			var val = el.value;
			el.addEventListener( 'keydown', function(e) {
				elems.forEach((el) => {
					var val = el.value;
					dirty.push(val);
				});
				var cleanSorted = clean.slice().sort();
				var dirtySorted = dirty.slice().sort();
				var equal = compareArrays( cleanSorted, dirtySorted );
				var unsubmitted = document.getElementById( 'mc_unsubmitted' );
				if ( ! equal && ! unsubmitted ) {
					unsubmitted = document.createElement( 'div' );
					unsubmitted.id = 'mc_unsubmitted';
					form.appendChild( unsubmitted );
				}
			});
			clean.push(val);
		});
	}

	function compareArrays( clean, dirty ) {
		clean.length === dirty.length && clean.every( function(value, index) {
			return value === dirty[index];
		});
	}

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
		$('.event_span').show();
		var num    = $('.datetime-template').length; // how many sets of input fields we have.
		var newNum = new Number(num + 1);   // the numeric ID of the new input field being added.
		// create the new element via clone(), and manipulate it's ID using newNum value.
		var newElem = $('#event' + num ).clone().attr('id', 'event' + newNum);
		$( newElem ).find( 'input' ).prop( 'disabled', false );
		$( newElem ).find( 'button.restore_field' ).removeClass( 'restore_field' ).addClass( 'del_field' ).text( mcAdmin.deleteButton );
		var oldElem = $('#event' + num );
		$( '#event1' ).hide();
		newElem.find( '.number_of' ).text( num );
		// insert the new element after the last "duplicatable" input field.
		$( '#event' + num ).after(newElem);
		// Update id & for relationships.
		var inputs       = newElem.find( 'input' );
		var timeControl  = newElem.find( '.event-time' );
		var startControl = newElem.find( '.event-begin' );
		var endControl   = newElem.find( '.event-end' );
		var initialTime  = $( '#mc_event_time' ).val();
		var initialEnd   = $( '#mc_event_endtime' ).val();
		var initialStart = oldElem.find( '.event-begin' ).val();
		if ( ! initialStart ) {
			initialStart = document.querySelector( '[identifier="mc_event_date"]' ).value;
		}
		if ( ! initialEnd ) {
			initialEnd = document.querySelector( '[identifier="mc_event_enddate"]' ).value;
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
		// You can only add up to 40 copies at a time.
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
			$('.event_span').hide();
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

	$( '#e_schedule' ).on( 'change', 'input', function() {
		recurrences.find( 'fieldset' ).show();
		recurrences.find( '.enable-repetition' ).attr( 'aria-expanded', 'true' );
		recurrences.find( '.enable-repetition .dashicons' ).addClass( 'dashicons-arrow-down' ).removeClass( 'dashicons-arrow-right' );
		recurrences.find( 'fieldset input, fieldset select, fieldset duet-date-picker' ).prop( 'disabled', false );
	});

	var addLocations = document.querySelector( '.add-location' );
	if ( null !== addLocations ) {
		var locationSelector = document.getElementById( 'l_preset' );
		var locationValue    = locationSelector.value;

		var controls = addLocations.getAttribute( 'aria-controls' );
		var fields   = document.getElementById( controls );
		fields.classList.add( 'hidden' );
		addLocations.addEventListener( 'click', function(e) {
			var expanded = this.getAttribute( 'aria-expanded' );
			if ( 'true' === expanded ) {
				locationSelector.value = locationValue;
				fields.classList.add( 'hidden' );
				this.setAttribute( 'aria-expanded', 'false' );
				this.firstChild.classList.add( 'dashicons-plus' );
				this.firstChild.classList.remove( 'dashicons-minus' );
			} else {
				locationSelector.value = 'none';
				fields.classList.remove( 'hidden' );
				this.setAttribute( 'aria-expanded', 'true' );
				this.firstChild.classList.add( 'dashicons-minus' );
				this.firstChild.classList.remove( 'dashicons-plus' );
			}
		});
	}

	var toggleInside = document.querySelector( '.toggle-inside' );
	if ( null !== toggleInside ) {
		var parentEl = toggleInside.parentNode.parentNode;
		var target   = parentEl.querySelector( '.inside' );
		target.classList.add( 'hidden' );
		toggleInside.addEventListener( 'click', function(e) {
			var expanded = this.getAttribute( 'aria-expanded' );
			if ( 'true' === expanded ) {
				target.classList.add( 'hidden' );
				this.setAttribute( 'aria-expanded', 'false' );
				this.firstChild.classList.add( 'dashicons-plus' );
				this.firstChild.classList.remove( 'dashicons-minus' );
			} else {
				target.classList.remove( 'hidden' );
				this.setAttribute( 'aria-expanded', 'true' );
				this.firstChild.classList.add( 'dashicons-minus' );
				this.firstChild.classList.remove( 'dashicons-plus' );
			}
		});
	}

	var viewDates = document.querySelector( '.toggle-dates' );
	if ( null !== viewDates ) {
		var addDates = document.getElementById( 'mc-view-scheduled-dates' );
		var container = document.getElementById( 'my-calendar' );
		var primary = document.querySelectorAll( '.button-primary' );

		addDates.classList.add( 'hidden' );
		viewDates.addEventListener( 'click', function(e) {
			var expanded = this.getAttribute( 'aria-expanded' );
			// If prior state is true, do these tasks.
			if ( 'true' === expanded ) {
				this.setAttribute( 'data-action', '' );
				container.classList.remove( 'disabled' );
				primary.forEach((el) => {
					el.disabled = false;
				});
				addDates.classList.add( 'hidden' );
				this.setAttribute( 'aria-expanded', 'false' );
				this.firstChild.classList.add( 'dashicons-arrow-right' );
				this.firstChild.classList.remove( 'dashicons-arrow-down' );
			} else {
				this.setAttribute( 'data-action', 'shiftforward' );
				primary.forEach((el) => {
					el.disabled = true;
				});
				container.classList.add( 'disabled' );
				addDates.classList.remove( 'hidden' );
				this.setAttribute( 'aria-expanded', 'true' );
				this.firstChild.classList.add( 'dashicons-arrow-down' );
				this.firstChild.classList.remove( 'dashicons-arrow-right' );
			}
		});
	}

	$(document).on( 'keydown', '#mc-scheduled-dates button',
		function(e) {
			var keycode = ( e.keyCode ? e.keyCode : e.which );
			var action  = $( ':focus' ).attr( 'data-action' );
			if ( ( !e.shiftKey && keycode == 9 ) && action == 'shiftback' ) {
				e.preventDefault();
				$( '.toggle-dates' ).trigger( 'focus' );
			}
			if ( ( e.shiftKey && keycode == 9 ) && action == 'shiftforward' ) {
				e.preventDefault();
				$( '[data-action=shiftback]' ).trigger( 'focus' );
			}
		});

	// Set default conditions.
	$( '.event_span' ).hide();
	$( '.mc-actions input[type="submit"]' ).attr( 'disabled', 'disabled' );

	$( '#mass_replace_on' ).on( 'click', function() {
		var checked_status = $(this).prop('checked');
		if ( checked_status ) {
			// Activate actions on bulk checked.
			$( '.mass-replace-container' ).show();
		} else {
			$( '.mass-replace-container' ).hide();
		}
	});

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

	$( '#my-calendar-admin-table input, .mc-actions input:not(#mass_replace_on)' ).on( 'change', function (e) {
		var checked_status = $(this).prop('checked');
		var groups_table   = $(this).parents( 'table' ).hasClass( 'mc-groups-table' );
		var checkboxes     = $( '#my-calendar-admin-table input:checked' );
		var checked        = checkboxes.length;
		if ( checked_status ) {
			if ( ( groups_table && checked > 1 ) || ! groups_table ) {
				$( '.mc-actions input[type="submit"]' ).removeAttr( 'disabled' );
				$( '.mc-actions #mass_replace_on' ).removeAttr( 'disabled' );
			}
		} else {
			if ( ( groups_table && checked < 2 ) || ( ! groups_table && checked == 0 ) ) {
				$( '.mc-actions input[type="submit"]' ).attr( 'disabled', 'disabled' );
				$( '.mc-actions #mass_replace_on' ).attr( 'disabled', 'disabled' );
			}
		}
	});

	var add_category = $( '.new-event-category' );
	add_category.hide();
	$( '#event_category_new' ).prop( 'checked', false );
	var add_cat_label = $( 'label[for=event_category_new]' );
	add_cat_label.find( '.dashicons-minus' ).hide();
	$( '#event_category_new' ).on( 'click', function() {
		var checked_status = $(this).prop('checked');
		if ( checked_status ) {
			add_category.show();
			$( '#event_category_name' ).prop( 'disabled', false );
			add_cat_label.find( '.dashicons' ).addClass( 'dashicons-minus' ).removeClass( 'dashicons-plus' );
		} else {
			add_category.hide();
			$( '#event_category_name' ).prop( 'disabled', true );
			add_cat_label.find( '.dashicons' ).addClass( 'dashicons-plus' ).removeClass( 'dashicons-minus' );
		}
	});

	var primary_category = $( '.mc-primary-category' );
	var categories       = $( '.categories input:checked' );
	if ( categories.length <= 1 ) {
		primary_category.hide();
	}
	categories.each( function() {
		var value = $( this ).val();
		var selector = primary_category.find( 'select' );
		selector.find( 'option[value=' + value + ']' ).show();
	});
	$( '.categories input' ).on( 'change', function(e) {
		var category_count = $( '.categories input:checked' );
		var categories     = $( '.categories input' );
		if ( category_count.length > 1 ) {
			primary_category.show().prop( 'disabled', false );
		} else {
			primary_category.hide().prop( 'disabled', true );
		}
		categories.each( function() {
			var value = $( this ).val();
			var checked = $( this ).prop( 'checked' );
			var selector = primary_category.find( 'select' );
			if ( checked ) {
				selector.find( 'option[value=' + value + ']' ).show().prop( 'disabled', false );
			} else {
				selector.find( 'option[value=' + value + ']' ).hide().prop( 'disabled', true );
			}
		});
	});

	$( '.fifth-week-schedule' ).hide();
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
			$( '.fifth-week-schedule' ).show();
		} else {
			$( '#e_every' ).attr( 'max', 99 );
			$( '.fifth-week-schedule' ).hide();
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

	var is_checked = $( 'input[id="mc_remote"]' ).prop( "checked" );
	if ( ! is_checked ) {
		$( '.mc_remote_info' ).hide();
	}

	$( 'input[id="mc_remote"]' ).change( function() {
		var checked = $(this).prop( "checked" );
		if ( checked ) {
			$( '.mc_remote_info' ).show();
		} else {
			$( '.mc_remote_info' ).hide();
		}
	});

	var gapi_checked = $( 'input[id="mc_display_single-gmap"]' ).prop( "checked" );
	if ( gapi_checked ) {
		$( '#mc_gmap_api_key' ).attr( 'required', 'true' );
	}

	$( 'input[id="mc_display_single-gmap"]' ).change( function() {
		var checked = $(this).prop( "checked" );
		if ( checked ) {
			$( '#mc_gmap_api_key' ).attr( 'required', 'true' );
		} else {
			$( '#mc_gmap_api_key' ).removeAttr( 'required' );
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
			showPanel( '#' + firstItem );
		}
	}
	var tabs = document.querySelectorAll('.mc-tabs [role=tab]'); // get all role=tab elements as a variable.
	for ( var i = 0; i < tabs.length; i++) {
		tabs[i].addEventListener( 'click', showTabPanel );
		tabs[i].addEventListener( 'keydown', handleKeyPress );
	} // add click event to each tab to run the showTabPanel function.
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
		if ( inside.length == 0 && ! container.hasClass( 'mcs-tabs' ) ) {
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
			tabPanels[i].setAttribute( 'aria-hidden', 'true' );
		} // hide all tabpanels
		// If this is an inner tab panel, don't set the window location.
		if ( inside.length == 0 ) {
			window.location.hash = tabPanelToOpen;
		}
		document.getElementById(tabPanelToOpen).setAttribute( 'aria-hidden', 'false' ); //show tabpanel
		var iframes = $( 'iframe.mc-iframe' );
		for ( var i = 0; i < iframes.length; i++ ) {
			iframe = iframes[i];
			resizeIframe(iframe);
		}
		$( '#' + tabPanelToOpen ).attr( 'tabindex', '-1' ).trigger( 'focus' );
		window.scrollTo( 0,0 );
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
			tabPanels[i].setAttribute( 'aria-hidden', 'true' );
		}
		var currentPanel = document.getElementById(id);
		if ( null !== currentPanel ) {
			currentPanel.setAttribute( 'aria-hidden', 'false' ); //show tabpanel
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

	$('.mc-sortable').sortable({
		placeholder: 'mc-ui-state-highlight',
		update: function (event, ui) {
			$('.mc-sortable-update').html( 'Submit form to save changes' );
		}
	});

	$('.mc-sortable .hide').on('click', function (e) {
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

	$('.mc-sortable .up').on('click', function (e) {
		var parentEls = $( this ).parents().map(function() { return this.tagName; } ).get();
		var parentLi  = $.inArray( 'LI', parentEls );
		var parentTr  = $.inArray( 'TR', parentEls );
		if ( 1 == parentLi ) {
			$(this).parents('li').insertBefore($(this).parents('li').prev());
			$( '.mc-sortable li' ).removeClass( 'mc-updated' );
			$(this).parents('li').addClass( 'mc-updated' );
		} else if ( 1 == parentTr ) {
			$(this).parents('tr').insertBefore($(this).parents('tr').prev());
			$( '.mc-sortable tr' ).removeClass( 'mc-updated' );
			$(this).parents('tr').addClass( 'mc-updated' );
		} else {
			$(this).parents('.mc-row').insertBefore($(this).parents('.mc-row').prev());
			$( '.mc-sortable .mc-row' ).removeClass( 'mc-updated' );
			$(this).parents('.mc-row').addClass( 'mc-updated' );
		}
		$( this ).trigger( 'focus' );
		wp.a11y.speak( 'Item moved up' );
	});

	$('.mc-sortable .down').on('click', function (e) {
		var parentEls = $( this ).parents().map(function() { return this.tagName; } ).get();
		var parentLi  = $.inArray( 'LI', parentEls );
		var parentTr  = $.inArray( 'TR', parentEls );
		if ( 1 == parentLi ) {
			$(this).parents('li').insertAfter($(this).parents('li').next());
			$( '.mc-sortable li' ).removeClass( 'mc-updated' );
			$(this).parents('li').addClass( 'mc-updated' );
		} else if ( 1 == parentTr ) {
			$(this).parents('tr').insertAfter($(this).parents('tr').next());
			$( '.mc-sortable tr' ).removeClass( 'mc-updated' );
			$(this).parents('tr').addClass( 'mc-updated' );
		} else {
			$(this).parents('.mc-row').insertAfter($(this).parents('.mc-row').next());
			$( '.mc-sortable .mc-row' ).removeClass( 'mc-updated' );
			$(this).parents('.mc-row').addClass( 'mc-updated' );
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

		$('.mc-image-upload').on( 'click', '.remove-image', function (e) {
			$( '#e_image_id' ).val( '' );
			$( '#e_image' ).val( '' );
			$( '#event_image' ).attr( 'src', '' ).attr( 'alt', '' );
			$( '.event_image' ).text( mcAdmin.imageRemoved );
		});

		$('.mc-image-upload')
			.on('click', '.select-image', function (e) {
				e.preventDefault();
				var $inpField = document.querySelector('#e_image');
				var $idField = document.querySelector('#e_image_id');
				var $displayField = document.querySelector('.event_image');
				clear_existing();
				mediaPopup = wp.media({
					multiple: false, // add, reset, false.
					title: mcAdmin.modalTitle,
					button: {
						text: mcAdmin.buttonName,
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

	// Historic; for older My Calendar Pro only.
	if ( mcAdmin.mcs < 2.1 ) {
		$( '.mcs-tabs' ).each( function ( index ) {
			var tabs = $('.mcs-tabs .wptab').length;
			var firstItem = window.location.hash;
			if ( ! firstItem ) {
				var firstItem = '#' + $( '.mcs-tabs .wptab:nth-of-type(1)' ).attr( 'id' );
			}
			$('.mcs-tabs .tabs a[href="' + firstItem + '"]').addClass('active').attr( 'aria-selected', 'true' );
			if ( tabs > 1 ) {
				$( '.mcs-tabs .wptab' ).not( firstItem ).attr( 'aria-hidden', 'true' );
				$( '.mcs-tabs .wptab' ).removeClass( 'initial-hidden' );
				$( firstItem ).show();
				$( '.mcs-tabs .tabs a' ).on( 'click', function (e) {
					e.preventDefault();
					$('.mcs-tabs .tabs a').removeClass('active').attr( 'aria-selected', 'false' );
					$(this).addClass('active').attr( 'aria-selected', 'true' );
					var target = $(this).attr('href');
					window.location.hash = target;
					$('.mcs-tabs .wptab').not(target).attr( 'aria-hidden', 'true' );
					$(target).removeAttr( 'aria-hidden' ).show().trigger( 'focus' );
				});
			}
		});
	}

})(jQuery);