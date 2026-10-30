<?php
// Copy this file to config.php and edit. config.php is not served by the web server
// (it lives outside public/), but keep real keys out of version control anyway.
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'cbt_portal',
        'user' => 'root',   // XAMPP default
        'pass' => '',       // XAMPP default
    ],
    'gemini' => [
        // Optional. Needed only for "Generate Live AI CBT" and the AI coach.
        // Free key: https://aistudio.google.com/app/apikey   (or set env var GEMINI_API_KEY)
        'api_key' => '',
        'model'   => 'gemini-3.5-flash-lite',
        'timeout' => 90,
        // 'endpoint' => '',  // advanced: override the API URL (e.g. a proxy or test double)
    ],
];
