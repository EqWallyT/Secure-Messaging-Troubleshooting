<?php
// add_contact.php

session_start();

if (!isset($_SESSION['loggedin'])) {
    header("Content-Type: application/json");
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

include 'config.php';

$user_id = $_SESSION['user_id'];
$contact_username = trim($_POST['contact_username']);

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header("Content-Type: application/json");

    if (!empty($contact_username)) {
        // Check if the contact exists
        $sql = "SELECT user_id FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Database prepare error: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param("s", $contact_username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $contact_user = $result->fetch_assoc();
            $contact_user_id = $contact_user['user_id'];

            // Prevent adding oneself as a contact
            if ($contact_user_id == $user_id) {
                echo json_encode(['success' => false, 'error' => 'You cannot add yourself as a contact.']);
                exit();
            } else {
                // Check if the contact is already added
                $sql = "SELECT * FROM contacts WHERE user_id = ? AND contact_user_id = ?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    echo json_encode(['success' => false, 'error' => 'Database prepare error: ' . $conn->error]);
                    exit();
                }
                $stmt->bind_param("ii", $user_id, $contact_user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows == 0) {
                    // Add the contact
                    $sql = "INSERT INTO contacts (user_id, contact_user_id) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        echo json_encode(['success' => false, 'error' => 'Database prepare error: ' . $conn->error]);
                        exit();
                    }
                    $stmt->bind_param("ii", $user_id, $contact_user_id);
                    if ($stmt->execute()) {
                        echo json_encode(['success' => true, 'message' => 'Contact added successfully.']);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Error adding contact: ' . $stmt->error]);
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => 'This user is already in your contacts.']);
                }
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'User not found.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Please enter a username.']);
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
    exit();
}
