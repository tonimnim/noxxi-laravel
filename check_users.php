<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "\n=== DATABASE USER SUMMARY ===\n\n";

$totalUsers = User::count();
echo "Total Users: {$totalUsers}\n\n";

// Count by role
$roleCount = User::selectRaw('role, COUNT(*) as count')
    ->groupBy('role')
    ->orderBy('role')
    ->get();

echo "Users by Role:\n";
echo str_repeat("-", 40) . "\n";
foreach ($roleCount as $role) {
    echo sprintf("%-15s: %d users\n", ucfirst($role->role), $role->count);
}

echo "\n\nDetailed User List:\n";
echo str_repeat("=", 80) . "\n";
printf("%-30s %-30s %-10s\n", "Name", "Email", "Role");
echo str_repeat("-", 80) . "\n";

$users = User::select('full_name', 'email', 'role', 'is_active', 'created_at')
    ->orderBy('role')
    ->orderBy('created_at')
    ->get();

foreach ($users as $user) {
    $status = $user->is_active ? '' : ' [INACTIVE]';
    printf("%-30s %-30s %-10s%s\n", 
        substr($user->full_name, 0, 28), 
        substr($user->email, 0, 28),
        $user->role,
        $status
    );
}

// Check for organizer profiles
echo "\n\nOrganizer Profiles:\n";
echo str_repeat("-", 40) . "\n";
$organizerUsers = User::where('role', 'organizer')->with('organizer')->get();
foreach ($organizerUsers as $user) {
    if ($user->organizer) {
        echo "{$user->full_name}: {$user->organizer->business_name}\n";
    } else {
        echo "{$user->full_name}: [NO ORGANIZER PROFILE]\n";
    }
}

echo "\n";