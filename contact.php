<?php
require 'vendor/autoload.php'; // Load Mailersend SDK

use MailerSend\MailerSend;
use MailerSend\Helpers\Builder\Recipient;
use MailerSend\Helpers\Builder\EmailParams;

session_start(); // Start session for CSRF protection

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Mailersend API Key
$apiKey = $_ENV['MAILERSEND_API_KEY'] ?? ''; // Ensure to store this in .env

// MySQL database connection
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'contact_form';

// Create MySQL connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Create table if not exists
$query = "CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($query);

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = $success = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF Token");
    }

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
        // Insert data into MySQL database
        $stmt = $conn->prepare("INSERT INTO messages (name, email, message) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $name, $email, $message);

        if ($stmt->execute()) {
            $success = "Thank you, $name! Your message has been saved.";

            // Send Email Notification using Mailersend API
            try {
                $mailersend = new MailerSend(['api_key' => $apiKey]);

                $recipients = [new Recipient('angika.nimnadi@gmail.com', 'Recipient Name')]; // Replace with your email

                $emailParams = (new EmailParams())
                    ->setFrom('angika.nimnadi@trial-jy7zpl921v545vx6.mlsender.net') // Change to your sender email
                    ->setFromName('Contact Form')
                    ->setRecipients($recipients)
                    ->setSubject('New Contact Form Submission')
                    ->setHtml("<p><strong>Name:</strong> $name</p><p><strong>Email:</strong> $email</p><p><strong>Message:</strong><br>$message</p>")
                    ->setText("Name: $name\nEmail: $email\nMessage:\n$message");

                $mailersend->email->send($emailParams);
            } catch (Exception $e) {
                $error = "Message saved, but email failed to send. Error: " . $e->getMessage();
            }
        } else {
            $error = "Failed to save message!";
        }

        $stmt->close();
    }
}

// Fetch messages
$messages = $conn->query("SELECT * FROM messages ORDER BY created_at DESC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Form with MySQL & MailerSend</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        .container { max-width: 500px; margin: auto; padding: 20px; background: white; border-radius: 5px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1); }
        input, textarea { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #28a745; color: white; padding: 10px; border: none; cursor: pointer; width: 100%; }
        .error { color: red; margin-bottom: 10px; }
        .success { color: green; margin-bottom: 10px; }
        .messages { margin-top: 20px; }
        .message-box { background: #e9ecef; padding: 10px; margin-bottom: 10px; border-radius: 5px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Contact Us</h2>

    <!-- Display error or success messages -->
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <?php if (!empty($success)) echo "<p class='success'>$success</p>"; ?>

    <form id="contactForm" method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        
        <label for="message">Message:</label>
        <textarea id="message" name="message" required></textarea>

        <button type="submit">Submit</button>
    </form>

    <div class="messages">
        <h3>Previous Messages</h3>
        <?php while ($row = $messages->fetch_assoc()): ?>
            <div class="message-box">
                <strong><?= htmlspecialchars($row['name']) ?> (<?= htmlspecialchars($row['email']) ?>)</strong>
                <p><?= nl2br(htmlspecialchars($row['message'])) ?></p>
                <small><?= $row['created_at'] ?></small>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
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

<?php
$conn->close();
?>
