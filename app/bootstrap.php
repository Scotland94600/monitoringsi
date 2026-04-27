<?php

session_start();

$configPath = __DIR__ . '/../config/config.php';
if (!file_exists($configPath)) {
    throw new RuntimeException('Configuration manquante. Copiez config/config.php.example vers config/config.php.');
}

$config = require $configPath;

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Repositories/DashboardRepository.php';
