<?php 
// Start the session if not already started
session_start();

// Include the config file to connect to the database
include 'config.php';

// Initialize variables
$first_name = "";
$last_name = "";
$phone_number = "";
$email = "";
$username = "";
$password = "";
$confirm_password = "";
$registration_message = "";

// Function to generate a unique random ID
function generateUniqueId($conn, $column) {
    do {
        $random_id = rand(1000, 9999); // Generate a random 4-digit ID

        // Check if the ID already exists in the specified column
        $sql = "SELECT * FROM users WHERE $column = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $random_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } while ($result->num_rows > 0); // Repeat if the ID is not unique

    return $random_id;
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form data and sanitize it
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone_number = trim($_POST['phone_number']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate the input
    if (!empty($first_name) && !empty($last_name) && !empty($phone_number) && !empty($email) && !empty($username) && !empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {
            // Check if the username or email already exists
            $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                // Generate unique sender_id and receiver_id
                $sender_id = generateUniqueId($conn, 'sender_id');
                $receiver_id = generateUniqueId($conn, 'receiver_id');

                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Prepare the SQL statement to insert the user data
                $sql = "INSERT INTO users (first_name, last_name, phone_number, email, username, password, sender_id, receiver_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssii", $first_name, $last_name, $phone_number, $email, $username, $hashed_password, $sender_id, $receiver_id);

                // Execute the query
                if ($stmt->execute()) {
                    // Registration successful, redirect to login page
                    header("Location: login.php");
                    exit();
                } else {
                    $registration_message = "Error: " . $stmt->error;
                }

                // Close the statement
                $stmt->close();
            } else {
                $registration_message = "Username or email already exists.";
            }
        } else {
            $registration_message = "Passwords do not match.";
        }
    } else {
        $registration_message = "Please fill in all fields.";
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
</head>
<body>
    <h2>Register</h2>

    <!-- Display registration message -->
    <?php if (!empty($registration_message)): ?>
        <p style="color:red;"><?php echo $registration_message; ?></p>
    <?php endif; ?>

    <form action="register.php" method="post">
        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" required><br><br>

        <label for="last_name">Last Name:</label>
        <input type="text" id="last_name" name="last_name" required><br><br>

        <label for="phone_number">Phone Number:</label>
        <input type="tel" id="phone_number" name="phone_number" required><br><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br><br>

        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required><br><br>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br><br>

        <label for="confirm_password">Confirm Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required><br><br>

        <input type="submit" value="Register">
    </form>

    <p>Already registered? <a href="login.php">Login Here</a></p>
</body>
</html>
