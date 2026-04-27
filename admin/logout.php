<?php
require_once __DIR__ . '/../app/bootstrap.php';

$pdo = Database::connect($config['db']);
Auth::logout($pdo);

header('Location: /admin/login.php');
