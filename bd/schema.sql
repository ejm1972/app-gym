-- ConInf AppGym Database Schema
-- Run this file once to set up the database

CREATE DATABASE IF NOT EXISTS app_gym CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE app_gym;

-- Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Exercise library (per user)
CREATE TABLE IF NOT EXISTS exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    muscle_group VARCHAR(50),
    equipment VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Routines
CREATE TABLE IF NOT EXISTS routines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Exercises within a routine
CREATE TABLE IF NOT EXISTS routine_exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    routine_day_id INT NOT NULL,
    exercise_id INT NOT NULL,
    sets INT DEFAULT 3,
    target_reps_min INT,
    target_reps_max INT,
    target_rpe DECIMAL(3,1),
    rest_seconds INT,
    order_index INT DEFAULT 0,
    FOREIGN KEY (routine_day_id) REFERENCES routine_days(id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE
);

-- Workout sessions (history)
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

-- Logged sets within a session
CREATE TABLE IF NOT EXISTS session_sets (
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

CREATE TABLE IF NOT EXISTS routine_days (
    id INT AUTO_INCREMENT PRIMARY KEY,
    routine_id INT NOT NULL,
    name VARCHAR(100) NOT NULL, -- Push, Pull, Piernas
    order_index INT DEFAULT 0,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (routine_id) REFERENCES routines(id) ON DELETE CASCADE
);

CREATE INDEX idx_sessions_user ON workout_sessions(user_id);
CREATE INDEX idx_sets_session ON session_sets(session_id);
CREATE INDEX idx_sets_exercise ON session_sets(exercise_id);
