<?php
require_once 'config/database.php';
session_start();

header('Content-Type: application/json');

$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$data = json_decode(file_get_contents("php://input"), true);

// 🔹 PR simple por volumen
$stmt = $db->prepare("
    SELECT weight, reps
    FROM session_sets
    WHERE exercise_id = ?
    ORDER BY (weight * reps) DESC
    LIMIT 1
");
$stmt->execute([$data['exercise_id']]);
$best = $stmt->fetch(PDO::FETCH_ASSOC);

$currentVolume = $data['weight'] * $data['reps'];
$isPR = false;

if (!$best || ($currentVolume > ($best['weight'] * $best['reps']))) {
    $isPR = true;
}

// 🔹 insertar
$stmt = $db->prepare("
    INSERT INTO session_sets 
    (session_id, exercise_id, routine_exercise_id, set_number, weight, reps, is_pr)
    VALUES (?,?,?,?,?,?,?)
");

$stmt->execute([
    $data['session_id'],
    $data['exercise_id'],
    $data['routine_exercise_id'],
    $data['set_number'],
    $data['weight'],
    $data['reps'],
    $isPR
]);

echo json_encode([
    "success" => true,
    "is_pr" => $isPR
]);
