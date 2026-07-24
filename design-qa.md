# MyAPES Account design QA

## Source of truth

- Approved design: `C:\Users\bmurp\.codex\generated_images\019f9422-2e12-7a63-b82e-4b4853eee47b\call_xoUR99KSxCTRj0tSeihQgGd8.png`
- Repository copy: `docs/design-qa/option-2-source.png`
- Source dimensions: 1487 × 1058 px
- Target state: authenticated Public local-QA user, light theme, desktop viewport

## Implementation evidence

- Desktop comparison, source and implementation in one image: `docs/design-qa/comparison-desktop-light.png` (2974 × 1102 px)
- Desktop light: `docs/design-qa/implementation-desktop-light-viewport.png` (1488 × 1058 px)
- Desktop dark: `docs/design-qa/implementation-desktop-dark-viewport.png` (1488 × 1058 px)
- Mobile light: `docs/design-qa/implementation-mobile-light.png` (374 × 2184 px full-page capture from a 390 × 844 CSS viewport)
- Mobile dark: `docs/design-qa/implementation-mobile-dark.png` (374 × 2184 px full-page capture from a 390 × 844 CSS viewport)
- Browser: Codex in-app browser against `http://localhost:8125/dashboard`
- Browser console: no warnings or errors

## Full-view comparison

The final desktop capture was reviewed beside Option 2 at the same 1058 px viewport height. The implementation preserves the approved horizontal navigation, parchment QA bar, asymmetric 31/69 content split, editorial Fraunces headings, compact Nunito Sans interface copy, realistic bearded-dragon portrait, six-row attention list, clay/violet/sage service coding, and four-column totals strip. The page now fits the complete dashboard and footer in the target viewport without horizontal or vertical overflow.

Content differences are intentional: the implementation renders deterministic seeded records and real role-scoped counts rather than the fictional labels and totals in the mock-up. The habitat treatment is restrained to the natural portrait, parchment surfaces, sage details, and botanical iconography instead of copying decorative corner artwork.

## Focused-region checks

- Header and navigation: active state, horizontal navigation, light/dark logo lockups, account controls, and hidden mobile scrollbar verified.
- QA role bar: Public, Staff, and Admin switches verified in the browser; the active role and available navigation update after each switch.
- Attention panel: exactly six normalized open items, newest first, with service colour, status, metadata, destination, and keyboard-focus treatment.
- Theme: a fresh `localhost` origin opened in light mode; dark mode survived a reload on `127.0.0.1`; the correct logo lockup was visible in both themes.
- Mobile: no horizontal document overflow at 390 px; navigation remains horizontally accessible and all dashboard sections collapse to one column.
- Keyboard: the skip link received a visible 2.67 px focus outline and moved into view; all controls use semantic links or buttons.
- Contrast: representative light text ratios are 5.08–15.80:1, dark text ratios are 7.63–12.35:1, brand-link ratios are 5.84:1 light and 9.67:1 dark, and dark service-icon ratios are 6.26–8.86:1.

## Comparison history

1. Initial comparison found a P1 density mismatch: the main cards were 631 px high against the source's approximately 610 px, pushing the footer below the target viewport. Header, panel, identity, summary, and page spacing were tightened until the page height matched 1058 px.
2. Initial comparison found a P2 desktop navigation artifact from the overflow scrollbar. The scrollbar is now visually suppressed while scrolling remains available at narrow widths.
3. Dark-mode review found a P1 contrast issue on lightened clay, violet, and sage icon backgrounds. Dedicated deep dark-mode backgrounds raised white-icon contrast to 6.26–8.86:1.
4. Final desktop, dark-theme, mobile, role-switching, keyboard-focus, first-visit, persistence, and console checks found no remaining P0, P1, or P2 issues.

final result: passed
