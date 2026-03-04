<?php
require_once 'config.php';
require_once 'src/Core/Database.php';
require_once 'src/Models/User.php';

use Clinica\Models\User;

$email = 'agent_admin@test.com';
$pass = 'Agent123!';

$u = User::findByEmail($email);
if ($u) {
    // Grant all perms including new ones
    $perms = ['dashboard', 'checkin', 'pacotes', 'relatorios', 'usuarios', 'registros', 'profissionais', 'salas'];
    User::setPermissions($u['id'], $perms);
    echo "Permissions updated for $email.\n";
} else {
    echo "User not found, run previous setup if needed, but likely exists.\n";
}
