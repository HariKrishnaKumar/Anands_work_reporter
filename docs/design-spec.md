# Apple Design Language — UI Redesign Spec

## Design Read
Reading this as: **employee productivity tool** for internal use, with an **Apple Human Interface** language, leaning toward **vanilla CSS + glassmorphism materials + fluid spring-based motion**.

## Dials
- **DESIGN_VARIANCE:** 7 (premium consumer / Apple-y)
- **MOTION_INTENSITY:** 6 (fluid CSS + spring physics, not cinematic)
- **VISUAL_DENSITY:** 3 (airy, spacious, document-style)

---

## 1. Typography Overhaul

### Current Problem
- Single font weight usage (regular + bold only)
- Letter-spacing not tuned per size
- Line-height not tracking size inversely

### Fix
- **Display/Headlines:** Use `font-family: var(--font-display)` with `letter-spacing: -0.02em` to `-0.04em`, `line-height: 1.1`
- **Body:** `line-height: 1.6` for readability, `letter-spacing: 0`
- **Small labels/badges:** `letter-spacing: 0.04em`, uppercase
- **Font weights:** Introduce Medium (500) and SemiBold (600) for hierarchy
- Respect `font-optical-sizing: auto` for SF Pro

---

## 2. Color & Surface System

### Current Problem
- Shadows are generic black
- No glassmorphism materials
- Surfaces lack depth hierarchy

### Fix
- **Primary surfaces:** `backdrop-filter: blur(20px) saturate(180%)` for header, nav, sidebar
- **Card surfaces:** Tinted backgrounds with subtle inner highlights
- **Shadows:** Colored/tinted to background hue, not pure black
- **Borders:** Ultra-thin `0.5px` hairlines (Apple style) instead of 1px
- **Depth layers:** bg-primary → surface → surface-elevated → floating (glass)

---

## 3. Motion & Micro-Interactions

### Principles (from Apple Design)
- **Response:** Feedback on pointer-down, not release
- **Interruptibility:** Every animation interruptible mid-flight
- **Velocity handoff:** Drag releases carry momentum
- **Springs:** Critically damped (damping 1.0, response 0.3-0.4) for UI, under-damped (0.8) for momentum

### Implementation
- Replace CSS transitions with spring-like cubic-bezier: `cubic-bezier(0.25, 0.1, 0.25, 1)`
- Add `:active` scale feedback on all interactive elements (0.97-0.98)
- Staggered entry animations on page load
- Smooth page transitions
- Rubber-band resistance at boundaries

---

## 4. Component Redesign

### 4.1 Header/Nav (Glass Material)
- Translucent `backdrop-filter` with scroll-under content
- Ultra-thin `0.5px` border at bottom
- Frosted glass effect

### 4.2 Sidebar (Tablet/Desktop)
- Translucent material background
- Active state with primary-subtle background
- Smooth hover transitions
- Logo with subtle primary glow

### 4.3 Bottom Nav (Mobile)
- Floating glass pill (detached from bottom)
- Active indicator with spring animation
- Haptic-like press feedback

### 4.4 Cards & List Items
- Remove hard borders, use subtle shadow + background differentiation
- Hover: gentle lift with shadow transition
- Active: scale-down press feedback
- Smooth arrow slide on hover

### 4.5 Forms
- iOS-style input fields with rounded corners
- Focus ring with primary glow
- Smooth label transitions
- File upload area with drag-over glass effect

### 4.6 Buttons
- Pill-shaped primary buttons with gradient
- Ghost buttons with subtle hover fill
- Scale feedback on press (0.97)
- Loading state with spinner

### 4.7 Modals
- Glass overlay with backdrop blur
- Scale-in entrance animation
- Smooth backdrop fade

### 4.8 Success Screen
- Animated checkmark (stroke draw)
- Scale-in celebration
- Smooth summary card reveal

---

## 5. Page-by-Page Plan

### Login
- Centered glass card on dark gradient background
- Subtle background animation (slow-moving radial gradient)
- Logo with glow effect
- Form inputs with iOS styling
- Smooth button press feedback

### Home (Dashboard)
- Large greeting with display typography
- Glass quick-action card
- Report list with hover lift effects
- Staggered entry animations
- Empty state with illustration

### Add Report
- Clean form with iOS-style inputs
- Glass upload area with drag feedback
- File list with smooth add/remove animations
- Character counter with color transition

### Review
- Glass review sections
- Confirmation modal with glass overlay
- Loading overlay with spinner

### Success
- Animated checkmark (CSS stroke animation)
- Scale-in success icon
- Summary card reveal

### Report Detail
- Clean read-only layout
- File cards with glass effect
- Subtle lock badge

---

## 6. Files to Modify

| File | Changes |
|------|---------|
| `variables.css` | Update tokens, add glass variables, refine shadows |
| `base.css` | Typography overhaul, form inputs, button refinements |
| `components.css` | Glass header, nav, cards, modals, all components |
| `responsive.css` | Sidebar glass material, responsive refinements |
| `app.js` | Add page transition, scroll effects, interaction feedback |
| `main.php` | Add page transition wrapper, meta tags |
| `footer.php` | Update bottom nav markup if needed |

---

## 7. Accessibility

- All animations respect `prefers-reduced-motion`
- Glass effects degrade under `prefers-reduced-transparency`
- Focus rings maintained for keyboard navigation
- Touch targets minimum 44px
- Contrast ratios WCAG AA

---

## 8. Anti-Patterns to Avoid

- No generic black shadows (tint to background)
- No instant state changes (always animate)
- No layout-triggering animations (transform + opacity only)
- No `backdrop-filter` on scrolling containers
- No pure `#000000` or `#ffffff` (use off-black/off-white)
