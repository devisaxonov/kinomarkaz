-- Foydalanuvchilar jadvali
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    telegram_id BIGINT UNIQUE NOT NULL,
    username VARCHAR(255),
    first_name VARCHAR(255),
    language_code VARCHAR(10) DEFAULT 'uz',
    is_admin BOOLEAN DEFAULT FALSE,
    is_banned BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_users_telegram_id ON users(telegram_id);

-- Kinolar jadvali
CREATE TABLE movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    genre VARCHAR(255),
    country VARCHAR(100),
    year VARCHAR(10),
    language VARCHAR(100),
    quality VARCHAR(50),
    duration VARCHAR(50),
    poster VARCHAR(255),
    channel_id BIGINT NOT NULL,
    message_id BIGINT NOT NULL,
    views BIGINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_movies_code ON movies(code);

-- Kanallar jadvali (Majburiy obuna uchun)
CREATE TABLE channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    title VARCHAR(255),
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tizim sozlamalari jadvali
CREATE TABLE settings (
    `key` VARCHAR(100) PRIMARY KEY,
    value TEXT
);

-- Reklama tarqatish navbati (Broadcast Queue)
CREATE TABLE broadcast_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    from_channel_id BIGINT NOT NULL,
    message_id BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
