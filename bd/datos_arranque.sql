INSERT INTO users (username, email, password_hash)
VALUES (
    'admin',
    'admin@tu-dominio.com',
    SHA2('Admin123', 256)
);