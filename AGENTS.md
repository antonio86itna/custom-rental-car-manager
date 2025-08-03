🚗 Custom Rental Car Manager – Costabilerent
Overview
Custom Rental Car Manager is a scalable, enterprise-grade WordPress plugin designed for Costabilerent, a car & scooter rental agency operating in Ischia (locations: Ischia Porto, Forio). Developed under the Totaliweb brand (https://www.totaliweb.com), it delivers a seamless, modern, and high-performance rental management experience for both admins and customers — ready for future web/mobile API integrations.

✨ Core Features (Backend)
Vehicle Management: Create and manage all vehicle details (model, seats, transmission, stock, daily rate, images, location).

Custom Pricing Rules: Configure special rates per date range, day of week, or custom scenarios.

Custom Availability: Remove vehicles (single or recurring, e.g. weekends) from the calendar; mark units as unavailable at specific times.

Extra Services: Add optional or included extras (child seat, GPS, etc.) at daily/flat/free rates.

Insurance Plans: Define base (included) or premium (paid, with/without deductible), set pricing per day and plan features.

Settings:

Free cancellation window (e.g. full refund if canceled at least N days before start).

Optional late-return rule: auto-charge one extra rental day if return is set after 10:00. Amount is calculated and shown in booking summary.

Manual Management: Admin can manually create, edit, cancel, refund bookings as needed from backend.

Full Booking Control: View, filter, search, update, and manage all reservations by status, dates, vehicles, customer, payment/refund/notes.

⚡ Dynamic Dashboard & Calendar
Main dashboard: Modern, interactive calendar with real-time vision of all daily bookings, pickups, drop-offs.

View & Filter: Filter by branch (Ischia Porto, Forio), status, vehicle, period; search any booking instantly.

Visual highlight: Vehicles outgoing and incoming, today's agenda, future reservations — color-coded and status badges.

Calendar is sync-ready: API endpoints enable external/mobile calendar sync.

🌎 Internationalization (i18n)
Base language: EN (English) — all code and UI

Fully ready for translation: All strings wrapped using __() or _e(), text domain custom-rental-manager

Translation files: .pot template under /languages/ provided

Front-end and admin JavaScript: Prepared for wp-i18n JS translation 

🏗️ Frontend User Experience
Search form: Modern, responsive vehicle search (date/time/location/riders/extras)

Dynamic Listing: Card-based, mobile-ready list of available vehicles with step filtering and availability logic

Step-By-Step Booking: Wizard-style process (vehicle > extras > insurance > details > payment > review)

Checkout: Stripe payment integration, booking summary, legal terms acceptance

Confirmation: Unique booking number, summary recap

Customer Area: Login with custom user role, manage/cancel/refund/view bookings, edit profile, reset password

🔔 Automated Communications
Dynamic Emails: Unified system to send booking confirmations, status changes, reminders, password resets — to both admin and customer

Templates: All emails use customizable, modern HTML templates, ready for translation

🔒 Security & Performance
Follows WordPress.org security standards (nonces, sanitization, validation)

Optimized for large fleets and high-traffic periods

Compatible with PHP 8.0+ and WordPress latest versions

No direct DB queries outside WP API unless strictly needed

All user input validated/sanitized

REST API Endpoints: Custom endpoints for vehicles, reservations, calendar and customer data

📦 Folder Structure
text
/custom-rental-manager/
├─ custom-rental-manager.php
├─ /inc/                # Core classes (vehicles, bookings, extras, calendar...)
├─ /templates/          # Custom admin/checkout templates
├─ /assets/             # CSS (backend & frontend), JS (admin, booking wizard)
├─ /languages/          # .pot for translation
├─ /api/                # REST endpoint handlers
└─ README.md/AGENTS.md
🛠️ Development Guidelines for AI/AGENT
Follow PSR-12, WP coding standards

Use PHPDoc for all public/protected methods

All backend logic in OOP classes under /inc

Use WP Settings API and Options API

Load assets conditionally (enqueue only as needed)

All UI matches the provided prototype/branding Totaliweb/Costabilerent, using cards, colors, layout faithfully

Every new feature = dedicated commit, with clear message

Before implementation, auto-generate a features list to submit for review/confirmation

All REST endpoints namespaced, JWT/auth support ready for future

Run linter and automated test suite before merging

No direct changes to main branch without approval unless urgent fix

QA/focus on accessibility (WCAG 2.1)

🚀 Example Output / Expected UX
“Fiat 500, Automatic, 4 Seats — €45/day”

“Add extras: GPS +€5/day, Child seat Free”

“Insurance: Base Included | Premium +€12/day (No Excess)”

“Free cancellation up to 3 days before start”

“Pay securely via Stripe”

“Pickup: Ischia Porto | Drop-off: Forio”

🏷️ Branding
“Powered by Totaliweb” (https://www.totaliweb.com) in admin & customer frontend, with link (discrete, accessible, filter to disable if needed)

📲 REST API (Future Ready)
/wp-json/custom-rental/v1/vehicles

/wp-json/custom-rental/v1/bookings

/wp-json/custom-rental/v1/extras

/wp-json/custom-rental/v1/availability

JWT/nonce security, CORS-ready.

Designed for scalability and easy mobile app extension.

🏁 Prompt di avvio per Agent AI/Developer
“Analyze AGENTS.md and prototype, confirm all features listed below before developing. Generate all code and files according to WP.org best practices and Totaliweb branding guidelines. Ensure complete internazionalization, REST API, security, responsiveness, and modularity. Use English as base language, prepare for translation. Ask for clarifications if needed before coding.”
