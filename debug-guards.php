<?php
// Temporary debug script - Delete after testing
// Run with: php debug-guards.php

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Debug Auth Guards ===\n\n";

echo "1. All configured guards in config/auth.php:\n";
$guards = array_keys(config('auth.guards', []));
print_r($guards);

echo "\n2. Default guard:\n";
echo config('auth.defaults.guard') . "\n";

echo "\n3. Observability desired guards:\n";
$desiredGuards = config('observability.dashboard.guards', ['web', 'sanctum']);
print_r($desiredGuards);

echo "\n4. Available guards (intersection):\n";
$availableGuards = array_intersect($desiredGuards, $guards);
print_r($availableGuards);

echo "\n5. Auth middleware that will be used:\n";
echo "'auth:" . implode(',', $availableGuards ?: ['web']) . "'\n";

echo "\n6. Is 'web' guard defined? " . (in_array('web', $guards) ? 'YES' : 'NO') . "\n";
echo "7. Is 'sanctum' guard defined? " . (in_array('sanctum', $guards) ? 'YES' : 'NO') . "\n";
echo "8. Is 'api' guard defined? " . (in_array('api', $guards) ? 'YES' : 'NO') . "\n";
