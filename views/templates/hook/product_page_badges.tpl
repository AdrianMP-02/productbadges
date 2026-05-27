{**
 * Badges overlay – product detail page.
 * Injected via displayProductAdditionalInfo hook.
 * JS (productbadges_front.js) repositions this wrapper into .product-cover.
 *}
{if isset($badges) && $badges|count > 0}
  <div class="productbadges-wrapper productbadges-wrapper--product-page">
    <div class="productbadges-col productbadges-col--left">
      {foreach $badges as $badge}
        {if $badge.position == 0}
          <span class="productbadge"
            style="background-color:{$badge.bg_color|escape:'html'};color:{$badge.text_color|escape:'html'};"
            aria-label="{$badge.label|escape:'html'}"
          >{$badge.label|escape:'html'}</span>
        {/if}
      {/foreach}
    </div>
    <div class="productbadges-col productbadges-col--right">
      {foreach $badges as $badge}
        {if $badge.position == 1}
          <span class="productbadge"
            style="background-color:{$badge.bg_color|escape:'html'};color:{$badge.text_color|escape:'html'};"
            aria-label="{$badge.label|escape:'html'}"
          >{$badge.label|escape:'html'}</span>
        {/if}
      {/foreach}
    </div>
  </div>
{/if}
