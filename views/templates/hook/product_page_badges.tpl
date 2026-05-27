{**
 * Badges overlay – product detail page.
 * Injected via displayProductAdditionalInfo hook.
 * The wrapper uses position:absolute so it overlays the product image.
 *}
{if isset($badges) && $badges|count > 0}
  <div class="productbadges-wrapper productbadges-wrapper--product-page">
    {foreach $badges as $badge}
      <span
        class="productbadge productbadge--{if $badge.position == 0}left{else}right{/if}"
        style="background-color:{$badge.bg_color|escape:'html'};color:{$badge.text_color|escape:'html'};"
        aria-label="{$badge.label|escape:'html'}"
      >
        {$badge.label|escape:'html'}
      </span>
    {/foreach}
  </div>
{/if}
