<?php
// SQLite database connection
$db = new SQLite3('contact_form.db');

// Create table if it doesn't exist
$query = "CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$db->exec($query);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize user input
    $name = filter_var(trim($_POST["name"]), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $message = filter_var(trim($_POST["message"]), FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Server-side validation
    if (empty($name) || empty($email) || empty($message)) {
        $error = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } else {
        // Insert data into SQLite database
        $stmt = $db->prepare("INSERT INTO messages (name, email, message) VALUES (:name, :email, :message)");
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':message', $message, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $success = "Thank you, $name! Your message has been saved.";
        } else {
            $error = "Failed to save message!";
        }
    }
}

// Fetch all messages from the database
$messages = $db->query("SELECT * FROM messages ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Form with SQLite</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 500px;
            margin: auto;
            padding: 20px;
            background: white;
            border-radius: 5px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background: #28a745;
            color: white;
            padding: 10px;
            border: none;
            cursor: pointer;
            width: 100%;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }
        .messages {
            margin-top: 20px;
        }
        .message-box {
            background: #e9ecef;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Contact Us</h2>

    <!-- Display error or success messages -->
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <?php if (!empty($success)) echo "<p class='success'>$success</p>"; ?>

    <form id="contactForm" method="POST" action="">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name">
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email">
        
        <label for="message">Message:</label>
        <textarea id="message" name="message"></textarea>

        <button type="submit">Submit</button>
    </form>

    <div class="messages">
        <h3>Previous Messages</h3>
        <?php while ($row = $messages->fetchArray(SQLITE3_ASSOC)): ?>
            <div class="message-box">
                <strong><?= htmlspecialchars($row['name']) ?> (<?= htmlspecialchars($row['email']) ?>)</strong>
                <p><?= nl2br(htmlspecialchars($row['message'])) ?></p>
                <small><?= $row['created_at'] ?></small>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
    // Client-side validation
    document.getElementById("contactForm").addEventListener("submit", function(event) {
        let name = document.getElementById("name").value.trim();
        let email = document.getElementById("email").value.trim();
        let message = document.getElementById("message").value.trim();
        let errorMessage = "";

        // Check if all fields are filled
        if (name === "" || email === "" || message === "") {
            errorMessage = "All fields are required!";
        }
        // Validate email format using regex
        else if (!/^\S+@\S+\.\S+$/.test(email)) {
            errorMessage = "Invalid email format!";
        }

        // If there's an error, prevent form submission and show alert
        if (errorMessage) {
            alert(errorMessage);
            event.preventDefault();
        }
    });
</script>

</body>
</html>
