# Design Guide

## 1. Design objective

Create a restrained, clear, consistent administrative interface for the first-version prototype. The interface should help authorized staff understand records and workflow status without visual clutter.

This is also the implemented design standard as of 2026-11-24; see the design system section below.

## 2. Design principles

- Clarity before decoration.
- Consistency across pages.
- Readable labels and validation feedback.
- Clear separation between viewing, editing, and workflow decisions.
- Responsive layouts for common desktop and tablet widths.
- Accessible contrast, keyboard navigation, and semantic HTML.
- Use status text as well as color; never communicate status by color alone.
- Keep sensitive information out of unnecessary page summaries.

## 3. Laravel implementation approach

- Use Laravel Blade for server-rendered pages.
- Reuse the existing layout and view conventions after auditing them.
- Prefer Blade components or partials when they reduce genuine repetition.
- Use existing frontend assets and styling before adding dependencies.
- Use HTML forms with Laravel CSRF protection and server-side validation.
- Use small amounts of vanilla JavaScript only when a real interaction requires it.
- Do not introduce a separate frontend framework.

## 4. Suggested application shell

Subject to existing code and developer approval:

- Header: application name, current user's account menu, sign-out.
- Navigation: dashboard, complaints, cases, assignments, reports, and user administration as permitted.
- Main content: page title, short context, primary action, content area, validation and status feedback.
- Footer: optional compact system/environment information for the prototype.

Navigation items must be permission-aware, but hiding a link is not a substitute for server-side authorization.

## 5. Page conventions

### Index pages

- Clear page title and purpose.
- Search/filter controls only where useful.
- Paginated records for potentially long lists.
- Consistent table headings.
- Empty states that explain what the user can do next.
- Actions appropriate to the current user's permissions.

### Detail pages

- Display reference number and current status clearly.
- Group related information into readable sections.
- Distinguish original complaint information from case information.
- Show important history in chronological order where relevant.
- Do not expose restricted fields to unauthorized users.

### Forms

- Use explicit labels.
- Indicate required fields.
- Preserve safe user input after validation failure.
- Show field-specific validation messages.
- Use confirmation for consequential workflow decisions.
- Ask for a reason when the requirements or approved workflow require a decision reason.
- Do not use browser-only validation as the sole validation layer.

### Status and decisions

- Show a readable status label.
- Use consistent neutral styling, with restrained semantic colors where appropriate.
- Include reason, actor, and timestamp for recorded decisions when required.
- Enforce allowed transitions in backend logic.

## 6. Visual tokens

Define actual values only after inspecting existing styles. Avoid introducing a competing design system.

Recommended direction:
- Neutral background and surfaces.
- High-contrast text.
- One restrained primary accent.
- Consistent spacing scale.
- Consistent border radius and shadows.
- Readable system font stack unless the project already uses a suitable font.

## 7. Responsive behavior

- Tables should remain usable on smaller screens, using responsive wrappers or an appropriate alternate presentation.
- Forms should collapse to a single column on narrow screens.
- Navigation should not obscure the main content.
- Controls should have practical touch targets.

## 8. Security and privacy in presentation

- Never rely on hidden buttons or hidden navigation for access control.
- Do not put sensitive record content in page titles, URLs, client-side data attributes, or unnecessary notifications.
- Escape displayed user-supplied content through Blade's normal escaped output.
- Avoid rendering untrusted content as raw HTML.

## 9. Design review checklist

- [ ] Page uses the shared application layout.
- [ ] Heading and purpose are clear.
- [ ] Labels and validation messages are readable.
- [ ] Status is understandable without color.
- [ ] Actions are permission-aware.
- [ ] Server-side authorization protects every action.
- [ ] Layout works at desktop and narrower widths.
- [ ] No unnecessary dependency or visual complexity was introduced.


## 10. Implemented design system (2026-11-24)

- Tokens: `public/css/app.css` defines color, typography, spacing, radius, shadow, layout, sidebar, header, and transition variables.
- Structure: `layouts/app.blade.php` is the only authenticated layout. `layouts/auth.blade.php` handles sign-in flows; `layouts/print.blade.php` handles printable documents.
- Reusable partials: `sidebar`, `topbar`, `breadcrumbs`, `alerts`, `status-badge`, `empty-state`, `icon`, `pagination`.
- Components are styled with plain CSS classes (`btn--primary`, `btn--outline`, `card`, `table`, `badge--*`, `alert--*`, `form-field`) instead of framework utility classes.
- Sidebar is persistent on desktop, collapsible (state saved locally), and a drawer on screens below 900px with overlay, close button, and Escape handling.
- All status colors are paired with text labels; focus-visible styles, labeled inputs, `scope="col"` headers, `aria-current`, `role="alert"`, and accessible pagination are used throughout.
- No frontend framework, CDN, or build step is required; assets are served from `public/`.


## 11. Dark mode

Dark mode is a separate palette, not an inverted light theme. `[data-theme="dark"]` redefines the same semantic tokens with darker surfaces, lighter text, softer borders, and adjusted status colors. The toggle is available in the authenticated topbar and on authentication screens; light is the default and the user's choice is stored locally. Print output always uses the light palette via `@media print` overrides.


## 12. Login branding

The authentication screen uses a two-column shell: a blue brand panel with the Ethiopian Federal Police emblem and a white form panel. The panels are equal width on desktop and collapse to the form only on small screens.
