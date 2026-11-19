---
name: proppulse-design-palette
description: >-
  Enforces the official PropPulse PMS Wealthsimple Design System & Stylized Palette
  (Warm Light & Obsidian Dark themes) across all views, pages, cards, tables,
  typography, buttons, inputs, status badges, and action controls. Use this skill
  whenever creating, modifying, styling, or reviewing any UI view, PHP template,
  CSS rule, or component in the Property Management application.
---

# PropPulse PMS — Design System & Stylized Palette Skill

Every new view, card, table, form, badge, button, and UI component built in **PropPulse PMS** MUST strictly adhere to the design tokens and component standards defined in this skill and in `public/css/app.css`.

---

## 1. Color Palette & Surface Tokens

Always use the semantic CSS variables below (with fallback hex values where inline styles are used) so components automatically support both **Warm Light** and **Obsidian Dark** modes:

### A. Primary CTA & Active Tab Accent
* **Token**: `var(--ws-accent, #F5A623)`
* **Hover / Active**: `var(--ws-accent-hover, #E69512)` (Light) / `#FFBD59` (Dark); Active `#D68507`
* **Subtle Tint**: `var(--ws-accent-subtle, rgba(245, 166, 35, 0.12))`
* **Usage**: Primary pill CTA buttons (`.btn-primary`), active segmented view tabs, active input focus rings, and accent highlights.

### B. Secondary — Card & Table Column Headers
1. **Distinguished Card Headers (`--table-th-dark`)**:
   * **Background**: `var(--table-th-dark, #d8d3c7)` *(Light: `#D8D3C7` | Dark: `#181A20`)*
   * **Bottom Border**: `1.5px solid var(--table-th-dark-border, #c4bcae)` *(Light: `#C4BCAE` | Dark: `#282B33`)*
   * **Title Color**: `var(--table-th-dark-text, var(--ws-text))` *(Light: `#111111` | Dark: `#F5F6F8`)*
   * **Padding & Typography**: `padding: 16px 20px; font-size: 16px; font-weight: 700; margin: 0;`
2. **Table Column Headers (`.table th` / `--table-th-bg`)**:
   * **Background**: `var(--table-th-bg, #ece8df)` *(Light: `#ECE8DF` | Dark: `#21242B`)*
   * **Bottom Border**: `1.5px solid var(--table-th-border, #ded8cd)` *(Light: `#DED8CD` | Dark: `#2C3039`)*
   * **Text Color**: `var(--table-th-text, #423e37)` *(Light: `#423E37` | Dark: `#CFD3DA`)*
   * **Typography**: `font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; padding: 14px 22px;`

### C. Tertiary — Semantic Status & Financial Figures
* **Success / Active (`.badge-success`)**:
  * Text: `var(--ws-success)` (`#15803D` Light / `#4ADE80` Dark)
  * Background: `var(--ws-success-bg)` (`#EAF7EE` Light / `rgba(74, 222, 128, 0.14)` Dark)
  * Border: `1px solid var(--ws-success-border)` (`#C6EDD1` Light)
* **Warning / Expiring Soon (`.badge-warning`)**:
  * Text: `var(--ws-warning)` (`#B45309` Light / `#FBBF24` Dark)
  * Background: `var(--ws-warning-bg)` (`#FFF6E5` Light)
  * Border: `1px solid var(--ws-warning-border)` (`#FED7AA` Light)
* **Danger / Terminated / Rejected (`.badge-danger`)**:
  * Text: `var(--ws-danger)` (`#DC2626` Light / `#F87171` Dark)
  * Background: `var(--ws-danger-bg)` (`#FEE2E2` Light)
  * Border: `1px solid var(--ws-danger-border)` (`#FCA5A5` Light)
* **Info / Review Workflow (`.badge-info`)**:
  * Text: `var(--ws-info)` (`#1D4ED8` Light / `#60A5FA` Dark)
  * Background: `var(--ws-info-bg)` (`#EFF6FF` Light)
  * Border: `1px solid var(--ws-info-border)` (`#BFDBFE` Light)

### D. Neutral — Page Backdrop, Cards & Text Ink
* **Page Backdrop (`--ws-bg`)**: `#F7F6F2` (Light) / `#0E0F12` (Dark)
* **Card & Table Cell Surface (`--ws-surface` / `--card-bg`)**: `#FFFFFF` (Light) / `#191B1F` (Dark)
* **Subtle / Raised Surface (`--ws-surface-subtle` / `--ws-raised`)**: `#FAF9F6` & `#F1EFE9` (Light) / `#21242A` & `#282B33` (Dark)
* **Borders (`--ws-border` / `--ws-border-subtle`)**: `#EBE7DF` & `#F2EFE9` (Light) / `#353A47` (Dark)
* **Primary Ink (`--ws-text`)**: `#111111` (Light) / `#F5F6F8` (Dark)
* **Secondary Text (`--ws-text-secondary`)**: `#4A4A4A` (Light) / `#A0A6B2` (Dark)
* **Muted Text (`--ws-text-muted`)**: `#737373` (Light) / `#717784` (Dark)
* **Charcoal Accent (`--ws-charcoal`)**: `#191919` (Light) / `#FFFFFF` (Dark)

---

## 2. Typography Scale

* **Primary Font Stack (`--ws-font`)**: `'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
* **Monospace Font Stack (`--ws-mono`)**: `'JetBrains Mono', ui-monospace, SFMono-Regular, monospace`

1. **Page & Card Headlines**:
   * **Page Title (`.page-title` / `h1`)**: `clamp(2rem, 4vw, 2.6rem)`, `font-weight: 800`, `letter-spacing: -0.025em`, `color: var(--ws-text)`
   * **Section / Card Title (`.card-title` / `h2`)**: `16px`, `font-weight: 700`, `color: var(--table-th-dark-text, var(--ws-text))`
2. **Table Cell & Body Copy**:
   * **Base Body**: `15px`, `font-weight: 400`, `line-height: 1.55`, `color: var(--ws-text)`
   * **Table Cell Primary Line (`.table td`)**: `14.5px`, `font-weight: 500`, `color: var(--ws-text)` (links inside table cells must use `color: var(--ws-text); text-decoration: none; font-weight: 500;`)
   * **Table Cell Subtitle (`.text-muted.text-xs`)**: `12px`, `color: var(--ws-text-muted)` (used for `Unit #`, phone numbers, secondary metadata)
3. **Table Column Headers & Labels**:
   * **Column Header (`.table th`)**: `11.5px`, `font-weight: 700`, `text-transform: uppercase`, `letter-spacing: 0.6px`
   * **Status Badges (`.badge`)**: `12px`, `font-weight: 700`, `padding: 4px 12px`, `border-radius: 9999px`

---

## 3. Component & Layout Standards

### A. Data Table Cards
Wrap all primary data tables in a rounded card with the distinguished `#D8D3C7` header:
```html
<div class="card" style="border-radius: 12px; overflow: hidden; border: 1.5px solid var(--ws-border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
    <div class="card-header" style="background-color: var(--table-th-dark, #d8d3c7); border-bottom: 1.5px solid var(--table-th-dark-border, #c4bcae); padding: 16px 20px;">
        <h2 class="card-title" style="margin: 0; font-size: 16px; font-weight: 700; color: var(--table-th-dark-text, var(--ws-text));">
            Section Title
        </h2>
    </div>
    <div class="table-responsive" style="background-color: var(--ws-bg);">
        <table class="table">
            ...
        </table>
    </div>
</div>
```

### B. Segmented View Switcher Pill Tabs
Use pill-shaped buttons (`border-radius: 9999px; padding: 6px 16px;`) above table cards to switch between active, historical, or filtered views:
```html
<div style="display: flex; gap: 8px; margin-bottom: 20px; align-items: center;">
    <a href="..." class="btn btn-primary btn-sm" style="border-radius: 9999px; padding: 6px 16px;">Active View</a>
    <a href="..." class="btn btn-outline btn-sm" style="border-radius: 9999px; padding: 6px 16px;">Historical View</a>
</div>
```

### C. Action Buttons vs. Table Row Action Icon Controls
1. **Standard Action Buttons (`.btn`, `.btn-primary`, `.btn-outline`, `.btn-danger`)**:
   * Must be **strictly text-only** (`border-radius: 9999px`, `font-weight: 700`). Never place `+`, arrows (`→`, `←`), or emojis inside text buttons (per `website-button-standards`).
2. **Table Row Actions Column Icon Controls**:
   * When compact icon buttons are used inside a table's `Actions` column (for View, Edit, Lease Agreement, Delete), use `30px × 30px` (`border-radius: 8px`) outline buttons with `14px–15px` stroke SVGs and explicit `title` and `aria-label` attributes:
   ```html
   <a href="..." class="btn btn-outline btn-xs" title="View Details" aria-label="View Details"
      style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border-radius: 8px;">
       <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
           <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
           <circle cx="12" cy="12" r="3"></circle>
       </svg>
   </a>
   ```

### D. Form Inputs (`.form-control`)
* Use `border-radius: var(--radius-md, 14px)`, `border: 1.5px solid var(--input-border, #dcd8d0)`, `padding: 11px 16px`, `font-size: 14.5px`, and focus ring `border-color: var(--ws-accent); box-shadow: 0 0 0 3px var(--ws-accent-subtle);`.

### E. Layout Stability
* Maintain `overflow-y: scroll; scrollbar-gutter: stable;` on `html` so centered containers (`max-width: 1220px; margin: 0 auto;`) never shift horizontally when navigating between pages of different heights.
