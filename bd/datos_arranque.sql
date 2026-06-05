INSERT INTO users (username, email, password_hash)
VALUES (
    'admin',
    'admin@tu-dominio.com',
    SHA2('Admin123', 256)
);

-- 🔹 PECHO
INSERT INTO exercises (user_id, name, muscle_group, equipment) VALUES
(1, 'Press banca con barra', 'Pecho', 'Barra'),
(1, 'Press inclinado con mancuernas', 'Pecho', 'Mancuernas'),
(1, 'Press en máquina', 'Pecho', 'Máquina'),
(1, 'Aperturas con mancuernas', 'Pecho', 'Mancuernas'),
(1, 'Cruce en polea', 'Pecho', 'Polea');

-- 🔹 ESPALDA
INSERT INTO exercises (user_id, name, muscle_group, equipment) VALUES
(1, 'Dominadas', 'Espalda', 'Peso corporal'),
(1, 'Jalón al pecho', 'Espalda', 'Polea'),
(1, 'Remo con barra', 'Espalda', 'Barra'),
(1, 'Remo con mancuerna', 'Espalda', 'Mancuernas'),
(1, 'Remo en máquina', 'Espalda', 'Máquina');

-- 🔹 PIERNAS
INSERT INTO exercises (user_id, name, muscle_group, equipment) VALUES
(1, 'Sentadilla con barra', 'Piernas', 'Barra'),
(1, 'Prensa', 'Piernas', 'Máquina'),
(1, 'Peso muerto rumano', 'Piernas', 'Barra'),
(1, 'Curl femoral', 'Piernas', 'Máquina'),
(1, 'Extensión de cuádriceps', 'Piernas', 'Máquina'),
(1, 'Elevaciones de gemelos', 'Piernas', 'Máquina');

-- 🔹 HOMBROS
INSERT INTO exercises (user_id, name, muscle_group, equipment) VALUES
(1, 'Press militar', 'Hombros', 'Barra'),
(1, 'Elevaciones laterales', 'Hombros', 'Mancuernas'),
(1, 'Elevaciones frontales', 'Hombros', 'Mancuernas'),
(1, 'Pájaros', 'Hombros', 'Mancuernas'),
(1, 'Face pull', 'Hombros', 'Polea');

-- 🔹 BRAZOS
INSERT INTO exercises (user_id, name, muscle_group, equipment) VALUES
(1, 'Curl con barra', 'Bíceps', 'Barra'),
(1, 'Curl con mancuernas', 'Bíceps', 'Mancuernas'),
(1, 'Curl martillo', 'Bíceps', 'Mancuernas'),
(1, 'Fondos', 'Tríceps', 'Peso corporal'),
(1, 'Extensión de tríceps en polea', 'Tríceps', 'Polea'),
(1, 'Press francés', 'Tríceps', 'Barra');

-- 🔹 CORE
INSERT INTO exercises (user_id, name, muscle_group, equipment) VALUES
(1, 'Crunch abdominal', 'Core', 'Peso corporal'),
(1, 'Elevaciones de piernas', 'Core', 'Peso corporal'),
(1, 'Plancha', 'Core', 'Peso corporal');

-- ============================
-- CONFIG
-- ============================
SET @USER_ID = 1;

-- ============================
-- CREAR RUTINA
-- ============================
INSERT INTO routines (user_id, name, description)
VALUES (@USER_ID, 'Push Pull Legs', 'Rutina base de hipertrofia');

SET @ROUTINE_ID = LAST_INSERT_ID();

-- ============================
-- CREAR DÍAS
-- ============================
INSERT INTO routine_days (routine_id, name, order_index) VALUES
(@ROUTINE_ID, 'Push', 1),
(@ROUTINE_ID, 'Pull', 2),
(@ROUTINE_ID, 'Legs', 3);

-- Obtener IDs
SET @PUSH_ID = (SELECT id FROM routine_days WHERE routine_id=@ROUTINE_ID AND name='Push');
SET @PULL_ID = (SELECT id FROM routine_days WHERE routine_id=@ROUTINE_ID AND name='Pull');
SET @LEGS_ID = (SELECT id FROM routine_days WHERE routine_id=@ROUTINE_ID AND name='Legs');

-- ============================
-- PUSH (Pecho, hombros, tríceps)
-- ============================
INSERT INTO routine_exercises 
(routine_day_id, exercise_id, sets, target_reps_min, target_reps_max, rest_seconds, order_index)
SELECT @PUSH_ID, id, 4, 6, 10, 120, 1 FROM exercises WHERE name='Press banca con barra' AND user_id=@USER_ID;

INSERT INTO routine_exercises 
SELECT @PUSH_ID, id, 3, 8, 12, 90, 2 FROM exercises WHERE name='Press inclinado con mancuernas' AND user_id=@USER_ID;

INSERT INTO routine_exercises 
SELECT @PUSH_ID, id, 3, 10, 15, 60, 3 FROM exercises WHERE name='Elevaciones laterales' AND user_id=@USER_ID;

INSERT INTO routine_exercises 
SELECT @PUSH_ID, id, 3, 10, 15, 60, 4 FROM exercises WHERE name='Extensión de tríceps en polea' AND user_id=@USER_ID;

INSERT INTO routine_exercises 
SELECT @PUSH_ID, id, 3, 8, 12, 90, 5 FROM exercises WHERE name='Fondos' AND user_id=@USER_ID;

-- ============================
-- PULL (Espalda, bíceps)
-- ============================
INSERT INTO routine_exercises 
SELECT @PULL_ID, id, 4, 6, 10, 120, 1 FROM exercises WHERE name='Dominadas' AND user_id=@USER_ID;

INSERT INTO routine_exercises 
SELECT @PULL_ID, id, 3, 8, 12, 90, 2 FROM exercises WHERE name='Remo con barra' AND user_id=@USER_ID;

INSERT INTO routine_exercises 
SELECT @PULL_ID, id, 3, 10, 15, 60, 3 FROM exercises WHERE name='Face pull' AND user_id=@USER_ID;

INSERT INTO routine_exercises 
SELECT @PULL_ID, id, 3, 8, 12, 60, 4 FROM exercises WHERE name='Curl con barra' AND user_id=@USER_ID;

INSERT INTO routine_exercises 
SELECT @PULL_ID, id, 3, 10, 12, 60, 5 FROM exercises WHERE name='Curl martillo' AND user_id=@USER_ID;

-- ============================
-- LEGS (Piernas completas)
-- ============================
INSERT INTO routine_exercises 
SELECT @LEGS_ID, id, 4, 5, 8, 150, 1 FROM exercises WHERE name='Sentadilla con barra' AND user_id=@USER_ID;

INSERT INTO routine_exercises 
SELECT @LEGS_ID, id, 3, 8, 12, 120, 2 FROM exercises WHERE name='Prensa' AND user_id=@USER_ID;

INSERT INTO routine_exercises 
SELECT @LEGS_ID, id, 3, 8, 12, 120, 3 FROM exercises WHERE name='Peso muerto rumano' AND user_id=@USER_ID;

INSERT INTO routine_exercises 
SELECT @LEGS_ID, id, 3, 10, 15, 60, 4 FROM exercises WHERE name='Extensión de cuádriceps' AND user_id=@USER_ID;

INSERT INTO routine_exercises 
SELECT @LEGS_ID, id, 3, 10, 15, 60, 5 FROM exercises WHERE name='Curl femoral' AND user_id=@USER_ID;

INSERT INTO routine_exercises 
SELECT @LEGS_ID, id, 4, 12, 20, 45, 6 FROM exercises WHERE name='Elevaciones de gemelos' AND user_id=@USER_ID;

