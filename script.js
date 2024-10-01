var currentUserId = currentUserId || null;
var currentConversationId = null;
var currentReceiverId = null;

// Initialize default tab and load conversations and contacts
document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("messages").classList.add("active");
    fetchConversations();
    fetchContacts();

});

// Function to open and switch tabs
function openTab(evt, tabName) {
    var tabcontent = document.getElementsByClassName("tab-content");
    for (var i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    var tablinks = document.getElementsByClassName("tab-button");
    for (var i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }

    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.classList.add("active");
}
// Function to toggle the settings modal
function toggleSettings() {
    var modal = document.getElementById('settings-modal');
    if (modal.style.display === 'block') {
        modal.style.display = 'none';
    } else {
        modal.style.display = 'block';
    }
}
// Function to apply the selected theme
function acceptThemeChange() {
    var themeSelect = document.getElementById('theme-select');
    var selectedTheme = themeSelect.value;

    // Change the theme
    document.getElementById('theme-style').setAttribute('href', selectedTheme);

    // Optionally, you can store this selection in localStorage to persist the theme change
    localStorage.setItem('selectedTheme', selectedTheme);

    // Close the settings modal
    toggleSettings();
}

// Apply the saved theme on page load
document.addEventListener("DOMContentLoaded", function () {
    var savedTheme = localStorage.getItem('selectedTheme');
    if (savedTheme) {
        document.getElementById('theme-style').setAttribute('href', savedTheme);
    }
});

// Function to fetch conversations
function fetchConversations() {
    fetch('index.php?action=fetch_conversations')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error fetching conversations:', data.error);
            } else {
                displayConversations(data);
            }
        })
        .catch(error => {
            console.error('Error fetching conversations:', error);
        });
}

// Function to display conversations in the Messages tab
function displayConversations(conversations) {
    var conversationsList = document.getElementById('conversations-list');
    conversationsList.innerHTML = '';

    if (conversations.length === 0) {
        conversationsList.innerHTML = '<li>No conversations found.</li>';
    } else {
        conversations.forEach(function (conversation) {
            var listItem = document.createElement('li');
            listItem.classList.add('conversation-item');
            listItem.textContent = conversation.username;
            listItem.dataset.conversationId = conversation.conversation_id;

            listItem.onclick = function () {
                currentConversationId = conversation.conversation_id;
                fetchMessages(currentConversationId);
            };

            conversationsList.appendChild(listItem);
        });
    }
}

// Function to fetch messages
function fetchMessages(conversationId) {
    fetch('index.php?action=fetch_messages&conversation_id=' + conversationId)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error fetching messages:', data.error);
            } else {
                displayMessages(data);
            }
        })
        .catch(error => {
            console.error('Error fetching messages:', error);
        });
}

// Function to display messages in the chat area
function displayMessages(messages) {
    var messagesContainer = document.getElementById('chat-messages');
    messagesContainer.innerHTML = '';

    if (messages.length === 0) {
        messagesContainer.innerHTML = '<p>No messages yet. Start the conversation!</p>';
    } else {
        messages.forEach(function (message) {
            var messageDiv = document.createElement('div');
            messageDiv.classList.add('message');

            if (message.sender_id == currentUserId) {
                messageDiv.classList.add('sent');
            } else {
                messageDiv.classList.add('received');
            }

            var messageContent = document.createElement('p');
            messageContent.textContent = message.message_text;

            var timestamp = document.createElement('span');
            timestamp.classList.add('timestamp');
            timestamp.textContent = new Date(message.sent_at).toLocaleString();

            messageDiv.appendChild(messageContent);
            messageDiv.appendChild(timestamp);
            messagesContainer.appendChild(messageDiv);
        });
    }

    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Function to send a message
function sendMessage() {
    var messageText = document.getElementById('message').value;
    var csrfToken = document.querySelector('input[name="csrf_token"]').value; // Fetch the CSRF token

    if (!currentReceiverId) {
        alert('Please select a contact to send your message.');
        return;
    }

    if (messageText.trim() === '') {
        alert('Please enter a message.');
        return;
    }

    var formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('receiver_id', currentReceiverId);
    formData.append('message', messageText);
    formData.append('csrf_token', csrfToken); // Include the CSRF token in the request

    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('message').value = '';
            fetchMessages(currentConversationId);
        } else {
            alert('Error sending message: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error sending message:', error);
    });
}

// Real-time updates every 5 seconds
setInterval(function () {
    if (currentConversationId !== null) {
        fetchMessages(currentConversationId);
    }
}, 5000);

// Function to fetch and display contacts
function fetchContacts() {
    fetch('index.php?action=fetch_contacts')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error fetching contacts:', data.error);
            } else {
                displayContacts(data);
            }
        })
        .catch(error => {
            console.error('Error fetching contacts:', error);
        });
}

// Function to display contacts with radio buttons
function displayContacts(contacts) {
    var contactsList = document.getElementById('contacts-list');
    contactsList.innerHTML = '';

    if (contacts.length === 0) {
        contactsList.innerHTML = '<li>No contacts found.</li>';
    } else {
        contacts.forEach(function (contact) {
            var listItem = document.createElement('li');
            listItem.innerHTML = `<label><input type="radio" name="contact" value="${contact.user_id}" onclick="selectContact(${contact.user_id})"> ${contact.username}</label>`;
            contactsList.appendChild(listItem);
        });
    }
}

// Function to select a contact and assign currentReceiverId
function selectContact(contactId) {
    currentReceiverId = contactId;
    alert('Contact selected. You can now send messages.');
}

// Function to add a new contact
function addContact() {
    var contactUsername = document.getElementById('contact_username').value.trim();
    var csrfToken = document.querySelector('input[name="csrf_token"]').value; // Fetch the CSRF token

    if (contactUsername === '') {
        alert('Please enter a contact username.');
        return;
    }

    var formData = new FormData();
    formData.append('contact_username', contactUsername);
    formData.append('csrf_token', csrfToken); // Include the CSRF token in the request

    fetch('add_contact.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Contact added successfully!');
            document.getElementById('contact_username').value = '';
            fetchContacts(); // Refresh the contacts list after adding a new contact
        } else {
            alert('Error adding contact: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error adding contact:', error);
    });
}

