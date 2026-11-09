<?php
declare(strict_types=1);
define('IS_API', true);
require __DIR__ . '/../../src/bootstrap.php';

$in = api_post();
json_out(["user" => Auth::login($in)]);
