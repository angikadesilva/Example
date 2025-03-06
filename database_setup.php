<?php
// Connect to SQLite database (or create if it doesn’t exist)
$db = new SQLite3('contact_form.db');

// Create a table if not exists
$query = "CREATE TABLE IF NOT EXISTS contacts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    message TEXT NOT NULL,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
$db->exec($query);

echo "Database setup complete!";
?>
