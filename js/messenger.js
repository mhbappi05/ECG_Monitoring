// Open Messenger
document.getElementById("openMessenger").addEventListener("click", function() {
    document.getElementById("messengerContainer").style.display = "block";
    document.getElementById("doctorList").style.display = "block";
    document.getElementById("chatUI").style.display = "none";
});

// Handle Doctor Selection
document.querySelectorAll(".doctor-item").forEach(item => {
    item.addEventListener("click", function() {
        const doctorId = this.getAttribute("data-doctor-id");
        selectDoctor(doctorId);
    });
});

function selectDoctor(doctorId) {
    // Hide the doctor list
    document.getElementById("doctorList").style.display = "none";

    // Show the chat UI
    document.getElementById("chatUI").style.display = "block";

    // Fetch the doctor messages
    loadMessages(doctorId);

    // Start polling for new messages for this doctor
    startPolling(doctorId);
}

// Function to load messages (AJAX call to get chat history)
function loadMessages(doctorId) {
    // Clear previous messages
    document.getElementById("messengerBody").innerHTML = '';

    // Make an AJAX call to fetch chat history
    fetch('get_messages.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ doctor_id: doctorId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // If we have messages, append them to the messenger body
            document.getElementById("messengerBody").innerHTML = data.messages;
            document.getElementById("messengerBody").scrollTop = document.getElementById("messengerBody").scrollHeight; // Scroll to the bottom
        } else {
            document.getElementById("messengerBody").innerHTML = '<p>Error loading messages.</p>';
        }
    })
    .catch(error => {
        console.error("Error loading messages:", error);
    });
}

// Send message (this can be further enhanced with AJAX to send and receive messages)
document.getElementById("sendMessageBtn").addEventListener("click", function() {
    const message = document.getElementById("messageInput").value;
    const doctorId = document.querySelector(".doctor-item").getAttribute("data-doctor-id");
    
    if (message) {
        sendMessageToDoctor(doctorId, message);
        document.getElementById("messengerBody").innerHTML += "<div>You: " + message + "</div>";
        document.getElementById("messageInput").value = '';  // Clear the input field
    }
});

// Function to send message to doctor
function sendMessageToDoctor(doctorId, message) {
    fetch('send_message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            doctor_id: doctorId,
            message: message
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log(data); // Check the response from PHP
        if (data.status === 'success') {
            document.getElementById("messengerBody").innerHTML += "<div>You: " + message + "</div>";
        } else {
            alert('Failed to send message');
        }
    })
    .catch(error => {
        console.error("Error sending message:", error);
    });
}

// Periodic polling for new messages
let messagePollingInterval;

function startPolling(doctorId) {
    // Poll for new messages every 5 seconds
    messagePollingInterval = setInterval(() => {
        loadMessages(doctorId);  // Fetch the latest messages
    }, 3000); // Fetch messages every 5 seconds
}

// Stop polling when the messenger is closed
document.getElementById('closeMessenger').addEventListener('click', function() {
    // Hide the entire messenger container
    document.getElementById('messengerContainer').style.display = 'none';

    // Stop polling for new messages
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
    }
});
