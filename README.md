# KUYA-NICKS-ROTISSERIE-CHICKEN-HOUSE
Deployment & User Guide (XAMPP Local Installation)
This section provides detailed instructions for setting up and running the Online Ordering System with Real-Time Status Tracking on a local machine using XAMPP.
8.1 System Requirements for Local Deployment
Hardware Requirements:
Processor: AMD Ryzen 3 or Intel i3 @ 1.80GHz (minimum)
RAM: 8GB (16GB recommended for optimal performance)
Storage: 30GB SSD free space (50GB recommended)
Monitor: 720p resolution minimum (1080p recommended)
Software Requirements:
Operating System: Windows 10 Home 64-bit (minimum), Windows 11 Pro recommended
Web Server: XAMPP (Apache, MySQL, PHP bundle)
Browser: Google Chrome v.134.0 or later
Text Editor: Visual Studio Code, Sublime Text, or Notepad++
8.2 Step-by-Step Installation Guide
Step 1: Install XAMPP
Download XAMPP from the official website: https://www.apachefriends.org/
Run the installer as Administrator
Select the following components during installation:
Apache
MySQL
 PHP
phpMyAdmin
Choose installation directory (default: C:\xampp)
Complete the installation process
Step 2: Start XAMPP Services
Open XAMPP Control Panel (Run as Administrator)
Click "Start" for Apache and MySQL services
Verify both services are running (green indicators appear)
Step 3: Set Up Project Directory
Navigate to XAMPP htdocs folder: C:\xampp\htdocs\
Create a new folder named: kuya_nicks_system
Extract or copy all project files into this folder
Main project files should be in: C:\xampp\htdocs\kuya_nicks_system\
Folder structure should include:
text
kuya_nicks_system/
├── admin/
├── assets/
│   ├── css/
│   ├── img/
│   └── js/
├── staff/
├── index.php
└── database.sql
Step 4: Create MySQL Database
Open your web browser and go to: http://localhost/phpmyadmin
Click "New" in the left sidebar
Create a database named: kuya_nicks_db
Set collation to: utf8mb4_general_ci
Click "Create"
Step 5: Import Database Schema
In phpMyAdmin, select the kuya_nicks_db database
Click the "Import" tab
Click "Choose File" and select database.sql from your project folder
Click "Go" to import the database structure and sample data
Step 6: Configure Database Connection
Navigate to: C:\xampp\htdocs\kuya_nicks_system\admin\
Open db_connect.php in a text editor
Update the connection parameters:
php
<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "kuya_nicks_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
Step 7: Configure PHP Settings (Optional)
Open C:\xampp\php\php.ini
Modify these settings for optimal performance:
ini
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 300
memory_limit = 256M
display_errors = On (for development)
error_reporting = E_ALL (for development)
Save the file and restart Apache from XAMPP Control Panel
8.3 Accessing the System
Local URLs:
Customer Interface: http://localhost/kuya_nicks_system/
Staff Interface: http://localhost/kuya_nicks_system/staff/
Admin Interface: http://localhost/kuya_nicks_system/admin/
Default Login Credentials:
For Testing Purposes:
Customer:
Email: customer@test.com
Password: password123
Staff:
Username: staff
Password: staff123
Administrator:
Username: admin
Password: admin123
8.4 File Structure Overview
text
C:\xampp\htdocs\kuya_nicks_system\
│
├── index.php                    # Main entry point (Customer)
├── database.sql                 # Database schema and sample data
│
├── admin/                       # Administrator files
│   ├── index.php               # Admin dashboard
│   ├── db_connect.php          # Database connection
│   ├── login.php               # Admin login
│   ├── dashboard.php           # Admin dashboard
│   ├── orders.php              # Order management
│   ├── menu.php                # Menu management
│   ├── reports.php             # Sales reports
│   └── users.php               # User management
│
├── staff/                       # Kitchen staff files
│   ├── index.php               # Staff dashboard
│   ├── login.php               # Staff login
│   ├── orders.php              # Order viewing/updating
│   └── display.php             # Digital display interface
│
├── assets/                      # Static assets
│   ├── css/
│   │   ├── style.css           # Main stylesheet
│   │   └── bootstrap.min.css   # Bootstrap framework
│   ├── js/
│   │   ├── main.js             # Main JavaScript
│   │   ├── ajax-polling.js     # Real-time updates
│   │   └── jquery.min.js       # jQuery library
│   └── img/
│       ├── products/           # Product images
│       ├── logo.png            # Restaurant logo
│       └── favicon.ico         # Browser icon
│
└── api/                         # API endpoints
    ├── get_order_status.php    # Order status API
    ├── update_status.php       # Status update API
    └── notifications.php       # Notification system
8.5 Configuration Settings
Application Configuration:
Open C:\xampp\htdocs\kuya_nicks_system\admin\config.php (if exists)
Update the following settings:
php
// Restaurant Information
define('RESTAURANT_NAME', 'Kuya Nick Rotisserie Chicken House');
define('RESTAURANT_ADDRESS', 'Bataan City, Philippines');
define('CONTACT_NUMBER', '+63 XXX-XXXX-XXX');

// System Settings
define('MAX_ORDER_ITEMS', 20);
define('ORDER_TIMEOUT_MINUTES', 120);
define('POLLING_INTERVAL_MS', 5000); // AJAX polling interval
Email Configuration (For Notifications):
Create C:\xampp\htdocs\kuya_nicks_system\admin\email_config.php:
php
<?php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-password');
define('FROM_EMAIL', 'noreply@kuyanicks.com');
define('FROM_NAME', 'Kuya Nick Rotisserie Chicken House');
?>
8.6 AJAX Polling Setup for Real-Time Updates
The system uses AJAX polling for real-time status updates. Configuration file located at:C:\xampp\htdocs\kuya_nicks_system\assets\js\ajax-polling.js
Default polling interval: Every 5 seconds
javascript
// Polling configuration
const POLLING_INTERVAL = 5000; // 5 seconds
const ORDER_STATUS_ENDPOINT = '/kuya_nicks_system/api/get_order_status.php';
8.7 Troubleshooting Common Issues
Issue 1: Apache Won't Start
Solution:
Check if port 80 or 443 is being used by another application
Open XAMPP Control Panel → Config → Apache (httpd.conf)
Change Listen ports:
apache
Listen 8080
ServerName localhost:8080
Access system via: http://localhost:8080/kuya_nicks_system/
Issue 2: Database Connection Error
Solution:
Verify MySQL service is running in XAMPP
Check credentials in db_connect.php
Test connection using:
php
<?php
$test = new mysqli('localhost', 'root', '', 'kuya_nicks_db');
if ($test->connect_error) {
    echo "Error: " . $test->connect_error;
} else {
    echo "Database connected successfully!";
}
?>
Issue 3: File Upload Not Working
Solution:
Check php.ini settings:
ini
file_uploads = On
upload_max_filesize = 10M
max_file_uploads = 20
Verify folder permissions: C:\xampp\htdocs\kuya_nicks_system\assets\img\
Restart Apache after making changes
Issue 4: AJAX Polling Not Updating
Solution:
Check browser console for JavaScript errors (F12 → Console)
Verify polling interval in ajax-polling.js
Test API endpoint directly: http://localhost/kuya_nicks_system/api/get_order_status.php?order_id=1
8.8 Database Backup Procedure
Manual Backup:
Open phpMyAdmin: http://localhost/phpmyadmin
Select kuya_nicks_db database
Click "Export" tab
Choose "Custom" export method
Select all tables
Choose format: SQL
Click "Go" to download backup
Automated Backup Script:
Create backup.bat in project root:
batch
@echo off
set BACKUP_DIR=C:\xampp-backups\
set DATE=%date:~10,4%%date:~4,2%%date:~7,2%
set TIME=%time:~0,2%%time:~3,2%

mkdir %BACKUP_DIR%

cd C:\xampp\mysql\bin
mysqldump -u root kuya_nicks_db > %BACKUP_DIR%backup_%DATE%_%TIME%.sql

echo Backup completed: %BACKUP_DIR%backup_%DATE%_%TIME%.sql
pause
8.9 Security Considerations for Local Development
Important Security Notes:
Change Default Passwords:
MySQL root password (from phpMyAdmin)
Admin and staff account passwords
Update passwords in db_connect.php
Restrict File Permissions:
batch
# In Command Prompt (Run as Administrator)
icacls "C:\xampp\htdocs\kuya_nicks_system\admin\db_connect.php" /deny Everyone:(R,W)
Disable Directory Listing:
Add to .htaccess in project root:
apache
Options -Indexes
Development vs Production:
Disable error display in production
Remove phpinfo() and debugging code
Use prepared statements to prevent SQL injection
8.10 Testing the Installation
Verification Checklist:
Apache and MySQL services running (green in XAMPP)
Database imported successfully (check tables in phpMyAdmin)
Can access http://localhost/kuya_nicks_system/
Customer registration/login works
Admin can login at http://localhost/kuya_nicks_system/admin/
Staff can login at http://localhost/kuya_nicks_system/staff/
Orders can be placed and status updated
Real-time updates work (status changes without page refresh)
Sales reports generate correctly
Test Order Flow:
Register a new customer account
Login and browse menu
Add items to cart and checkout
As staff, login and update order status
As customer, verify real-time status updates
As admin, generate sales report
8.11 Deployment to Production (Optional)
When ready to deploy to a live server:
Choose Hosting: Select a hosting provider with PHP and MySQL support
Transfer Files: Upload all files via FTP/SFTP
Create Database: Set up database on hosting panel
Update Configuration: Change database connection details
Configure Domain: Point domain to hosting server
SSL Certificate: Install for secure connections (HTTPS)
Disable Debug Mode: Turn off error display in production
8.12 Support and Maintenance
For Technical Issues:
Check error logs: C:\xampp\apache\logs\
Enable PHP error logging in php.ini
Monitor database performance via phpMyAdmin
Regular Maintenance Tasks:
Backup database weekly
Clear old order records monthly
Update product images as needed
Test system after XAMPP updates
