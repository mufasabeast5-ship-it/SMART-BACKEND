<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDB();

echo "Creating missing tables...\n";

$sql = "
CREATE TABLE IF NOT EXISTS enrollment_requests (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED        NOT NULL,
    class_id    INT UNSIGNED        NOT NULL,
    status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at  TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    UNIQUE KEY  uq_enrollment (user_id, class_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lessons (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_id    INT UNSIGNED        NOT NULL,
    title       VARCHAR(255)        NOT NULL,
    description TEXT                NULL,
    lesson_date DATE                NOT NULL,
    created_at  TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB;
";

try {
    $pdo->exec($sql);
    echo "Tables created successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
