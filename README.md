# ABADI Comm - Event Organizer Schedule Management System

A Laravel-based web application designed to help event organizers manage worker schedules efficiently. The system prevents scheduling conflicts by automatically detecting when workers or supervisors are double-booked, ensuring smooth event operations.

---

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Database Structure](#database-structure)
- [User Roles & Permissions](#user-roles--permissions)
- [Application Pages](#application-pages)
- [Core Functionality](#core-functionality)
- [File Structure](#file-structure)
- [API Reference](#api-reference)
- [Troubleshooting](#troubleshooting)
- [License](#license)

---

## 🎯 Overview

ABADI Comm is a schedule management system built for event organizers who need to:
- Assign workers to events with specific job descriptions
- Prevent double-booking of workers and supervisors
- Track schedules across multiple locations
- Export weekly schedules to PDF
- Manage workers, job descriptions, and locations

The system uses **WIB (Western Indonesian Time, UTC+7)** for all time displays while storing timestamps in UTC.

---

## ✨ Features

| Feature | Description |
|---------|-------------|
| **Schedule Conflict Detection** | Automatically prevents double-booking of workers and supervisors |
| **Weekly Schedule Grid** | Visual calendar view showing events organized by day, time, and supervisor |
| **Location-Based Filtering** | Filter schedules by location on the dashboard |
| **Role-Based Access Control** | Three user roles with different permissions |
| **Worker Assignment** | Assign multiple workers to a single event with specific job descriptions |
| **PDF Export** | Export weekly schedules to PDF using html2canvas and jsPDF |
| **Dynamic Management** | Add/edit/delete job descriptions and locations on the fly |
| **Worker Statistics** | Track how many schedules each worker has per week/month |

---

## 📦 Requirements

- **PHP** >= 8.1
- **Composer** >= 2.0
- **MySQL** >= 5.7
- **Laravel** 10.x
- **Node.js** >= 16 (optional, for asset compilation)

---

## 🚀 Installation

### Step 1: Clone the Repository

```bash
git clone https://github.com/your-username/abadi-comm.git
cd abadi-comm
```

### Step 2: Install Dependencies

```bash
composer install
```

### Step 3: Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### Step 4: Configure Database

Edit the `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=abadi_comm
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Step 5: Run Migrations

```bash
php artisan migrate
```

### Step 6: Seed the Database

```bash
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=WorkerSeeder
```

This creates:
- 3 roles: Supervisor, Karyawan (Worker), Admin
- 1 admin user with credentials:
  - **Name:** `Admin User`
  - **Password:** `password123`

### Step 7: Start the Server

```bash
php artisan serve
```

Access the application at `http://localhost:8000`

---

## 🗄️ Database Structure

### Entity Relationship Diagram

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│    roles     │     │   workers    │     │   jobdescs   │
├──────────────┤     ├──────────────┤     ├──────────────┤
│ id           │◄────│ role_id      │     │ id           │
│ nama         │     │ id           │     │ name         │
└──────────────┘     │ name         │     └──────┬───────┘
                     │ email        │            │
                     │ password     │            │
                     └──────┬───────┘            │
                            │                    │
                            │    ┌───────────────┘
                            │    │
                     ┌──────▼────▼───┐     ┌──────────────┐
                     │   schedules   │     │  locations   │
                     ├───────────────┤     ├──────────────┤
                     │ id            │     │ id           │
                     │ waktu_mulai   │     │ name         │
                     │ waktu_selesai │     └──────┬───────┘
                     │ worker_id     │◄───────────┘
                     │ jobdesc_id    │     (location_id)
                     │ superfisor_id │
                     │ location_id   │
                     └───────────────┘
```

### Tables Description

#### `roles`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| nama | string | Role name (Supervisor/Karyawan/Admin) |

#### `workers`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Worker's name (unique, used for login) |
| email | string | Worker's email (unique) |
| password | string | Hashed password |
| role_id | int | Foreign key to roles (1=Supervisor, 2=Karyawan, 3=Admin) |

#### `schedules`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| waktu_mulai | int | Start timestamp (UTC) |
| waktu_selesai | int | End timestamp (UTC) |
| worker_id | bigint | FK to workers (the assigned worker) |
| jobdesc_id | bigint | FK to jobdescs |
| superfisor_id | bigint | FK to workers (the supervisor) |
| location_id | bigint | FK to locations |

> **Note:** The column `superfisor_id` has a typo (missing 'v'). This is intentional to maintain backward compatibility.

#### `jobdescs`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Job description name |

#### `locations`
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Location/venue name |

---

## 👥 User Roles & Permissions

| Role | ID | Can View Schedule | Can Create Schedule | Can Edit Schedule | Can Manage Workers | Can Manage Jobdesc/Location |
|------|-----|-------------------|---------------------|-------------------|--------------------|-----------------------------|
| Supervisor | 1 | ✅ | ❌ | ❌ | ❌ | ❌ |
| Karyawan | 2 | ✅ | ❌ | ❌ | ❌ | ❌ |
| Admin | 3 | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## 📱 Application Pages

### 1. Login Page (`/login`)
- Authenticate using **name** and **password**
- Redirects to Dashboard on success

### 2. Dashboard (`/dashboard`)
**Left Panel:**
- List of all workers (Karyawan role) with their schedule count for the selected month
- Filter by month and year

**Right Panel:**
- Schedules grouped by supervisor and date
- Filter by location
- Shows: Supervisor name, location, date, time, and all assigned workers with their job descriptions

### 3. Assign Worker (`/assign`) - Admin Only
Create new schedule assignments:
- Select date
- Select location (can add new locations inline)
- Set start and end time (07:00 - 21:00)
- Add multiple worker assignments:
  - Select worker
  - Select job description (can add new job descriptions inline)
  - Select supervisor

### 4. Weekly Schedule (`/schedule`)
- Visual grid showing 7 days of schedules
- Rows = time slots (07:00 - 21:00)
- Columns = days, subdivided by supervisors
- Events displayed as cards showing supervisor, location, time, and workers
- Click on any event to edit (Admin only)
- Worker summary showing schedule count per worker for the displayed week
- Date range selector
- PDF export functionality

### 5. Edit Schedule (`/schedule/edit/{dateKey}/{supervisor}/{start}`) - Admin Only
- Modify existing schedule details
- Change date, location, time
- Add/remove worker assignments
- Can add new locations inline

### 6. Job Description Management (`/jobdesc`) - Admin Only
- List all job descriptions with usage count
- Add new job descriptions
- Edit existing job descriptions
- Delete job descriptions (only if not used in any schedule)

### 7. Location Management (`/location`) - Admin Only
- List all locations with usage count
- Add new locations
- Edit existing locations
- Delete locations (only if not used in any schedule)

### 8. Register Worker (`/register`) - Admin Only
- Create new worker accounts
- Set name, email, role, and password

---

## ⚙️ Core Functionality

### Schedule Conflict Detection

The system prevents double-booking by checking if any worker or supervisor already has a schedule that overlaps with the proposed time:

```php
$conflicts = Schedule::where(function ($query) use ($allWorkerIds, $allSupervisorIds) {
    $query->whereIn('worker_id', $allWorkerIds)
          ->orWhereIn('superfisor_id', $allSupervisorIds);
})
->where(function ($query) use ($startTimestamp, $endTimestamp) {
    $query->where('waktu_mulai', '<', $endTimestamp)
          ->where('waktu_selesai', '>', $startTimestamp);
})
->first();
```

### Time Handling

- **Storage:** All times are stored as Unix timestamps in UTC
- **Display:** Times are converted to WIB (UTC+7) using Carbon
- **Offset Calculation:**
  ```php
  $WIB_OFFSET = 7 * 60 * 60; // 25200 seconds
  $utcTimestamp = $wibTimestamp - $WIB_OFFSET;
  ```

### PDF Export

Uses **html2canvas** and **jsPDF** libraries loaded from CDN:
- Captures the schedule grid as an image
- Converts to PDF with A4 format
- Filename: `Jadwal_{startDate}_{endDate}_{exportDate}.pdf`

---

## 📁 File Structure

```
app/
├── Http/Controllers/
│   ├── AssignWorkerController.php   # Worker assignment logic
│   ├── AuthController.php           # Login, logout, registration
│   ├── DashboardController.php      # Dashboard with filters
│   ├── JobdescController.php        # CRUD for job descriptions
│   ├── LocationController.php       # CRUD for locations
│   └── ScheduleViewController.php   # Schedule display and editing
├── Models/
│   ├── Jobdesc.php                  # Job description model
│   ├── Location.php                 # Location model
│   ├── Role.php                     # Role model
│   ├── Schedule.php                 # Schedule model
│   ├── User.php                     # Default Laravel user (unused)
│   └── Worker.php                   # Worker model (authenticatable)
database/
├── migrations/                      # Database migrations
└── seeders/
    ├── RoleSeeder.php               # Seeds 3 roles
    └── WorkerSeeder.php             # Seeds admin user
resources/views/
├── auth/
│   ├── login.blade.php              # Login page
│   └── register.blade.php           # Registration page
├── layouts/
│   └── app.blade.php                # Main layout template
├── assign-worker.blade.php          # Worker assignment form
├── dashboard.blade.php              # Dashboard view
├── edit-schedule.blade.php          # Schedule editing form
├── jobdesc-management.blade.php     # Job description CRUD
├── location-management.blade.php    # Location CRUD
└── schedule.blade.php               # Weekly schedule grid
routes/
└── web.php                          # All route definitions
```

---

## 🔌 API Reference

### Authentication Routes

| Method | URI | Controller | Description |
|--------|-----|------------|-------------|
| GET | `/login` | AuthController@showLogin | Show login form |
| POST | `/login` | AuthController@login | Process login |
| GET | `/logout` | AuthController@logout | Logout user |
| GET | `/register` | AuthController@showRegister | Show registration form |
| POST | `/register` | AuthController@register | Create new worker |

### Schedule Routes

| Method | URI | Controller | Description |
|--------|-----|------------|-------------|
| GET | `/schedule` | ScheduleViewController@showSchedulePage | Weekly schedule view |
| GET | `/schedule/edit/{dateKey}/{supervisor}/{start}` | ScheduleViewController@edit | Edit schedule form |
| POST | `/schedule/update` | ScheduleViewController@update | Update schedule |

### Assignment Routes

| Method | URI | Controller | Description |
|--------|-----|------------|-------------|
| GET | `/assign` | AssignWorkerController@index | Assignment form |
| POST | `/assign` | AssignWorkerController@store | Create assignment |

### Job Description Routes

| Method | URI | Controller | Description |
|--------|-----|------------|-------------|
| GET | `/jobdesc` | JobdescController@index | List all |
| POST | `/jobdesc` | JobdescController@store | Create new |
| PUT | `/jobdesc/{id}` | JobdescController@update | Update |
| DELETE | `/jobdesc/{id}` | JobdescController@destroy | Delete |

### Location Routes

| Method | URI | Controller | Description |
|--------|-----|------------|-------------|
| GET | `/location` | LocationController@index | List all |
| POST | `/location` | LocationController@store | Create new |
| PUT | `/location/{id}` | LocationController@update | Update |
| DELETE | `/location/{id}` | LocationController@destroy | Delete |

---

## 🔧 Troubleshooting

### Common Issues

**1. "Class not found" error**
```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

**2. Migration errors**
```bash
php artisan migrate:fresh --seed
```

**3. Session/authentication issues**
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

**4. PDF export not working**
- Ensure you have internet connection (libraries loaded from CDN)
- Check browser console for JavaScript errors
- Disable browser extensions that might block scripts

**5. Time display incorrect**
- Verify your server timezone in `config/app.php`
- Ensure Carbon is using `Asia/Jakarta` timezone for display

### Database Seeder Commands

```bash
# Seed roles only
php artisan db:seed --class=RoleSeeder

# Seed admin user only
php artisan db:seed --class=WorkerSeeder

# Fresh migration with all seeders
php artisan migrate:fresh --seed
```

---

## 🔐 Default Credentials

After seeding the database:

| Field | Value |
|-------|-------|
| Name | Admin User |
| Password | password123 |
| Role | Admin |

---

## 📄 License

This project is licensed under the MIT License.

---

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📞 Support

For support, please open an issue on GitHub or contact the development team.

---

**Built with ❤️ using Laravel 10 and Tailwind CSS**
