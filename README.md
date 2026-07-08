# CampTix Check-In

A self-hosted WordPress plugin for WordCamp attendee check-in. Designed to run on a **separate check-in site** — not on WordCamp.org itself — it syncs attendee data from a WordCamp site via REST API or CSV import, generates HMAC-signed QR codes, emails them to attendees, and provides a camera-based QR scanner to mark arrivals in real time.

Built for **WordCamp Philippines 2026**.

---

## Features

### Dashboard
<img width="1911" height="917" alt="image" src="https://github.com/user-attachments/assets/f178dab1-82cc-434e-9ed2-07105297cd3e" />

- Live stats: total attendees, checked-in count, not-yet-arrived, QR emails sent, Contributor Day registrations
- Check-in progress bar with percentage
- Ticket type breakdown with proportional bars
- Recent check-ins list (last 10)
- Today's hourly check-in timeline chart
- Quick-action buttons to scanner, add attendee, send QR codes, and sync

### Attendee Management

<img width="1906" height="910" alt="image" src="https://github.com/user-attachments/assets/bb5e838d-e913-46a6-8317-922f98cdc91b" />

- Local `ctci_attendee` custom post type mirrors all WordCamp attendee data
- Full attendee table with search, filter by ticket type and check-in status
- **Add attendee** manually via form
- **Edit attendee** — all fields editable including check-in status and timestamp
- **Delete attendee** with confirmation (per-row, instant via REST)
- Columns: Name on Badge, Full Name, Email, Ticket Type, Company, WP Username, Twitter/X, Contributor Day, Check-In Status, Actions

### QR Codes
<img width="410" height="466" alt="image" src="https://github.com/user-attachments/assets/095824d7-6623-4527-b3a1-487e3139b524" />

- HMAC-SHA256 signed payload per attendee — tamper-proof
- QR image fetched server-side from `api.qrserver.com` and cached as raw PNG bytes (24 h transient)
- Sent to attendees as a **CID inline attachment** (renders in Gmail, Outlook, Apple Mail — `data:` URIs are blocked by email clients and are not used)
- Bulk send to all attendees who haven't received a QR yet
- Per-attendee resend button with sent timestamp
- Send QR page shows: Name on Badge, Full Name, Email, Ticket Type, QR sent date, Resend button

### QR Scanner
<img width="1916" height="912" alt="image" src="https://github.com/user-attachments/assets/313d1632-7914-4b06-b2a0-7aa8686b83ae" />

- Camera-based scanner using the bundled [jsQR](https://github.com/cozmo/jsQR) library (no external CDN)
- Verifies HMAC signature before accepting a scan
- Marks attendee as checked in via REST API, stores timestamp
- Visual and audio feedback on successful scan, duplicate scan, or invalid QR
- Manual entry fallback (type/paste a QR payload)
- Recent scans log on screen

### Name Badges
<img width="423" height="336" alt="image" src="https://github.com/user-attachments/assets/e999949f-1618-484f-b6cc-23b4fc2de636" />

- Standalone HTML page — **no WordPress admin chrome** — safe to print directly
- Shows: Name on Badge (large), Company, WordPress.org username, Twitter/X handle, Website URL
- Ticket type shown only for non-Regular tickets (Regular is the default and omitted)
- Print button triggers browser print dialog; no QR code on the badge (QR is for check-in only)

### Data Sync
<img width="1904" height="913" alt="image" src="https://github.com/user-attachments/assets/1730182e-04d2-4da6-85c4-59f81d61301d" />
<img width="1917" height="914" alt="image" src="https://github.com/user-attachments/assets/2fadaf8b-0a7a-43c1-91bb-7f4f95c40e7c" />


- **REST API pull** from any WordCamp site (requires Application Password)
- **CSV import** — compatible with the real CampTix export from WordCamp.org
  - Handles all standard CampTix column headers including long Contributor Day question text
  - Skips placeholder "Unknown Attendee" rows automatically
- Configurable auto-sync schedule

### Settings & Reset
<img width="1900" height="913" alt="image" src="https://github.com/user-attachments/assets/ea5c6721-1c15-4e66-8b49-774ac70848fb" />

- Configurable check-in meta key, email subject, badge field visibility
- **Danger Zone** — Reset All button permanently deletes all attendee data, check-in records, QR cache, and sent-email flags

---

## Requirements

| Requirement | Minimum |
|---|---|
| WordPress | 6.0+ |
| PHP | 8.0+ |
| PHP extensions | `openssl` (for HMAC), `gd` (bundled with most hosts) |
| Outbound HTTP | Required during QR generation and REST sync (not needed at scan time) |

> **This plugin is installed on a separate check-in site, not on WordCamp.org.** WordCamp.org does not allow third-party plugin installation. Import attendee data via CSV export or REST API sync.

---

## Installation

1. Download or clone this repository into your WordPress site's plugin directory:
   ```
   wp-content/plugins/camptix-checkin/
   ```

2. Activate the plugin in **WordPress Admin → Plugins**.

3. Navigate to **Check-In** in the admin sidebar.

4. Import your attendees via **↓ Sync WordCamp** (CSV upload or REST API pull).

5. Optionally send QR codes via **Send QR Codes**.

6. On event day, open **QR Scanner** on a tablet or phone with a camera.

---

## Importing Attendees

### Option A — CSV Import (recommended)

1. On your WordCamp site, go to **CampTix → Attendees → Export**.
2. Export as CSV.
3. In this plugin, go to **↓ Sync WordCamp → CSV Import**.
4. Upload the file and click **Import**.

The plugin maps all standard CampTix export column names automatically, including:
- `First Name`, `Last Name`, `Email Address`, `Ticket Type`, `Order Status`
- `Name on Badge`, `Company`, `WordPress.org Username`, `Twitter/X Handle`, `Website URL`
- Contributor Day question (matched case-insensitively)

### Option B — REST API Sync

1. On your WordCamp site, create an **Application Password** under **Users → Profile**.
2. In this plugin, go to **↓ Sync WordCamp → Settings**.
3. Enter your WordCamp site URL, admin username, and Application Password.
4. Click **Sync Now**.

---

## File Structure

```
camptix-checkin/
├── camptix-checkin.php              # Plugin bootstrap, constants, includes
├── README.md
├── assets/
│   ├── css/
│   │   └── admin.css                # All admin styles
│   └── js/
│       ├── jsqr.min.js              # Bundled QR decode library (offline-safe)
│       └── scanner.js               # Camera loop, REST scan, audio feedback
├── includes/
│   ├── class-admin-ui.php           # Admin menus, page renderers, form handlers
│   ├── class-attendee-cpt.php       # ctci_attendee custom post type & meta
│   ├── class-badge-endpoint.php     # Standalone badge HTML (intercepts admin_init)
│   ├── class-badge-print.php        # Badge data helper
│   ├── class-checkin-api.php        # REST API: scan, get, list, delete
│   ├── class-email.php              # QR email sending (CID inline attachment)
│   ├── class-qr-generator.php       # HMAC payload, QR URL, PNG fetch & cache
│   └── class-wordcamp-sync.php      # REST API pull + CSV import engine
└── templates/
    ├── add-attendee.php             # Add attendee form
    ├── attendees.php                # Attendee list table (JS-rendered, filterable)
    ├── badge.php                    # Standalone badge/name tag HTML
    ├── dashboard.php                # Stats dashboard
    ├── edit-attendee.php            # Edit attendee form + check-in toggle
    ├── scanner.php                  # Camera QR scanner UI
    ├── send-qr.php                  # Bulk + per-attendee QR email sending
    ├── settings.php                 # Plugin settings + danger zone
    └── sync.php                     # REST API / CSV sync UI
```

---

## REST API

All endpoints are under `/wp-json/camptix-checkin/v1/` and require the `edit_posts` capability (authenticated via cookie nonce or Application Password).

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/scan` | Process a QR scan. Body: `{ "payload": "<id>\|<hmac>" }` |
| `GET` | `/attendee/{id}` | Get a single attendee's data |
| `GET` | `/attendees` | List all attendees with check-in status |
| `DELETE` | `/attendee/{id}/delete` | Permanently delete an attendee (requires `delete_posts`) |

### Scan response

```json
{
  "id": 261,
  "name": "Kaye",
  "badge_name": "Kaye",
  "email": "kaye@example.com",
  "ticket": "Regular",
  "checked_in": true,
  "checked_in_at": "2026-07-07 08:42:11",
  "status": "checked_in"
}
```

`status` is one of: `checked_in`, `already_checked_in`.

---

## Security

- **QR payloads are HMAC-SHA256 signed** using a secret key stored in `wp_options`. A forged or altered QR will be rejected.
- The secret key is auto-generated on first activation and never exposed in the UI.
- All form submissions use WordPress nonces.
- All output is escaped with `esc_html`, `esc_attr`, `esc_url`.
- Badge pages are served from `wp-admin` with a login requirement — the standalone HTML is output by PHP and never stored publicly.
- Delete operations require `delete_posts` capability.

---

## QR Code Flow

```
1. Organizer imports CSV or syncs via REST API
         ↓
2. Plugin stores attendees in local ctci_attendee CPT
         ↓
3. Organizer clicks "Send QR Codes"
         ↓
4. For each attendee:
   a. Plugin fetches QR PNG from api.qrserver.com (server-side)
   b. Saves PNG to temp file
   c. Sends HTML email with QR as CID inline attachment
   d. Marks ctci_qr_sent timestamp on attendee
         ↓
5. Attendee arrives at venue, shows QR on phone or printout
         ↓
6. Volunteer opens QR Scanner on admin tablet
         ↓
7. jsQR decodes the camera frame → payload extracted
         ↓
8. Plugin POST /scan → verifies HMAC → stores check-in timestamp
         ↓
9. Scanner shows green ✓ + plays beep
```

---

## CSV Format

The plugin expects the standard CampTix export from WordCamp.org. Column headers are matched case-insensitively. A minimal example:

```csv
ID,First Name,Last Name,Email Address,Ticket Type,Order Status,Name on Badge,Company,WordPress.org Username,Twitter/X Handle,Website URL,Contributor Day
1001,Maria,Santos,maria@example.com,Regular,publish,Maria,Acme Corp,mariasantos,,https://maria.dev,Yes
1002,Juan,Dela Cruz,juan@example.com,Student,publish,JDC,,,@juandc,,No
```

Rows where `First Name` is `Unknown` or email is blank are skipped automatically.

---

## Configuration

| Option | Default | Description |
|--------|---------|-------------|
| Check-In Meta Key | `camptix_checkin_time` | Post meta key used to store check-in timestamp |
| QR Email Subject | `Your WordCamp Check-In QR Code` | Subject line for QR emails |
| Badge: Show Website | ✓ | Display website URL on printed badge |
| Badge: Show Social Handle | ✓ | Display Twitter/X handle on badge |
| Badge: Show Company | ✓ | Display company/org on badge |

---

## Credits

- **QR generation** — [api.qrserver.com](https://api.qrserver.com) (free, no API key required)
- **QR decoding** — [jsQR](https://github.com/cozmo/jsQR) by Cosmo Wolfe (MIT licence, bundled)
- **Built for** — WordCamp Philippines 2026

---

## Licence

GPLv2 or later — consistent with WordPress plugin licensing requirements.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```
