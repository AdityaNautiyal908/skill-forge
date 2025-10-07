USE coding_platform;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

show tables;
select * from users;

desc users;

ALTER TABLE `users`
ADD COLUMN `reset_token` VARCHAR(64) NULL AFTER `password_hash`,
ADD COLUMN `reset_expires_at` DATETIME NULL AFTER `reset_token`;

show tables;


