# TaskFlow - Project Management System

TaskFlow is a comprehensive PHP-based project management system designed to manage hierarchical project structures: **Projects → Phases → Tasks → Workers**. Built with a custom MVC architecture, it provides analytics, reporting, and performance tracking capabilities.

## 📋 Table of Contents
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Configuration](#configuration)
- [Running the Application](#running-the-application)
- [Project Structure](#project-structure)
- [Technologies Used](#technologies-used)
- [Contributing](#contributing)

---

## ✨ Features

- **Hierarchical Project Management**: Organize work into Projects, Phases, Tasks, and Workers
- **Interactive Analytics Dashboard**: Real-time charts and graphs using Chart.js
  - Phase timeline visualization
  - Task status and priority distribution
  - Worker performance metrics
  - Periodic task count tracking
- **Print Functionality**: Generate professional PDF reports with optimized layouts
- **Responsive Design**: Mobile-first approach with breakpoints for all device sizes
- **User Authentication**: Secure session management with CSRF protection
- **Role-Based Access Control**: Different permissions for Project Managers and Workers
- **Performance Calculators**: Automated metrics for projects, phases, and workers
- **Search & Filtering**: Debounced search across projects, tasks, and users
- **Cloud Storage Integration**: Cloudinary integration for media assets
- **Email Notifications**: PHPMailer integration for system notifications

---

## 🔧 Requirements

- **PHP**: 8.2 or higher
- **Database**: MariaDB 10.4+ or MySQL 8.0+
- **Web Server**: Apache with mod_rewrite enabled (XAMPP recommended)
- **Composer**: Latest version
- **Node.js**: 16+ (for frontend dependencies)
- **pnpm**: Latest version (will be installed automatically if missing)

### Required PHP Extensions
- `pdo_mysql`
- `mbstring`
- `openssl`
- `json`
- `curl`

---

## 🚀 Installation

### Step 1: Clone and Install Dependencies

#### Option A: Automated Installation (Recommended)

Open PowerShell (Windows) or Bash (Linux/Mac) and navigate to the TaskFlow folder:

**For Windows (PowerShell):**
```powershell
cd C:\xampp\htdocs\TaskFlow
.\install.ps1
```

If you encounter execution policy errors:
```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\install.ps1
```

**For Linux/Mac (Bash):**
```bash
cd /var/www/html/TaskFlow
# or for XAMPP: cd /opt/lampp/htdocs/TaskFlow
chmod +x install.sh
./install.sh
```

#### Option B: Manual Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/krtScrtr004/TaskFlow.git
   cd TaskFlow
   ```

2. **Install PHP dependencies**
   ```bash
   composer install --no-interaction --prefer-dist --optimize-autoloader
   ```

3. **Install Node.js dependencies**
   ```bash
   pnpm install
   ```
   
   If pnpm is not installed:
   ```bash
   npm install -g pnpm
   pnpm install
   ```

---

### Step 2: Database Setup

1. **Create the database**
   - Open phpMyAdmin (`http://localhost/phpmyadmin`)
   - Create a new database named `taskflow`

2. **Import the schema**
   ```bash
   mysql -u your_username -p taskflow < database/taskflow.sql
   ```

3. **Import the event scheduler**
   ```bash
   mysql -u your_username -p taskflow < database/event-scheduler.sql
   ```

   Or via phpMyAdmin:
   - Select the `taskflow` database
   - Click "Import"
   - Upload `database/taskflow.sql`
   - Upload `database/event-scheduler.sql`

---

### Step 3: Environment Configuration

1. **Create environment file**
   ```bash
   cp .env.example .env
   ```
   *(If `.env.example` doesn't exist, create `.env` manually)*

2. **Configure `.env` file**
   ```env
   # Database Configuration
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=taskflow
   DB_USER=root
   DB_PASSWORD=

   # Application
   APP_ENV=development
   APP_URL=http://taskflow.local

   # Cloudinary (Optional - for image uploads)
   CLOUDINARY_URL=cloudinary://api_key:api_secret@cloud_name

   # Email (Optional - for notifications)
   MAIL_HOST=smtp.example.com
   MAIL_PORT=587
   MAIL_USERNAME=your_email@example.com
   MAIL_PASSWORD=your_password
   MAIL_FROM=noreply@taskflow.com
   ```

---

### Step 4: Configure Local Domain (taskflow.local)

#### For Windows (XAMPP)

1. **Edit the hosts file** (Run Notepad as Administrator)
   
   Open `C:\Windows\System32\drivers\etc\hosts` and add:
   ```
   127.0.0.1    taskflow.local
   ```

2. **Configure Apache Virtual Host**
   
   Open `C:\xampp\apache\conf\extra\httpd-vhosts.conf` and add:
   ```apache
   <VirtualHost *:80>
       ServerAdmin admin@taskflow.local
       ServerName taskflow.local
       DocumentRoot "C:/xampp/htdocs/TaskFlow"

       <Directory "C:/xampp/htdocs/TaskFlow">
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>

       ErrorLog "logs/taskflow_error.log"
       CustomLog "logs/taskflow_access.log" combined
   </VirtualHost>
   ```

3. **Enable Virtual Hosts**
   
   Open `C:\xampp\apache\conf\httpd.conf` and ensure this line is uncommented:
   ```apache
   Include conf/extra/httpd-vhosts.conf
   ```

4. **Restart Apache** from XAMPP Control Panel

#### For Linux (XAMPP or Native Apache)

1. **Edit the hosts file**
   ```bash
   sudo nano /etc/hosts
   ```
   
   Add this line:
   ```
   127.0.0.1    taskflow.local
   ```

2. **Create Apache Virtual Host configuration**
   
   **For Native Apache (Ubuntu/Debian):**
   ```bash
   sudo nano /etc/apache2/sites-available/taskflow.conf
   ```
   
   **For XAMPP:**
   ```bash
   sudo nano /opt/lampp/etc/extra/httpd-vhosts.conf
   ```
   
   Add the following:
   ```apache
   <VirtualHost *:80>
       ServerAdmin admin@taskflow.local
       ServerName taskflow.local
       DocumentRoot /var/www/html/TaskFlow

       <Directory /var/www/html/TaskFlow>
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>

       ErrorLog ${APACHE_LOG_DIR}/taskflow_error.log
       CustomLog ${APACHE_LOG_DIR}/taskflow_access.log combined
   </VirtualHost>
   ```
   
   *For XAMPP, use `/opt/lampp/htdocs/TaskFlow` as DocumentRoot*

3. **Enable the site and required modules** (Native Apache only)
   ```bash
   sudo a2ensite taskflow.conf
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```
   
   **For XAMPP:**
   ```bash
   sudo /opt/lampp/lampp restart
   ```

4. **Set proper file permissions** (Linux)
   ```bash
   sudo chown -R $USER:$USER /var/www/html/TaskFlow
   sudo chmod -R 755 /var/www/html/TaskFlow
   ```

---

## 🏃 Running the Application

### Using XAMPP (Recommended)

1. **Start XAMPP services**
   - **Windows:** Open XAMPP Control Panel → Start Apache and MySQL
   - **Linux:** `sudo /opt/lampp/lampp start` or `sudo systemctl start apache2 mysql`

2. **Access the application**
   
   Open your browser and navigate to:
   ```
   http://taskflow.local
   ```

### Using PHP Built-in Server (Development Only)
```bash
cd public
php -S localhost:8000
```

Access at: `http://localhost:8000`

> ⚠️ **Note:** The built-in PHP server is for development only. Use XAMPP/Apache for production.

---

## 📁 Project Structure

```
TaskFlow/
├── database/                    # Database schema and migrations
│   ├── taskflow.sql
│   └── event-scheduler.sql
├── documentation/               # Project documentation and diagrams
├── public/                      # Public web root
│   ├── index.php               # Entry point
│   ├── script/                 # JavaScript files
│   │   ├── event/              # Event handlers
│   │   ├── render/             # UI rendering
│   │   └── utility/            # Utility functions
│   ├── style/                  # CSS stylesheets
│   ├── asset/                  # Images, icons, fonts
│   └── library/                # Third-party libraries (Chart.js)
├── source/
│   ├── backend/
│   │   ├── controller/         # Page controllers
│   │   ├── endpoint/           # API endpoints
│   │   ├── model/              # Database models
│   │   ├── entity/             # Data entities
│   │   ├── container/          # Entity collections
│   │   ├── service/            # Business logic
│   │   ├── validator/          # Input validation
│   │   ├── middleware/         # Request middleware
│   │   ├── router/             # Routing system
│   │   ├── auth/               # Authentication
│   │   ├── utility/            # Helper classes
│   │   ├── enumeration/        # Constants/enums
│   │   └── exception/          # Custom exceptions
│   └── frontend/
│       ├── view/               # PHP templates
│       └── component/          # Reusable UI components
├── vendor/                     # Composer dependencies
├── .env                        # Environment configuration
├── composer.json               # PHP dependencies
├── package.json                # Node.js dependencies
├── install.sh                  # Bash installation script
└── install.ps1                 # PowerShell installation script
```

---

## 🛠️ Technologies Used

### Backend
- **PHP 8.2** - Core language
- **MariaDB** - Database
- **Composer** - Dependency management
- **Ramsey UUID** - UUID generation
- **PHP dotenv** - Environment configuration
- **PHPMailer** - Email functionality
- **Cloudinary PHP SDK** - Cloud storage

### Frontend
- **Vanilla JavaScript (ES6+)** - No frameworks
- **Chart.js** - Data visualization
- **CSS3** - Responsive design with custom properties

### Development
- **pnpm** - Fast package manager
- **Git** - Version control

---

## 📖 Usage Guide

### Default User Roles
After importing the database, use the following credentials (if seeded):
- **Project Manager**: Check your database for initial users
- **Worker**: Check your database for initial users

*Note: Update default credentials immediately after first login*

### Key Features

#### Creating a Project
1. Navigate to Projects page
2. Click "Create New Project"
3. Fill in project details (name, description, dates)
4. Assign phases and tasks
5. Add workers to tasks

#### Viewing Analytics
1. Open any project
2. Click "Report" or "Analytics"
3. View interactive charts:
   - Phase timeline
   - Task distribution
   - Worker performance
4. Use print button for PDF export

#### Managing Tasks
1. Navigate to Tasks page
2. Filter by status/priority
3. Update task progress
4. Assign/remove workers
5. Track completion metrics

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📄 License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.

---

## 👥 Authors

- **krtScrtr004** - [GitHub Profile](https://github.com/krtScrtr004)

---

## 🐛 Bug Reports & Feature Requests

Please use the [GitHub Issues](https://github.com/krtScrtr004/TaskFlow/issues) page to report bugs or request features.

---

## 📞 Support

For questions or support, please open an issue or contact the maintainers.

---

## 🎯 Roadmap

- [ ] API documentation
- [ ] Unit tests
- [ ] CI/CD pipeline
- [ ] Docker support
- [ ] Multi-language support
- [ ] Mobile application

---

**Happy Project Managing! 🚀**
