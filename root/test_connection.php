<?php
require_once __DIR__ . '/config/Database.php';
try {
    Database::getConnection();
    echo "Connected successfully.";
} catch (Throwable $e) {
    echo "Connection failed — check config/.env and the error log.";
}