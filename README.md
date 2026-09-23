# Rent and Ride Nepal (RRN)

A vehicle (bike & car) rental platform for Nepal, built with **core PHP, MySQL, HTML, CSS, and JavaScript only** — no frameworks.

## Folder Structure

```
rrn/
├── config/
│   └── db.php                  # PDO database connection
├── includes/
│   ├── header.php               # Shared site header/nav
│   ├── footer.php               # Shared site footer
│   └── functions.php            # Helper functions (auth, CSRF, uploads, etc.)
├── assets/
│   ├── css/style.css            # All styling
│   ├── js/main.js               # All interactivity
│   ├── images/vehicles/         # Public vehicle photos
│   └── uploads/
│       ├── licenses/            # Uploaded driving license images
│       └── vehicles/            # Uploaded vehicle images (admin)
├── admin/
│   ├── includes/                # Admin layout partials
│   ├── login.php / logout.php
│   ├── index.php                # Dashboard
│   ├── vehicles.php / vehicle-add.php / vehicle-edit.php
│   ├── reservations.php         # Approve/reject bookings
│   ├── licenses.php             # Verify driving licenses
│   ├── payments.php             # Payment records
│   ├── users.php                # Manage customer accounts
│   ├── reports.php              # Stats & reports
│   └── external-sync.php        # Import vehicles from an external source
├── database/
│   └── rrn.sql                  # Full schema + sample data
├── index.php                    # Homepage
├── vehicles.php                 # Browse/search/filter vehicles
├── vehicle-detail.php
├── register.php / login.php / logout.php
├── profile.php
├── reserve.php                  # Reservation + license upload
├── payment.php                  # eSewa / Khalti (simulated) checkout
├── booking-confirmation.php
├── my-bookings.php
├── about.php / contact.php / faq.php / how-it-works.php
└── README.md
```

## Setup

1. **Create the database.** Import `database/rrn.sql` into MySQL (e.g. via phpMyAdmin, or `mysql -u root -p < database/rrn.sql`). This creates the `rrn_db` database, all tables, and sample data.
2. **Configure the connection.** Edit `config/db.php` and set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` to match your environment (defaults assume XAMPP/WAMP: host `localhost`, user `root`, no password).
3. **Set folder permissions.** Make sure `assets/uploads/licenses/`, `assets/uploads/vehicles/`, and `assets/images/vehicles/` are writable by the web server, so license and vehicle image uploads work.
4. **Run it.** Place the `rrn` folder inside your server's document root (e.g. `htdocs/` for XAMPP) and visit `http://localhost/rrn/`.

## Default Logins

- **Admin panel** (`/admin/login.php`)
  Email: `admin@rentandridenepal.com`
  Password: `Admin@123`

- **Customers** register their own accounts via `/register.php`.

## Key Features Implemented

- Session-based auth for customers and a separate admin auth system, with `password_hash()` / `password_verify()` and CSRF tokens on every form.
- All database access uses PDO **prepared statements** — no raw string-concatenated SQL.
- Vehicle browsing with search, multi-filter (type, location, brand, fuel, transmission, price range), and sorting.
- Reservation flow that collects pickup/return dates and driving-license details (Nepalese or Foreign) in one step, uploads the license image, and **prevents double-booking** by checking for overlapping active reservations on the same vehicle before saving.
- A simulated eSewa/Khalti payment step (clearly marked as a demo flow — see comments in `payment.php`) that records a transaction and generates an email-ready booking confirmation with a reference number.
- Admin dashboard with stats, vehicle CRUD (with image upload), reservation approval/rejection workflow (which also keeps vehicle availability in sync), driving-license verification queue, payment records, user management (suspend/reactivate), reports/statistics, and an external-vehicle-source import tool (`admin/external-sync.php` — wire `EXTERNAL_API_URL` to a real provider feed to go live).
- Basic security throughout: prepared statements, input validation, upload MIME/size validation, CSRF tokens, session-based authorization checks (`requireLogin()` / `requireAdmin()`), and escaped output (`e()`) everywhere user data is printed.

## Notes for Your Report / Presentation

- The **payment integration** is implemented as a realistic simulated flow (transaction code generation, payment record, status update) since live eSewa/Khalti merchant credentials aren't available in a student environment — this is called out directly in `payment.php`'s comments so you can explain it if asked.
- The **external vehicle source sync** (`admin/external-sync.php`) is built to import from any JSON API feed; it currently uses sample data as a placeholder so you can demonstrate the import workflow end-to-end, with a single config line (`EXTERNAL_API_URL`) to point it at a real provider.
