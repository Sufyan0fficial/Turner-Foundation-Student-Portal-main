<?php
require_once('C:/xampp/htdocs/tuner-foundation/wp-load.php');

global $wpdb;

// Create portal admins table
$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tfsp_portal_admins (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    username varchar(60) NOT NULL,
    password varchar(255) NOT NULL,
    email varchar(100) NOT NULL,
    full_name varchar(100) DEFAULT NULL,
    role varchar(20) DEFAULT 'admin',
    is_active tinyint(1) DEFAULT 1,
    last_login datetime DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);

echo "✓ Portal admins table created!<br><br>";

// Check if default admin exists
$exists = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}tfsp_portal_admins WHERE username = 'admin'");

if (!$exists) {
    // Create default admin
    $result = $wpdb->insert(
        $wpdb->prefix . 'tfsp_portal_admins',
        array(
            'username' => 'admin',
            'password' => password_hash('YCAMAdmin2024!', PASSWORD_DEFAULT),
            'email' => 'admin@ycam.org',
            'full_name' => 'YCAM Administrator',
            'role' => 'admin',
            'is_active' => 1
        )
    );
    
    if ($result) {
        echo "✓ Default admin created successfully!<br><br>";
    } else {
        echo "✗ Failed to create admin: " . $wpdb->last_error . "<br><br>";
    }
} else {
    echo "ℹ Default admin already exists<br><br>";
}

// Show all admins
$admins = $wpdb->get_results("SELECT id, username, email, full_name, is_active, created_at FROM {$wpdb->prefix}tfsp_portal_admins");

echo "<strong>Portal Admins:</strong><br>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Active</th><th>Created</th></tr>";
foreach ($admins as $admin) {
    echo "<tr>";
    echo "<td>{$admin->id}</td>";
    echo "<td><strong>{$admin->username}</strong></td>";
    echo "<td>{$admin->email}</td>";
    echo "<td>{$admin->full_name}</td>";
    echo "<td>" . ($admin->is_active ? 'Yes' : 'No') . "</td>";
    echo "<td>{$admin->created_at}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><br><strong>Login Credentials:</strong><br>";
echo "Username: <strong>admin</strong><br>";
echo "Password: <strong>YCAMAdmin2024!</strong><br>";
echo "<br>Login URL: <a href='" . home_url('/portal-admin/login/') . "'>" . home_url('/portal-admin/login/') . "</a>";
?>
