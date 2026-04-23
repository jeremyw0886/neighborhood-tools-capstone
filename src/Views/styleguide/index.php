<?php

/**
 * Style Guide — internal design-system reference.
 *
 * Renders every design token, typography specimen, component variant,
 * and state in one place using the live, shipped CSS. Breaking a token
 * or a component rule breaks this page, so it doubles as a visual
 * smoke test for the design system.
 *
 * @see src/Controllers/styleguide_controller.php
 * @see public/assets/css/base.css     — tokens this page documents
 * @see public/assets/css/components.css — components this page showcases
 */
?>
<article class="styleguide-page" aria-labelledby="styleguide-heading">

  <header>
    <h1 id="styleguide-heading"><i class="fa-solid fa-palette" aria-hidden="true"></i> NeighborhoodTools Style Guide</h1>
    <p>A living reference to every design token, typography specimen, component, and state used across the platform. Every example below is rendered with the production CSS — if a token drifts, the page shows it.</p>

    <dl>
      <dt>Philosophy</dt>
      <dd>Semantic structure first, classes as last resort. Modern CSS (Grid, container queries, <code>:has()</code>, <code>color-mix()</code>). No <code>!important</code>. Specificity-driven cascade.</dd>
      <dt>Typography</dt>
      <dd>Fluid type scale via <code>clamp()</code>. System font stack. Line-length clamped to ~65ch for readability.</dd>
      <dt>Accessibility</dt>
      <dd>WCAG AA target. Visible focus rings on every interactive element. 44px minimum touch targets. Reduced-motion and forced-colors specimens below.</dd>
    </dl>
  </header>

  <div class="sg-body">

    <nav aria-label="Style guide contents">
      <h2>Contents</h2>
      <ol>
        <li><a href="#colors">Color tokens</a></li>
        <li><a href="#typography">Typography</a></li>
        <li><a href="#spacing">Spacing &amp; radii</a></li>
        <li><a href="#buttons">Buttons</a></li>
        <li><a href="#forms">Form fields</a></li>
        <li><a href="#badges">Badges</a></li>
        <li><a href="#alerts">Alerts</a></li>
        <li><a href="#tables">Tables</a></li>
        <li><a href="#pagination">Pagination</a></li>
        <li><a href="#modals">Modals</a></li>
        <li><a href="#accessibility">Accessibility</a></li>
        <li><a href="#semantics">Semantic patterns</a></li>
      </ol>
    </nav>

    <div class="sg-sections">

      <!-- ================================================================
           Color tokens
           ================================================================ -->
      <section id="colors" aria-labelledby="colors-heading">
        <h2 id="colors-heading">Color tokens</h2>
        <p>All brand colors live in <code>base.css</code> as custom properties. Use the variable name, never the hex — it lets the palette evolve without touching component CSS.</p>

        <h3>Primary — Deep Pine</h3>
        <ul class="swatch-grid">
          <li>
            <span class="swatch-chip" data-token="--mountain-pine" aria-hidden="true"></span>
            <span class="swatch-label">Pine</span>
            <span class="swatch-var">var(--mountain-pine)</span>
            <span class="swatch-hex">#1b3a2f</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--mountain-pine-dark" aria-hidden="true"></span>
            <span class="swatch-label">Pine Dark</span>
            <span class="swatch-var">var(--mountain-pine-dark)</span>
            <span class="swatch-hex">#0f251d</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--mountain-pine-light" aria-hidden="true"></span>
            <span class="swatch-label">Pine Light</span>
            <span class="swatch-var">var(--mountain-pine-light)</span>
            <span class="swatch-hex">#3a6a55</span>
          </li>
        </ul>

        <h3>Secondary — Misty Mountain</h3>
        <ul class="swatch-grid">
          <li>
            <span class="swatch-chip" data-token="--mountain-mist" aria-hidden="true"></span>
            <span class="swatch-label">Mist</span>
            <span class="swatch-var">var(--mountain-mist)</span>
            <span class="swatch-hex">#38444b</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--mountain-mist-light" aria-hidden="true"></span>
            <span class="swatch-label">Mist Light</span>
            <span class="swatch-var">var(--mountain-mist-light)</span>
            <span class="swatch-hex">#8ea1ab</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--mountain-slate" aria-hidden="true"></span>
            <span class="swatch-label">Slate</span>
            <span class="swatch-var">var(--mountain-slate)</span>
            <span class="swatch-hex">#394a53</span>
          </li>
        </ul>

        <h3>Accent — Golden Sunset</h3>
        <ul class="swatch-grid">
          <li>
            <span class="swatch-chip" data-token="--mountain-gold" aria-hidden="true"></span>
            <span class="swatch-label">Gold</span>
            <span class="swatch-var">var(--mountain-gold)</span>
            <span class="swatch-hex">#c89b3c</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--mountain-gold-light" aria-hidden="true"></span>
            <span class="swatch-label">Gold Light</span>
            <span class="swatch-var">var(--mountain-gold-light)</span>
            <span class="swatch-hex">#e0bd71</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--mountain-gold-dark" aria-hidden="true"></span>
            <span class="swatch-label">Gold Dark</span>
            <span class="swatch-var">var(--mountain-gold-dark)</span>
            <span class="swatch-hex">#a1792b</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--mountain-gold-darker" aria-hidden="true"></span>
            <span class="swatch-label">Gold Darker</span>
            <span class="swatch-var">var(--mountain-gold-darker)</span>
            <span class="swatch-hex">#856828</span>
          </li>
        </ul>

        <h3>Earth tones</h3>
        <ul class="swatch-grid">
          <li>
            <span class="swatch-chip" data-token="--mountain-earth" aria-hidden="true"></span>
            <span class="swatch-label">Earth</span>
            <span class="swatch-var">var(--mountain-earth)</span>
            <span class="swatch-hex">#7a5a3d</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--mountain-bark" aria-hidden="true"></span>
            <span class="swatch-label">Bark</span>
            <span class="swatch-var">var(--mountain-bark)</span>
            <span class="swatch-hex">#4c3626</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--mountain-stone" aria-hidden="true"></span>
            <span class="swatch-label">Stone</span>
            <span class="swatch-var">var(--mountain-stone)</span>
            <span class="swatch-hex">#312d26</span>
          </li>
        </ul>

        <h3>Neutrals</h3>
        <ul class="swatch-grid">
          <li>
            <span class="swatch-chip" data-token="--mountain-cream" data-light="true" aria-hidden="true"></span>
            <span class="swatch-label">Cream (page bg)</span>
            <span class="swatch-var">var(--mountain-cream)</span>
            <span class="swatch-hex">#f4efe7</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--mountain-fog" data-light="true" aria-hidden="true"></span>
            <span class="swatch-label">Fog</span>
            <span class="swatch-var">var(--mountain-fog)</span>
            <span class="swatch-hex">#e0e2db</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--mountain-cloud" data-light="true" aria-hidden="true"></span>
            <span class="swatch-label">Cloud</span>
            <span class="swatch-var">var(--mountain-cloud)</span>
            <span class="swatch-hex">#fbfaf6</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--mountain-charcoal" aria-hidden="true"></span>
            <span class="swatch-label">Charcoal (body text)</span>
            <span class="swatch-var">var(--mountain-charcoal)</span>
            <span class="swatch-hex">#2a2a2a</span>
          </li>
        </ul>

        <h3>Semantic — Success</h3>
        <ul class="swatch-grid">
          <li>
            <span class="swatch-chip" data-token="--color-success-100" data-light="true" aria-hidden="true"></span>
            <span class="swatch-label">Success 100 (bg)</span>
            <span class="swatch-var">var(--color-success-100)</span>
            <span class="swatch-hex">#d1fae5</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--color-success-500" aria-hidden="true"></span>
            <span class="swatch-label">Success 500</span>
            <span class="swatch-var">var(--color-success-500)</span>
            <span class="swatch-hex">#16a34a</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--color-success-700" aria-hidden="true"></span>
            <span class="swatch-label">Success 700 (text)</span>
            <span class="swatch-var">var(--color-success-700)</span>
            <span class="swatch-hex">#166534</span>
          </li>
        </ul>

        <h3>Semantic — Danger</h3>
        <ul class="swatch-grid">
          <li>
            <span class="swatch-chip" data-token="--color-danger-100" data-light="true" aria-hidden="true"></span>
            <span class="swatch-label">Danger 100</span>
            <span class="swatch-var">var(--color-danger-100)</span>
            <span class="swatch-hex">#fee2e2</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--color-danger-500" aria-hidden="true"></span>
            <span class="swatch-label">Danger 500</span>
            <span class="swatch-var">var(--color-danger-500)</span>
            <span class="swatch-hex">#ef4444</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--color-danger-700" aria-hidden="true"></span>
            <span class="swatch-label">Danger 700</span>
            <span class="swatch-var">var(--color-danger-700)</span>
            <span class="swatch-hex">#b91c1c</span>
          </li>
        </ul>

        <h3>Semantic — Warning</h3>
        <ul class="swatch-grid">
          <li>
            <span class="swatch-chip" data-token="--color-warning-100" data-light="true" aria-hidden="true"></span>
            <span class="swatch-label">Warning 100</span>
            <span class="swatch-var">var(--color-warning-100)</span>
            <span class="swatch-hex">#fef3c7</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--color-warning-500" aria-hidden="true"></span>
            <span class="swatch-label">Warning 500</span>
            <span class="swatch-var">var(--color-warning-500)</span>
            <span class="swatch-hex">#f59e0b</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--color-warning-700" aria-hidden="true"></span>
            <span class="swatch-label">Warning 700</span>
            <span class="swatch-var">var(--color-warning-700)</span>
            <span class="swatch-hex">#b45309</span>
          </li>
        </ul>

        <h3>Semantic — Info</h3>
        <ul class="swatch-grid">
          <li>
            <span class="swatch-chip" data-token="--color-info-100" data-light="true" aria-hidden="true"></span>
            <span class="swatch-label">Info 100</span>
            <span class="swatch-var">var(--color-info-100)</span>
            <span class="swatch-hex">#dbeafe</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--color-info-500" aria-hidden="true"></span>
            <span class="swatch-label">Info 500</span>
            <span class="swatch-var">var(--color-info-500)</span>
            <span class="swatch-hex">#3b82f6</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--color-info-700" aria-hidden="true"></span>
            <span class="swatch-label">Info 700</span>
            <span class="swatch-var">var(--color-info-700)</span>
            <span class="swatch-hex">#1d4ed8</span>
          </li>
        </ul>

        <h3>Semantic — Special / High</h3>
        <ul class="swatch-grid">
          <li>
            <span class="swatch-chip" data-token="--color-special-600" aria-hidden="true"></span>
            <span class="swatch-label">Special (super admin)</span>
            <span class="swatch-var">var(--color-special-600)</span>
            <span class="swatch-hex">#7c3aed</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--color-high-600" aria-hidden="true"></span>
            <span class="swatch-label">High (escalation)</span>
            <span class="swatch-var">var(--color-high-600)</span>
            <span class="swatch-hex">#ea580c</span>
          </li>
        </ul>

        <h3>Shadows &amp; halos</h3>
        <ul class="swatch-grid">
          <li>
            <span class="swatch-chip" data-token="--shadow-pine" data-light="true" aria-hidden="true"></span>
            <span class="swatch-label">Shadow — Pine</span>
            <span class="swatch-var">var(--shadow-pine)</span>
            <span class="swatch-hex">rgba(27,58,47,0.18)</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--shadow-mountain" data-light="true" aria-hidden="true"></span>
            <span class="swatch-label">Shadow — Mountain</span>
            <span class="swatch-var">var(--shadow-mountain)</span>
            <span class="swatch-hex">rgba(57,74,83,0.14)</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--glow-pine-soft" data-light="true" aria-hidden="true"></span>
            <span class="swatch-label">Glow — Soft</span>
            <span class="swatch-var">var(--glow-pine-soft)</span>
            <span class="swatch-hex">rgba(58,106,85,0.12)</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--glow-pine" data-light="true" aria-hidden="true"></span>
            <span class="swatch-label">Glow — Base</span>
            <span class="swatch-var">var(--glow-pine)</span>
            <span class="swatch-hex">rgba(58,106,85,0.15)</span>
          </li>
          <li>
            <span class="swatch-chip" data-token="--glow-pine-strong" data-light="true" aria-hidden="true"></span>
            <span class="swatch-label">Glow — Strong</span>
            <span class="swatch-var">var(--glow-pine-strong)</span>
            <span class="swatch-hex">rgba(58,106,85,0.2)</span>
          </li>
        </ul>
      </section>

      <!-- ================================================================
           Typography
           ================================================================ -->
      <section id="typography" aria-labelledby="typography-heading">
        <h2 id="typography-heading">Typography</h2>
        <p>Fluid type scale driven by <code>clamp()</code> — values grow with viewport width, so there's no "mobile vs desktop" breakpoint fight. System font stack; no web-font payload.</p>

        <figure>
          <dl>
            <div class="type-specimen">
              <dt>h1 <br><code>clamp(2rem, 1.5rem + 2vw, 2.75rem)</code></dt>
              <dd>
                <h1>The quick brown fox jumps over the lazy dog</h1>
              </dd>
            </div>
            <div class="type-specimen">
              <dt>h2 <br><code>var(--font-size-h2)</code></dt>
              <dd>
                <h2>The quick brown fox jumps over the lazy dog</h2>
              </dd>
            </div>
            <div class="type-specimen">
              <dt>h3 <br><code>var(--font-size-h3)</code></dt>
              <dd>
                <h3>The quick brown fox jumps over the lazy dog</h3>
              </dd>
            </div>
            <div class="type-specimen">
              <dt>h4 <br><code>var(--font-size-h4)</code></dt>
              <dd>
                <h4>The quick brown fox jumps over the lazy dog</h4>
              </dd>
            </div>
            <div class="type-specimen">
              <dt>body <br><code>clamp(0.938rem, 0.75rem + 0.5vw, 1.125rem)</code></dt>
              <dd>
                <p>The quick brown fox jumps over the lazy dog. Line length clamped to <code>65ch</code> for comfortable reading.</p>
              </dd>
            </div>
            <div class="type-specimen">
              <dt>body-lg <br><code>var(--font-size-body-lg)</code></dt>
              <dd>
                <p class="sg-lead">Lead paragraph — larger body copy used for page intros.</p>
              </dd>
            </div>
            <div class="type-specimen">
              <dt>xs <br><code>var(--font-size-xs)</code></dt>
              <dd><small>Small metadata, timestamps, helper text.</small></dd>
            </div>
          </dl>
        </figure>
      </section>

      <!-- ================================================================
           Spacing & radii
           ================================================================ -->
      <section id="spacing" aria-labelledby="spacing-heading">
        <h2 id="spacing-heading">Spacing &amp; radii</h2>
        <p>Standardized radii and paddings drive consistent visual rhythm across buttons, cards, and interactive controls.</p>

        <h3>Border radii</h3>
        <figure>
          <div class="spec-row">
            <code>0.25rem</code>
            <span class="spec-sample" data-radius="sm" aria-hidden="true"></span>
            <span class="spec-note">Small — inline code, subtle elements</span>
          </div>
          <div class="spec-row">
            <code>0.5rem / var(--btn-radius)</code>
            <span class="spec-sample" data-radius="md" aria-hidden="true"></span>
            <span class="spec-note">Medium — buttons, cards, inputs (default)</span>
          </div>
          <div class="spec-row">
            <code>1rem</code>
            <span class="spec-sample" data-radius="lg" aria-hidden="true"></span>
            <span class="spec-note">Large — hero sections, feature cards</span>
          </div>
          <div class="spec-row">
            <code>2rem / var(--badge-radius)</code>
            <span class="spec-sample" data-radius="pill" aria-hidden="true"></span>
            <span class="spec-note">Pill — badges, pill-shaped buttons</span>
          </div>
        </figure>

        <h3>Button paddings</h3>
        <figure>
          <div class="spec-row">
            <code>--btn-pad-sm</code>
            <button type="button" data-intent="primary" data-size="sm">Small</button>
            <span class="spec-note"><code>0.5rem 1rem</code>, min-height 2.25rem</span>
          </div>
          <div class="spec-row">
            <code>--btn-pad-md</code>
            <button type="button" data-intent="primary">Default</button>
            <span class="spec-note"><code>0.65rem 1.5rem</code>, min-height 2.75rem (implicit)</span>
          </div>
          <div class="spec-row">
            <code>--btn-pad-lg</code>
            <button type="button" data-intent="primary" data-size="lg">Large</button>
            <span class="spec-note"><code>0.75rem 2rem</code>, min-height 3rem</span>
          </div>
          <figcaption>Padding and min-height apply from the base <code>button:where([type])</code> rule. A <code>data-intent</code> is only needed for the visible background — the paddings above would be applied even on an unstyled button, just invisibly.</figcaption>
        </figure>
      </section>

      <!-- ================================================================
           Buttons
           ================================================================ -->
      <section id="buttons" aria-labelledby="buttons-heading">
        <h2 id="buttons-heading">Buttons</h2>
        <p>Targeted via <code>button:where([type])</code> + <code>[data-intent]</code> / <code>[data-size]</code> / <code>[data-shape]</code>. Single source of truth in <code>components.css</code>; every intent honors <code>:hover</code>, <code>:focus-visible</code>, <code>:active</code>, <code>:disabled</code>.</p>

        <h3>Intents</h3>
        <figure>
          <div class="demo-row">
            <button type="button" data-intent="primary">Primary</button>
            <button type="button" data-intent="success">Success</button>
            <button type="button" data-intent="danger">Danger</button>
            <button type="button" data-intent="warning">Warning</button>
            <button type="button" data-intent="info">Info</button>
            <button type="button" data-intent="secondary">Secondary</button>
            <button type="button" data-intent="ghost">Ghost</button>
          </div>
          <figcaption>Six semantic intents plus <code>secondary</code> (outlined) and <code>ghost</code> (transparent). <code>type="submit"</code> without a data-intent gets primary treatment implicitly.</figcaption>
        </figure>

        <h3>Sizes</h3>
        <figure>
          <div class="demo-row">
            <button type="button" data-intent="primary" data-size="sm">Small</button>
            <button type="button" data-intent="primary">Default</button>
            <button type="button" data-intent="primary" data-size="lg">Large</button>
          </div>
          <figcaption>Three size tiers — all clear WCAG 44×44 touch-target minimum on default and large.</figcaption>
        </figure>

        <h3>Shapes</h3>
        <figure>
          <div class="demo-row">
            <button type="button" data-intent="primary">Default radius</button>
            <button type="button" data-intent="primary" data-shape="pill">Pill shape</button>
            <button type="button" data-intent="primary" data-shape="icon" aria-label="Settings">
              <i class="fa-solid fa-gear" aria-hidden="true"></i>
            </button>
          </div>
          <figcaption>Icon-only buttons must carry <code>aria-label</code> so screen readers announce the action.</figcaption>
        </figure>

        <h3>Full width</h3>
        <figure>
          <button type="button" data-intent="primary" data-width="full">Full-width CTA</button>
          <figcaption>Used in auth cards, dialog footers, mobile sticky action bars.</figcaption>
        </figure>

        <h3>States</h3>
        <figure>
          <table class="state-matrix">
            <thead>
              <tr>
                <th scope="col">Intent</th>
                <th scope="col">Default</th>
                <th scope="col">Disabled</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th scope="row">primary</th>
                <td><button type="button" data-intent="primary">Submit</button></td>
                <td><button type="button" data-intent="primary" disabled>Submit</button></td>
              </tr>
              <tr>
                <th scope="row">success</th>
                <td><button type="button" data-intent="success">Approve</button></td>
                <td><button type="button" data-intent="success" disabled>Approve</button></td>
              </tr>
              <tr>
                <th scope="row">danger</th>
                <td><button type="button" data-intent="danger">Delete</button></td>
                <td><button type="button" data-intent="danger" disabled>Delete</button></td>
              </tr>
              <tr>
                <th scope="row">secondary</th>
                <td><button type="button" data-intent="secondary">Cancel</button></td>
                <td><button type="button" data-intent="secondary" disabled>Cancel</button></td>
              </tr>
            </tbody>
          </table>
          <figcaption>Hover and focus states aren't captured in a static matrix — hover or tab into any button above to see them live.</figcaption>
        </figure>

        <h3>Anchors as buttons</h3>
        <figure>
          <div class="demo-row">
            <a href="#buttons" role="button" data-intent="primary">Link styled as button</a>
            <a href="#buttons" role="button" data-intent="secondary">Secondary link</a>
          </div>
          <figcaption>Use <code>a[role="button"]</code> when the target is a URL. Keep real <code>&lt;button&gt;</code> for actions that POST or mutate state.</figcaption>
        </figure>
      </section>

      <!-- ================================================================
           Form fields
           ================================================================ -->
      <section id="forms" aria-labelledby="forms-heading">
        <h2 id="forms-heading">Form fields</h2>
        <p>Form styling targets elements by attribute (<code>input[type="email"]</code>, <code>input[type="date"]</code>) inside a <code>&lt;fieldset&gt;</code>. Error state via <code>aria-invalid</code>. Required indicator via <code>:required</code> + <code>::after</code>.</p>

        <h3>Text inputs</h3>
        <figure>
          <form action="/styleguide" method="get">
            <fieldset>
              <legend>Tool listing</legend>
              <label>
                Tool name
                <input type="text" name="sg_name" value="DeWalt cordless drill">
              </label>
              <label>
                Daily rate (USD) <span aria-hidden="true">*</span>
                <input type="number" name="sg_rate" value="12" min="0" step="1" required>
              </label>
              <label>
                Pickup time
                <input type="time" name="sg_time" value="14:30">
              </label>
              <label>
                Delivery note
                <input type="text" name="sg_note" placeholder="e.g. leave at the side gate">
              </label>
            </fieldset>
          </form>
          <figcaption>The generic fieldset selector targets <code>type="text"</code>, <code>number</code>, <code>date</code>, <code>time</code>, plus <code>select</code> and <code>textarea</code>. <code>email</code>, <code>tel</code>, and <code>password</code> live under the separate <code>.auth-card</code> pattern used by the login and register forms.</figcaption>
        </figure>

        <h3>Select, textarea, date</h3>
        <figure>
          <form action="/styleguide" method="get">
            <fieldset>
              <legend>Tool details</legend>
              <label>
                Category
                <select name="sg_cat">
                  <option>Power tools</option>
                  <option>Hand tools</option>
                  <option>Garden &amp; outdoor</option>
                  <option>Ladders</option>
                </select>
              </label>
              <label>
                Description
                <textarea name="sg_desc" rows="3">A 20-volt cordless drill with two batteries and a charger. Lightly used.</textarea>
              </label>
              <label>
                Available from
                <input type="date" name="sg_date" value="2026-05-01">
              </label>
            </fieldset>
          </form>
        </figure>

        <h3>Error state</h3>
        <figure>
          <form action="/styleguide" method="get">
            <fieldset>
              <legend>Tool name</legend>
              <label>
                Tool name <span aria-hidden="true">*</span>
                <input type="text" name="sg_bad_name" value="" aria-invalid="true" aria-describedby="sg-name-err" required>
              </label>
              <p id="sg-name-err" role="alert">Tool name is required.</p>
            </fieldset>
          </form>
          <figcaption><code>aria-invalid="true"</code> paints the error border (<code>--field-error-border</code>); the associated <code>p[role="alert"]</code> picks up the danger palette automatically.</figcaption>
        </figure>

        <h3>Disabled &amp; readonly</h3>
        <figure>
          <form action="/styleguide" method="get">
            <fieldset>
              <legend>Locked fields</legend>
              <label>
                Username (readonly)
                <input type="text" name="sg_ro" value="jeremyw" readonly>
              </label>
              <label>
                Membership (disabled)
                <input type="text" name="sg_dis" value="Core member" disabled>
              </label>
            </fieldset>
          </form>
        </figure>
      </section>

      <!-- ================================================================
           Badges
           ================================================================ -->
      <section id="badges" aria-labelledby="badges-heading">
        <h2 id="badges-heading">Badges</h2>
        <p>Auto-styled via attribute selectors — <code>[data-status]</code>, <code>[data-timing]</code>, <code>[data-urgency]</code>, <code>[data-role]</code>, <code>[data-condition]</code>, <code>[data-badge]</code>. The right token lights up automatically; no utility classes.</p>

        <h3>Status</h3>
        <figure>
          <div class="demo-row">
            <span data-status="active">Active</span>
            <span data-status="pending">Pending</span>
            <span data-status="approved">Approved</span>
            <span data-status="borrowed">Borrowed</span>
            <span data-status="returned">Returned</span>
            <span data-status="overdue">Overdue</span>
            <span data-status="denied">Denied</span>
            <span data-status="cancelled">Cancelled</span>
            <span data-status="resolved">Resolved</span>
            <span data-status="dismissed">Dismissed</span>
            <span data-status="suspended">Suspended</span>
            <span data-status="super_admin">Super admin</span>
          </div>
        </figure>

        <h3>Timing</h3>
        <figure>
          <div class="demo-row">
            <span data-timing="happening-now">Happening now</span>
            <span data-timing="this-week">This week</span>
            <span data-timing="this-month">This month</span>
            <span data-timing="upcoming">Upcoming</span>
            <span data-timing="past">Past</span>
          </div>
        </figure>

        <h3>Urgency</h3>
        <figure>
          <div class="demo-row">
            <span data-urgency="critical">Critical</span>
            <span data-urgency="high">High</span>
            <span data-urgency="moderate">Moderate</span>
            <span data-urgency="new">New</span>
          </div>
        </figure>

        <h3>Role &amp; condition</h3>
        <figure>
          <div class="demo-row">
            <span data-role="member">Member</span>
            <span data-role="admin">Admin</span>
            <span data-role="super_admin">Super admin</span>
          </div>
          <div class="demo-row">
            <span data-condition="new">New</span>
            <span data-condition="good">Good</span>
            <span data-condition="fair">Fair</span>
            <span data-condition="poor">Poor</span>
          </div>
        </figure>

        <h3>Named badges</h3>
        <figure>
          <div class="demo-row">
            <span data-badge="new">New</span>
            <span data-badge="lent">Lent</span>
            <span data-badge="owner">Owner</span>
            <span data-badge="deposit_hold">Deposit held</span>
            <span data-badge="deposit_release">Ready for release</span>
            <span data-badge="deposit_forfeit">Forfeit</span>
            <span data-badge="rental_fee">Rental fee</span>
          </div>
          <figcaption>Named badges map to domain concepts the platform tracks internally (deposit lifecycle, listing state).</figcaption>
        </figure>
      </section>

      <!-- ================================================================
           Alerts
           ================================================================ -->
      <section id="alerts" aria-labelledby="alerts-heading">
        <h2 id="alerts-heading">Alerts &amp; flash messages</h2>
        <p>Flash messages use <code>[data-flash]</code> attribute with four variants. Icon is auto-generated via <code>::before</code> pseudo-element — no markup pollution.</p>

        <figure>
          <p role="status" data-flash="success">Your tool listing has been published.</p>
          <p role="alert" data-flash="error">We couldn't process that request. Please try again.</p>
          <p role="status" data-flash="warning">Your session expires in 2 minutes.</p>
          <p role="status" data-flash="info">The TOS was updated on April 21, 2026.</p>
          <figcaption>Use <code>role="alert"</code> for errors (announced immediately) and <code>role="status"</code> for non-urgent feedback.</figcaption>
        </figure>
      </section>

      <!-- ================================================================
           Tables
           ================================================================ -->
      <section id="tables" aria-labelledby="tables-heading">
        <h2 id="tables-heading">Tables</h2>
        <p>Zebra striping via <code>tbody tr:nth-child(even)</code>, hover tint via <code>color-mix()</code>. Row headings use <code>th[scope="row"]</code>.</p>

        <figure>
          <table>
            <thead>
              <tr>
                <th scope="col">Tool</th>
                <th scope="col">Owner</th>
                <th scope="col">Status</th>
                <th scope="col">Last borrowed</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th scope="row">Cordless drill</th>
                <td>Allyson</td>
                <td><span data-status="active">Available</span></td>
                <td>2 days ago</td>
              </tr>
              <tr>
                <th scope="row">Extension ladder</th>
                <td>Jeremiah</td>
                <td><span data-status="borrowed">Borrowed</span></td>
                <td>6 days ago</td>
              </tr>
              <tr>
                <th scope="row">Hedge trimmer</th>
                <td>Chantelle</td>
                <td><span data-status="overdue">Overdue</span></td>
                <td>3 weeks ago</td>
              </tr>
              <tr>
                <th scope="row">Pressure washer</th>
                <td>Alec</td>
                <td><span data-status="pending">Pending</span></td>
                <td>Never</td>
              </tr>
            </tbody>
          </table>
          <figcaption>Tables fill their container width. Hover any row to see the subtle pine tint applied via <code>color-mix()</code>.</figcaption>
        </figure>
      </section>

      <!-- ================================================================
           Pagination
           ================================================================ -->
      <section id="pagination" aria-labelledby="pagination-heading">
        <h2 id="pagination-heading">Pagination</h2>
        <p>Styled via <code>nav[aria-label$="pagination" i]</code> — matches any nav with an <code>aria-label</code> ending in "pagination" (case-insensitive). No classes required on the nav element.</p>

        <figure>
          <nav aria-label="Demo pagination">
            <ul>
              <li>
                <span aria-disabled="true">
                  <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                  <span>Previous</span>
                </span>
              </li>
              <li><a href="#pagination" aria-current="page" aria-label="Page 1, current page">1</a></li>
              <li><a href="#pagination" aria-label="Go to page 2">2</a></li>
              <li><a href="#pagination" aria-label="Go to page 3">3</a></li>
              <li><span aria-hidden="true">&hellip;</span></li>
              <li><a href="#pagination" aria-label="Go to page 12">12</a></li>
              <li>
                <a href="#pagination" aria-label="Go to next page">
                  <span>Next</span>
                  <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>
              </li>
            </ul>
          </nav>
          <figcaption>First page — "Previous" disabled, "1" has <code>aria-current="page"</code>.</figcaption>
        </figure>

        <figure>
          <nav aria-label="Demo pagination 2">
            <ul>
              <li>
                <a href="#pagination" aria-label="Go to previous page">
                  <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                  <span>Previous</span>
                </a>
              </li>
              <li><a href="#pagination" aria-label="Go to page 1">1</a></li>
              <li><span aria-hidden="true">&hellip;</span></li>
              <li><a href="#pagination" aria-label="Go to page 4">4</a></li>
              <li><a href="#pagination" aria-current="page" aria-label="Page 5, current page">5</a></li>
              <li><a href="#pagination" aria-label="Go to page 6">6</a></li>
              <li><span aria-hidden="true">&hellip;</span></li>
              <li><a href="#pagination" aria-label="Go to page 12">12</a></li>
              <li>
                <a href="#pagination" aria-label="Go to next page">
                  <span>Next</span>
                  <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>
              </li>
            </ul>
          </nav>
          <figcaption>Middle page — windowed range with ellipses on both sides.</figcaption>
        </figure>
      </section>

      <!-- ================================================================
           Modals
           ================================================================ -->
      <section id="modals" aria-labelledby="modals-heading">
        <h2 id="modals-heading">Modals</h2>
        <p>Modals use the native <code>&lt;dialog&gt;</code> element with <code>::backdrop</code>. Container queries (<code>container-type: inline-size</code>) let the modal re-layout without tripping viewport breakpoints.</p>

        <figure>
          <div class="demo-row">
            <a href="/how-to" role="button" data-intent="primary" data-modal="how-to">
              <i class="fa-solid fa-book" aria-hidden="true"></i> Open "How It Works"
            </a>
            <a href="/faq" role="button" data-intent="secondary" data-modal="faq">
              <i class="fa-solid fa-circle-question" aria-hidden="true"></i> Open FAQ
            </a>
            <a href="/tos" role="button" data-intent="secondary" data-modal="tos">
              <i class="fa-solid fa-file-contract" aria-hidden="true"></i> Open TOS
            </a>
          </div>
          <figcaption>Each link has an <code>href</code> fallback — without JavaScript, it navigates to the full page; with JS, <code>data-modal</code> opens the dialog. Progressive enhancement by default.</figcaption>
        </figure>
      </section>

      <!-- ================================================================
           Accessibility
           ================================================================ -->
      <section id="accessibility" aria-labelledby="a11y-heading">
        <h2 id="a11y-heading">Accessibility specimens</h2>
        <p>Every interactive surface on this page is keyboard-reachable and has a visible focus ring. Tab through any section to verify.</p>

        <h3>Skip-to-content link</h3>
        <figure>
          <div class="a11y-specimen">
            <strong>Try it:</strong>
            <span>Press <kbd>Tab</kbd> once from the top of the page — the "Skip to main content" link appears at the top-left of the viewport.</span>
          </div>
        </figure>

        <h3>Focus ring</h3>
        <figure>
          <div class="a11y-specimen">
            <strong>Try it:</strong>
            <span>Tab through these buttons — each gets a pine outline + 2px offset.</span>
            <div class="demo-row">
              <button type="button" data-intent="primary">Button</button>
              <a href="#accessibility">Link</a>
              <input type="text" value="Input" aria-label="Focus demo input">
            </div>
          </div>
        </figure>

        <h3>Visually hidden</h3>
        <figure>
          <div class="a11y-specimen">
            <strong>Try it:</strong>
            <span>This button's label is hidden visually but announced by screen readers:</span>
            <button type="button" data-intent="primary" data-shape="icon">
              <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
              <span class="visually-hidden">Search tools</span>
            </button>
          </div>
        </figure>

        <h3>Reduced motion</h3>
        <figure>
          <div class="a11y-specimen">
            <strong>How to check:</strong>
            <span>DevTools → Rendering → Emulate CSS media feature <code>prefers-reduced-motion: reduce</code>. Every transition and animation on the site drops to 0.01ms — hover effects snap instead of ease.</span>
          </div>
        </figure>

        <h3>Forced colors</h3>
        <figure>
          <div class="a11y-specimen">
            <strong>How to check:</strong>
            <span>DevTools → Rendering → Emulate CSS media feature <code>forced-colors: active</code>. Structural borders remain on cards, figures, and a11y specimens so the layout stays legible against the system palette.</span>
          </div>
        </figure>
      </section>

      <!-- ================================================================
           Semantic selector philosophy
           ================================================================ -->
      <section id="semantics" aria-labelledby="semantics-heading">
        <h2 id="semantics-heading">Semantic-selector philosophy</h2>
        <p>The codebase targets HTML structure and ARIA state before reaching for classes. This keeps markup clean, makes screen-reader semantics load-bearing, and means a rogue <code>div.flex.items-center.justify-between</code> doesn't slip in under code review.</p>

        <h3>Active nav item</h3>
        <pre><code>/* Not: .nav-link.active { ... }  */
nav a[aria-current="page"] { ... }</code></pre>
        <p>The screen reader gets "current page" for free; CSS gets the styling hook for free. One attribute, two wins.</p>

        <h3>Form field by input type</h3>
        <pre><code>/* Not: .form-input, .form-input--email { ... } */
fieldset :is(input[type="text"], input[type="email"], select, textarea) { ... }</code></pre>
        <p>Adding a new field type requires zero markup changes — the selector already covers it.</p>

        <h3>Pagination nav by aria-label</h3>
        <pre><code>/* Not: .pagination, nav.pagination { ... } */
nav[aria-label$="pagination" i] { ... }</code></pre>
        <p>The ARIA label serves both the screen reader and the stylesheet. Case-insensitive suffix match lets every pagination block use a descriptive label (<code>aria-label="Tool pagination"</code>) without coordinating a shared class name.</p>

        <h3>Ownership check on a tool card</h3>
        <pre><code>/* Not: .tool-card.is-owned { ... }    */
article[data-tool-id]:has(&gt; [data-badge="owner"]) { ... }</code></pre>
        <p><code>:has()</code> eliminates the "sync a class from the server" step — if the owner badge is there, the owned-state styling applies automatically.</p>
      </section>

    </div>
  </div>
</article>