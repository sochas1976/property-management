---
name: website-button-standards
description: >-
  Enforces clean, modern UI button standards for website development.
  Use this skill whenever designing, creating, modifying, or reviewing HTML, CSS,
  or UI buttons, action links, submit buttons, or toolbar controls to ensure
  no icons, symbols, or emoji are placed inside buttons.
---

# Website Button Standards

This skill defines the styling and component rules for buttons across website projects.

## Core Rules

1. **Text-Only Button Labels (Strictly No Icons)**:
   - Do NOT place icons, SVGs, arrow symbols (`→`, `←`, `&rarr;`, `&larr;`), plus signs (`+`), or icon fonts inside button elements (`<button>`, `<a class="btn">`, `input[type="submit"]`).
   - Buttons must communicate their intent solely through clear, concise, and descriptive text (e.g., "Review Terms", "Send Agreement", "Download", "Save Changes", "Cancel").

2. **Visual Hierarchy & State Over Glyphs**:
   - Communicate priority using color, background fills, border weights, padding, and typography instead of decorative icons.
   - **Primary Buttons**: Bold brand or accent fill with high-contrast text.
   - **Secondary / Outline Buttons**: Clean border outline with subtle hover fill.
   - **Danger / Destructive Buttons**: Muted red borders or danger fills with plain text warnings (e.g., "Delete", "Reject").

3. **Checklist Before Generating Code**:
   - [ ] Are any icons (`<i>`, `<svg>`, `<span>` icon classes) present inside `<button>` or `.btn` tags? -> Remove them.
   - [ ] Are any arrow characters or glyphs (`→`, `←`, `+`) appended or prepended to button text? -> Remove them.
   - [ ] Is the button text self-descriptive and action-oriented on its own?
