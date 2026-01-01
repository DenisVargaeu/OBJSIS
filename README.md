# 🍽️ OBJSIS V2 - Restaurant Management System

![Version](https://img.shields.io/badge/version-beta%202.0-orange)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-blue)
![License](https://img.shields.io/badge/license-MIT-green)

A comprehensive, modern restaurant management system with order tracking, employee management, inventory control, and customer-facing kiosk interface.

## ✨ Features

- 🔐 **Secure PIN-based login** with role management (Admin, Cook, Waiter)
- 📱 **Customer kiosk** for self-service ordering
- 🍔 **Dynamic menu management** with categories and availability toggle
- 📊 **Real-time order tracking** with status updates
- 🎟️ **Advanced coupon system** with expiration dates and usage limits
- 🧾 **Receipt generation** and printing
- 📈 **Sales statistics** and revenue reports
- 📜 **Order history** with filtering
- 🌓 **Dark/Light theme** toggle
- ⏰ **Shift tracking** for employees

---

## 🚀 Quick Start Installation

### Prerequisites
- **XAMPP** (or similar) with PHP 7.4+ and MySQL/MariaDB
- Web browser
- Basic knowledge of PHP/MySQL

### Installation Steps

1. **Place files in XAMPP directory**
   ```
   C:\xampp\htdocs\objsis-v2\
   ```

2. **Configure database connection**
   - Open `config/db.php`
   - Update database credentials if needed:
     ```php
     $host = 'localhost';
     $db_name = 'objsis_v2';
     $username = 'main_app';
     $password = '';
     ```

3. **Create database user** (in phpMyAdmin or MySQL console)
   ```sql
   CREATE DATABASE objsis_v2;
   CREATE USER 'main_app'@'localhost';
   GRANT ALL PRIVILEGES ON objsis_v2.* TO 'main_app'@'localhost';
   FLUSH PRIVILEGES;
   ```

4. **Run the installer**
   - Open browser and navigate to: `http://localhost/objsis-v2/install.php`
   - The installer will:
     - Create all database tables
     - Insert default admin user (PIN: 1234)
     - Set up sample data
     - Clean up temporary files

5. **Delete installer for security**
   ```
   Delete: install.php
   ```

6. **Login and configure**
   - Go to `http://localhost/objsis-v2/login.php`
   - Login with PIN: **1234**
   - Change admin PIN in Users section
   - Configure restaurant name in Settings

---

## 📋 Features

### Phase 1-3 (Complete)
- ✅ Employee PIN login with roles (Admin, Cook, Waiter)
- ✅ Dynamic menu management
- ✅ Customer kiosk for ordering
- ✅ Order status tracking
- ✅ Table management
- ✅ Shift tracking
- ✅ Dark/Light theme toggle
- ✅ Restaurant settings

### Phase 4 (Complete)
- ✅ Enhanced coupon system (expiration, usage limits, one-time use)
- ✅ Receipt generation and printing
- ✅ Order history with filtering
- ✅ Revenue reports

### Phase 6 (Complete)
- ✅ Full Inventory Management
- ✅ Ingredient & Recipe system
- ✅ Auto-deduction of stock on orders
- ✅ Auto-disabling dishes when out of stock
- ✅ Stock movement history/logging

### Coming Soon (Phase 5 - Delayed)
- 🔄 Database backup/restore
- 🔄 Training mode
- 🔄 Multi-language support (Slovak, English)

---

## 🗂️ Project Structure

```
OBJSIS V2/
├── admin/              # Admin panel pages
│   ├── dashboard.php   # Active orders
│   ├── menu.php        # Menu management
│   ├── tables.php      # Table management
│   ├── users.php       # Employee management
│   ├── coupons.php     # Coupon management
│   ├── inventory.php   # Inventory & Stock management
│   ├── shifts.php      # Shift history
│   ├── stats.php       # Statistics
│   ├── history.php     # Order history
│   ├── settings.php    # System settings
│   └── receipt.php     # Receipt viewer
├── api/                # Backend API endpoints
├── assets/             # CSS, JS, images
├── config/             # Database configuration
├── includes/           # Helper functions
├── sql/                # Database schema
├── index.php           # Customer kiosk
├── login.php           # Employee login
└── install.php         # Database installer
```

---

## 👥 User Roles

### Admin
- Full system access
- Manage employees, menu, tables, coupons
- View statistics and history
- Configure system settings

### Cook
- View orders
- Update order status (preparing, ready)

### Waiter
- View orders
- Take orders
- Update order status (delivered, paid)

### Inventory Manager
- Manage ingredient stock
- Set recipes for menu items
- View stock movement logs

---

## 🎨 Customization

### Change Restaurant Name
1. Login as Admin
2. Go to Settings
3. Update "Restaurant Name"

### Add Menu Items
1. Go to Menu
2. Click "Add Item"
3. Fill in details (name, price, category, image URL)

### Create Coupons
1. Go to Coupons
2. Enter code, type (% or €), value
3. Optional: Set expiration date, usage limits

---

## 🔧 Troubleshooting

### "Could not connect to database"
- Check XAMPP MySQL is running
- Verify credentials in `config/db.php`
- Ensure database `objsis_v2` exists

### "Table doesn't exist"
- Run `install.php` again
- Check MySQL error logs

### Login not working
- Verify user exists in database
- Default admin PIN is `1234`
- Clear browser cookies

### Coupon not applying
- Check expiration date
- Verify usage limit not reached
- Ensure coupon is active

---

## 📱 Usage Guide

### For Customers (Kiosk)
1. Select your table number
2. Browse menu by category
3. Add items to cart
4. Apply coupon code (optional)
5. Confirm order
6. Track order status

### For Staff
1. Login with your PIN
2. View active orders on dashboard
3. Update order status as you work
4. Clock in/out for shift tracking

### For Admins
1. Manage menu items and prices
2. Create promotional coupons
3. Manage Inventory and Recipes
4. View sales statistics
5. Print receipts and reports
6. Manage employee accounts

---

## 🔐 Security Notes

- **Change default admin PIN immediately**
- **Delete install.php after setup**
- Keep database credentials secure
- Regularly backup your database
- Use HTTPS in production

---

## 📄 License

This project is for educational/commercial use.

---

## 🆘 Support

For issues or questions:
1. Check this README
2. Review error logs in XAMPP
3. Verify database connection

---

## 📸 Screenshots

<!-- Add screenshots here -->
*Coming soon - screenshots of the admin panel, customer kiosk, and receipt system*

---

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

### Quick Start for Contributors

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🆘 Support

For issues or questions:
1. Check this README
2. Review [existing issues](../../issues)
3. Create a [new issue](../../issues/new) if needed

---

## 🗺️ Roadmap

### Phase 5 (Future)
- [ ] Database backup/restore functionality
- [ ] Training mode for new employees
- [ ] Multi-language support (Slovak, English, German)
- [ ] Mobile app for waiters
- [ ] Kitchen display system

### Phase 6 (Complete)
- [x] Inventory management
- [x] Recipe-based stock deduction
- [x] Sold-out UI refinements (Grayed out cards)

---

**Developed with ❤️ for restaurant efficiency**

**Star ⭐ this repository if you find it helpful!**
