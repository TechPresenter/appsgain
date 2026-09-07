<?php
/**
 * Appsgain — hero device scenes.
 *
 * Each scene is a composed CSS/SVG mock-up rather than a flat image:
 * sharp at any resolution, no stock photography, nothing to download,
 * and it inherits the brand palette automatically.
 *
 * heroVisual('web'|'mobile'|'software'|'ecommerce'|'uiux'|'marketing')
 */
if (!defined('SITE_URL')) { die('Direct access not allowed'); }

if (!function_exists('heroVisual')) {
function heroVisual(string $key): void {
    switch ($key) {

    /* ── 1 · Web: laptop + companion phone ── */
    case 'web': ?>
      <div class="hv hv-web">
        <div class="hv-laptop">
          <div class="hv-screen">
            <div class="hv-browser">
              <span class="hv-dot"></span><span class="hv-dot"></span><span class="hv-dot"></span>
              <span class="hv-url"></span>
            </div>
            <div class="hv-site">
              <div class="hv-site-nav"><span></span><span></span><span></span><span class="hv-site-cta"></span></div>
              <div class="hv-site-hero">
                <div class="hv-bar w70"></div>
                <div class="hv-bar w45"></div>
                <div class="hv-bar w30 hv-accent"></div>
              </div>
              <div class="hv-site-grid"><i></i><i></i><i></i></div>
            </div>
          </div>
          <div class="hv-base"></div>
        </div>
        <div class="hv-phone hv-phone-sm">
          <div class="hv-phone-screen">
            <div class="hv-notch"></div>
            <div class="hv-bar w60"></div>
            <div class="hv-bar w80"></div>
            <div class="hv-tile"></div>
            <div class="hv-bar w50"></div>
          </div>
        </div>
      </div>
    <?php break;

    /* ── 2 · Mobile: three phones at depth ── */
    case 'mobile': ?>
      <div class="hv hv-mobile">
        <div class="hv-phone hv-p-back hv-p-left">
          <div class="hv-phone-screen">
            <div class="hv-notch"></div>
            <div class="hv-bar w70"></div><div class="hv-bar w40"></div>
            <div class="hv-list"><i></i><i></i><i></i></div>
          </div>
        </div>
        <div class="hv-phone hv-p-main">
          <div class="hv-phone-screen">
            <div class="hv-notch"></div>
            <div class="hv-app-head"><span class="hv-avatar"></span><span class="hv-bar w45"></span></div>
            <div class="hv-stat-row"><i></i><i></i></div>
            <div class="hv-chart"><i style="--h:38%"></i><i style="--h:62%"></i><i style="--h:48%"></i><i style="--h:80%"></i><i style="--h:66%"></i></div>
            <div class="hv-bar w70"></div><div class="hv-bar w55"></div>
            <div class="hv-pill"></div>
          </div>
        </div>
        <div class="hv-phone hv-p-back hv-p-right">
          <div class="hv-phone-screen">
            <div class="hv-notch"></div>
            <div class="hv-tile"></div>
            <div class="hv-bar w65"></div><div class="hv-bar w45"></div>
          </div>
        </div>
      </div>
    <?php break;

    /* ── 3 · Software: monitor with a code editor ── */
    case 'software': ?>
      <div class="hv hv-software">
        <div class="hv-monitor">
          <div class="hv-screen hv-screen-dark">
            <div class="hv-browser hv-browser-dark">
              <span class="hv-dot"></span><span class="hv-dot"></span><span class="hv-dot"></span>
              <span class="hv-tab">main.ts</span>
            </div>
            <div class="hv-code">
              <div class="hv-code-gutter"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
              <div class="hv-code-body">
                <span class="c-k w30"></span><span class="c-f w45"></span>
                <span class="c-t w55" style="margin-left:14px"></span>
                <span class="c-s w40" style="margin-left:14px"></span>
                <span class="c-t w60" style="margin-left:28px"></span>
                <span class="c-k w25" style="margin-left:14px"></span>
                <span class="c-f w50"></span>
                <span class="c-t w35"></span>
              </div>
            </div>
          </div>
          <div class="hv-stand"></div>
          <div class="hv-foot"></div>
        </div>
        <span class="hv-chip hv-chip-1"><i class="fas fa-code" aria-hidden="true"></i></span>
        <span class="hv-chip hv-chip-2"><i class="fas fa-database" aria-hidden="true"></i></span>
        <span class="hv-chip hv-chip-3"><i class="fas fa-shield-halved" aria-hidden="true"></i></span>
      </div>
    <?php break;

    /* ── 4 · E-commerce: storefront + cart + product cards ── */
    case 'ecommerce': ?>
      <div class="hv hv-shop">
        <div class="hv-laptop">
          <div class="hv-screen">
            <div class="hv-browser">
              <span class="hv-dot"></span><span class="hv-dot"></span><span class="hv-dot"></span>
              <span class="hv-url"></span>
            </div>
            <div class="hv-site">
              <div class="hv-site-nav"><span></span><span></span><span class="hv-site-cta"></span></div>
              <div class="hv-shop-grid"><i></i><i></i><i></i><i></i><i></i><i></i></div>
            </div>
          </div>
          <div class="hv-base"></div>
        </div>
        <div class="hv-card hv-card-cart">
          <span class="hv-card-ico"><i class="fas fa-cart-shopping" aria-hidden="true"></i></span>
          <span class="hv-card-body"><b class="hv-bar w70"></b><i class="hv-bar w45"></i></span>
        </div>
        <div class="hv-card hv-card-pay">
          <span class="hv-card-ico hv-ok"><i class="fas fa-check" aria-hidden="true"></i></span>
          <span class="hv-card-body"><b class="hv-bar w60"></b><i class="hv-bar w40"></i></span>
        </div>
      </div>
    <?php break;

    /* ── 5 · UI/UX: floating screens, palette, type scale ── */
    case 'uiux': ?>
      <div class="hv hv-uiux">
        <div class="hv-panel hv-panel-main">
          <div class="hv-panel-head"><span></span><span class="hv-bar w40"></span></div>
          <div class="hv-panel-cols">
            <div class="hv-panel-side"><i></i><i></i><i></i><i></i></div>
            <div class="hv-panel-body">
              <div class="hv-tile"></div>
              <div class="hv-bar w75"></div><div class="hv-bar w55"></div>
              <div class="hv-stat-row"><i></i><i></i></div>
            </div>
          </div>
        </div>
        <div class="hv-phone hv-p-float">
          <div class="hv-phone-screen">
            <div class="hv-notch"></div>
            <div class="hv-tile"></div>
            <div class="hv-bar w70"></div><div class="hv-bar w50"></div>
            <div class="hv-pill"></div>
          </div>
        </div>
        <div class="hv-swatches"><i></i><i></i><i></i><i></i><i></i></div>
        <div class="hv-type">
          <span class="hv-type-lg">Aa</span>
          <span class="hv-bar w60"></span><span class="hv-bar w40"></span>
        </div>
      </div>
    <?php break;

    /* ── 6 · Marketing: analytics + reach + channels ── */
    case 'marketing': ?>
      <div class="hv hv-mkt">
        <div class="hv-panel hv-panel-main">
          <div class="hv-panel-head"><span></span><span class="hv-bar w40"></span></div>
          <div class="hv-mkt-kpis"><i></i><i></i><i></i></div>
          <svg class="hv-graph" viewBox="0 0 300 110" preserveAspectRatio="none" aria-hidden="true">
            <defs>
              <linearGradient id="hvGraphFill" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#6A00FF" stop-opacity=".22"/>
                <stop offset="100%" stop-color="#6A00FF" stop-opacity="0"/>
              </linearGradient>
              <linearGradient id="hvGraphLine" x1="0" y1="0" x2="1" y2="0">
                <stop offset="0%" stop-color="#FF8A00"/><stop offset="50%" stop-color="#F50072"/>
                <stop offset="100%" stop-color="#6A00FF"/>
              </linearGradient>
            </defs>
            <path d="M0 88 L44 74 L88 80 L132 52 L176 60 L220 30 L264 36 L300 12 L300 110 L0 110 Z" fill="url(#hvGraphFill)"/>
            <path d="M0 88 L44 74 L88 80 L132 52 L176 60 L220 30 L264 36 L300 12"
                  fill="none" stroke="url(#hvGraphLine)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="300" cy="12" r="4" fill="#6A00FF"/>
          </svg>
        </div>
        <div class="hv-card hv-card-reach">
          <span class="hv-card-ico"><i class="fas fa-arrow-trend-up" aria-hidden="true"></i></span>
          <span class="hv-card-body"><b class="hv-bar w65"></b><i class="hv-bar w40"></i></span>
        </div>
        <div class="hv-channels">
          <i class="fab fa-google" aria-hidden="true"></i>
          <i class="fab fa-facebook-f" aria-hidden="true"></i>
          <i class="fab fa-instagram" aria-hidden="true"></i>
          <i class="fab fa-linkedin-in" aria-hidden="true"></i>
        </div>
      </div>
    <?php break;

    default: ?>
      <div class="hv hv-web"><div class="hv-laptop"><div class="hv-screen"></div><div class="hv-base"></div></div></div>
    <?php }
}
}
