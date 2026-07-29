---
name: Syifa Recruitment
colors:
  surface: '#f6fafe'
  surface-dim: '#d6dade'
  surface-bright: '#f6fafe'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f0f4f8'
  surface-container: '#eaeef2'
  surface-container-high: '#e4e9ed'
  surface-container-highest: '#dfe3e7'
  on-surface: '#171c1f'
  on-surface-variant: '#444650'
  inverse-surface: '#2c3134'
  inverse-on-surface: '#edf1f5'
  outline: '#747781'
  outline-variant: '#c4c6d2'
  surface-tint: '#405c9e'
  primary: '#001844'
  on-primary: '#ffffff'
  primary-container: '#042c6c'
  on-primary-container: '#7a96dc'
  inverse-primary: '#b1c6ff'
  secondary: '#006c49'
  on-secondary: '#ffffff'
  secondary-container: '#92f3c2'
  on-secondary-container: '#00714c'
  tertiary: '#3b002a'
  on-tertiary: '#ffffff'
  tertiary-container: '#600046'
  on-tertiary-container: '#f261bd'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#d9e2ff'
  primary-fixed-dim: '#b1c6ff'
  on-primary-fixed: '#001946'
  on-primary-fixed-variant: '#264484'
  secondary-fixed: '#95f6c5'
  secondary-fixed-dim: '#78d9aa'
  on-secondary-fixed: '#002113'
  on-secondary-fixed-variant: '#005236'
  tertiary-fixed: '#ffd8ea'
  tertiary-fixed-dim: '#ffaeda'
  on-tertiary-fixed: '#3c002a'
  on-tertiary-fixed-variant: '#890065'
  background: '#f6fafe'
  on-background: '#171c1f'
  surface-variant: '#dfe3e7'
  navy-hover: '#032356'
  orange-dark: '#9a3412'
  orange-mid: '#ea580c'
  orange-light: '#fb923c'
  timeline-yellow: '#FCF0D6'
  timeline-gold: '#F0DBA3'
  surface-dark: '#0f172a'
  text-muted: '#64748b'
typography:
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '700'
    lineHeight: '1.2'
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '700'
    lineHeight: '1.2'
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '600'
    lineHeight: '1.3'
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: '1.4'
  body-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 18px
    fontWeight: '400'
    lineHeight: '1.6'
  body-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.5'
  label-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '600'
    lineHeight: '1.2'
    letterSpacing: 0.02em
  label-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '500'
    lineHeight: '1.2'
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 4px
  gutter: 20px
  margin-mobile: 16px
  margin-desktop: 32px
  card-padding: 24px
  section-gap: 48px
---

## Brand & Style

The design system for the Recruitment Portal embodies a "Professional-Energetic" personality, balancing the reliability of a global corporate entity with the approachability of a modern tech platform. It leverages a **Domain-Oriented Identity** where color acts as a primary navigation cue, ensuring applicants and administrators can distinguish functional areas at a glance.

The visual style is a hybrid of **Modern Corporate** and **Soft Neumorphism**. It uses clean, high-contrast surfaces for data entry, while utilizing subtle depth through dual-shadow effects for interactive widgets and specialized "Identity Cards." The interface is designed to evoke a sense of trust, efficiency, and clarity, guiding candidates through the application funnel with distinct visual feedback and strong brand presence.

## Colors

The palette is structured into two tiers: **Fixed Brand Colors** and **Theme-Aware Tokens**. 

### Fixed Identity
These colors do not change in dark mode. They are reserved for "Identity Cards" (Gradients) and status-specific components.
- **Brand Navy (#042c6c):** Primary brand identity.
- **Success Green (#047C54):** Presence and successful actions.
- **Accent Magenta (#B9308C):** Decorative highlights and top navigation accents.

### Theme-Aware Tokens
Dark mode is implemented via CSS variables. Generic UI surfaces (backgrounds, text, borders) flip their values, while the identity cards retain their vibrant gradients to maintain domain recognition.
- **Surface Page:** Light (`#f1f5f9`) / Dark (`#020617`)
- **Surface Card:** Light (`#ffffff`) / Dark (`#1e293b`)
- **Text Heading:** Light (`#1e293b`) / Dark (`#f8fafc`)
- **Text Body:** Light (`#334155`) / Dark (`#cbd5e1`)

## Typography

This design system uses **Plus Jakarta Sans** exclusively to maintain a contemporary, welcoming, and professional aesthetic. 

- **Hierarchy:** Use `headline-lg` for section headers and welcome messages.
- **Tables:** Headers should use `label-sm` with uppercase styling and `text-muted` coloring.
- **Readability:** For long-form descriptions in job postings, use `body-md` with standard line-height for optimal legibility.
- **Accessibility:** Avoid using text colors lighter than `slate-500` (#64748b) for body or label text to ensure WCAG AA compliance.

## Layout & Spacing

The system follows a **Fluid Grid** model with a consistent 4px/8px rhythm. 

- **Responsive Strategy:** On desktop, data is presented in structured tables. On mobile (under 768px), layout shifts to a "Card-List" pattern where each row becomes an independent card to avoid horizontal scrolling.
- **PWA Considerations:** Main content containers must include a bottom padding of at least `80px` on mobile devices to ensure interactive elements are not obscured by fixed bottom navigation bars.
- **Fields:** Use a "Field-Row" pattern for application details: labels are positioned on the left (or top on mobile) with a subtle hairline divider between rows.

## Elevation & Depth

Visual hierarchy is established through three specific layering techniques:

1.  **Tonal Surfaces:** The base page uses a light gray (`--surface-page`), while cards sit on top using a white or dark-slate surface (`--surface`).
2.  **Neumorphic Depth:** Specific interactive widgets use the "Neuro" style: a background of `#e8edf6` with dual shadows (`6px 6px 14px #c5ccd8, -6px -6px 14px #fff`) to create a physical "extruded" effect.
3.  **Identity Gradients:** The "Welcome" and "Absensi" cards utilize vibrant linear gradients with low-opacity decorative shapes (circles and rotated squares) clipped to the card boundaries. This creates depth without traditional shadows.

## Shapes

The shape language is defined by large, friendly radii. 

- **Primary Cards:** A fixed `20px` border radius is required for all Identity Cards and high-level containers.
- **Standard UI:** Elements like input fields and standard buttons use `rounded-lg` (16px) or `rounded-xl` (24px) to maintain visual continuity with the large-radius cards.
- **Interactive Pills:** Status badges (Approved, Pending) and navigation toggle buttons must use a full pill-shape (circular ends) for distinct contrast against square-ish cards.

## Components

### Buttons
- **Primary:** Solid Brand Navy with white text.
- **Success:** Solid Success Green for "Submit Application" or "Clock In."
- **QA/Overlay:** Translucent white pill buttons for use specifically on top of identity card gradients.

### Form Fields (Recruitment Portal)
Following the recruitment structure, input fields should be large with a subtle border. 
- **Application Fields:** Name, Email, Phone, Position Applied, Current Salary, and Expected Salary.
- **File Upload:** A dedicated card-style dropzone with a dashed border and `20px` roundedness.

### Identity Cards
- **Welcome Card:** Navy to Magenta gradient.
- **Absen Card:** Green identity gradient.
- **Approval Card:** Multi-stop Orange gradient (#9a3412 to #fb923c).

### Status Badges
Use high-contrast pairings:
- **Approved:** Light Green background / Dark Green text.
- **Pending:** Amber background / Brown text.
- **Rejected:** Soft Red background / Dark Red text.