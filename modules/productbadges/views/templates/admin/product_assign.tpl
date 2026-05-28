{**
 * Product assignment widget – rendered below the badge form.
 * Add/remove operations are saved to DB immediately via AJAX.
 * No form submit required for product assignments.
 *}
<div class="panel" id="productbadges-assign-panel">
  <div class="panel-heading">
    <i class="icon-shopping-cart"></i>
    {l s='Assigned products' mod='productbadges'}
    <span class="panel-heading-action">
      <small class="text-muted">{l s='Changes are saved immediately' mod='productbadges'}</small>
    </span>
  </div>

  <div class="panel-body">

    {* --- Search box ---------------------------------------------------- *}
    <div class="form-group row">
      <label class="col-lg-3 control-label">{l s='Add product' mod='productbadges'}</label>
      <div class="col-lg-6" style="position:relative;">
        <div class="input-group">
          <input
            type="text"
            id="pb-product-search"
            class="form-control"
            placeholder="{l s='Search by name or reference…' mod='productbadges'}"
            autocomplete="off"
          />
          <span class="input-group-addon"><i class="icon-search"></i></span>
        </div>
        <div id="pb-product-suggestions" class="list-group"
             style="display:none;position:absolute;z-index:9999;width:100%;"></div>
      </div>
    </div>

    {* --- Assigned products table --------------------------------------- *}
    <table class="table" id="pb-product-table">
      <thead>
        <tr>
          <th>{l s='ID' mod='productbadges'}</th>
          <th>{l s='Name' mod='productbadges'}</th>
          <th>{l s='Reference' mod='productbadges'}</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        {foreach $assigned_products as $p}
          <tr id="pb-product-row-{$p.id_product}">
            <td>{$p.id_product|intval}</td>
            <td>{$p.name|escape:'html'}</td>
            <td>{$p.reference|escape:'html'}</td>
            <td class="text-center">
              <button
                type="button"
                class="btn btn-danger btn-xs pb-remove-product"
                data-id-product="{$p.id_product|intval}"
                data-id-badge="{$badge_id|intval}"
                title="{l s='Remove' mod='productbadges'}"
              >
                <i class="icon-trash"></i>
              </button>
            </td>
          </tr>
        {foreachelse}
          <tr id="pb-no-products">
            <td colspan="4" class="text-center text-muted">
              {l s='No products assigned yet.' mod='productbadges'}
            </td>
          </tr>
        {/foreach}
      </tbody>
    </table>

  </div>{* /panel-body *}
</div>{* /panel *}

<script>
(function ($) {
  'use strict';

  var badgeId    = {$badge_id|intval};
  var searchUrl  = '{$product_search_url|escape:'javascript'}';
  var adminToken = '{$admin_token|escape:'javascript'}';
  var baseUrl    = searchUrl.replace('SearchProducts', '');

  // ---- Remove product -------------------------------------------------------

  $(document).on('click', '.pb-remove-product', function () {
    var btn       = $(this);
    var idProduct = btn.data('id-product');

    btn.prop('disabled', true);

    $.post(baseUrl + 'RemoveProduct', {
      ajax:       1,
      id_badge:   badgeId,
      id_product: idProduct,
      token:      adminToken
    }, function (resp) {
      if (resp && resp.success) {
        $('#pb-product-row-' + idProduct).remove();
        if ($('#pb-product-table tbody tr[id^="pb-product-row-"]').length === 0) {
          $('#pb-product-table tbody').html(
            '<tr id="pb-no-products"><td colspan="4" class="text-center text-muted">' +
            '{l s='No products assigned yet.' mod='productbadges' js=1}</td></tr>'
          );
        }
      } else {
        btn.prop('disabled', false);
      }
    }, 'json').fail(function () { btn.prop('disabled', false); });
  });

  // ---- Autocomplete search --------------------------------------------------

  var searchTimer;

  $('#pb-product-search').on('input', function () {
    var q = $(this).val().trim();
    clearTimeout(searchTimer);
    if (q.length < 2) {
      $('#pb-product-suggestions').hide();
      return;
    }
    searchTimer = setTimeout(function () {
      $.get(searchUrl, { ajax: 1, q: q, token: adminToken }, function (rows) {
        var $list = $('#pb-product-suggestions').empty().show();
        if (!rows || !rows.length) {
          $list.append('<a class="list-group-item disabled">{l s='No results' mod='productbadges' js=1}</a>');
          return;
        }
        $.each(rows, function (i, p) {
          $('<a class="list-group-item" href="#"></a>')
            .text(p.name + (p.reference ? ' (' + p.reference + ')' : ''))
            .data('product', p)
            .appendTo($list);
        });
      }, 'json');
    }, 300);
  });

  // ---- Select suggestion → add product -------------------------------------

  $(document).on('click', '#pb-product-suggestions .list-group-item:not(.disabled)', function (e) {
    e.preventDefault();

    var p  = $(this).data('product');
    var id = parseInt(p.id_product, 10);

    $('#pb-product-suggestions').hide();
    $('#pb-product-search').val('');

    if ($('#pb-product-row-' + id).length) {
      return; // already in the list
    }

    $.post(baseUrl + 'AddProduct', {
      ajax:       1,
      id_badge:   badgeId,
      id_product: id,
      token:      adminToken
    }, function (resp) {
      if (resp && resp.success) {
        $('#pb-no-products').remove();
        var $row = $('<tr id="pb-product-row-' + parseInt(resp.id_product, 10) + '"></tr>');
        $row.append('<td>' + parseInt(resp.id_product, 10) + '</td>');
        $row.append('<td>' + $('<span>').text(resp.name).html() + '</td>');
        $row.append('<td>' + $('<span>').text(resp.reference).html() + '</td>');
        $row.append(
          '<td class="text-center">' +
          '<button type="button" class="btn btn-danger btn-xs pb-remove-product"' +
          ' data-id-product="' + parseInt(resp.id_product, 10) + '"' +
          ' data-id-badge="' + badgeId + '"' +
          ' title="{l s='Remove' mod='productbadges' js=1}"><i class="icon-trash"></i></button></td>'
        );
        $('#pb-product-table tbody').append($row);
      }
    }, 'json');
  });

  // ---- Hide suggestions on outside click -----------------------------------

  $(document).on('click', function (e) {
    if (!$(e.target).closest('#pb-product-search, #pb-product-suggestions').length) {
      $('#pb-product-suggestions').hide();
    }
  });

}(jQuery));
</script>
