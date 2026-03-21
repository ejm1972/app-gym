# 🏋️ ConInf — AppGym

> App web en PHP puro para administrar rutinas de entrenamiento, registrar progreso y ver tu historial.

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat-square&logo=mysql&logoColor=white)
![CSS](https://img.shields.io/badge/CSS-Vanilla-1572B6?style=flat-square&logo=css3&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)

---

## ✨ Funcionalidades

| Módulo | Descripción |
|--------|-------------|
| 🔐 **Autenticación** | Registro y login multi-usuario con contraseñas hasheadas (bcrypt) |
| 🏃 **Entrenar** | Sesión en vivo: registrá cada serie con peso y repeticiones |
| 📋 **Rutinas** | Creá rutinas con ejercicios, series, reps y tiempos de descanso |
| 💪 **Ejercicios** | Biblioteca personalizada por usuario con grupo muscular y equipamiento |
| 📊 **Historial** | Todas tus sesiones con volumen total, duración y detalle por ejercicio |
| 🏆 **Mejor marca** | Al entrenar te muestra tu mejor peso anterior en cada ejercicio |

---

## 🚀 Instalación

### Requisitos

- PHP 8.0+
- MySQL 5.7+ o MariaDB 10.3+
- Extensión PDO + `pdo_mysql` habilitada

### 1. Clonar el repositorio

```bash
git clone https://github.com/TU_USUARIO/app-gym.git
cd app-gym
```

### 2. Crear la base de datos

```bash
mysql -u root -p < bd/schema.sql
```

O importar `bd/schema.sql` desde phpMyAdmin / DBeaver.

### 3. Configurar la conexión

Copiá el archivo de ejemplo y editalo con tus credenciales:

```bash
cp config/database.example.php config/database.php
```

```php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
define('DB_NAME', 'app_gym');
```

### 4. Levantar el servidor

**Desarrollo (PHP built-in):**
```bash
php -S localhost:8080
```
Abrí → [http://localhost:8080](http://localhost:8080)

**XAMPP / Laragon:**
Colocá la carpeta en `htdocs/` y accedé desde `http://localhost/app-gym/`

**Apache (producción):**
```bash
cp -r app-gym /var/www/html/
```

---

## 📁 Estructura del proyecto

```
app-gym/
├── assets/
│   └── css/
│       └── style.css          # Sistema de diseño completo
├── config/
│   ├── database.php           # ⚠️ No commiteado (.gitignore)
│   └── database.example.php   # Plantilla de configuración
├── includes/
│   ├── auth.php               # Login, registro, CSRF, sesiones
│   ├── header.php             # Layout: sidebar + HTML head
│   └── footer.php             # Cierre HTML
├── index.php                  # Login / Registro
├── dashboard.php              # Panel principal con stats
├── exercises.php              # CRUD de ejercicios
├── routines.php               # Gestión de rutinas
├── workout.php                # Sesión de entrenamiento en vivo
├── history.php                # Historial de sesiones
├── logout.php
├── bd/
│   └── schema.sql                 # Esquema de la base de datos
└── README.md
```

---

## 🗄️ Esquema de base de datos

```
users
  └── exercises          (biblioteca por usuario)
  └── routines
        └── routine_exercises   (ejercicios dentro de una rutina)
  └── workout_sessions
        └── session_sets        (series registradas por sesión)
```

---

## 🔒 Seguridad

- Contraseñas hasheadas con `password_hash()` — bcrypt
- Protección CSRF en todos los formularios
- Queries con PDO y prepared statements — previene SQL injection
- Validación de pertenencia por `user_id` en cada operación

---

## 🛠️ Tecnologías

- **Backend:** PHP 8 puro — sin frameworks
- **Base de datos:** MySQL / MariaDB con PDO
- **Frontend:** HTML5 + CSS vanilla (sin dependencias JS)
- **Tipografía:** Bebas Neue + DM Sans (Google Fonts)

---

## 📝 Licencia

MIT — libre para usar, modificar y distribuir.
