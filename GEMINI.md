# ConInf — AppGym Development Guide

## 1. Project Overview
ConInf — AppGym is a pure, lightweight PHP 8 web application designed for tracking workouts, routine management, and viewing workout progress/history.

### Core Technologies
- **Backend:** Pure PHP 8.0+ (no external frameworks).
- **Database:** MySQL 5.7+ / MariaDB 10.3+ using PHP's standard PDO database abstraction.
- **Frontend:** Vanilla HTML5 and CSS with an industrial-dark, brutalist-refined theme (`assets/css/style.css`). It does not rely on heavy JavaScript frameworks, ensuring extremely high performance.
- **Fonts & Icons:** Google Fonts (Bebas Neue + DM Sans) and inline SVG icons.

### System Architecture & Security
- **Authentication:** Custom session-based authentication in `includes/auth.php` using secure `password_hash()` (bcrypt) and `password_verify()`.
- **Database Access:** All queries leverage PDO prepared statements to prevent SQL injection.
- **CSRF Protection:** Form actions are secured using a custom token mechanism managed in `includes/auth.php` (`csrf()` and `verifyCsrf()`).
- **Authorization:** Standard multi-tenant system separating data by `user_id` across all operations.

---

## 2. Directory Structure & Key Files
- `index.php`: Login and registration entry point.
- `dashboard.php`: Main logged-in hub showing recent workouts and summary statistics.
- `exercises.php`: Manage and edit the personalized exercise library.
- `routines.php`: Build and structure custom training programs (with support for Days, Blocks, and Circuits).
- `workout.php`: The "live" workout tracker for registering sets, reps, and weight.
- `history.php`: Detailed history of all completed workout sessions.
- `save_set.php`: Async endpoint for logging individual workout sets.
- `config/`: Contains database configuration.
  - `database_ejemplo.php`: Template configuration using environment variables or standard fallbacks.
- `includes/`: Common layout parts and security helpers.
  - `auth.php`: Login, register, CSRF check, session handling.
  - `header.php` / `footer.php`: Reusable HTML layouts.
- `bd/`: Schema definitions and sample records.
  - `01_schema.sql`: Full database structure (Users, Exercises, Routines, Days, Blocks, routine_exercises, Sessions, and Sets).
  - `02_datos_arranque.sql`: Starter database rows.
- `docker/`: Docker Compose configurations and configurations for development/production.

---

## 3. Environment Setup & Execution

### Option A: Local Development (PHP Built-in Server)
This is the simplest way to run the application if you have PHP and MySQL installed locally.

1. **Database Setup**:
   Create a database named `app_gym` and import the schema:
   ```bash
   mysql -u root -p < bd/01_schema.sql
   mysql -u root -p < bd/02_datos_arranque.sql
   ```
2. **Configuration**:
   Copy the example database config:
   ```bash
   cp config/database_ejemplo.php config/database.php
   ```
   Edit `config/database.php` to match your local database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_PORT', '3306');
   define('DB_NAME', 'app_gym');
   define('DB_USER', 'your_user');
   define('DB_PASS', 'your_password');
   ```
3. **Run Server**:
   Start the built-in PHP server:
   ```bash
   php -S localhost:8080
   ```
   Access the app at `http://localhost:8080`.

### Option B: Local Development (Docker Compose)
A complete containerized stack featuring Nginx, PHP-FPM 8.2, and MySQL 5.7.

1. **Docker .env File**:
   Create a `.env` file from the example in `docker/.env.example`.
2. **Run Containers**:
   ```bash
   docker compose -f docker/docker-compose.dev.yml up --build -d
   ```
3. **Database Initialization**:
   Once running, import the schema files into the MySQL container.

---

## 4. Coding & Architecture Conventions

### Backend (PHP 8)
- **Typing:** Use explicit PHP type declarations for function parameters and return types where possible (e.g., `isLoggedIn(): bool`, `login(string $username, string $password): bool`).
- **Database Operations:** Always use `getDB()` to obtain a PDO instance and execute parameterized prepared statements. Do not concatenate variables into SQL strings.
- **Layout Structure:** Files should follow the multi-page PHP standard:
  ```php
  <?php
  $pageTitle = 'My Page Title';
  require_once __DIR__ . '/includes/header.php';
  requireLogin();
  // page-specific controller logic here
  ?>
  <!-- HTML Content Here -->
  <?php require_once __DIR__ . '/includes/footer.php'; ?>
  ```
- **Form Submissions:** Forms must include a CSRF token:
  ```html
  <form action="..." method="POST">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <!-- form inputs -->
  </form>
  ```
  The endpoint must verify it using `verifyCsrf();` at the beginning of the request processing.

### Styling & CSS Customization
- **Theme:** AppGym is styled around a high-contrast industrial-dark design.
- **CSS Variables:** Colors, sizing, and transitions must always use the CSS custom properties (variables) defined in `:root` inside `assets/css/style.css` (e.g., `var(--accent)`, `var(--surface)`, `var(--radius)`).
- **Responsive Design:** AppGym uses a mobile-first design with helper media queries for desktop layouts.
- **Strictly No CSS Frameworks:** Refrain from introducing utility frameworks (Tailwind, Bootstrap) unless explicitly requested. Rely on pure semantic CSS.

### Database Conventions
- **Cascade Deletes:** Always configure `FOREIGN KEY (...) REFERENCES ... ON DELETE CASCADE` or `SET NULL` on related child tables to keep the database tidy.
- **Indexes:** Ensure queries are optimized by indexing frequently filtered/joined columns (e.g. `user_id` on sessions, `session_id` on sets, etc.).
