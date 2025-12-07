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
- **Web Server**: Apache with mod_rewrite enabled
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

### Option 1: Automated Installation (Recommended)

#### For Windows (PowerShell):
```powershell
.\install.ps1
```

If you encounter execution policy errors:
```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\install.ps1
```

#### For Linux/Mac (Bash):
```bash
chmod +x install.sh
./install.sh
```

### Option 2: Manual Installation

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

## 🗄️ Database Setup

1. **Create the database**
   - Open phpMyAdmin or your preferred MySQL client
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

## ⚙️ Configuration

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
   DB_USER=your_username
   DB_PASSWORD=your_password

   # Application
   APP_ENV=development
   APP_URL=http://localhost/TaskFlow

   # Cloudinary (Optional - for image uploads)
   CLOUDINARY_URL=cloudinary://api_key:api_secret@cloud_name

   # Email (Optional - for notifications)
   MAIL_HOST=smtp.example.com
   MAIL_PORT=587
   MAIL_USERNAME=your_email@example.com
   MAIL_PASSWORD=your_password
   MAIL_FROM=noreply@taskflow.com
   ```

3. **Configure Apache Virtual Host (Optional)**
   
   For better routing, set up a virtual host:
   ```apache
   <VirtualHost *:80>
       ServerName taskflow.local
       DocumentRoot "C:/path/to/TaskFlow/public"
       
       <Directory "C:/path/to/TaskFlow/public">
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
   
   Add to your hosts file:
   ```
   127.0.0.1 taskflow.local
   ```

---

## 🏃 Running the Application

### Using PHP Built-in Server (Development)
```bash
cd public
php -S localhost:8000
```

Access the application at: `http://localhost:8000`

### Using XAMPP/WAMP
1. Move the project to your web server's document root (e.g., `C:/xampp/htdocs/TaskFlow`)
2. Start Apache and MySQL services
3. Access at: `http://localhost/TaskFlow/login`

### Using Docker (Optional)
```bash
docker-compose up -d
```

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

This project is licensed under the ISC License.

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
