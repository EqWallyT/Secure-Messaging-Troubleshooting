<?php
session_start();

include 'config.php';
include 'encryption.php';

// Set CSP Header to mitigate XSS and data injection attacks
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self';");

// Handle AJAX request to send a message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    header('Content-Type: application/json');

    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit();
    }

    if (!isset($_SESSION['loggedin'])) {
        echo json_encode(['success' => false, 'error' => 'User not logged in']);
        exit();
    }

    $sender_id = $_SESSION['user_id'];
    $receiver_id = intval($_POST['receiver_id']);
    $message_text = trim($_POST['message']);

    if (!empty($message_text) && !empty($receiver_id)) {
        // Encrypt the message
        $encrypted_message = encryptMessage($message_text);

        if ($encrypted_message === null) {
            echo json_encode(['success' => false, 'error' => 'Encryption failed']);
            exit();
        }

        // Check if a conversation already exists
        $sql = "SELECT conversation_id FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $conversation_id = $row['conversation_id'];
        } else {
            $conversation_id = mt_rand(100000, 999999); // Generate a new conversation_id
        }

        // Insert the encrypted message into the database
        $sql = "INSERT INTO messages (sender_id, receiver_id, message_text, sent_at, conversation_id) VALUES (?, ?, ?, NOW(), ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Database prepare error: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param("iisi", $sender_id, $receiver_id, $encrypted_message, $conversation_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
    }

    $conn->close();
    exit();
}

// Handle AJAX request to fetch conversations
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch_conversations') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['loggedin'])) {
        echo json_encode(['error' => 'User not logged in']);
        exit();
    }

    $user_id = $_SESSION['user_id'];

    $sql = "SELECT DISTINCT m.conversation_id, u.user_id, u.username 
            FROM messages m
            JOIN users u ON (u.user_id = m.sender_id OR u.user_id = m.receiver_id)
            WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.user_id != ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $conversations = [];
    while ($row = $result->fetch_assoc()) {
        $conversations[] = $row;
    }

    echo json_encode($conversations);

    $stmt->close();
    $conn->close();
    exit();
}

// Handle AJAX request to fetch messages for a specific conversation
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch_messages') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['loggedin'])) {
        echo json_encode(['error' => 'User not logged in']);
        exit();
    }

    $conversation_id = intval($_GET['conversation_id']);

    $sql = "SELECT sender_id, receiver_id, message_text, sent_at FROM messages 
            WHERE conversation_id = ?
            ORDER BY sent_at ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $conversation_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        // Decrypt the message before sending it to the client
        $decrypted_message = decryptMessage($row['message_text']);
        if ($decrypted_message === null) {
            $decrypted_message = "[Decryption Failed]";
        }
        $row['message_text'] = $decrypted_message;
        $messages[] = $row;
    }

    echo json_encode($messages);

    $stmt->close();
    $conn->close();
    exit();
}

// Handle AJAX request to fetch contacts
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch_contacts') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['loggedin'])) {
        echo json_encode(['error' => 'User not logged in']);
        exit();
    }

    $user_id = $_SESSION['user_id'];

    $sql = "SELECT u.user_id, u.username 
            FROM users u 
            INNER JOIN contacts c ON u.user_id = c.contact_user_id 
            WHERE c.user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $contacts = [];
    while ($row = $result->fetch_assoc()) {
        $contacts[] = $row;
    }

    echo json_encode($contacts);

    $stmt->close();
    $conn->close();
    exit();
}

if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Messaging App</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="light-theme.css" id="theme-style">
    <script>
        var currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
    </script>
</head>
<body>
    <div class="navbar">
        <div class="navbar-left">
            <h1>Secure Messaging App</h1>
        </div>
        <div class="navbar-right">
            <span id="logged-in-user"><?php echo htmlspecialchars($username); ?></span>
            <button onclick="toggleSettings()">Settings</button>
        </div>
    </div>

    <div class="main-container">
        <div class="chat-area">
            <div class="messages-display" id="chat-messages"></div>
            <div class="message-input">
                <form id="message-form" method="post" onsubmit="sendMessage(); return false;">
                    <input type="hidden" name="action" value="send_message">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>"> <!-- CSRF Token -->
                    <textarea name="message" id="message" rows="4" placeholder="Type your message..." required></textarea>
                    <button type="submit" id="send-button">Send</button>
                </form>
            </div>
        </div>

        <div class="sidebar">
            <div class="tabs">
                <button class="tab-button active" onclick="openTab(event, 'messages')">Messages</button>
                <button class="tab-button" onclick="openTab(event, 'contacts')">Contacts</button>
            </div>
            <div class="tab-content-container">
                <div id="messages" class="tab-content active">
                    <h3>Your Conversations</h3>
                    <ul id="conversations-list">
                        <li>Loading conversations...</li>
                    </ul>
                </div>
                <div id="contacts" class="tab-content">
                    <h3>Your Contacts</h3>
                    <ul id="contacts-list"></ul>
                    <h3>Add a Contact</h3>
                    <form id="add-contact-form" onsubmit="addContact(); return false;">
                        <label for="contact_username">Contact Username:</label>
                        <input type="text" id="contact_username" name="contact_username" required>
                        <input type="submit" value="Add Contact">
                    </form>
                    <p>Add or select a contact to start messaging.</p>
                </div>
            </div>
        </div>
    </div>

    <div id="settings-modal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="toggleSettings()">&times;</span>
        <h2>Settings</h2>
        <label for="theme-select">Choose Theme:</label>
        <select id="theme-select">
            <option value="light-theme.css">Light Mode</option>
            <option value="dark-theme.css">Dark Mode</option>
        </select>
        <button id="accept-theme-button" onclick="acceptThemeChange()">Accept</button>
    </div>
</div>

    <script src="script.js"></script>
</body>
</html>
