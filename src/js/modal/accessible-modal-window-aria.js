/**
 * ES2015 accessible modal window system, using ARIA
 *
 * Forked from van11y.net
 *
 * Website: https://van11y.net/accessible-modal/
 * License MIT: https://github.com/nico3333fr/van11y-accessible-modal-window-aria/blob/master/LICENSE
 */
'use strict';

(function (doc) {

  'use strict';

  var MODAL_JS_CLASS = 'js-modal';
  var MODAL_ID_PREFIX = 'label_modal_';
  var MODAL_CLASS_SUFFIX = 'modal';
  var MODAL_DATA_BACKGROUND_ATTR = 'data-modal-background-click';
  var MODAL_PREFIX_CLASS_ATTR = 'data-modal-prefix-class';
  var MODAL_TEXT_ATTR = 'data-modal-text';
  var MODAL_CONTENT_ID_ATTR = 'data-modal-content-id';
  var MODAL_DESCRIBEDBY_ID_ATTR = 'data-modal-describedby-id';
  var MODAL_TITLE_ATTR = 'data-modal-title';
  var MODAL_FOCUS_TO_ATTR = 'data-modal-focus-toid';
  var MODAL_CLOSE_TEXT_ATTR = 'data-modal-close-text';
  var MODAL_ROLE = 'dialog';

  var MODAL_BUTTON_CLASS_SUFFIX = 'modal-close';
  var MODAL_BUTTON_JS_ID = 'js-modal-close';
  var MODAL_BUTTON_JS_CLASS = 'js-modal-close';
  var MODAL_BUTTON_CONTENT_BACK_ID = 'data-content-back-id';
  var MODAL_BUTTON_FOCUS_BACK_ID = 'data-focus-back';

  var MODAL_WRAPPER_CLASS_SUFFIX = 'modal__wrapper';
  var MODAL_CONTENT_CLASS_SUFFIX = 'modal__content';
  var MODAL_CONTENT_JS_ID = 'js-modal-content';

  var MODAL_CLOSE_TEXT_CLASS_SUFFIX = 'modal-close__text';

  var MODAL_TITLE_ID = 'modal-title';
  var MODAL_TITLE_CLASS_SUFFIX = 'modal-title';

  var FOCUSABLE_ELEMENTS_STRING = "a[href], area[href], input:not([type='hidden']):not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), iframe, object, embed, *[tabindex], *[contenteditable]";
  var WRAPPER_PAGE_JS = 'js-modal-page';

  var MODAL_JS_ID = 'js-modal';

  var MODAL_OVERLAY_ID = 'js-modal-overlay';
  var MODAL_OVERLAY_CLASS_SUFFIX = 'modal-overlay';
  var MODAL_OVERLAY_TXT = 'Close modal';
  var MODAL_OVERLAY_BG_ENABLED_ATTR = 'data-background-click';

  var VISUALLY_HIDDEN_CLASS = 'invisible';
  var NO_SCROLL_CLASS = 'mc-no-scroll';

  var ATTR_ROLE = 'role';
  var ATTR_OPEN = 'open';
  var ATTR_LABELLEDBY = 'aria-labelledby';
  var ATTR_DESCRIBEDBY = 'aria-describedby';
  var ATTR_HIDDEN = 'aria-hidden';
  //const ATTR_MODAL = 'aria-modal="true"';
  var ATTR_HASPOPUP = 'aria-haspopup';
  var ATTR_HASPOPUP_VALUE = 'dialog';

  var findById = function findById(id) {
    return doc.getElementById(id);
  };

  var addClass = function addClass(el, className) {
    if (el.classList) {
      el.classList.add(className); // IE 10+
    } else {
        el.className += ' ' + className; // IE 8+
      }
  };

  var removeClass = function removeClass(el, className) {
    if (el.classList) {
      el.classList.remove(className); // IE 10+
    } else {
        el.className = el.className.replace(new RegExp('(^|\\b)' + className.split(' ').join('|') + '(\\b|$)', 'gi'), ' '); // IE 8+
      }
  };

  var hasClass = function hasClass(el, className) {
    if (el.classList) {
      return el.classList.contains(className); // IE 10+
    } else {
        return new RegExp('(^| )' + className + '( |$)', 'gi').test(el.className); // IE 8+ ?
      }
  };
  /*const wrapInner = (el, wrapper_el) => { // doesn't work on IE/Edge, f…
      while (el.firstChild)
          wrapper_el.append(el.firstChild);
      el.append(wrapper_el);
   }*/
  function wrapInner(parent, wrapper) {
    if (typeof wrapper === "string") wrapper = document.createElement(wrapper);

    parent.appendChild(wrapper);

    while (parent.firstChild !== wrapper) wrapper.appendChild(parent.firstChild);
  }

  function remove(el) {
    /* node.remove() is too modern for IE≤11 */
    el.parentNode.removeChild(el);
  }

  /* gets an element el, search if it is child of parent class, returns id of the parent */
  var searchParent = function searchParent(el, parentClass) {
    var found = false;
    var parentElement = el.parentNode;
    while (parentElement && found === false) {
      if (hasClass(parentElement, parentClass) === true) {
        found = true;
      } else {
        parentElement = parentElement.parentNode;
      }
    }
    if (found === true) {
      return parentElement.getAttribute('id');
    } else {
      return '';
    }
  };

  /**
   * Create the template for an overlay
   * @param  {Object} config
   * @return {String}
   */
  var createOverlay = function createOverlay(config) {

    var id = MODAL_OVERLAY_ID;
    var overlayText = config.text || MODAL_OVERLAY_TXT;
    var overlayClass = config.prefixClass + MODAL_OVERLAY_CLASS_SUFFIX;
    var overlayBackgroundEnabled = config.backgroundEnabled === 'disabled' ? 'disabled' : 'enabled';

    return '<span\n                    id="' + id + '"\n                    class="' + overlayClass + '"\n                    ' + MODAL_OVERLAY_BG_ENABLED_ATTR + '="' + overlayBackgroundEnabled + '"\n                    title="' + overlayText + '"\n                    >\n                    <span class="' + VISUALLY_HIDDEN_CLASS + '">' + overlayText + '</span>\n                  </span>';
  };

  /**
   * Create the template for a modal
   * @param  {Object} config
   * @return {String}
   */
  var createModal = function createModal(config) {
	var dialog = document.createElement( 'dialog' );
	var outerContent = document.createElement( 'div' );
	var innerContent = document.createElement( 'div' );
	var contentContainer = document.createElement( 'div' );
	contentContainer.setAttribute( 'id', MODAL_CONTENT_JS_ID );

	var id = MODAL_JS_ID;
    var modalClassName = config.modalPrefixClass + MODAL_CLASS_SUFFIX;
    var modalClassWrapper = config.modalPrefixClass + MODAL_WRAPPER_CLASS_SUFFIX;
    var buttonCloseClassName = config.modalPrefixClass + MODAL_BUTTON_CLASS_SUFFIX;
    var buttonCloseInner = '<span class="' + config.modalPrefixClass + MODAL_CLOSE_TEXT_CLASS_SUFFIX + '">\n                                          ' + config.modalCloseText + '\n                                         </span>';
    var contentClassName = config.modalPrefixClass + MODAL_CONTENT_CLASS_SUFFIX;
    var titleClassName = config.modalPrefixClass + MODAL_TITLE_CLASS_SUFFIX;
    var title = config.modalTitle !== '' ? '<div class="js-modal-title-container"><h1 id="' + MODAL_TITLE_ID + '" class="' + titleClassName + '">\n                                          ' + config.modalTitle + '\n                                         </h1></div>' : '';
    var button_close = '<button type="button" class="' + MODAL_BUTTON_JS_CLASS + ' ' + buttonCloseClassName + '" id="' + MODAL_BUTTON_JS_ID + '" ' + MODAL_BUTTON_CONTENT_BACK_ID + '="' + config.modalContentId + '" ' + MODAL_BUTTON_FOCUS_BACK_ID + '="' + config.modalFocusBackId + '"><span class="dashicons dashicons-no" aria-hidden="true"></span>\n                               ' + buttonCloseInner + '\n                              </button>';
    var content = config.modalText;

    // If there is no content but an id we try to fetch content id
    if (content === '' && config.modalContentId) {
      var contentFromId = findById(config.modalContentId);
      if (contentFromId) {
		contentContainer.insertAdjacentElement( 'beforeEnd', contentFromId );
      }
    } else {
		contentContainer.insertAdjacentHTML( 'beforeEnd', content );
	}
	dialog.setAttribute( 'id', id );
	dialog.classList.add( modalClassName );
	dialog.setAttribute( ATTR_ROLE, MODAL_ROLE );
	dialog.setAttribute( ATTR_DESCRIBEDBY, config.modalDescribedById + ' ' + ATTR_OPEN );
	dialog.setAttribute( ATTR_LABELLEDBY, MODAL_TITLE_ID );
	outerContent.setAttribute( 'role', 'document' );
	outerContent.classList.add( modalClassWrapper );
	outerContent.insertAdjacentHTML( 'afterBegin', button_close );
	innerContent.classList.add( contentClassName );
	innerContent.insertAdjacentHTML( 'afterBegin', title );
	innerContent.insertAdjacentElement( 'beforeEnd', contentContainer );
	outerContent.insertAdjacentElement( 'afterBegin', innerContent );
	dialog.insertAdjacentElement( 'afterBegin', outerContent );

	return dialog;
  };

  var closeModal = function closeModal(config) {

    if (config.modalFocusBackId !== '') {
		var modalReturn = findById( config.modalFocusBackId );
		var modalReturnContainer = modalReturn.closest( '.mc-events' ); // only works when control is inside container.
		if (modalReturnContainer ) {
			modalReturnContainer.insertAdjacentElement( 'beforeEnd', config.modalContent.firstChild );
		}
	}

	remove(config.modal);
    remove(config.overlay);

    if (config.modalFocusBackId) {
      var contentFocus = findById(config.modalFocusBackId);
      if (contentFocus) {
        contentFocus.focus();
      }
    }
  };

  /** Find all modals inside a container
   * @param  {Node} node Default document
   * @return {Array}
   */
  var $listModals = function $listModals() {
    var node = arguments.length <= 0 || arguments[0] === undefined ? doc : arguments[0];
    return [].slice.call(node.querySelectorAll('.' + MODAL_JS_CLASS));
  };

  /**
   * Build modals for a container
   * @param  {Node} node
   */
  var attach = function attach(node) {
    var addListeners = arguments.length <= 1 || arguments[1] === undefined ? true : arguments[1];

    $listModals(node).forEach(function (modal_node) {

      var iLisible = Math.random().toString(32).slice(2, 12);
      var wrapperBody = findById(WRAPPER_PAGE_JS);
      var body = doc.querySelector('body');

      modal_node.setAttribute('id', MODAL_ID_PREFIX + iLisible);
      modal_node.setAttribute(ATTR_HASPOPUP, ATTR_HASPOPUP_VALUE);

      if (wrapperBody === null || wrapperBody.length === 0) {
        var wrapper = doc.createElement('DIV');
        wrapper.setAttribute('id', WRAPPER_PAGE_JS);
        wrapInner(body, wrapper);
		wrapperBody = wrapper;
      }
    });

    if (addListeners) {

      /* listeners */
      ['click', 'keydown'].forEach(function (eventName) {

        doc.body.addEventListener(eventName, function (e) {

          // click on link modal
          var parentModalLauncher = searchParent(e.target, MODAL_JS_CLASS);
          if ((hasClass(e.target, MODAL_JS_CLASS) === true || parentModalLauncher !== '') && eventName === 'click') {
            var body = doc.querySelector('body');
            var modalLauncher = parentModalLauncher !== '' ? findById(parentModalLauncher) : e.target;
            var modalPrefixClass = modalLauncher.hasAttribute(MODAL_PREFIX_CLASS_ATTR) === true ? modalLauncher.getAttribute(MODAL_PREFIX_CLASS_ATTR) + '-' : '';
            var modalText = modalLauncher.hasAttribute(MODAL_TEXT_ATTR) === true ? modalLauncher.getAttribute(MODAL_TEXT_ATTR) : '';
            var modalContentId = modalLauncher.hasAttribute(MODAL_CONTENT_ID_ATTR) === true ? modalLauncher.getAttribute(MODAL_CONTENT_ID_ATTR) : '';
            var modalDescribedById = modalLauncher.hasAttribute(MODAL_DESCRIBEDBY_ID_ATTR) === true ? modalLauncher.getAttribute(MODAL_DESCRIBEDBY_ID_ATTR) : '';
            var modalTitle = modalLauncher.hasAttribute(MODAL_TITLE_ATTR) === true ? modalLauncher.getAttribute(MODAL_TITLE_ATTR) : '';
            var modalCloseText = modalLauncher.hasAttribute(MODAL_CLOSE_TEXT_ATTR) === true ? modalLauncher.getAttribute(MODAL_CLOSE_TEXT_ATTR) : MODAL_OVERLAY_TXT;
            var backgroundEnabled = modalLauncher.hasAttribute(MODAL_DATA_BACKGROUND_ATTR) === true ? modalLauncher.getAttribute(MODAL_DATA_BACKGROUND_ATTR) : '';
            var modalGiveFocusToId = modalLauncher.hasAttribute(MODAL_FOCUS_TO_ATTR) === true ? modalLauncher.getAttribute(MODAL_FOCUS_TO_ATTR) : '';

            var wrapperBody = findById(WRAPPER_PAGE_JS);

            // insert overlay
            body.insertAdjacentHTML('beforeEnd', createOverlay({
              text: modalCloseText,
              backgroundEnabled: backgroundEnabled,
              prefixClass: modalPrefixClass
            }));

			// insert modal
            body.insertAdjacentElement('beforeEnd', createModal({
              modalText: modalText,
              modalPrefixClass: modalPrefixClass,
              backgroundEnabled: modalContentId,
              modalTitle: modalTitle,
              modalCloseText: modalCloseText,
              modalCloseTitle: modalCloseText,
              modalContentId: modalContentId,
              modalDescribedById: modalDescribedById,
              modalFocusBackId: modalLauncher.getAttribute('id')
            }));

            // hide page
            wrapperBody.setAttribute(ATTR_HIDDEN, 'true');

            // add class noscroll to body
            addClass(body, NO_SCROLL_CLASS);

            // give focus to close button or specified element
            var closeButton = findById(MODAL_BUTTON_JS_ID);
            if (modalGiveFocusToId !== '') {
              var focusTo = findById(modalGiveFocusToId);
              if (focusTo) {
                focusTo.focus();
              } else {
                closeButton.focus();
              }
            } else {
              closeButton.focus();
            }

            e.preventDefault();
          }

          // click on close button or on overlay not blocked
          var parentButton = searchParent(e.target, MODAL_BUTTON_JS_CLASS);
          if ((e.target.getAttribute('id') === MODAL_BUTTON_JS_ID || parentButton !== '' || e.target.getAttribute('id') === MODAL_OVERLAY_ID || hasClass(e.target, MODAL_BUTTON_JS_CLASS) === true) && eventName === 'click') {
            var body = doc.querySelector('body');
            var wrapperBody = findById(WRAPPER_PAGE_JS);
            var modal = findById(MODAL_JS_ID);
            var modalContent = findById(MODAL_CONTENT_JS_ID) ? findById(MODAL_CONTENT_JS_ID) : '';
            var overlay = findById(MODAL_OVERLAY_ID);
            var modalButtonClose = findById(MODAL_BUTTON_JS_ID);
            var modalFocusBackId = modalButtonClose.getAttribute(MODAL_BUTTON_FOCUS_BACK_ID);
            var contentBackId = modalButtonClose.getAttribute(MODAL_BUTTON_CONTENT_BACK_ID);
            var backgroundEnabled = overlay.getAttribute(MODAL_OVERLAY_BG_ENABLED_ATTR);

            if (!(e.target.getAttribute('id') === MODAL_OVERLAY_ID && backgroundEnabled === 'disabled')) {

              closeModal({
                modal: modal,
                modalContent: modalContent,
                overlay: overlay,
                modalFocusBackId: modalFocusBackId,
                contentBackId: contentBackId,
                backgroundEnabled: backgroundEnabled,
                fromId: e.target.getAttribute('id')
              });

              // show back page
              wrapperBody.removeAttribute(ATTR_HIDDEN);

              // remove class noscroll to body
              removeClass(body, NO_SCROLL_CLASS);
            }
          }

          // strike a key when modal opened
          if (findById(MODAL_JS_ID) && eventName === 'keydown') {
            var body = doc.querySelector('body');
            var wrapperBody = findById(WRAPPER_PAGE_JS);
            var modal = findById(MODAL_JS_ID);
            var modalContent = findById(MODAL_CONTENT_JS_ID) ? findById(MODAL_CONTENT_JS_ID) : '';
            var overlay = findById(MODAL_OVERLAY_ID);
            var modalButtonClose = findById(MODAL_BUTTON_JS_ID);
            var modalFocusBackId = modalButtonClose.getAttribute(MODAL_BUTTON_FOCUS_BACK_ID);
            var contentBackId = modalButtonClose.getAttribute(MODAL_BUTTON_CONTENT_BACK_ID);
            var $listFocusables = [].slice.call(modal.querySelectorAll(FOCUSABLE_ELEMENTS_STRING));

            // esc
            if (e.keyCode === 27) {

              closeModal({
                modal: modal,
                modalContent: modalContent,
                overlay: overlay,
                modalFocusBackId: modalFocusBackId,
                contentBackId: contentBackId
              });

              // show back page
              wrapperBody.removeAttribute(ATTR_HIDDEN);

              // remove class noscroll to body
              removeClass(body, NO_SCROLL_CLASS);
            }

            // tab or Maj Tab in modal => capture focus
            if (e.keyCode === 9 && $listFocusables.indexOf(e.target) >= 0) {

              // maj-tab on first element focusable => focus on last
              if (e.shiftKey) {
                if (e.target === $listFocusables[0]) {
                  $listFocusables[$listFocusables.length - 1].focus();
                  e.preventDefault();
                }
              } else {
                // tab on last element focusable => focus on first
                if (e.target === $listFocusables[$listFocusables.length - 1]) {
                  $listFocusables[0].focus();
                  e.preventDefault();
                }
              }
            }

            // tab outside modal => put it in focus
            if (e.keyCode === 9 && $listFocusables.indexOf(e.target) === -1) {
              e.preventDefault();
              $listFocusables[0].focus();
            }
          }
        }, true);
      });
    }
  };

  var onLoad = function onLoad() {
    attach();
    document.removeEventListener('DOMContentLoaded', onLoad);
  };

  document.addEventListener('DOMContentLoaded', onLoad);

  window.accessibleModalWindowAria = attach;
})(document);
