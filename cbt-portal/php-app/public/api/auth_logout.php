<?php
declare(strict_types=1);
define('IS_API', true);
require __DIR__ . '/../../src/bootstrap.php';

$in = api_post();
Auth::logout();
json_out(["ok" => true]);
