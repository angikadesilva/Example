<?php
// Connect to SQLite database
$db = new SQLite3('contact_form.db');

// Initialize variables
$name = $email = $message = "";
$success = $error = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = filter_var(trim($_POST["name"]), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $message = filter_var(trim($_POST["message"]), FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Server-side validation
    if (empty($name) || empty($email) || empty($message)) {
        $error = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } else {
        // Prepare and execute SQLite query
        $stmt = $db->prepare("INSERT INTO contacts (name, email, message) VALUES (:name, :email, :message)");
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':message', $message, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $success = "Thank you, $name! Your message has been received.";
            // Clear input fields after submission
            $name = $email = $message = "";
        } else {
            $error = "Error submitting your message.";
        }
    }
}

// Retrieve all submitted messages
$result = $db->query("SELECT * FROM contacts ORDER BY submitted_at DESC");
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
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 400px;
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
            padding: 10px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0px 0px 5px rgba(0, 0, 0, 0.1);
        }
        .message-box {
            border-bottom: 1px solid #ddd;
            padding: 10px 0;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Contact Us</h2>

    <!-- Display error or success message -->
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <?php if (!empty($success)) echo "<p class='success'>$success</p>"; ?>

    <form id="contactForm" method="POST" action="">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($name); ?>">
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email); ?>">
        
        <label for="message">Message:</label>
        <textarea id="message" name="message"><?= htmlspecialchars($message); ?></textarea>

        <button type="submit">Submit</button>
    </form>
</div>

<!-- Display submitted messages -->
<div class="container messages">
    <h3>Previous Messages</h3>
    <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)): ?>
        <div class="message-box">
            <p><strong>Name:</strong> <?= htmlspecialchars($row['name']); ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($row['email']); ?></p>
            <p><strong>Message:</strong> <?= htmlspecialchars($row['message']); ?></p>
            <small><em>Submitted on <?= $row['submitted_at']; ?></em></small>
        </div>
    <?php endwhile; ?>
</div>

<script>
    // Client-side validation
    document.getElementById("contactForm").addEventListener("submit", function(event) {
        let name = document.getElementById("name").value.trim();
        let email = document.getElementById("email").value.trim();
        let message = document.getElementById("message").value.trim();
        let errorMessage = "";

        if (name === "" || email === "" || message === "") {
            errorMessage = "All fields are required!";
        } else if (!/^\S+@\S+\.\S+$/.test(email)) {
            errorMessage = "Invalid email format!";
        }

        if (errorMessage) {
            alert(errorMessage);
            event.preventDefault();
        }
    });
</script>

</body>
</html>
