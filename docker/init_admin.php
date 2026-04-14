<?php

$host = getenv('DB_HOST');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$charset = getenv('DB_CHARSET');

$adminUser  = 'admin';
$adminEmail = 'admin@app-gym.coninf.com.ar';
$adminPass  = 'Admin123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Verificar si existe admin
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$adminUser]);

    if (!$stmt->fetch()) {
        $hash = password_hash($adminPass, PASSWORD_DEFAULT);

        $insert = $pdo->prepare("
            INSERT INTO users (username, email, password_hash)
            VALUES (?, ?, ?)
        ");

        $insert->execute([$adminUser, $adminEmail, $hash]);

        echo "✔ Usuario admin creado\n";
    } else {
        echo "✔ Usuario admin ya existe\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}