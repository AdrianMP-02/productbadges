{**
 * Badges overlay – product listings (category, search, home modules…)
 * Injected via displayProductListingAction hook.
 * Parent container must have position:relative (handled in productbadges.css).
 *}
{if isset($badges) && $badges|count > 0}
  <div class="productbadges-wrapper">
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
