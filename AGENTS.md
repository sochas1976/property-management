# Property Management UI & Design Standards

## 1. Card & Table Header Color Standards
Always follow these precise color tokens for card headers and data table headers across all views in the application:

### A. Card Headers (Accent / Distinguished Headers)
* **CSS Variable**: `var(--table-th-dark)`
* **Background Color**: `#d8d3c7` *(RGB: 216, 211, 199)* (Dark mode: `#181a20`)
* **Bottom Border**: `1.5px solid var(--table-th-dark-border, #c4bcae)` *(RGB: 196, 188, 174)* (Dark mode: `#282b33`)
* **Text / Title Color**: `var(--table-th-dark-text, var(--ws-text))` (`#111111` in light mode, `#f5f6f8` in dark mode)
* **Typography**: `font-size: 16px; font-weight: 700; margin: 0;`
* **Padding**: `16px 20px;`

### B. Table Column Headers (`.table th` row)
* **CSS Variable**: `var(--table-th-bg)`
* **Background Color**: `#ece8df` *(RGB: 236, 232, 223)* (Dark mode: `#21242b`)
* **Bottom Border**: `1.5px solid var(--table-th-border, #ded8cd)` *(RGB: 222, 216, 205)* (Dark mode: `#2c3039`)
* **Text Color**: `var(--table-th-text, #423e37)` *(RGB: 66, 62, 55)* (Dark mode: `#cfd3da`)
* **Typography**: uppercase, `letter-spacing: 0.6px; font-size: 11.5px; font-weight: 700;`

---

## 2. Button & Design System Palette Standards
* Always consult and follow the `proppulse-design-palette` skill (`.agents/skills/proppulse-design-palette/SKILL.md`) for all colors, typography, cards, tables, badges, inputs, segmented tabs, and table action icon controls.
* Text Buttons must be text-only: **NEVER** place icons, glyphs, arrows, or emojis inside standard buttons, submit controls, action links, or toolbars (per `website-button-standards`).

---

## 3. Environment & Deployment Synchronization
* The workspace (`c:\Users\there\Projects\AntiGravity\Projects`) and live XAMPP server (`C:\xampp\htdocs\property-management`) must always remain strictly 1:1 synchronized.
* Always verify PHP syntax on both files before finalizing turns.
