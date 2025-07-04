document.addEventListener("DOMContentLoaded", function () {
  const messageButtons = document.querySelectorAll('[data-bs-toggle="modal"]');
  const patientNameSpan = document.getElementById("patientName");
  const receiverInput = document.getElementById("receiver_id");
  const messagesContainer = document.getElementById("messages-container");
  const chatForm = document.getElementById("chatForm");
  const messageModal = new bootstrap.Modal(
    document.getElementById("messageModal")
  );

  // Open the modal and fetch the chat history when clicking on a patient
  messageButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const patientId = this.getAttribute("data-patient-id"); // Get patient ID
      const patientName = this.getAttribute("data-patient-name"); // Get patient name
      patientNameSpan.textContent = patientName; // Set patient name in modal
      receiverInput.value = patientId; // Store patient ID in hidden input
      fetchMessages(patientId); // Fetch previous messages
      startPolling(patientId); // Start polling for new messages
      messageModal.show(); // Show the modal
    });
  });

  // Stop polling when the modal is closed
  document
    .getElementById("messageModal")
    .addEventListener("hidden.bs.modal", function () {
      stopPolling();
    });

  // Submit the form to send a message
  chatForm.addEventListener("submit", function (e) {
    e.preventDefault();
    const patientId = receiverInput.value; // Get receiver ID (patient ID)
    const message = chatForm
      .querySelector('textarea[name="message"]')
      .value.trim(); // Get message content

    if (message !== "") {
      sendMessage(patientId, message); // Send message via AJAX
      chatForm.querySelector('textarea[name="message"]').value = ""; // Clear input after sending
      setTimeout(() => fetchMessages(patientId), 100); // Fetch updated messages after 0.5 seconds
    }
  });

  // Fetch messages for a specific patient
  function fetchMessages(patientId) {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "get_messages.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function () {
      if (xhr.status === 200) {
        const response = JSON.parse(xhr.responseText);
        if (response.status === "success") {
          messagesContainer.innerHTML = response.messages;
          scrollToBottom(); // Ensure it scrolls to the bottom
        } else {
          messagesContainer.innerHTML =
            '<p class="text-danger">Failed to load messages.</p>';
        }
      } else {
        console.error("Failed to fetch messages", xhr.statusText);
      }
    };
    xhr.send("patient_id=" + patientId); // Send patient ID to get messages for the selected patient
  }

  // Add polling mechanism (e.g., every 5 seconds)
  let messagePollingInterval;

  function startPolling(patientId) {
    // Poll for new messages every 5 seconds
    messagePollingInterval = setInterval(() => {
      fetchMessages(patientId);
    }, 1000); // Fetch messages every 5 seconds
  }

  // Stop polling when the modal is closed
  function stopPolling() {
    if (messagePollingInterval) {
      clearInterval(messagePollingInterval);
    }
  }

  // Send a message to the server
  function sendMessage(patientId, message) {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "send_message.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function () {
      if (xhr.status === 200) {
        const response = JSON.parse(xhr.responseText);
        if (response.status === "success") {
          console.log("Message sent successfully");
        } else {
          console.error("Message sending failed:", response.message);
        }
      } else {
        console.error("Failed to send message:", xhr.statusText);
      }
    };
    xhr.send(
      "doctor_id=" + patientId + "&message=" + encodeURIComponent(message)
    ); // Send doctor ID and message
  }

  // Scroll the message container to the bottom
  function scrollToBottom() {
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  }
});
