{**
 * Badges overlay – product listings (category, search, home modules…)
 * Injected via displayProductListingAction hook.
 * Parent container must have position:relative (handled in productbadges.css).
 *}
{if isset($badges) && $badges|count > 0}
  <div class="productbadges-wrapper">
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
