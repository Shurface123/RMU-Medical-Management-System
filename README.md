# RMU Medical Management System

A comprehensive medical facility management system for Regional Maritime University's Medical Sickbay. This system provides healthcare services management, patient booking, staff administration, and a modern, responsive user interface.

![Version](https://img.shields.io/badge/version-2.0-blue.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

## 🏥 Features

- **Patient Management** - Register and manage patient records
- **Doctor & Staff Profiles** - Showcase medical professionals
- **Appointment Booking** - Online booking system for patients
- **Service Management** - Emergency care, ambulance, pharmacy, diagnostics
- **Admin Dashboard** - Comprehensive management interface
- **Chatbot Support** - Interactive FAQ chatbot for common queries
- **Theme Switching** - Light/dark mode support
- **Responsive Design** - Works on all devices (desktop, tablet, mobile)

## 📋 Prerequisites

Before running this project, ensure you have the following installed:

- **WAMP Server** (Windows Apache MySQL PHP) - [Download here](https://www.wampserver.com/en/)
  - Apache 2.4+
  - PHP 7.4+ or 8.0+
  - MySQL 5.7+ or MariaDB 10.4+
- **Web Browser** - Chrome, Firefox, Edge, or Safari

## 🚀 Installation & Setup

### Step 1: Install WAMP Server

1. Download and install WAMP Server from [wampserver.com](https://www.wampserver.com/en/)
2. Install it to the default location: `C:\wamp64\`
3. Launch WAMP Server - you should see a green icon in your system tray

### Step 2: Verify Project Location

Your project should already be located at:
```
C:\wamp64\www\RMU-Medical-Management-System\
```

If not, copy the project folder to `C:\wamp64\www\`

### Step 3: Database Setup

1. **Start WAMP Server** - Ensure the WAMP icon is green in the system tray

2. **Access phpMyAdmin:**
   - Open your browser
   - Navigate to: `http://localhost/phpmyadmin`
   - Default credentials: Username: `root`, Password: (leave blank)

3. **Create Database:**
   - Click "New" in the left sidebar
   - Database name: `rmu_medical` (or your preferred name)
   - Collation: `utf8mb4_general_ci`
   - Click "Create"

4. **Import Database Schema:**
   - Select the newly created database
   - Click the "Import" tab
   - Click "Choose File" and select your SQL file (if you have one)
   - Click "Go" to import

   > **Note:** If you don't have a SQL file, you'll need to create the necessary tables manually or run the application to auto-generate them.

### Step 4: Configure Database Connection

1. Open the database configuration file:
   ```
   C:\wamp64\www\RMU-Medical-Management-System\php\db_conn.php
   ```

2. Update the database credentials if needed:
   ```php
   <?php
   $servername = "localhost";
   $username = "root";
   $password = "";  // Default WAMP password is empty
   $dbname = "rmu_medical";  // Your database name
   
   $conn = new mysqli($servername, $username, $password, $dbname);
   
   if ($conn->connect_error) {
       die("Connection failed: " . $conn->connect_error);
   }
   ?>
   ```

## 🌐 Running the Application

### Option 1: Access via Browser (Recommended)

1. **Ensure WAMP is running** (green icon in system tray)

2. **Open your web browser** and navigate to:
   ```
   http://localhost/RMU-Medical-Management-System/html/index.html
   ```

3. **Alternative URLs:**
   - Landing Page: `http://localhost/RMU-Medical-Management-System/html/index.html`
   - Services: `http://localhost/RMU-Medical-Management-System/html/services.html`
   - About: `http://localhost/RMU-Medical-Management-System/html/about.html`
   - Doctors: `http://localhost/RMU-Medical-Management-System/html/doctors.html`
   - Admin Login: `http://localhost/RMU-Medical-Management-System/php/index.php`
   - Booking: `http://localhost/RMU-Medical-Management-System/php/booking.php`

### Option 2: Direct File Access

You can also open HTML files directly:
```
C:\wamp64\www\RMU-Medical-Management-System\html\index.html
```

> **Note:** Some PHP features (database connections, form submissions) will only work when accessed through `http://localhost/`

## 📁 Project Structure

```
RMU-Medical-Management-System/
├── css/
│   ├── main.css              # Main design system and styles
│   ├── chatbot.css           # Chatbot widget styles
│   └── [other css files]     # Legacy/page-specific styles
├── html/
│   ├── index.html            # Landing page
│   ├── services.html         # Services page
│   ├── about.html            # About page
│   ├── director.html         # Medical director profile
│   ├── doctors.html          # Doctors showcase
│   └── staff.html            # Staff directory
├── js/
│   ├── main.js               # Core JavaScript utilities
│   ├── chatbot.js            # Chatbot functionality
│   ├── theme-switcher.js     # Light/dark theme toggle
│   └── modals.js             # Modal system
├── php/
│   ├── index.php             # Admin login
│   ├── home.php              # Admin dashboard
│   ├── booking.php           # Appointment booking
│   ├── db_conn.php           # Database connection
│   └── [other php files]     # Various admin functions
├── image/                    # Images and assets
├── style.css                 # Legacy global styles
└── README.md                 # This file
```

## 🎨 Key Features Guide

### 1. Chatbot
- Click the **floating chat button** (bottom-right corner)
- Ask questions about:
  - Operating hours
  - Services offered
  - Location and directions
  - Contact information
  - Booking appointments

### 2. Theme Switching
- Click the **moon/sun icon** in the header
- Toggle between light and dark modes
- Preference is saved automatically

### 3. Interactive Doctor Profiles
- Navigate to the Doctors page
- Click **"View Profile"** on any doctor card
- View detailed information in a modal
- Click outside or press ESC to close

### 4. Responsive Navigation
- On mobile devices (< 768px width)
- Click the **hamburger menu icon** (☰)
- Access all navigation links

## 🔧 Troubleshooting

### WAMP Server Issues

**WAMP icon is orange or red:**
- Port 80 might be in use by another application (Skype, IIS)
- Solution: Stop the conflicting service or change Apache port
- Right-click WAMP icon → Tools → Use a port other than 80

**Cannot access localhost:**
- Ensure WAMP is running (green icon)
- Check Windows Firewall settings
- Try `http://127.0.0.1/` instead of `http://localhost/`

### Database Connection Errors

**Error: "Connection failed"**
- Verify MySQL service is running in WAMP
- Check database credentials in `php/db_conn.php`
- Ensure database `rmu_medical` exists

**Error: "Table doesn't exist"**
- Import the SQL schema file
- Or create tables manually in phpMyAdmin

### Page Not Found (404)

**Error: "Object not found"**
- Check the URL path is correct
- Ensure you're using: `/RMU-Medical-Management-System/` in the path
- Verify the file exists in the correct directory

### CSS/JavaScript Not Loading

**Styles not applying:**
- Check browser console for errors (F12)
- Verify CSS file paths are correct
- Clear browser cache (Ctrl + Shift + Delete)

**Chatbot/Theme switcher not working:**
- Ensure JavaScript is enabled in browser
- Check browser console for errors
- Verify JS files are in the correct location

## 👥 Default Admin Credentials

> **Note:** Update these after first login for security

```
Username: admin
Password: admin123
```

Or check your database for existing admin accounts.

## 🌐 Accessing from Other Devices (Optional)

To access the system from other devices on your network:

1. Find your computer's IP address:
   ```
   Open Command Prompt
   Type: ipconfig
   Look for "IPv4 Address" (e.g., 192.168.1.100)
   ```

2. On another device, navigate to:
   ```
   http://YOUR_IP_ADDRESS/RMU-Medical-Management-System/html/index.html
   ```
   Example: `http://192.168.1.100/RMU-Medical-Management-System/html/index.html`

3. Ensure Windows Firewall allows Apache connections

## 📱 Browser Compatibility

- ✅ Google Chrome (Recommended)
- ✅ Mozilla Firefox
- ✅ Microsoft Edge
- ✅ Safari
- ⚠️ Internet Explorer (Not recommended - limited support)

## 🔐 Security Notes

For production deployment:

1. **Change default passwords** - Update admin credentials
2. **Update database credentials** - Use strong passwords
3. **Enable HTTPS** - Use SSL certificates
4. **Sanitize inputs** - Prevent SQL injection
5. **Validate forms** - Client and server-side validation
6. **Backup database** - Regular automated backups

## 📞 Support & Contact

**RMU Medical Sickbay**
- Emergency: 153
- Phone: 0502371207
- Email: medicalju123@gmail.com
- Location: Regional Maritime University, Accra, Ghana

**Development Team**
- Created by: Lovelace & Craig (Group Six)
- Version: 2.0
- Last Updated: February 2026

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🙏 Acknowledgments

- Regional Maritime University
- RMU Medical Sickbay Staff
- Font Awesome for icons
- Google Fonts for typography

---

**Quick Start Command:**
```
1. Start WAMP Server (ensure green icon)
2. Open browser
3. Navigate to: http://localhost/RMU-Medical-Management-System/html/index.html
4. Enjoy! 🎉
```
