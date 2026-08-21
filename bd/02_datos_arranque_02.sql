USE app_gym;

-- =============================================================================
-- 1. USUARIO ADMINISTRADOR
-- =============================================================================
INSERT INTO users (username, email, password_hash)
VALUES ('admin', 'admin@tu-dominio.com', '$2y$10$jWoqzSUbjm6wxiJRm/i4cuOROX1qt2lLnn3PhxveSs/33XFK39yyy');

SET @USER_ID = 1;

-- =============================================================================
-- 2. CATÁLOGO DE EJERCICIOS (Rutina 202608)
-- =============================================================================
INSERT INTO exercises (user_id, name, muscle_group, equipment) VALUES
-- Movilidad
(@USER_ID, 'Movilidad de tobillo en posicion de plancha frontal', 'Movilidad', 'Peso corporal'),
(@USER_ID, 'Rotación tronco en pared', 'Movilidad', 'Pared'),
(@USER_ID, 'Caminata Cuadrupedia a sentadilla', 'Movilidad', 'Peso corporal'),
(@USER_ID, 'Dislocaciones de hombro', 'Movilidad', 'Banda / Bastón'),
(@USER_ID, 'Movilidad de tobillo posición abierta', 'Movilidad', 'Peso corporal'),
(@USER_ID, 'Movilidad de cadera', 'Movilidad', 'Peso corporal'),

-- Zona Media / Core
(@USER_ID, 'Hollow en barra', 'Core', 'Barra de dominadas'),
(@USER_ID, 'Plancha Copenhagen', 'Core', 'Banco / Peso corporal'),
(@USER_ID, 'Flexiones de tronco talón apoyado', 'Core', 'Mancuerna / Disco'),
(@USER_ID, 'Remo en plancha', 'Core', 'Mancuerna'),
(@USER_ID, 'Rotaciones en polea', 'Core', 'Polea'),
(@USER_ID, 'Hiperextensiones en banco', 'Core', 'Banco romano'),

-- Fuerza
(@USER_ID, 'Triple extension sin salto con mancuernas', 'Piernas', 'Mancuernas'),
(@USER_ID, 'Wall push isometrico', 'Pecho / Brazos', 'Pared'),
(@USER_ID, 'Estocada atrás con mancuerna', 'Piernas', 'Mancuernas'),
(@USER_ID, 'Floor press con mancuernas', 'Pecho', 'Mancuernas'),
(@USER_ID, 'Peso muerto a una pierna con mancuernas', 'Piernas', 'Mancuernas'),
(@USER_ID, 'Press Landmine de pie', 'Hombros', 'Landmine'),
(@USER_ID, 'Fondos', 'Tríceps', 'Paralelas / Peso corporal'),
(@USER_ID, 'Snatch al cajon una pierna alternanda', 'Full Body', 'Cajón / Mancuerna'),
(@USER_ID, 'Sentadilla isometrica en pared una pierna', 'Piernas', 'Pared'),
(@USER_ID, 'Remo invertido supino pies en banco', 'Espalda', 'Peso corporal / Banco'),
(@USER_ID, 'Sentadilla frontal', 'Piernas', 'Barra / Mancuerna'),
(@USER_ID, 'Puente glúteo en cajon una pierna', 'Glúteos', 'Cajón / Mancuerna'),
(@USER_ID, 'Halo de rodillas alternado', 'Hombros / Core', 'Kettlebell / Disco'),
(@USER_ID, 'Pull-up', 'Espalda', 'Barra de dominadas');

-- =============================================================================
-- 3. RUTINA PRE-OPERATORIA 202608
-- =============================================================================

INSERT INTO routines (user_id, name, description)
VALUES (@USER_ID, 'Rutina Pre-Operatoria 202608', 'Programa de acondicionamiento pre-operatorio estructurado en bloques de Movilidad, Zona Media y Fuerza.');

SET @ROUTINE_ID = LAST_INSERT_ID();

INSERT INTO routine_days (routine_id, name, order_index) VALUES 
(@ROUTINE_ID, 'Día 1', 1),
(@ROUTINE_ID, 'Día 2', 2);

SET @DAY1_ID = (SELECT id FROM routine_days WHERE routine_id = @ROUTINE_ID AND name = 'Día 1');
SET @DAY2_ID = (SELECT id FROM routine_days WHERE routine_id = @ROUTINE_ID AND name = 'Día 2');

-- -----------------------------------------------------------------------------
-- DÍA 1
-- -----------------------------------------------------------------------------

-- Día 1: Movilidad
INSERT INTO routine_blocks (routine_day_id, name, block_type, order_index) VALUES (@DAY1_ID, 'Bloque Movilidad', 'TRADITIONAL', 1);
SET @D1_B1 = LAST_INSERT_ID();

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, subgroup_label, order_index)
SELECT @D1_B1, id, 2, 10, 10, 'A1', 1 FROM exercises WHERE name = 'Movilidad de tobillo en posicion de plancha frontal' AND user_id = @USER_ID;
INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, subgroup_label, order_index)
SELECT @D1_B1, id, 2, 10, 10, 'A2', 2 FROM exercises WHERE name = 'Rotación tronco en pared' AND user_id = @USER_ID;
INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, subgroup_label, order_index)
SELECT @D1_B1, id, 2, 10, 10, 'A3', 3 FROM exercises WHERE name = 'Caminata Cuadrupedia a sentadilla' AND user_id = @USER_ID;

-- Día 1: Zona Media
INSERT INTO routine_blocks (routine_day_id, name, block_type, work_seconds, rest_between_exercises, order_index) VALUES (@DAY1_ID, 'Bloque Zona Media', 'CIRCUIT', 20, 40, 2);
SET @D1_B2 = LAST_INSERT_ID();

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, work_seconds, rest_seconds, subgroup_label, notes, order_index)
SELECT @D1_B2, id, 3, 20, 40, 'A1', 'trabajo+descanso 20+40', 1 FROM exercises WHERE name = 'Hollow en barra' AND user_id = @USER_ID;
INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, work_seconds, rest_seconds, subgroup_label, notes, order_index)
SELECT @D1_B2, id, 3, 20, 40, 'A2', 'trabajo+descanso 20+40', 2 FROM exercises WHERE name = 'Plancha Copenhagen' AND user_id = @USER_ID;
INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, work_seconds, rest_seconds, target_weight, subgroup_label, notes, order_index)
SELECT @D1_B2, id, 3, 20, 40, 2.50, 'A3', 'trabajo+descanso 20+40', 3 FROM exercises WHERE name = 'Flexiones de tronco talón apoyado' AND user_id = @USER_ID;

-- Día 1: Fuerza
INSERT INTO routine_blocks (routine_day_id, name, block_type, order_index) VALUES (@DAY1_ID, 'Bloque Fuerza', 'TRADITIONAL', 3);
SET @D1_B3 = LAST_INSERT_ID();

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, target_weight, subgroup_label, order_index)
SELECT @D1_B3, id, 3, 6, 6, 12.50, 'A1', 1 FROM exercises WHERE name = 'Triple extension sin salto con mancuernas' AND user_id = @USER_ID;
INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, subgroup_label, notes, order_index)
SELECT @D1_B3, id, 3, 3, 3, 'B1', 'repes de 8\'\'', 2 FROM exercises WHERE name = 'Wall push isometrico' AND user_id = @USER_ID;
INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, target_weight, subgroup_label, order_index)
SELECT @D1_B3, id, 3, 8, 8, 25.00, 'B2', 3 FROM exercises WHERE name = 'Estocada atrás con mancuerna' AND user_id = @USER_ID;
INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, target_weight, subgroup_label, order_index)
SELECT @D1_B3, id, 3, 8, 8, 25.00, 'C1', 4 FROM exercises WHERE name = 'Floor press con mancuernas' AND user_id = @USER_ID;
INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, target_weight, subgroup_label, order_index)
SELECT @D1_B3, id, 3, 8, 8, 25.00, 'D1', 5 FROM exercises WHERE name = 'Peso muerto a una pierna con mancuernas' AND user_id = @USER_ID;
INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, target_weight, subgroup_label, order_index)
SELECT @D1_B3, id, 3, 10, 10, 40.00, 'D2', 6 FROM exercises WHERE name = 'Press Landmine de pie' AND user_id = @USER_ID;
INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, target_weight, subgroup_label, notes, order_index)
SELECT @D1_B3, id, 3, 12, 12, 10.00, 'E1', '2 repes de 5\'\'+10', 7 FROM exercises WHERE name = 'Fondos' AND user_id = @USER_ID;

-- -----------------------------------------------------------------------------
-- DÍA 2
-- -----------------------------------------------------------------------------

-- Día 2: Movilidad
INSERT INTO routine_blocks (routine_day_id, name, block_type, order_index) VALUES (@DAY2_ID, 'Bloque Movilidad', 'TRADITIONAL', 1);
SET @D2_B1 = LAST_INSERT_ID();

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, subgroup_label, order_index)
SELECT @D2_B1, id, 2, 10, 10, 'A1', 1 FROM exercises WHERE name = 'Dislocaciones de hombro' AND user_id = @USER_ID;
INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, subgroup_label, order_index)
SELECT @D2_B1, id, 2, 10, 10, 'A2', 2 FROM exercises WHERE name = 'Movilidad de tobillo posición abierta' AND user_id = @USER_ID;
INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, subgroup_label, order_index)
SELECT @D2_B1, id, 2, 10, 10, 'A3', 3 FROM exercises WHERE name = 'Movilidad de cadera' AND user_id = @USER_ID;

-- Día 2: Zona Media
INSERT INTO routine_blocks (routine_day_id, name, block_type, work_seconds, rest_between_exercises, order_index) VALUES (@DAY2_ID, 'Bloque Zona Media', 'CIRCUIT', 20, 40, 2);
SET @D2_B2 = LAST_INSERT_ID();

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, work_seconds, rest_seconds, subgroup_label, notes, order_index)
SELECT @D2_B2, id, 3, 20, 40, 'A1', 'trabajo+descanso 20+40', 1 FROM exercises WHERE name = 'Remo en plancha' AND user_id = @USER_ID;
INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, work_seconds, rest_seconds, subgroup_label, notes, order_index)
SELECT @D2_B2, id, 3, 20, 40, 'A2', 'trabajo+descanso 20+40', 2 FROM exercises WHERE name = 'Rotaciones en polea' AND user_id = @USER_ID;
INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, work_seconds, rest_seconds, subgroup_label, notes, order_index)
SELECT @D2_B2, id, 3, 20, 40, 'A3', 'trabajo+descanso 20+40', 3 FROM exercises WHERE name = 'Hiperextensiones en banco' AND user_id = @USER_ID;

-- Día 2: Fuerza
INSERT INTO routine_blocks (routine_day_id, name, block_type, order_index) VALUES (@DAY2_ID, 'Bloque Fuerza', 'TRADITIONAL', 3);
SET @D2_B3 = LAST_INSERT_ID();

INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, target_weight, subgroup_label, order_index)
SELECT @D2_B3, id, 3, 4, 4, 20.00, 'A1', 1 FROM exercises WHERE name = 'Snatch al cajon una pierna alternanda' AND user_id = @USER_ID;
INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, subgroup_label, notes, order_index)
SELECT @D2_B3, id, 3, 2, 2, 'B1', 'repes de 10\'\'', 2 FROM exercises WHERE name = 'Sentadilla isometrica en pared una pierna' AND user_id = @USER_ID;
INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, subgroup_label, notes, order_index)
SELECT @D2_B3, id, 3, 11, 11, 'B2', '1 repes de 5\'\'+10', 3 FROM exercises WHERE name = 'Remo invertido supino pies en banco' AND user_id = @USER_ID;
INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, target_weight, subgroup_label, order_index)
SELECT @D2_B3, id, 3, 8, 8, 55.00, 'C1', 4 FROM exercises WHERE name = 'Sentadilla frontal' AND user_id = @USER_ID;
INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, target_weight, subgroup_label, order_index)
SELECT @D2_B3, id, 3, 13, 13, 27.50, 'D1', 5 FROM exercises WHERE name = 'Puente glúteo en cajon una pierna' AND user_id = @USER_ID;
INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, target_weight, subgroup_label, order_index)
SELECT @D2_B3, id, 3, 8, 8, 15.00, 'D2', 6 FROM exercises WHERE name = 'Halo de rodillas alternado' AND user_id = @USER_ID;
INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, target_weight, subgroup_label, order_index)
SELECT @D2_B3, id, 3, 6, 6, 5.00, 'E1', 7 FROM exercises WHERE name = 'Pull-up' AND user_id = @USER_ID;