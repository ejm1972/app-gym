USE app_gym;

-- 1. Crear Usuario Administrador
INSERT INTO users (username, email, password_hash)
VALUES ('admin', 'admin@tu-dominio.com', SHA2('Admin123', 256));

SET @USER_ID = 1;

-- 2. Cargar Catálogo de Ejercicios
INSERT INTO exercises (user_id, name, muscle_group, equipment) VALUES
-- Movilidad
(@USER_ID, 'Movilidad de tobillo', 'Movilidad', 'Ninguno'),
(@USER_ID, 'Rotación tronco en pared', 'Movilidad', 'Pared'),
(@USER_ID, 'Push up + caminata lateral desde el suelo', 'Movilidad', 'Peso corporal'),
(@USER_ID, 'Dislocaciones de hombro', 'Movilidad', 'Banda / Bastón'),
(@USER_ID, 'Caminata Cuadrupedia a sentadilla', 'Movilidad', 'Peso corporal'),
(@USER_ID, 'Movilidad de cadera', 'Movilidad', 'Peso corporal'),

-- Zona Media / Core
(@USER_ID, 'Hollow en barra', 'Core', 'Barra de dominadas'),
(@USER_ID, 'Plancha Copenhagen', 'Core', 'Banco / Peso corporal'),
(@USER_ID, 'Plancha con transferencia', 'Core', 'Mancuerna / Disco'),
(@USER_ID, 'Flexiones de tronco talón apoyado', 'Core', 'Mancuerna / Disco'),
(@USER_ID, 'Remo 1 brazo en posición de bird dog', 'Core', 'Mancuerna'),
(@USER_ID, 'Giros dinámicos con Landmine', 'Core', 'Landmine'),
(@USER_ID, 'Caminata de granjero unilateral', 'Core', 'Mancuerna / Kettlebell'),
(@USER_ID, 'Hiperextensiones en banco', 'Core', 'Banco romano'),

-- Fuerza
(@USER_ID, 'Triple extención a una pierna', 'Piernas', 'Peso corporal'),
(@USER_ID, 'Sentadilla frontal', 'Piernas', 'Barra / Mancuerna'),
(@USER_ID, 'Floor press alternado', 'Pecho', 'Mancuernas'),
(@USER_ID, 'Puente glúteo en cajon una pierna', 'Glúteos', 'Cajón / Mancuerna'),
(@USER_ID, 'Remo invertido pies en banco', 'Espalda', 'Peso corporal / Banco'),
(@USER_ID, 'Press Landmine', 'Hombros', 'Landmine'),
(@USER_ID, 'Triple extención sin salto', 'Piernas', 'Peso corporal'),
(@USER_ID, 'Subida al banco', 'Piernas', 'Mancuerna / Banco'),
(@USER_ID, 'Vuelo posterior', 'Hombros', 'Mancuernas'),
(@USER_ID, 'Pull-up supino', 'Espalda', 'Barra de dominadas'),
(@USER_ID, 'Peso muerto a una pierna', 'Piernas', 'Mancuernas'),
(@USER_ID, 'Fondos', 'Tríceps', 'Paralelas / Peso corporal');

-- 3. Crear Rutina "Rutina Pre Operatoria"
INSERT INTO routines (user_id, name, description)
VALUES (@USER_ID, 'Rutina Pre Operatoria', 'Rutina de preparación física pre-operatoria enfocada en movilidad, zona media y fuerza');

SET @ROUTINE_ID = LAST_INSERT_ID();

-- 4. Crear Días
INSERT INTO routine_days (routine_id, name, order_index) VALUES
(@ROUTINE_ID, 'Día 1', 1),
(@ROUTINE_ID, 'Día 2', 2);

SET @DAY1_ID = (SELECT id FROM routine_days WHERE routine_id = @ROUTINE_ID AND name = 'Día 1');
SET @DAY2_ID = (SELECT id FROM routine_days WHERE routine_id = @ROUTINE_ID AND name = 'Día 2');

-- ============================================================================
-- DÍA 1
-- ============================================================================

-- DÍA 1 / Bloque 1: Movilidad
INSERT INTO routine_blocks (routine_day_id, name, block_type, order_index)
VALUES (@DAY1_ID, 'Bloque Movilidad', 'TRADITIONAL', 1);
SET @D1_B1_ID = LAST_INSERT_ID();

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, subgroup_label, order_index)
SELECT @D1_B1_ID, id, 2, 10, 10, 'A1', 1 FROM exercises WHERE name = 'Movilidad de tobillo' AND user_id = @USER_ID;

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, subgroup_label, order_index)
SELECT @D1_B1_ID, id, 2, 10, 10, 'A2', 2 FROM exercises WHERE name = 'Rotación tronco en pared' AND user_id = @USER_ID;

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, subgroup_label, order_index)
SELECT @D1_B1_ID, id, 2, 12, 12, 'A3', 3 FROM exercises WHERE name = 'Push up + caminata lateral desde el suelo' AND user_id = @USER_ID;


-- DÍA 1 / Bloque 2: Zona Media (Circuito de Tiempo: 20s de trabajo x 40s de descanso)
INSERT INTO routine_blocks (routine_day_id, name, block_type, work_seconds, rest_between_exercises, order_index)
VALUES (@DAY1_ID, 'Bloque Zona media', 'CIRCUIT', 20, 40, 2);
SET @D1_B2_ID = LAST_INSERT_ID();

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, work_seconds, rest_seconds, subgroup_label, order_index)
SELECT @D1_B2_ID, id, 3, 20, 40, 'A1', 1 FROM exercises WHERE name = 'Hollow en barra' AND user_id = @USER_ID;

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, work_seconds, rest_seconds, subgroup_label, order_index)
SELECT @D1_B2_ID, id, 3, 20, 40, 'A2', 2 FROM exercises WHERE name = 'Plancha Copenhagen' AND user_id = @USER_ID;

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, work_seconds, rest_seconds, target_weight, subgroup_label, order_index)
SELECT @D1_B2_ID, id, 3, 20, 40, 12.00, 'A3', 3 FROM exercises WHERE name = 'Plancha con transferencia' AND user_id = @USER_ID;

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, work_seconds, rest_seconds, target_weight, subgroup_label, order_index)
SELECT @D1_B2_ID, id, 3, 20, 40, 2.50, 'A4', 4 FROM exercises WHERE name = 'Flexiones de tronco talón apoyado' AND user_id = @USER_ID;


-- DÍA 1 / Bloque 3: Fuerza
INSERT INTO routine_blocks (routine_day_id, name, block_type, order_index)
VALUES (@DAY1_ID, 'Bloque Fuerza', 'TRADITIONAL', 3);
SET @D1_B3_ID = LAST_INSERT_ID();

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, subgroup_label, order_index)
SELECT @D1_B3_ID, id, 3, 10, 10, 'A1', 1 FROM exercises WHERE name = 'Triple extención a una pierna' AND user_id = @USER_ID;

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, target_weight, subgroup_label, order_index)
SELECT @D1_B3_ID, id, 3, 10, 10, 50.00, 'B1', 2 FROM exercises WHERE name = 'Sentadilla frontal' AND user_id = @USER_ID;

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, target_weight, subgroup_label, order_index)
SELECT @D1_B3_ID, id, 3, 10, 10, 20.00, 'C1', 3 FROM exercises WHERE name = 'Floor press alternado' AND user_id = @USER_ID;

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, target_weight, subgroup_label, order_index)
SELECT @D1_B3_ID, id, 3, 10, 10, 25.00, 'C2', 4 FROM exercises WHERE name = 'Puente glúteo en cajon una pierna' AND user_id = @USER_ID;

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, subgroup_label, order_index)
SELECT @D1_B3_ID, id, 3, 10, 10, 'D1', 5 FROM exercises WHERE name = 'Remo invertido pies en banco' AND user_id = @USER_ID;

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, target_weight, subgroup_label, order_index)
SELECT @D1_B3_ID, id, 3, 10, 10, 30.00, 'D2', 6 FROM exercises WHERE name = 'Press Landmine' AND user_id = @USER_ID;


-- ============================================================================
-- DÍA 2
-- ============================================================================

-- DÍA 2 / Bloque 1: Movilidad
INSERT INTO routine_blocks (routine_day_id, name, block_type, order_index)
VALUES (@DAY2_ID, 'Bloque Movilidad', 'TRADITIONAL', 1);
SET @D2_B1_ID = LAST_INSERT_ID();

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, subgroup_label, order_index)
SELECT @D2_B1_ID, id, 2, 10, 10, 'A1', 1 FROM exercises WHERE name = 'Dislocaciones de hombro' AND user_id = @USER_ID;

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, subgroup_label, order_index)
SELECT @D2_B1_ID, id, 2, 20, 20, 'A2', 2 FROM exercises WHERE name = 'Caminata Cuadrupedia a sentadilla' AND user_id = @USER_ID;

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, subgroup_label, order_index)
SELECT @D2_B1_ID, id, 2, 20, 20, 'A3', 3 FROM exercises WHERE name = 'Movilidad de cadera' AND user_id = @USER_ID;


-- DÍA 2 / Bloque 2: Zona Media (Circuito de Tiempo)
INSERT INTO routine_blocks (routine_day_id, name, block_type, work_seconds, rest_between_exercises, order_index)
VALUES (@DAY2_ID, 'Bloque Zona media', 'CIRCUIT', 20, 40, 2);
SET @D2_B2_ID = LAST_INSERT_ID();

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, work_seconds, rest_seconds, target_weight, subgroup_label, order_index)
SELECT @D2_B2_ID, id, 3, 20, 40, 12.50, 'A1', 1 FROM exercises WHERE name = 'Remo 1 brazo en posición de bird dog' AND user_id = @USER_ID;

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, work_seconds, rest_seconds, target_weight, subgroup_label, order_index)
SELECT @D2_B2_ID, id, 3, 20, 40, 25.00, 'A2', 2 FROM exercises WHERE name = 'Giros dinámicos con Landmine' AND user_id = @USER_ID;

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, work_seconds, rest_seconds, target_weight, subgroup_label, order_index)
SELECT @D2_B2_ID, id, 3, 20, 40, 27.50, 'A3', 3 FROM exercises WHERE name = 'Caminata de granjero unilateral' AND user_id = @USER_ID;

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, work_seconds, rest_seconds, subgroup_label, order_index)
SELECT @D2_B2_ID, id, 3, 20, 40, 'A4', 4 FROM exercises WHERE name = 'Hiperextensiones en banco' AND user_id = @USER_ID;


-- DÍA 2 / Bloque 3: Fuerza
INSERT INTO routine_blocks (routine_day_id, name, block_type, order_index)
VALUES (@DAY2_ID, 'Bloque Fuerza', 'TRADITIONAL', 3);
SET @D2_B3_ID = LAST_INSERT_ID();

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, subgroup_label, order_index)
SELECT @D2_B3_ID, id, 3, 10, 10, 'A1', 1 FROM exercises WHERE name = 'Triple extención sin salto' AND user_id = @USER_ID;

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, target_weight, subgroup_label, notes, order_index)
SELECT @D2_B3_ID, id, 3, 10, 10, 22.50, 'B1', 'Una mancuerna', 2 FROM exercises WHERE name = 'Subida al banco' AND user_id = @USER_ID;

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, target_weight, subgroup_label, order_index)
SELECT @D2_B3_ID, id, 3, 10, 10, 7.50, 'B2', 3 FROM exercises WHERE name = 'Vuelo posterior' AND user_id = @USER_ID;

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, target_weight, subgroup_label, order_index)
SELECT @D2_B3_ID, id, 3, 6, 6, 7.50, 'C1', 4 FROM exercises WHERE name = 'Pull-up supino' AND user_id = @USER_ID;

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, target_weight, subgroup_label, notes, order_index)
SELECT @D2_B3_ID, id, 3, 8, 8, 22.50, 'D1', 'Dos mancuernas', 5 FROM exercises WHERE name = 'Peso muerto a una pierna' AND user_id = @USER_ID;

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, target_weight, subgroup_label, order_index)
SELECT @D2_B3_ID, id, 3, 10, 10, 7.50, 'E1', 6 FROM exercises WHERE name = 'Fondos' AND user_id = @USER_ID;