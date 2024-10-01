<?php
// login.php

// Start a session to manage user login status
session_start();

// Include the database configuration file
include 'config.php';

// Initialize variables for login errors
$error = "";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get the form data
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Validate that username and password are not empty
    if (!empty($username) && !empty($password)) {

        // Prepare the SQL query to check if the username exists
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error = "Database prepare error: " . $conn->error;
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            // Check if a user with that username exists
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();

                // Verify the password
                if (password_verify($password, $row['password'])) {

                    // Successful login, set session variables
                    $_SESSION['loggedin'] = true;
                    $_SESSION['username'] = $username;
                    $_SESSION['user_id'] = $row['user_id']; // Store user_id in session

                    // Redirect to the main application page
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Incorrect password. Please try again.";
                }
            } else {
                $error = "No user found with that username.";
            }

            // Close the statement
            $stmt->close();
        }
    } else {
        $error = "Please enter both username and password.";
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>

    <!-- Display any error messages -->
    <?php if (!empty($error)): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <!-- Login form -->
    <form action="login.php" method="post">
        <label for="username">Username:</label>
        <input type="text" name="username" required><br><br>

        <label for="password">Password:</label>
        <input type="password" name="password" required><br><br>

        <input type="submit" value="Login">
    </form>

    <!-- Add the registration link here -->
    <p>Not registered? <a href="register.php">Register Here</a></p>
</body>
</html>