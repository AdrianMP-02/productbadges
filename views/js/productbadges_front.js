(function ($) {
  'use strict';

  /**
   * Moves .productbadges-wrapper--product-page into the product image container
   * so the badges overlay the image rather than appearing in the info section.
   * Works for both full product page and quick-view modal.
   */
  function injectBadgesIntoImage(context) {
    var $ctx = context ? $(context) : $(document);

    $ctx.find('.productbadges-wrapper--product-page').each(function () {
      var $badge = $(this);

      // Classic theme uses .product-cover; some themes use .images-container or .product-images
      var $cover = $ctx.find('.product-cover, .images-container, .product-images').first();

      if (!$cover.length) {
        // Fallback: look for the first <img> inside the modal/page that is a product image
        $cover = $ctx.find('.js-qv-product-cover, .js-product-cover').first().parent();
      }

      if ($cover.length) {
        $cover.css('position', 'relative');
        $badge.appendTo($cover).addClass('pb-positioned');
      }
    });
  }

  $(document).ready(function () {
    injectBadgesIntoImage(null);

    // Quick view opened (Bootstrap modal event fired by Classic theme)
    $(document).on('shown.bs.modal', '#product-modal', function () {
      injectBadgesIntoImage(this);
    });

    // PrestaShop 1.7 fires a custom event when quick view is ready
    $(document).on('quickview-opened', function () {
      injectBadgesIntoImage(null);
    });

    // Fallback: re-run when PS emits its standard product events
    if (window.prestashop) {
      prestashop.on('updatedProduct', function () {
        injectBadgesIntoImage(null);
      });
      prestashop.on('quickview-loaded', function (e) {
        injectBadgesIntoImage(e && e.target ? e.target : null);
      });
    }
  });

}(jQuery));
