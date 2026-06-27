# 🍽️ OBJSIS V2 - Restaurant Management System

<div align="center">

[![Version](https://img.shields.io/badge/version-2.6.0-orange?style=for-the-badge)](https://github.com/DenisVargaeu/OBJSIS/releases)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple?style=for-the-badge)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-blue?style=for-the-badge)](https://www.mysql.com/)
[![CSS](https://img.shields.io/badge/CSS3-Glassmorphism-ff69b4?style=for-the-badge)](#)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6%2B-yellow?style=for-the-badge)](#)
[![License](https://img.shields.io/badge/License-All%20Rights%20Reserved-red?style=for-the-badge)](#license)

[![GitHub Stars](https://img.shields.io/github/stars/DenisVargaeu/OBJSIS?style=flat-square&logo=github)](https://github.com/DenisVargaeu/OBJSIS/stargazers)
[![GitHub Forks](https://img.shields.io/github/forks/DenisVargaeu/OBJSIS?style=flat-square&logo=github)](https://github.com/DenisVargaeu/OBJSIS/network/members)
[![Last Commit](https://img.shields.io/github/last-commit/DenisVargaeu/OBJSIS?style=flat-square&logo=github)](https://github.com/DenisVargaeu/OBJSIS/commits/main)
[![Open Issues](https://img.shields.io/github/issues/DenisVargaeu/OBJSIS?style=flat-square&logo=github)](https://github.com/DenisVargaeu/OBJSIS/issues)

**A Modern, Full-Featured Restaurant Management & POS System**

[Quick Start](#-quick-start) • [Features](#-features) • [Installation](#-installation) • [Documentation](#-documentation) • [License](#-license)

</div>

---

## 📌 Overview

OBJSIS V2 is a **professional-grade restaurant management system** built with PHP, CSS, and JavaScript. Designed for independent restaurants, cafes, and small restaurant chains, it provides comprehensive order management, real-time kitchen displays, inventory tracking, employee management, and a powerful modular addon ecosystem.

> **Perfect for:** Restaurants seeking an affordable, self-hosted alternative to expensive POS systems.

---

## ✨ Features

### 🎯 Core Features

| Feature | Description |
|---------|-------------|
| 🔌 **Modular Addon System** | Extend functionality with one-click installable plugins without modifying core code |
| 📊 **Real-time Dashboard** | Live business metrics with Chart.js analytics and flicker-free updates |
| 👨‍🍳 **Elite KDS (Kitchen Display)** | High-visibility kitchen mode with urgency alerts and audio notifications |
| 🔑 **API Manager PRO** | Enterprise REST API with multi-key management and IP whitelisting |
| 💻 **Terminal Pro Ultra** | Full-screen interactive CLI with system monitoring and diagnostics |
| 🌓 **Glassmorphic UI** | Premium, modern design across 15+ admin pages with responsive layouts |
| 🪙 **Multi-Currency** | Real-time price conversion (EUR, USD, GBP, HUF) for international guests |
| 🔔 **Smart Notifications** | Real-time alerts for orders, low stock, and critical system events |
| ⚡ **Quick Actions** | Floating speed-dial for frequent administrative tasks |
| 📱 **Table Management** | Digital table assignments with seating arrangements |
| 📦 **Inventory Control** | Stock tracking, recipe management, and automatic deduction |
| 👥 **Employee Management** | Role-based access, shift tracking, and performance monitoring |
| 📈 **Advanced Reports** | Sales analytics, employee performance, and business insights |

---

## 🛠️ Tech Stack

<div align="center">

[![PHP Badge](https://img.shields.io/badge/Backend-PHP%207.4%2B-777BB4?style=flat&logo=php&logoColor=white)](#)
[![MySQL Badge](https://img.shields.io/badge/Database-MySQL%205.7%2B-00758F?style=flat&logo=mysql&logoColor=white)](#)
[![CSS Badge](https://img.shields.io/badge/Styling-CSS3%20%26%20Glassmorphism-1572B6?style=flat&logo=css3&logoColor=white)](#)
[![JavaScript Badge](https://img.shields.io/badge/Frontend-JavaScript%20ES6%2B-F7DF1E?style=flat&logo=javascript&logoColor=black)](#)
[![Chart.js Badge](https://img.shields.io/badge/Charts-Chart.js-F7BC3D?style=flat&logo=chartdotjs&logoColor=white)](#)
[![Apache Badge](https://img.shields.io/badge/Server-Apache%202.4%2B-D39519?style=flat&logo=apache&logoColor=white)](#)

</div>

---

## 📋 System Requirements

| Requirement | Specification |
|-------------|----------------|
| **PHP Version** | 7.4 or higher (8.0+ recommended) |
| **MySQL** | 5.7+ or MariaDB 10.3+ |
| **Web Server** | Apache 2.4+ (with mod_rewrite) or Nginx |
| **Server Stack** | XAMPP, WAMP, Docker, or dedicated hosting |
| **Browser Support** | Chrome, Firefox, Safari, Edge (modern versions) |
| **Disk Space** | ~500MB (with data) |
| **RAM** | 512MB minimum, 1GB recommended |

---

## 🚀 Quick Start

### Installation (5 Minutes)

#### Step 1: Deploy Files
```bash
# Extract to your web root
C:\xampp\htdocs\objsis-v2\
# or
/var/www/html/objsis-v2/
```

#### Step 2: Configure Database
Edit `config/db.php`:
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'objsis_v2');
define('DB_USER', 'main_app');
define('DB_PASS', 'YourSecurePassword123!');
define('DB_CHARSET', 'utf8mb4');
?>
```

#### Step 3: Create Database
Run in phpMyAdmin or MySQL Console:
```sql
CREATE DATABASE objsis_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'main_app'@'localhost' IDENTIFIED BY 'YourSecurePassword123!';

GRANT ALL PRIVILEGES ON objsis_v2.* TO 'main_app'@'localhost';

FLUSH PRIVILEGES;
```

#### Step 4: Run Installer
Navigate to: **`http://localhost/objsis-v2/install.php`**

Follow the setup wizard to:
- ✅ Initialize database schema
- ✅ Create admin account
- ✅ Configure restaurant details

#### Step 5: Enable Addons
Go to: **Admin Panel → Settings → Addons**

#### Step 6: Secure Installation
```bash
# Delete installer
rm install.php

# Set proper permissions
chmod 755 objsis-v2/
chmod 644 config/db.php
```

**Done!** Your system is ready at `http://localhost/objsis-v2/`

---

## 📁 Project Architecture

```
OBJSIS V2/
│
├── 📂 admin/ # Admin Dashboard
│   ├── dashboard.php    # Main analytics hub
│   ├── addons.php       # Addon management
│   ├── settings.php     # System configuration
│   ├── menu.php         # Menu & item management
│   ├── orders.php       # Order management
│   ├── new_order.php    # Create new order
│   ├── tables.php       # Table management
│   ├── inventory.php    # Inventory & Stock
│   ├── recipes.php      # Recipe management
│   ├── users.php        # Staff management
│   ├── employees.php    # Employee management
│   ├── shifts.php       # Shift management
│   ├── kitchen.php      # Kitchen Display (KDS)
│   ├── reports.php      # Analytics & reports
│   ├── coupons.php      # Coupon management
│   ├── categories.php   # Menu categories
│   ├── history.php      # Order history
│   ├── import_menu.php  # Menu import
│   ├── profile.php      # User profile
│   ├── receipt.php      # Receipt generation
│   ├── print_coupon.php # Print coupons
│   ├── stats.php        # Statistics
│   └── updates.php      # Software updates
│
├── 📂 addons/ # Plugin System
│   ├── activity_log_pro/
│   ├── analytics_pro/
│   ├── api_manager/
│   ├── global_search/
│   ├── kds_pro/
│   ├── multi_currency/
│   ├── quick_actions/
│   ├── staff_notes/
│   ├── system_health/
│   ├── system_info/
│   ├── system_notifications/
│   ├── terminal_pro/
│   └── voice_control/
│
├── 📂 api/ # API Endpoints
│   ├── addons.php
│   ├── addons_api.php
│   ├── admin_actions.php
│   ├── check_coupon.php
│   ├── create_order.php
│   ├── dashboard_fetch.php
│   ├── get_active_orders_fragment.php
│   ├── get_api_key.php
│   ├── import_actions.php
│   ├── inventory_logs.php
│   ├── kitchen_fetch.php
│   ├── migrate_inventory.php
│   ├── mock_update_server.php
│   ├── order_status.php
│   ├── order_status_update.php
│   ├── recipe_actions.php
│   ├── shift_actions.php
│   ├── software_update.php
│   └── verify_coupon.php
│
├── 📂 assets/ # Static Resources
│   ├── css/
│   │   ├── style.css           # Core styles
│   │   ├── kiosk_improvements.css
│   │   ├── page_coupons.css
│   │   ├── page_menu.css
│   │   ├── page_shifts.css
│   │   ├── page_stats.css
│   │   ├── page_tables.css
│   │   └── page_users.css
│   └── js/
│       ├── app.js    # Main application logic
│       └── theme.js  # Theme management
│
├── 📂 includes/ # Core Functions
│   ├── functions.php      # Helper functions
│   ├── addon_loader.php   # Addon system loader
│   ├── addon_helper.php   # Addon helper utilities
│   ├── sidebar.php        # Sidebar rendering
│   └── updater_helper.php # Update system helper
│
├── 📂 config/ # System Configuration
│   └── version.php        # Version & build info
│
├── 📂 sql/ # Database Files
│   └── schema.sql         # Full database schema
│
├── 📂 docs/ # Documentation
│   ├── RELEASE_NOTES.md
│   └── development_plan.txt
│
├── login.php              # Login page
├── logout.php             # Logout handler
├── install.php            # Setup wizard (delete after setup)
└── index.php              # Application entry point
```

---

## 👥 User Roles & Permissions

### Role-Based Access Control (RBAC)

| Role | Permissions | Use Case |
|------|-------------|----------|
| **👑 Admin** | Full system access, addon management, API configuration, user creation, backup/restore | Restaurant owner, IT manager |
| **👨‍🍳 Cook** | KDS view, order urgency alerts, recipe details, inventory visibility | Kitchen staff |
| **🚶 Waiter** | Place/modify orders, table management, order status tracking, guest requests | Wait staff, servers |
| **📊 Manager** | Inventory management, supplier details, detailed reports, sales analytics | Operations manager |
| **👁️ Supervisor** | View-only access to all dashboards and reports | Quality assurance |

---

## 🔐 Security Features

### Built-in Security Mechanisms

| Feature | Description |
|---------|-------------|
| 🔑 **API Key Authentication** | Multiple API keys with per-key permissions |
| 🌐 **IP Whitelisting** | Restrict API access to trusted IP addresses |
| 🔒 **Session Security** | Secure session management with timeout protection |
| 📝 **Activity Logging** | Complete audit trail of all system actions |
| 🚨 **Failed Login Alerts** | Automatic alerts after suspicious activity |
| 🔐 **Password Hashing** | bcrypt password encryption (salted & hashed) |
| 🛡️ **SQL Injection Protection** | Prepared statements & input validation |
| 🚫 **CSRF Protection** | Token-based CSRF prevention |

### Security Checklist

- [ ] Change default admin PIN immediately
- [ ] Create strong database user password
- [ ] Enable API IP whitelisting
- [ ] Delete `install.php` after setup
- [ ] Set proper file permissions (644/755)
- [ ] Enable HTTPS in production
- [ ] Regular backup strategy in place
- [ ] Monitor activity logs weekly

---

## 📊 Database Schema

### Core Tables

```sql
-- Users & Authentication
users (id, username, email, password_hash, role, created_at)

-- Orders
orders (id, table_id, status, total_amount, created_at, updated_at)
order_items (id, order_id, menu_item_id, quantity, price, notes)

-- Menu Management
menu_items (id, name, price, category_id, description, available)
menu_categories (id, name, display_order)

-- Inventory
inventory (id, item_name, quantity, unit, min_level, supplier_id)
recipes (id, menu_item_id, ingredient_id, quantity_needed)

-- Restaurant Operations
tables (id, table_number, capacity, status, section)
employees (id, user_id, position, hire_date, active)

-- API & Integration
api_keys (id, key_hash, created_by, rate_limit, ip_whitelist, last_used)
addons (id, name, version, enabled, config_json)

-- Monitoring
activity_logs (id, user_id, action, details, timestamp)
```

---

## 🔌 API Integration

### Getting Started with API

#### Generate API Key
1. Log in as Admin
2. Go to: **Settings → API Manager**
3. Click: **Generate New Key**
4. Copy key and IP whitelist if needed

#### Make API Request

```bash
curl -X GET "http://localhost/objsis-v2/api/v2/orders" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json"
```

#### Available Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/v2/orders` | GET | List all orders |
| `/api/v2/orders` | POST | Create new order |
| `/api/v2/orders/{id}` | PUT | Update order |
| `/api/v2/inventory` | GET | Get inventory levels |
| `/api/v2/inventory/{id}` | PUT | Update stock |
| `/api/v2/analytics/sales` | GET | Sales metrics |
| `/api/v2/tables` | GET | Table status |

---

## 🎨 Themes & Customization

### Glassmorphic Design System

OBJSIS V2 features a modern **Glassmorphic** UI with:
- ✨ Frosted glass effects
- 🎯 Smooth animations
- 📱 Fully responsive design
- 🌓 Light/Dark mode support
- ♿ Accessibility optimized

### Custom Themes

Customize colors in `assets/css/theme-v2.css`:

```css
:root {
  --primary-color: #FF6B35;
  --secondary-color: #004E89;
  --accent-color: #1A7F64;
  --background: #F5F5F5;
  --text-dark: #212121;
}
```

---

## 🧩 Addon System

### Available Addons

| Addon | Description |
|---------|-------------|
| 🔥 **Activity Log PRO** | Comprehensive event logging & audit trail |
| 📊 **Analytics PRO** | Advanced business analytics & reporting |
| 🔑 **API Manager** | REST API key management with IP whitelisting |
| 🔍 **Global Search** | Unified search across orders, menu, staff |
| 👨‍🍳 **KDS PRO** | Kitchen Display System with audio alerts |
| 💱 **Multi Currency** | Real-time price conversion (EUR, USD, GBP, HUF) |
| ⚡ **Quick Actions** | Floating speed-dial for frequent admin tasks |
| 📝 **Staff Notes** | Internal notes & memo system for employees |
| 🩺 **System Health** | Server & system diagnostics monitoring |
| ℹ️ **System Info** | Environment & configuration overview |
| 🔔 **System Notifications** | Real-time in-app alerts & notifications |
| 💻 **Terminal PRO** | Full-screen system terminal & diagnostics |
| 🎤 **Voice Control** | Voice-command support for hands-free operation |

---

## 📈 Roadmap

### Version 2.6.0 (Current) ✅
- [x] Modular addon system
- [x] API Manager PRO
- [x] Terminal Pro Ultra
- [x] Real-time dashboard
- [x] KDS with audio alerts
- [x] Glassmorphic UI
- [x] Multi-currency support

### Version 2.7.0 (In Development)
- [ ] Training mode implementation
- [ ] Multi-language support (SK, EN, DE, CZ)
- [ ] Mobile staff application
- [ ] QR code menu system
- [ ] Payment gateway integration

### Planned Features
- 🔮 Advanced BI dashboards
- 🔮 Loyalty program system
- 🔮 Table pre-ordering
- 🔮 SMS/WhatsApp notifications
- 🔮 Delivery management
- 🔮 Recipe cost calculation

---

## 🐛 Troubleshooting

### Common Issues & Solutions

#### 1. "Access Denied" on Installation
```bash
# Fix file permissions
chmod -R 755 objsis-v2/
chmod 644 config/db.php
```

#### 2. Database Connection Failed
- Verify MySQL is running
- Check credentials in `config/db.php`
- Ensure database user has proper permissions:
```sql
GRANT ALL PRIVILEGES ON objsis_v2.* TO 'main_app'@'localhost';
```

#### 3. Addons Not Appearing
- Check `addons/` folder exists and is writable
- Verify addon structure matches template
- Check PHP error logs: `tail -f /var/log/php-errors.log`

#### 4. API Returning 401 Unauthorized
- Verify API key is active in Admin Panel
- Check IP whitelist settings
- Ensure key is included in request header:
```bash
-H "Authorization: Bearer YOUR_API_KEY"
```

#### 5. KDS Audio Not Working
- Check browser audio permissions
- Verify speaker volume
- Test in browser console: `new Audio('assets/sounds/alert.mp3').play()`

---

## 📞 Support & Contact

### Getting Help

| Channel | Details |
|---------|---------|
| 🐛 **Bug Reports** | [GitHub Issues](https://github.com/DenisVargaeu/OBJSIS/issues) |
| 💬 **Discussions** | [GitHub Discussions](https://github.com/DenisVargaeu/OBJSIS/discussions) |
| 📖 **Wiki** | [Documentation Wiki](https://github.com/DenisVargaeu/OBJSIS/wiki) |
| 📧 **Email** | Contact developer for support inquiries |

---

## 📄 License

<div align="center">

[![License: All Rights Reserved](https://img.shields.io/badge/License-All%20Rights%20Reserved-red?style=for-the-badge)](#license)

</div>

**Copyright © 2026 Denis Varga**

This source code is **proprietary and confidential**. It may **NOT** be:
- ❌ Copied or reproduced
- ❌ Modified or altered
- ❌ Distributed or shared
- ❌ Used commercially without permission
- ❌ Sublicensed or resold

**Unauthorized use is strictly prohibited and may result in legal action.**

For licensing inquiries, please contact the author directly.

---

## 🌟 Contributing

While this is a proprietary project, we welcome bug reports and feature suggestions!

- 🐛 [Report a Bug](https://github.com/DenisVargaeu/OBJSIS/issues/new?template=bug_report.md)
- 💡 [Request a Feature](https://github.com/DenisVargaeu/OBJSIS/issues/new?template=feature_request.md)
- 💬 [Join Discussions](https://github.com/DenisVargaeu/OBJSIS/discussions)

---

## 🎉 Acknowledgments

- **Chart.js** - Beautiful data visualization
- **PHP Community** - Amazing framework and tools
- **Bootstrap Icons** - Quality icon set
- **All Contributors** - Bug reports and feature suggestions

---

<div align="center">

### ❤️ Developed with passion for restaurant efficiency

**[⬆ Back to Top](#-objsis-v2---restaurant-management-system)**

[![GitHub Repo Stars](https://img.shields.io/github/stars/DenisVargaeu/OBJSIS?style=social)](https://github.com/DenisVargaeu/OBJSIS)
[![Follow on GitHub](https://img.shields.io/github/followers/DenisVargaeu?style=social)](https://github.com/DenisVargaeu)

</div>
