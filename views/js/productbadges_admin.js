/**
 * productbadges – back-office helpers
 * - Attaches a native HTML5 color picker swatch next to each .pb-color-picker input.
 * No external libraries required; jQuery is already provided by PrestaShop.
 */
(function ($) {
  'use strict';

  function attachColorPickers() {
    $('.pb-color-picker').each(function () {
      var $input = $(this);

      if ($input.data('pb-color-attached')) {
        return;
      }
      $input.data('pb-color-attached', true);

      // Create a native <input type="color"> swatch next to the text field
      var $swatch = $('<input type="color" class="pb-color-swatch" />')
        .css({
          width: '36px',
          height: '34px',
          padding: '2px',
          border: '1px solid #ccc',
          borderRadius: '3px',
          cursor: 'pointer',
          verticalAlign: 'middle',
          marginLeft: '6px',
        })
        .val($input.val() || '#ff0000');

      $input.after($swatch);

      // Sync swatch → text
      $swatch.on('input change', function () {
        $input.val($(this).val());
      });

      // Sync text → swatch (on blur / keyup)
      $input.on('blur keyup', function () {
        var v = $(this).val().trim();
        if (/^#[0-9a-fA-F]{6}$/.test(v)) {
          $swatch.val(v);
        }
      });
    });
  }

  $(document).ready(function () {
    attachColorPickers();
  });

}(jQuery));
