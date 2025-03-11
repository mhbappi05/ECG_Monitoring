document.querySelector(".chatbot-toggle").addEventListener("click", () => {
    document.querySelector(".chatbox").style.display = "block";
});

document.querySelector(".close-chat").addEventListener("click", () => {
    document.querySelector(".chatbox").style.display = "none";
});

document.getElementById("send-btn").addEventListener("click", sendMessage);
document.getElementById("chat-input").addEventListener("keypress", function (e) {
    if (e.key === "Enter") sendMessage();
});

function sendMessage() {
    const inputField = document.getElementById("chat-input");
    const message = inputField.value.trim();
    if (!message) return;

    addMessage(message, "user-message");
    inputField.value = "";
    
    setTimeout(() => {
        const response = getBotResponse(message);
        addMessage(response, "bot-message");
    }, 1000);
}

function addMessage(text, className) {
    const chatboxBody = document.getElementById("chatbox-body");
    const messageDiv = document.createElement("div");
    messageDiv.classList.add(className);
    messageDiv.textContent = text;
    chatboxBody.appendChild(messageDiv);
    chatboxBody.scrollTop = chatboxBody.scrollHeight;
}

function getBotResponse(input) {
    const responses = {
        "hello": "Hello! How can I help you with ECG monitoring?",
        "ecg": "ECG monitoring detects the electrical activity of the heart. What would you like to know?",
        "fetal ecg": "Fetal ECG helps monitor the baby's heart condition. Would you like more details?",
        "oxygen": "Oxygen levels should be between 95-100%. If it's lower, consult a doctor.",
        "temperature": "A normal body temperature is around 36.5-37.5°C.",
        "heart rate": "Normal heart rate for adults is 60-100 bpm, and for fetuses, it ranges from 120-160 bpm."
    };

    return responses[input.toLowerCase()] || "Sorry, I didn't understand. Please ask something related to health monitoring.";
}
