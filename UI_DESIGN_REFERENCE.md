# 🎨 Event Management System - UI Design Reference

## Ventixe-Inspired Dashboard Design

This document shows the target UI design that has been implemented in the Event Management System.

---

## 📸 Reference Screenshots

### 1. Admin Dashboard (Main View)
![Dashboard Screenshot 1](file:///C:/Users/SAHIL/.gemini/antigravity/brain/a3e446fc-1937-4e60-adf6-cff91b0861ef/uploaded_image_0_1765206340106.jpg)

**Implemented Features**:
- ✅ Pastel purple left sidebar with icon navigation
- ✅ Top header with search bar and user avatar
- ✅ KPI tiles (Upcoming Events, Total Bookings, Tickets Sold)
- ✅ Sales Revenue bar chart
- ✅ Popular Events horizontal cards
- ✅ Recent Bookings table with status badges
- ✅ Soft shadows and rounded corners
- ✅ Pink/purple gradient accents

**File**: `admin/index.php`

---

### 2. Bookings Dashboard
![Bookings Screenshot](file:///C:/Users/SAHIL/.gemini/antigravity/brain/a3e446fc-1937-4e60-adf6-cff91b0861ef/uploaded_image_1_1765206340106.jpg)

**Key Elements to Implement**:
- KPI tiles (Total Bookings, Tickets Sold, Total Earnings)
- Bookings Overview line chart
- Bookings Category donut chart
- Bookings table with filters (All, Confirmed, Pending, Cancelled)
- Invoice ID, Date, Name, Event, Ticket Category, Price, Qty, Amount, Status columns
- Pagination controls
- E-Voucher button

**Target File**: `admin/bookings.php` (To be created)

---

### 3. E-Voucher / Ticket View
![E-Voucher Screenshot](file:///C:/Users/SAHIL/.gemini/antigravity/brain/a3e446fc-1937-4e60-adf6-cff91b0861ef/uploaded_image_2_1765206340106.jpg)

**Key Elements to Implement**:
- Left panel: Event image (Rhythm & Beats Music Festival)
- Center panel: Ticket details
  - Name: Jackson Moore
  - Invoice ID: INV202945
  - Ticket Category: Platinum
  - Seat Number: B12
  - Gate: 3
  - Location: Sunset Park, Los Angeles, CA
  - Date: April 20, 2029
  - Time: 12:00 PM - 11:00 PM
- Right panel: Barcode with "Scan to Enter"
- Event Schedule section
- Venue Map with legend
- Prohibited Items grid (icons)
- Terms & Conditions

**Target Files**: 
- `user/ticket-print.php` (To be created)
- `user/event-details.php` (To be created)

---

### 4. Invoices / Payments Dashboard
![Invoices Screenshot](file:///C:/Users/SAHIL/.gemini/antigravity/brain/a3e446fc-1937-4e60-adf6-cff91b0861ef/uploaded_image_3_1765206340106.jpg)

**Key Elements to Implement**:
- Left panel: Invoice list
  - KPI tiles (Paid, Unpaid, Overdue)
  - Search and filter controls
  - Invoice items with date, amount, status
- Right panel: Invoice Details
  - Invoice number (e.g., #INV10012)
  - Issued Date & Due Date
  - Status badge (Unpaid)
  - Bill From (Event Management Co.)
  - Bill To (Customer details)
  - Ticket Details table
  - Sub Total, Tax, Fee, Total
  - Action buttons (Edit Invoice, Send Invoice, Hold Invoice)

**Target File**: `admin/payments.php` (To be created)

---

## 🎨 Design System Implementation

### Color Palette
```css
/* Implemented in common/assets/css/app.css */
--color-bg: #f6f7fb;          /* Light gray background */
--color-surface: #ffffff;      /* White cards */
--accent-1: #7c3aed;          /* Soft violet */
--accent-2: #f472b6;          /* Soft pink */
--muted-1: #eef2ff;           /* Pale violet (sidebar) */
--muted-2: #fce7f3;           /* Pale pink */
--text-primary: #0f172a;      /* Dark slate */
--text-secondary: #64748b;    /* Gray */
```

### Typography
- **Font**: Inter (Google Fonts)
- **Sizes**: 
  - H1: 24-28px (text-2xl, text-3xl)
  - H2: 18-20px (text-lg, text-xl)
  - Body: 14px (text-sm, text-base)
  - Small: 12px (text-xs)

### Spacing
- **Base**: 16px (1rem)
- **Card Padding**: 24px (1.5rem)
- **Grid Gap**: 24px (gap-6)

### Border Radius
- **Cards**: 2rem (rounded-2xl)
- **Buttons**: 1rem (rounded-xl)
- **Pills**: 9999px (rounded-full)

### Shadows
```css
--shadow-soft: 0 6px 20px rgba(10,10,20,0.06);
--shadow-glow: 0 0 20px rgba(124,58,237,0.3);
```

---

## 🧩 Component Breakdown

### 1. Sidebar Navigation
```html
<aside class="sidebar w-64 fixed left-0 top-0 h-screen">
  <div class="p-6">
    <!-- Logo -->
    <div class="flex items-center gap-3 mb-8">
      <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-pink-500 rounded-xl">
        <i class="fas fa-calendar-alt text-white"></i>
      </div>
      <h1 class="text-xl font-bold">Ventixe</h1>
    </div>
    
    <!-- Nav Links -->
    <nav class="space-y-1">
      <a href="#" class="sidebar-link active">
        <i class="fas fa-home"></i>
        <span>Dashboard</span>
      </a>
      <!-- More links -->
    </nav>
  </div>
</aside>
```

### 2. KPI Tile
```html
<div class="kpi-tile">
  <div class="kpi-icon kpi-icon-purple">
    <i class="fas fa-calendar-alt"></i>
  </div>
  <p class="text-sm text-gray-600 mb-1">Upcoming Events</p>
  <h3 class="text-3xl font-bold text-gray-800">345</h3>
  <p class="text-xs text-green-600 mt-2">
    <i class="fas fa-arrow-up"></i> Active events
  </p>
</div>
```

### 3. Event Card
```html
<div class="event-card">
  <div class="h-48 bg-gradient-to-br from-purple-400 to-pink-400">
    <!-- Image or placeholder -->
  </div>
  <div class="event-card-body">
    <span class="event-card-tag">Music</span>
    <h4 class="font-bold text-gray-800">Event Title</h4>
    <p class="text-sm text-gray-600">Description...</p>
    <div class="flex items-center justify-between">
      <span class="text-xl font-bold text-purple-600">$30</span>
      <button class="btn btn-primary btn-sm">Book Now</button>
    </div>
  </div>
</div>
```

### 4. Status Badge
```html
<span class="badge badge-success">Confirmed</span>
<span class="badge badge-warning">Pending</span>
<span class="badge badge-error">Cancelled</span>
<span class="badge badge-info">Unpaid</span>
```

### 5. Data Table
```html
<div class="table-container">
  <table class="table">
    <thead>
      <tr>
        <th>Invoice ID</th>
        <th>Date</th>
        <th>Name</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><span class="font-mono">INV10011</span></td>
        <td>Feb 15, 2029</td>
        <td>Jackson Moore</td>
        <td><span class="badge badge-success">Paid</span></td>
      </tr>
    </tbody>
  </table>
</div>
```

---

## 📱 Responsive Design

### Desktop (≥1024px)
- Full sidebar (280px width)
- 3-4 column grid for event cards
- Horizontal charts
- Right-hand widgets visible

### Tablet (768px - 1023px)
- Collapsed sidebar (icon only)
- 2-3 column grid
- Stacked charts
- Right widgets below main content

### Mobile (<768px)
- Hidden sidebar
- Bottom navigation bar
- Single column grid
- Vertical stacking
- Touch-friendly buttons (min 44px)

---

## 🎯 Implementation Checklist

### Completed ✅
- [x] Sidebar navigation with icons
- [x] Top header with search
- [x] KPI tiles with gradients
- [x] Bar chart visualization
- [x] Event cards
- [x] Data tables with status badges
- [x] Responsive grid layout
- [x] Mobile bottom navigation
- [x] Design token system
- [x] Component classes

### To Implement 📝
- [ ] Donut chart for bookings
- [ ] Line chart for trends
- [ ] E-Voucher layout
- [ ] Invoice detail panel
- [ ] Venue map component
- [ ] Prohibited items grid
- [ ] Calendar widget
- [ ] Recent activity feed
- [ ] QR code scanner UI
- [ ] Seat selection map

---

## 🖼️ Image Assets Needed

For production, you'll need:
1. Event cover images (1200x600px)
2. Category icons (SVG or PNG)
3. Venue photos
4. User avatars (default placeholder)
5. Logo (SVG preferred)
6. Favicon (32x32px)

**Placeholder images** are currently used with gradient backgrounds and Font Awesome icons.

---

## 🎨 Customization Guide

### Change Theme Colors
1. Go to **Admin → Settings**
2. Update "Accent Color 1" (purple)
3. Update "Accent Color 2" (pink)
4. Save and refresh

### Change App Name
1. Go to **Admin → Settings**
2. Update "Application Name"
3. Save (updates header, footer, emails)

### Add Custom Fonts
Edit `common/assets/css/app.css`:
```css
@import url('https://fonts.googleapis.com/css2?family=YourFont:wght@400;600;700&display=swap');

:root {
  --font-sans: 'YourFont', sans-serif;
}
```

---

## 📚 Resources

- **Tailwind CSS Docs**: https://tailwindcss.com/docs
- **Font Awesome Icons**: https://fontawesome.com/icons
- **Google Fonts**: https://fonts.google.com
- **Color Palette Tool**: https://coolors.co

---

**This design system ensures consistency across all pages and makes the UI feel premium and modern!** ✨
