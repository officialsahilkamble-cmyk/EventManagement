# 📊 Database Schema Reference

## Complete Database Structure for Event Management System

**Database Name**: `eventmanage_db`  
**Character Set**: `utf8mb4`  
**Collation**: `utf8mb4_unicode_ci`  
**Engine**: InnoDB

---

## 📋 Table of Contents

1. [User Management](#user-management)
2. [Admin Management](#admin-management)
3. [Event Management](#event-management)
4. [Booking & Ticketing](#booking--ticketing)
5. [Payment & Financial](#payment--financial)
6. [Content & Reviews](#content--reviews)
7. [System & Settings](#system--settings)

---

## 👥 User Management

### `users`
User accounts for the platform.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Unique user ID |
| `username` | VARCHAR(80) | UNIQUE, NOT NULL | Username for login |
| `email` | VARCHAR(150) | UNIQUE, NOT NULL | Email address |
| `password` | VARCHAR(255) | NOT NULL | Hashed password (bcrypt) |
| `full_name` | VARCHAR(150) | NOT NULL | User's full name |
| `phone` | VARCHAR(30) | NULL | Phone number |
| `avatar` | VARCHAR(255) | NULL | Avatar image path |
| `role` | ENUM('user') | DEFAULT 'user' | User role |
| `is_active` | TINYINT(1) | DEFAULT 1 | Account status |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Registration date |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Last update |

**Indexes**: `email`, `username`

---

## 🔐 Admin Management

### `admin`
Administrator accounts.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Unique admin ID |
| `username` | VARCHAR(80) | UNIQUE, NOT NULL | Admin username |
| `password` | VARCHAR(255) | NOT NULL | Hashed password |
| `full_name` | VARCHAR(150) | NOT NULL | Admin's full name |
| `email` | VARCHAR(150) | NULL | Email address |
| `role` | ENUM('super','manager','editor') | DEFAULT 'super' | Admin role |
| `is_active` | TINYINT(1) | DEFAULT 1 | Account status |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Creation date |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Last update |

**Default Admin**: username=`admin`, password=`Admin@123`

### `admin_logs`
Admin activity tracking.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Log ID |
| `admin_id` | INT | FK → admin.id | Admin who performed action |
| `action` | VARCHAR(255) | NULL | Action description |
| `ip` | VARCHAR(45) | NULL | IP address |
| `user_agent` | TEXT | NULL | Browser info |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Log timestamp |

---

## 🎭 Event Management

### `categories`
Event categories.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Category ID |
| `name` | VARCHAR(120) | NOT NULL | Category name |
| `slug` | VARCHAR(120) | UNIQUE, NOT NULL | URL-friendly slug |
| `icon` | VARCHAR(80) | NULL | Font Awesome icon class |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Creation date |

**Sample Data**: Music, Sports, Fashion, Art & Design, Food & Culinary, Technology

### `venues`
Event venues/locations.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Venue ID |
| `name` | VARCHAR(255) | NOT NULL | Venue name |
| `address` | TEXT | NULL | Full address |
| `city` | VARCHAR(100) | NULL | City |
| `country` | VARCHAR(100) | NULL | Country |
| `capacity` | INT | NULL | Maximum capacity |
| `latitude` | DECIMAL(10,7) | NULL | GPS latitude |
| `longitude` | DECIMAL(10,7) | NULL | GPS longitude |
| `contact_name` | VARCHAR(120) | NULL | Contact person |
| `contact_phone` | VARCHAR(30) | NULL | Contact phone |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Creation date |

### `events`
Main events table.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Event ID |
| `title` | VARCHAR(255) | NOT NULL | Event title |
| `slug` | VARCHAR(255) | UNIQUE, NOT NULL | URL slug |
| `short_description` | TEXT | NULL | Brief description |
| `full_description` | TEXT | NULL | Full description |
| `category_id` | INT | FK → categories.id | Event category |
| `venue_id` | INT | FK → venues.id | Event venue |
| `start_datetime` | DATETIME | NOT NULL | Event start time |
| `end_datetime` | DATETIME | NOT NULL | Event end time |
| `booking_open` | DATETIME | NULL | Booking opens |
| `booking_close` | DATETIME | NULL | Booking closes |
| `status` | ENUM('draft','active','disabled') | DEFAULT 'draft' | Event status |
| `capacity` | INT | NULL | Total capacity |
| `created_by` | INT | FK → admin.id | Admin who created |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Creation date |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Last update |

**Indexes**: `slug`, `status`, `category_id`

### `event_gallery`
Event images/photos.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Gallery ID |
| `event_id` | INT | FK → events.id | Event reference |
| `file_path` | VARCHAR(255) | NOT NULL | Image file path |
| `is_cover` | TINYINT(1) | DEFAULT 0 | Is cover image |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Upload date |

### `seat_types`
Ticket types and pricing.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Seat type ID |
| `event_id` | INT | FK → events.id | Event reference |
| `name` | VARCHAR(120) | NOT NULL | Seat type name (Diamond, Platinum, etc.) |
| `price` | DECIMAL(10,2) | NOT NULL | Ticket price |
| `quantity` | INT | NOT NULL | Total available |
| `sold` | INT | DEFAULT 0 | Number sold |
| `tax_percent` | DECIMAL(5,2) | DEFAULT 0 | Tax percentage |
| `refundable` | TINYINT(1) | DEFAULT 1 | Is refundable |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Creation date |

### `event_sessions`
Event schedule/sessions.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Session ID |
| `event_id` | INT | FK → events.id | Event reference |
| `title` | VARCHAR(255) | NULL | Session title |
| `start_time` | DATETIME | NULL | Session start |
| `end_time` | DATETIME | NULL | Session end |
| `speaker` | VARCHAR(255) | NULL | Speaker name |
| `description` | TEXT | NULL | Session description |

---

## 🎫 Booking & Ticketing

### `bookings`
Booking records.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Booking ID |
| `booking_ref` | VARCHAR(100) | UNIQUE, NOT NULL | Booking reference (BK-YYYYMMDD-XXXXXXXX) |
| `user_id` | INT | FK → users.id | User who booked |
| `event_id` | INT | FK → events.id | Event booked |
| `total_amount` | DECIMAL(12,2) | NOT NULL | Total amount paid |
| `status` | ENUM('confirmed','pending','cancelled','refunded') | DEFAULT 'pending' | Booking status |
| `payment_id` | INT | FK → payments.id | Payment reference |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Booking date |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Last update |

**Indexes**: `booking_ref`, `user_id`, `status`

### `tickets`
Individual tickets.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Ticket ID |
| `booking_id` | INT | FK → bookings.id | Booking reference |
| `ticket_id` | VARCHAR(120) | UNIQUE, NOT NULL | Ticket ID (EVT-YYYYMMDD-XXXXXX) |
| `seat_type_id` | INT | FK → seat_types.id | Seat type |
| `seat_number` | VARCHAR(50) | NULL | Seat number |
| `qr_code_path` | VARCHAR(255) | NULL | QR code image path |
| `status` | ENUM('active','used','cancelled') | DEFAULT 'active' | Ticket status |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Issue date |

**Indexes**: `ticket_id`

### `attendance`
Check-in logs.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Attendance ID |
| `ticket_id` | INT | FK → tickets.id | Ticket scanned |
| `checked_in_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Check-in time |
| `checked_in_by` | INT | FK → admin.id | Admin who scanned |
| `device_info` | VARCHAR(255) | NULL | Device information |

---

## 💳 Payment & Financial

### `payments`
Payment transactions.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Payment ID |
| `booking_id` | INT | FK → bookings.id | Booking reference |
| `gateway` | VARCHAR(80) | NULL | Payment gateway (Stripe, PayPal, etc.) |
| `transaction_id` | VARCHAR(150) | NULL | Gateway transaction ID |
| `amount` | DECIMAL(12,2) | NOT NULL | Payment amount |
| `status` | ENUM('successful','failed','pending') | DEFAULT 'pending' | Payment status |
| `payment_date` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Payment date |
| `meta` | TEXT | NULL | Additional metadata (JSON) |

---

## ⭐ Content & Reviews

### `reviews`
Event reviews and ratings.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Review ID |
| `event_id` | INT | FK → events.id | Event reviewed |
| `user_id` | INT | FK → users.id | User who reviewed |
| `rating` | INT | NOT NULL, CHECK (1-5) | Star rating |
| `comment` | TEXT | NULL | Review comment |
| `status` | ENUM('pending','approved','rejected') | DEFAULT 'pending' | Moderation status |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Review date |

---

## ⚙️ System & Settings

### `settings`
Application configuration.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Setting ID |
| `config_key` | VARCHAR(120) | UNIQUE, NOT NULL | Setting key |
| `config_value` | TEXT | NULL | Setting value |
| `updated_at` | DATETIME | ON UPDATE CURRENT_TIMESTAMP | Last update |

**Default Settings**:
- `app_name` = "Event Management"
- `contact_email` = "info@eventmanage.com"
- `contact_phone` = "+1 234 567 8900"
- `smtp_host`, `smtp_port`, `smtp_user`, `smtp_pass`
- `accent_color_1` = "#7c3aed"
- `accent_color_2` = "#f472b6"

### `notifications`
Email notification queue.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Notification ID |
| `user_id` | INT | FK → users.id | Recipient user |
| `type` | VARCHAR(80) | NULL | Notification type |
| `message` | TEXT | NULL | Message content |
| `is_sent` | TINYINT(1) | DEFAULT 0 | Sent status |
| `send_at` | DATETIME | NULL | Scheduled send time |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Creation date |

### `password_resets`
Password reset tokens.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Reset ID |
| `user_id` | INT | FK → users.id | User requesting reset |
| `token` | VARCHAR(255) | NOT NULL | Reset token |
| `expires_at` | DATETIME | NOT NULL | Token expiration |

---

## 🔗 Relationships Diagram

```
users (1) ----< (N) bookings
bookings (1) ----< (N) tickets
bookings (1) ---- (1) payments
events (1) ----< (N) bookings
events (1) ----< (N) event_gallery
events (1) ----< (N) seat_types
events (1) ----< (N) reviews
events (N) ---- (1) categories
events (N) ---- (1) venues
admin (1) ----< (N) events
admin (1) ----< (N) admin_logs
tickets (1) ----< (N) attendance
```

---

## 📝 Common Queries

### Get Event with Details
```sql
SELECT e.*, c.name as category_name, v.name as venue_name,
       (SELECT file_path FROM event_gallery WHERE event_id = e.id AND is_cover = 1 LIMIT 1) as cover_image,
       (SELECT MIN(price) FROM seat_types WHERE event_id = e.id) as min_price
FROM events e
LEFT JOIN categories c ON e.category_id = c.id
LEFT JOIN venues v ON e.venue_id = v.id
WHERE e.status = 'active' AND e.start_datetime > NOW()
ORDER BY e.start_datetime ASC;
```

### Get User Bookings
```sql
SELECT b.*, e.title as event_title, e.start_datetime,
       COUNT(t.id) as ticket_count
FROM bookings b
JOIN events e ON b.event_id = e.id
LEFT JOIN tickets t ON b.id = t.booking_id
WHERE b.user_id = ?
GROUP BY b.id
ORDER BY b.created_at DESC;
```

### Get Dashboard Stats
```sql
-- Upcoming Events
SELECT COUNT(*) FROM events WHERE status = 'active' AND start_datetime > NOW();

-- Total Bookings
SELECT COUNT(*) FROM bookings WHERE status IN ('confirmed', 'pending');

-- Tickets Sold
SELECT COUNT(*) FROM tickets WHERE status = 'active';

-- Total Revenue
SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE status = 'confirmed';
```

### Check Seat Availability
```sql
SELECT st.quantity - st.sold as available
FROM seat_types st
WHERE st.id = ? AND st.event_id = ?
FOR UPDATE; -- Lock row for transaction
```

---

## 🔧 Maintenance Queries

### Reset Admin Password
```sql
UPDATE admin 
SET password = '$2y$10$...' -- Use password_hash() in PHP
WHERE username = 'admin';
```

### Clean Old Password Reset Tokens
```sql
DELETE FROM password_resets WHERE expires_at < NOW();
```

### Archive Old Events
```sql
UPDATE events 
SET status = 'disabled' 
WHERE end_datetime < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

---

## 📊 Performance Optimization

### Recommended Indexes
```sql
-- Already created in install.php
CREATE INDEX idx_events_status ON events(status);
CREATE INDEX idx_events_start ON events(start_datetime);
CREATE INDEX idx_bookings_user ON bookings(user_id);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_tickets_booking ON tickets(booking_id);
```

### Query Optimization Tips
1. Use `EXPLAIN` to analyze slow queries
2. Add indexes on frequently filtered columns
3. Use `LIMIT` for pagination
4. Cache settings table in application
5. Use `JOIN` instead of subqueries when possible

---

## 🔒 Security Notes

1. **Never store plain passwords** - Always use `password_hash()`
2. **Use prepared statements** - Prevent SQL injection
3. **Validate foreign keys** - Ensure referential integrity
4. **Regular backups** - Schedule daily database backups
5. **Monitor logs** - Check `admin_logs` for suspicious activity

---

**Database Version**: 1.0  
**Last Updated**: 2025-12-08  
**Total Tables**: 20+
