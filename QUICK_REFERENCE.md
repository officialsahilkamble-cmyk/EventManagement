# 🚀 Event Management System - Quick Reference

## 📁 Project Structure

```
eventmanage/
├── 📄 index.php                    → Redirects to user/index.php
├── 📄 .htaccess                    → Apache security & performance
├── 📄 README.md                    → Complete documentation
├── 📄 IMPLEMENTATION_GUIDE.md      → Development roadmap
├── 📄 DELIVERY_SUMMARY.md          → What's included
│
├── 📁 common/                      → Shared resources
│   ├── config.php                  → Database connection (PDO)
│   ├── functions.php               → Utility functions
│   ├── auth.php                    → Authentication helpers
│   └── assets/
│       ├── css/
│       │   └── app.css             → Design tokens & components
│       └── js/
│           ├── app.js              → UI interactions
│           └── disable_ui.js       → Security restrictions
│
├── 📁 install/                     → Installation wizard
│   └── install.php                 → One-click installer
│
├── 📁 admin/                       → Admin panel
│   ├── login.php                   → Admin authentication
│   ├── index.php                   → Dashboard (Ventixe UI)
│   ├── settings.php                → App settings
│   └── logout.php                  → Logout handler
│
└── 📁 user/                        → User panel
    ├── login.php                   → Login/Register
    ├── index.php                   → Homepage
    └── logout.php                  → Logout handler
```

---

## 🔑 Database Credentials

```php
Host:     127.0.0.1
Database: eventmanage_db
Username: root
Password: root
```

**Change in**: `common/config.php`

---

## 👤 Default Accounts

### Admin
```
URL:      http://localhost/eventmanage/admin/login.php
Username: admin
Password: Admin@123
```

### User
```
URL:      http://localhost/eventmanage/user/login.php
Action:   Register new account
```

---

## 🎨 Design Tokens (CSS Variables)

```css
/* Colors */
--color-bg: #f6f7fb          /* Background */
--color-surface: #ffffff     /* Cards */
--accent-1: #7c3aed          /* Purple */
--accent-2: #f472b6          /* Pink */
--muted-1: #eef2ff           /* Light purple */
--text-primary: #0f172a      /* Dark text */

/* Spacing */
--spacing-sm: 0.5rem         /* 8px */
--spacing-md: 1rem           /* 16px */
--spacing-lg: 1.5rem         /* 24px */

/* Border Radius */
--radius-lg: 1rem            /* 16px */
--radius-xl: 1.5rem          /* 24px */
--radius-2xl: 2rem           /* 32px */
--radius-full: 9999px        /* Circle */

/* Shadows */
--shadow-soft: 0 6px 20px rgba(10,10,20,0.06)
--shadow-glow: 0 0 20px rgba(124,58,237,0.3)
```

---

## 🧩 Common Component Classes

### Cards
```html
<div class="card">Content</div>
<div class="card card-compact">Compact card</div>
```

### Buttons
```html
<button class="btn btn-primary">Primary</button>
<button class="btn btn-secondary">Secondary</button>
<button class="btn btn-outline">Outline</button>
<button class="btn btn-sm">Small</button>
```

### Badges
```html
<span class="badge badge-success">Confirmed</span>
<span class="badge badge-warning">Pending</span>
<span class="badge badge-error">Cancelled</span>
<span class="badge badge-purple">Music</span>
```

### KPI Tiles
```html
<div class="kpi-tile">
  <div class="kpi-icon kpi-icon-purple">
    <i class="fas fa-calendar-alt"></i>
  </div>
  <p class="text-sm text-gray-600">Label</p>
  <h3 class="text-3xl font-bold">345</h3>
</div>
```

### Event Cards
```html
<div class="event-card">
  <img src="..." class="event-card-image">
  <div class="event-card-body">
    <span class="event-card-tag">Music</span>
    <h4>Event Title</h4>
    <p>Description</p>
  </div>
</div>
```

---

## 🛠️ Common PHP Functions

### Security
```php
esc($string)                    // Escape output (XSS prevention)
generateCSRFToken()             // Generate CSRF token
verifyCSRFToken($token)         // Verify CSRF token
```

### Authentication
```php
isLoggedIn()                    // Check if user logged in
isAdminLoggedIn()               // Check if admin logged in
requireAuth()                   // Require user auth (redirect if not)
requireAdminAuth()              // Require admin auth
loginUser($userId)              // Login user
loginAdmin($adminId)            // Login admin
logoutUser()                    // Logout user
logoutAdmin()                   // Logout admin
getCurrentUser()                // Get current user data
getCurrentAdmin()               // Get current admin data
```

### Utilities
```php
formatCurrency($amount)         // Format as $X.XX
formatDate($date)               // Format date
formatDateTime($datetime)       // Format date & time
generateTicketID()              // Generate ticket ID (EVT-YYYYMMDD-XXXXXX)
generateBookingRef()            // Generate booking ref (BK-YYYYMMDD-XXXXXXXX)
uploadFile($file, $dir)         // Upload & validate file
sendEmail($to, $subject, $body) // Send email
redirect($url)                  // Redirect to URL
setFlash($type, $message)       // Set flash message
getFlash()                      // Get & clear flash message
slugify($text)                  // Create URL slug
paginate($total, $perPage)      // Pagination helper
```

### Database
```php
global $pdo;                    // PDO connection

// Prepared statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Insert
$stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
$stmt->execute([$name, $email]);
$lastId = $pdo->lastInsertId();

// Update
$stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
$stmt->execute([$name, $userId]);

// Delete
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$userId]);
```

---

## 🎯 Common Patterns

### Page Template (Admin)
```php
<?php
define('APP_ACCESS', true);
require_once '../common/config.php';
require_once '../common/functions.php';
require_once '../common/auth.php';

requireAdminAuth();
checkSessionTimeout();

$admin = getCurrentAdmin();
$pageTitle = 'Page Title';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME_DISPLAY; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../common/assets/css/app.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Include sidebar -->
    <!-- Include header -->
    <main class="p-6">
        <!-- Content -->
    </main>
    <script src="../common/assets/js/app.js"></script>
    <script src="../common/assets/js/disable_ui.js"></script>
</body>
</html>
```

### Form with CSRF
```html
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <!-- Form fields -->
    <button type="submit" class="btn btn-primary">Submit</button>
</form>
```

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        // Process form
    }
}
```

### Flash Messages
```php
// Set flash
setFlash('success', 'Operation successful!');
redirect('page.php');

// Display flash
<?php $flash = getFlash(); if ($flash): ?>
    <div class="bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-50 border-l-4 border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-400 p-4 mb-6 rounded-lg">
        <p class="text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-700"><?php echo esc($flash['message']); ?></p>
    </div>
<?php endif; ?>
```

---

## 📊 Database Tables Quick Reference

```sql
users           → User accounts
admin           → Admin accounts
events          → Event listings
event_gallery   → Event images
categories      → Event categories
venues          → Event venues
seat_types      → Ticket pricing
bookings        → Booking records
tickets         → Individual tickets
payments        → Payment transactions
reviews         → Event reviews
settings        → App configuration
notifications   → Email queue
attendance      → Check-in logs
admin_logs      → Admin activity
```

---

## 🔧 Common Tasks

### Change App Name
1. Login to Admin Panel
2. Go to Settings
3. Update "Application Name"
4. Save

### Add New Category
```sql
INSERT INTO categories (name, slug, icon) 
VALUES ('Technology', 'technology', 'fa-laptop');
```

### Add New Admin
```php
$password = password_hash('SecurePass123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO admin (username, password, full_name, email) VALUES (?, ?, ?, ?)");
$stmt->execute(['newadmin', $password, 'Admin Name', 'admin@example.com']);
```

### Reset Admin Password
```php
$newPassword = password_hash('NewPass123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE admin SET password = ? WHERE username = 'admin'");
$stmt->execute([$newPassword]);
```

---

## 🎨 Tailwind CSS Quick Reference

### Layout
```html
<div class="container mx-auto px-4">         <!-- Container -->
<div class="grid grid-cols-3 gap-6">         <!-- Grid -->
<div class="flex items-center justify-between"> <!-- Flex -->
```

### Spacing
```html
<div class="p-6">      <!-- Padding: 24px -->
<div class="m-4">      <!-- Margin: 16px -->
<div class="mb-8">     <!-- Margin bottom: 32px -->
<div class="space-y-4"> <!-- Vertical spacing -->
```

### Typography
```html
<h1 class="text-3xl font-bold text-gray-800">
<p class="text-sm text-gray-600">
<span class="font-semibold">
```

### Colors
```html
<div class="bg-purple-600 text-white">
<div class="bg-gray-50 text-gray-800">
<div class="border border-gray-200">
```

---

## 🐛 Debugging Tips

### Enable Error Display
```php
// In config.php (development only)
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Check Database Connection
```php
try {
    $stmt = $pdo->query("SELECT 1");
    echo "Database connected!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
```

### View Session Data
```php
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
```

### Check Upload Permissions
```bash
ls -la uploads/
chmod -R 777 uploads/  # Development only!
```

---

## 📞 Quick Links

- **Admin Dashboard**: `/admin/index.php`
- **User Homepage**: `/user/index.php`
- **Settings**: `/admin/settings.php`
- **Installation**: `/install/install.php`

---

## ⚡ Performance Tips

1. **Enable OpCache** (production)
2. **Use CDN** for Tailwind & Font Awesome
3. **Optimize images** before upload
4. **Enable Gzip** compression
5. **Cache database queries** for settings
6. **Use indexes** on frequently queried columns

---

## 🔒 Security Checklist

- [x] CSRF tokens on all forms
- [x] Prepared statements (no SQL injection)
- [x] Output escaping (no XSS)
- [x] Password hashing (bcrypt)
- [x] File upload validation
- [x] Session timeout
- [x] Admin activity logging
- [ ] HTTPS enabled (production)
- [ ] Rate limiting on login
- [ ] Input sanitization

---

**Keep this file handy for quick reference during development!** 📌
