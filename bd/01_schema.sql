CREATE DATABASE IF NOT EXISTS app_gym CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE app_gym;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS session_sets;
DROP TABLE IF EXISTS workout_sessions;
DROP TABLE IF EXISTS routine_exercises;
DROP TABLE IF EXISTS routine_blocks;
DROP TABLE IF EXISTS routine_days;
DROP TABLE IF EXISTS routines;
DROP TABLE IF EXISTS exercises;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Exercises library
CREATE TABLE exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    muscle_group VARCHAR(50),
    equipment VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Routines
CREATE TABLE routines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. Routine Days
CREATE TABLE routine_days (
    id INT AUTO_INCREMENT PRIMARY KEY,
    routine_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    order_index INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (routine_id) REFERENCES routines(id) ON DELETE CASCADE
);

-- 5. Routine Blocks (Soporta tipo de bloque y tiempos de circuito)
CREATE TABLE routine_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    routine_day_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    block_type VARCHAR(20) DEFAULT 'TRADITIONAL', -- 'TRADITIONAL', 'CIRCUIT', 'SUPERSET'
    work_seconds INT NULL,                       -- Ej: 20 seg trabajo
    rest_between_exercises INT NULL,             -- Ej: 40 seg descanso entre ejercicios
    order_index INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (routine_day_id) REFERENCES routine_days(id) ON DELETE CASCADE
);

-- 6. Routine Exercises (Soporta peso objetivo, subgrupo A1/B1/C1 y tiempo por ejercicio)
CREATE TABLE routine_exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    routine_block_id INT NOT NULL,
    exercise_id INT NOT NULL,
    sets INT DEFAULT 3,
    target_reps_min INT NULL,
    target_reps_max INT NULL,
    work_seconds INT NULL,              -- Para ejercicios por tiempo (Ej: 20s)
    target_weight DECIMAL(6,2) NULL,    -- Peso prescrito
    target_rpe DECIMAL(3,1) NULL,
    rest_seconds INT NULL,              -- Descanso
    subgroup_label VARCHAR(10) NULL,    -- Identificador como 'A1', 'B1', 'C1', 'C2'
    order_index INT DEFAULT 0,
    notes TEXT NULL,
    FOREIGN KEY (routine_block_id) REFERENCES routine_blocks(id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE
);

-- 7. Workout sessions
CREATE TABLE workout_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    routine_id INT NULL,
    routine_day_id INT NULL,
    started_at DATETIME NOT NULL,
    ended_at DATETIME NULL,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (routine_id) REFERENCES routines(id) ON DELETE SET NULL,
    FOREIGN KEY (routine_day_id) REFERENCES routine_days(id) ON DELETE SET NULL
);

-- 8. Session sets
CREATE TABLE session_sets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    exercise_id INT NOT NULL,
    routine_exercise_id INT NULL,
    set_number INT NOT NULL,
    reps INT NOT NULL,
    weight DECIMAL(6,2) NOT NULL,
    rpe DECIMAL(3,1) NULL,
    is_pr BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES workout_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE,
    FOREIGN KEY (routine_exercise_id) REFERENCES routine_exercises(id) ON DELETE SET NULL
);

CREATE INDEX idx_sessions_user ON workout_sessions(user_id);
CREATE INDEX idx_sets_session ON session_sets(session_id);
CREATE INDEX idx_sets_exercise ON session_sets(exercise_id);