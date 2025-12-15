# Event Management System

A comprehensive, mobile-first event management web application with a modern Ventixe-inspired UI design.

## 🎨 Design Philosophy

This application features a **Ventixe-style dashboard** with:
- Soft pastel purple sidebar with rounded cards
- Pink/violet accent gradients
- Large hero KPI tiles with circular visualizations
- Charts with pastel pink/purple tones
- Right-hand widgets (upcoming events, calendar, recent activity)
- Consistent airy spacing and rounded-2xl corners
- Soft inner shadows and glassmorphism effects

## 📋 Technology Stack

- **Frontend**: HTML5, Tailwind CSS, Vanilla JavaScript
- **Backend**: Core PHP (No frameworks)
- **Database**: MySQL with PDO
- **Icons**: Font Awesome 6
- **Typography**: Inter font family

## 🚀 Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- PDO Extension enabled

### Setup Steps

1. **Clone/Download** this project to your web server directory (e.g., `htdocs`, `www`, or `public_html`)

2. **Set Permissions** for upload directories:
   ```bash
   chmod -R 755 uploads/
   chmod -R 755 install/
   ```

3. **Configure Database** (Optional - defaults work with XAMPP/WAMP):
   - Edit `common/config.php` if you need different credentials
   - Default: Host=127.0.0.1, User=root, Pass=root, DB=eventmanage_db

4. **Run Installation**:
   - Navigate to: `http://localhost/eventmanage/install/install.php`
   - Click "Install Now"
   - Wait for database creation and sample data insertion

5. **Login Credentials**:
   - **Admin Panel**: `http://localhost/eventmanage/admin/login.php`
     - Username: `admin`
     - Password: `Admin@123`
   - **User Panel**: `http://localhost/eventmanage/user/login.php`
     - Register a new account

6. **Security**: Change the default admin password immediately after first login!

## 📁 Project Structure

```
eventmanage/
├── common/
│   ├── config.php              # Database configuration
│   ├── functions.php           # Utility functions
│   ├── auth.php                # Authentication helpers
│   ├── header.php              # Common header
│   ├── bottom.php              # Mobile bottom navigation
│   └── assets/
│       ├── css/
│       │   ├── tailwind.css    # Tailwind CDN
│       │   └── app.css         # Custom styles & design tokens
│       ├── js/
│       │   ├── app.js          # Main application JS
│       │   └── disable_ui.js   # UI restrictions
│       └── images/             # Preview/demo images
│
├── install/
│   ├── install.php             # Installation wizard
│   └── install.lock            # Lock file (created after install)
│
├── admin/
│   ├── login.php               # Admin login
│   ├── index.php               # Dashboard (Ventixe-style)
│   ├── events.php              # Event management
│   ├── event-add.php           # Add new event
│   ├── event-edit.php          # Edit event
│   ├── bookings.php            # Booking management
│   ├── booking-view.php        # View booking details
│   ├── users.php               # User management
│   ├── payments.php            # Payment/invoice management
│   ├── reviews.php             # Review moderation
│   ├── categories.php          # Category CRUD
│   ├── venues.php              # Venue management
│   ├── settings.php            # App settings (change app name, etc.)
│   ├── reports.php             # Analytics & reports
│   ├── attendance-scan.php     # QR code scanner
│   └── logout.php              # Admin logout
│
├── user/
│   ├── login.php               # User login/register
│   ├── register.php            # User registration
│   ├── index.php               # User homepage
│   ├── events.php              # Browse events
│   ├── event-details.php       # Event details page
│   ├── book-ticket.php         # Ticket booking form
│   ├── my-bookings.php         # User's bookings
│   ├── ticket-print.php        # Printable e-voucher
│   ├── download-ticket.php     # Download ticket PDF
│   ├── profile.php             # User profile
│   └── logout.php              # User logout
│
├── uploads/                    # File uploads
│   ├── events/                 # Event images
│   ├── tickets/                # QR codes & barcodes
│   ├── avatars/                # User avatars
│   └── gallery/                # Event gallery
│
├── cron/                       # Scheduled tasks
│   ├── send_reminders.php      # Email reminders
│   ├── cleanup_temp.php        # Cleanup temporary files
│   └── generate_reports.php    # Generate reports
│
├── index.php                   # Root redirect
├── .htaccess                   # Apache configuration
└── README.md                   # This file
```

## 🎨 Design Tokens & UI Components

### Color Palette

```css
--color-bg: #f6f7fb          /* Very light gray background */
--color-surface: #ffffff     /* White cards */
--accent-1: #7c3aed          /* Soft violet */
--accent-2: #f472b6          /* Soft pink */
--muted-1: #eef2ff           /* Pale violet (sidebar) */
--text-primary: #0f172a      /* Dark slate */
--text-muted: #6b7280        /* Gray text */
```

### Typography

- **Font Family**: Inter, system-sans fallback
- **Sizes**: h1 (24-28px), h2 (18-20px), body (14px)
- **Tailwind Classes**: `text-base`, `text-sm`, `text-lg`

### Spacing & Shapes

- **Base Spacing**: 16px
- **Card Padding**: 24px
- **Border Radius**: `1rem` (rounded-2xl)
- **Shadows**: `box-shadow: 0 6px 20px rgba(10,10,20,0.06)`

### Key Components

#### KPI Tile
```html
<div class="kpi-tile">
  <div class="kpi-icon kpi-icon-purple">
    <i class="fas fa-calendar-alt"></i>
  </div>
  <p class="text-sm text-gray-600">Upcoming Events</p>
  <h3 class="text-3xl font-bold">345</h3>
</div>
```

#### Event Card
```html
<div class="event-card">
  <img src="..." class="event-card-image">
  <div class="event-card-body">
    <span class="event-card-tag">Music</span>
    <h4>Event Title</h4>
    <p>Description...</p>
  </div>
</div>
```

#### Status Badge
```html
<span class="badge badge-success">Confirmed</span>
<span class="badge badge-warning">Pending</span>
<span class="badge badge-error">Cancelled</span>
```

## 🗄️ Database Schema

### Key Tables

- **users** - User accounts
- **admin** - Admin accounts
- **events** - Event listings
- **event_gallery** - Event images
- **categories** - Event categories
- **venues** - Event venues
- **seat_types** - Ticket types/pricing
- **bookings** - Booking records
- **tickets** - Individual tickets with QR codes
- **payments** - Payment transactions
- **reviews** - Event reviews
- **settings** - Application settings
- **notifications** - Email notifications
- **attendance** - Check-in logs
- **admin_logs** - Admin activity logs

## ⚙️ Features

### User Panel
- ✅ User registration & login
- ✅ Browse events with filters
- ✅ Event details with schedule
- ✅ Ticket booking with seat selection
- ✅ My bookings dashboard
- ✅ Printable e-voucher with QR code
- ✅ User profile management
- ✅ Event reviews & ratings

### Admin Panel
- ✅ Ventixe-style dashboard with KPIs
- ✅ Event management (CRUD)
- ✅ Booking management
- ✅ User management
- ✅ Payment/invoice tracking
- ✅ Review moderation
- ✅ Category & venue management
- ✅ Settings (change app name, logo, colors)
- ✅ Reports & analytics
- ✅ QR code attendance scanner
- ✅ Admin Panel id : admin  and Pass : Admin@1234

### Security Features
- ✅ Password hashing (bcrypt)
- ✅ CSRF protection
- ✅ SQL injection prevention (PDO prepared statements)
- ✅ XSS prevention (output escaping)
- ✅ Session management with timeout
- ✅ File upload validation
- ✅ Admin activity logging
- ✅ Disabled text selection & right-click
- ✅ Disabled zoom (pinch & keyboard)

## 🔧 Configuration

### Change Application Name

1. Login to Admin Panel
2. Go to **Settings** → **General**
3. Update "Application Name" field
4. Save changes

The name will update across:
- Page titles
- Headers
- Footers
- Email templates

### Email Configuration

Edit SMTP settings in Admin Panel → Settings:
- SMTP Host
- SMTP Port
- SMTP Username
- SMTP Password

### Theme Customization

Update accent colors in Settings:
- Accent Color 1 (Purple)
- Accent Color 2 (Pink)

## 📅 Cron Jobs

Set up these cron jobs for automated tasks:

```bash
# Send event reminders (daily at 9 AM)
0 9 * * * php /path/to/eventmanage/cron/send_reminders.php

# Cleanup temporary files (daily at 2 AM)
0 2 * * * php /path/to/eventmanage/cron/cleanup_temp.php

# Generate reports (weekly on Monday at 1 AM)
0 1 * * 1 php /path/to/eventmanage/cron/generate_reports.php
```

## 🎫 Ticket System

### Ticket ID Format
`EVT-YYYYMMDD-XXXXXX`

Example: `EVT-20250420-A3F7B2`

### Booking Reference Format
`BK-YYYYMMDD-XXXXXXXX`

Example: `BK-20250420-D8E9F1A2`

### QR Code & Barcode
- QR codes contain ticket ID for scanning
- Barcodes generated as SVG
- Stored in `uploads/tickets/`

## 📱 Mobile Responsiveness

- Mobile-first design
- Sidebar collapses to bottom navigation on small screens
- KPI tiles stack vertically
- Touch-friendly buttons and inputs
- Optimized for iOS and Android browsers

## 🖨️ Print Functionality

The e-voucher page (`ticket-print.php`) includes:
- Print-optimized stylesheet
- A4-friendly layout
- QR code and barcode
- Event details and terms
- "Scan to Enter" section

## 🔒 Security Best Practices

1. **Change default admin password** immediately
2. **Delete install folder** after installation
3. **Set proper file permissions**:
   - Files: 644
   - Directories: 755
   - config.php: 600 (recommended)
4. **Enable HTTPS** in production
5. **Regular backups** of database and uploads
6. **Keep PHP and MySQL updated**

## 🐛 Troubleshooting

### Database Connection Failed
- Check MySQL is running
- Verify credentials in `common/config.php`
- Ensure database exists

### Upload Errors
- Check folder permissions (755)
- Verify PHP upload_max_filesize setting
- Ensure uploads/ directory exists

### Session Issues
- Check PHP session configuration
- Verify session.save_path is writable
- Clear browser cookies

## 📝 Sample Data

The installation includes:
- 1 admin account
- 6 event categories
- 1 sample venue
- 3 demo events with seat types
- Default settings

## 🎯 Future Enhancements

- Payment gateway integration (Stripe, PayPal)
- Email templates with PHPMailer
- Advanced reporting with charts
- Multi-language support
- Social media integration
- Mobile app (PWA)
- Advanced seat map visualization

## 📄 License

This project is provided as-is for educational and commercial use.

## 👨‍💻 Support

For issues or questions:
1. Check this README
2. Review code comments
3. Check database schema
4. Verify file permissions

## 🎉 Credits

- **Design Inspiration**: Ventixe Dashboard
- **Icons**: Font Awesome
- **CSS Framework**: Tailwind CSS
- **Fonts**: Google Fonts (Inter)

---

**Version**: 1.0  
**Last Updated**: 2025-12-08  
**PHP Version**: 7.4+  
**MySQL Version**: 5.7+

---

## Quick Start Checklist

- [ ] Extract files to web server
- [ ] Set folder permissions
- [ ] Run install.php
- [ ] Login to admin panel
- [ ] Change default password
- [ ] Configure SMTP settings
- [ ] Customize app name and logo
- [ ] Add real events
- [ ] Set up cron jobs
- [ ] Delete install folder
- [ ] Enable HTTPS (production)

**Enjoy your Event Management System! 🎉**
