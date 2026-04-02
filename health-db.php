<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

$health = db_healthcheck();

http_response_code($health['ok'] ? 200 : 500);
header('Content-Type: text/plain; charset=utf-8');

echo $health['message'];
