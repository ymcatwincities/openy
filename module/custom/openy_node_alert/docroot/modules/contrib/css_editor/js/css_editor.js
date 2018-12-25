/*jslint nomen: true, plusplus: true, todo: true, white: true, browser: true, indent: 2 */

jQuery(function($) {
  'use strict';

  // Set variable for toggles.
  var $preview_toggle = $('#edit-autopreview-enabled');
  var $editor_toggle = $('#edit-plaintext-enabled');

  var autoPreview = function() {
    if ($preview_toggle.is(':checked')) {
      var value = ($editor_toggle.is(':checked') ? $textarea.val() : editor.getValue());
      var id = 'css-editor-preview-style';
      var $css = $preview.contents().find('#' + id);
      if ($css.length) {
        $css.html(value);
      }
      else {
        $preview.contents().find('head').append($('<style type="text/css" id="' + id + '">' + value + '</style>'));
      }
    }
  };

  // Unobstrusive syntax highlighting.
  var $textarea = $('#css-editor-textarea');

  var createEditor = function() {
    var editor = CodeMirror.fromTextArea($textarea[0], { lineNumbers : true, extraKeys : { "Ctrl-Space" : "autocomplete" } });
    editor.on('change', autoPreview);
    return editor;
  };

  var editor = createEditor();

  // Initial state of the editor checkbox.
  if ($editor_toggle.is(':checked')) {
    editor.toTextArea();
  }

  $editor_toggle.on('click', function() {
    if ($(this).is(':checked')) {
      editor.toTextArea();
    }
    else {
      editor = createEditor();
    }
  });

  // Preview.
  var $preview = $('#css-editor-preview');

  var $previewSettings = $('.js-form-item-preview-path');

  // Initial state of the preview toggle checkbox.
  if (!$preview_toggle.is(':checked')) {
    $preview.hide();
    $previewSettings.hide();
  }

  $preview_toggle.on('click', function() {
    if ($(this).is(':checked')) {
      $preview.show();
      $previewSettings.show();
      autoPreview();
    }
    else {
      $preview.hide();
      $previewSettings.hide();
    }
  });

  $textarea.on('keyup', autoPreview);

  $preview.on('load', autoPreview);

  $('#edit-preview-path').on('blur', function() {
    $preview.attr('src', drupalSettings.CSSEditor.frontPage.replace('?', '/' + $(this).val() + '?'));
  });

});
