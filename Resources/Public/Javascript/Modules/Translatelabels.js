"use strict";

(function () {

  function initializeTranslatelabelsModule() {

    adminPanelTranslateModule_makeTranslateButtonsClickable()
    adminPanelTranslateModule_makeInlineEditable();

    content_createLinksToTranslateModule();
    content_createToolTips();
  }

  function adminPanelTranslateModule_makeInlineEditable()
  {
    var tableOfLabelsInAdminPanel = document.getElementsByClassName('tx_translatelabels-translations');
    // do nothing if translate labels module in admin panel is disabled
    if (tableOfLabelsInAdminPanel.length === 0) {
      return;
    }
    var editableDivs = Array.from(
      tableOfLabelsInAdminPanel[0].getElementsByClassName('editable')
    );

    // show translate and undo buttons only if translation is modified
    editableDivs.forEach(function (elem) {
      elem.addEventListener('input', function () {
        var translateButtons = Array.from(this.parentNode.querySelectorAll('[data-typo3-role=translateButton]'));
        var undoButtons = Array.from(this.parentNode.querySelectorAll('[data-typo3-role=undoButton]'));
        var origTranslation = this.parentNode.getElementsByClassName('orig-translation')[0].innerHTML;
        var editableContent = this;

        translateButtons[0].style.display = (this.innerHTML.trim() !== origTranslation.trim()) ? 'inline-block' : 'none';
        undoButtons[0].style.display = (this.innerHTML.trim() !== origTranslation.trim()) ? 'inline-block' : 'none';
        undoButtons[0].addEventListener('click', function () {
          editableContent.innerHTML = origTranslation;
          this.style.display = 'none';
          translateButtons[0].style.display = 'none';
        });
      });
    });
  }

  function adminPanelTranslateModule_makeTranslateButtonsClickable()
  {
    var buttons = Array.from(document.querySelectorAll('[data-typo3-role=translateButton]'));

    buttons.forEach(function (elem) {
      elem.addEventListener('click', translateLabel);
    });
  }

  function content_createLinksToTranslateModule()
  {
    var translateLabelModule = document.querySelectorAll('[data-typo3-tab-id="translatelabel_translatelabel"]')[0];
    var translateLabelInfoModule = document.querySelectorAll('[data-typo3-tab-id="translatelabel_translatelabel_info"]')[0];

    // open translation module in admin panel and highlight/focus the selected label
    // on clicked tooltips on page
    var linksInContent = Array.from(document.querySelectorAll('[data-translatelabels-link]'));
    linksInContent.forEach(function (elem) {
      elem.addEventListener('click', function () {

        // open translate module
        document.querySelectorAll('[data-identifier="actions-document-localize"]')[0].click();

        var lines = Array.from(translateLabelModule.querySelectorAll('[data-translatelabels-key]')).concat(
          Array.from(translateLabelInfoModule.querySelectorAll('[data-translatelabels-key]'))
        );
        lines.forEach(function (elem) {
          elem.classList.remove('translatelabels-highlight');
        });
        var tableRow = translateLabelModule.querySelectorAll(
          '[data-translatelabels-key="' + this.dataset.translatelabelsLink + '"]'
        )[0];
        tableRow.classList.add('translatelabels-highlight');
        var tableRowInfoModule = translateLabelInfoModule.querySelectorAll(
          '[data-translatelabels-key="' + this.dataset.translatelabelsLink + '"]'
        )[0];
        tableRowInfoModule.classList.add('translatelabels-highlight');

        var editField = tableRow.getElementsByClassName('editable')[0];

        setFocusIntoEditable(editField);
      });
    });
  }

  function setFocusIntoEditable(editField)
  {
    // focus after last char of editable field
    var s = window.getSelection(),
      r = document.createRange();
    r.setStart(editField.firstChild, 0);
    r.setEnd(editField.lastChild, editField.lastChild.length);
    s.removeAllRanges();
    s.addRange(r);
  }

  function content_createToolTips()
  {
    var tooltips = Array.from(document.querySelectorAll('.translatelabels-tooltip'));

    tooltips.forEach(function (tooltip) {
      var tooltipContent = tooltip.getElementsByClassName('translatelabels-tooltip-inner')[0];
      if (tooltipContent) {
        tooltipContent.style.display = 'block';
        tippy(tooltip, {
          arrow: true,
          interactive: true,
          allowHTML: true,
          followCursor: true,
          content: tooltipContent,
          maxWidth: 500
        });
      }
    });
  }

  function decodeHtmlSpecialChars(encodedStr)
  {
    // encode <br> and <div> tags to preserve them after removing all other html tags
    // entering newline during inline editing sometimes results in <br>, sometimes in <div></div>
    encodedStr = encodedStr.replaceAll('<br>', '&lt;br&gt;');
    encodedStr = encodedStr.replaceAll('<div>', '&lt;br&gt;<div>');
    var parser = new DOMParser;
    var dom = parser.parseFromString(
      '<!doctype html><body>' + encodedStr,
      'text/html');
    var parsedStrWithPreservedBrTags = dom.body.textContent.replace('&lt;br&gt;', '<br>');
    return parsedStrWithPreservedBrTags;
  }

  function updateTranslations(translationKey, newTranslation)
  {
    newTranslation = decodeHtmlSpecialChars(newTranslation);

    // update translations outside of tags
    var elements = Array.from(
      document.querySelectorAll('[data-translatelabels-role="translation"][data-translatelabels-key="' + translationKey + '"]')
    );
    elements.forEach(function (elem) {
      elem.innerHTML = newTranslation;
    });

    // update translations in attributes inside of tags
    var elementsWithTranslatableAttributes = Array.from(
      document.querySelectorAll('[data-translatelabels-role="translations-in-attributes"]')
    );

    elementsWithTranslatableAttributes.forEach(function (elem) {
      var attributes = JSON.parse(elem.dataset.translatelabelAttributes);
      attributes.forEach(function (attribute) {
        if(attribute.key === translationKey) {
          var translationWithPostfix = newTranslation + " (LABEL: " + attribute.identifier + ")";
          switch(attribute.attribute) {
            case 'value':
              elem.value = translationWithPostfix;
              break;
            case 'placeholder':
              elem.placeholder = translationWithPostfix;
              break;
            default:
              try {
                elem.setAttribute(attribute.attribute, translationWithPostfix);
              }
              catch(error) {
                console.log(error);
              }
          }
        }
      });
    });
  }

  // this function is called from translate buttons in translate module from admin panel for
  // inline translation
  function translateLabel() {
    var url = this.dataset.typo3AjaxUrl;
    var translatedContent = this.parentNode.getElementsByClassName('editable')[0].innerHTML;
    var key = this.dataset.typo3Key;
    var sysLanguageUid = this.dataset.typo3Syslanguageuid;
    var storagePid = this.dataset.typo3Storagepid;
    var currentTranslation = this.parentNode.getElementsByClassName('orig-translation')[0].innerHTML;
    var button = this;
    var undoButton = this.parentNode.querySelectorAll('[data-typo3-role=undoButton]')[0];
    var data = JSON.stringify({
      "key": key,
      "value": translatedContent,
      "sysLanguageUid": sysLanguageUid,
      "storagePid": storagePid,
      "currentTranslation": currentTranslation
    });
    var request = new XMLHttpRequest();
    request.open("POST", url);
    request.setRequestHeader("Content-type", "application/json;charset=UTF-8");

    request.onload = function () {

      button.style.display = 'none';
      undoButton.style.display = 'none';

      var statusMessage = button.parentNode.getElementsByClassName('status-message')[0];
      var response = JSON.parse(request.response);

      if (response.status === 'ok') {
        button.parentNode.getElementsByClassName('orig-translation')[0].innerHTML = translatedContent;
        updateTranslations(key, translatedContent);
        statusMessage.classList.remove('translatelabels-error-response');
        statusMessage.classList.add('translatelabels-ok-response');
      } else {
        // revoke changes
        button.parentNode.getElementsByClassName('editable')[0].innerHTML = currentTranslation;
        statusMessage.classList.add('translatelabels-error-response');
        statusMessage.classList.remove('translatelabels-ok-response');
      }
      statusMessage.innerHTML = response.message;
      statusMessage.style.display = 'block';
      window.setTimeout(function () {
        statusMessage.style.display = 'none';
      }, 2000);
      // location.reload();
    };

    request.send(data);
  }

  window.addEventListener('load', initializeTranslatelabelsModule, false);

})();
