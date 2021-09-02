<?php

// Place a constants.php file in the same directory with correct values for following keys.
$vendor_db_link = new mysqli('host', 'username', 'password', 'db_name');

if ($vendor_db_link -> connect_errno) {
    echo "Failed to connect to MySQL: " . $vendor_db_link -> connect_error;
    exit();
}
