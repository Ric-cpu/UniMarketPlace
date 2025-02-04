// Toggle chat window visibility
function toggleChat() {
    const chatWindow = document.getElementById('chat-window');
    chatWindow.style.display = (chatWindow.style.display === 'none' || chatWindow.style.display === '') ? 'flex' : 'none';
}

// Handle image preview before sending
function previewImage(event) {
    const imagePreviewContainer = document.getElementById('image-preview-container');
    const imagePreview = document.getElementById('image-preview');
    const file = event.target.files[0];
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.src = e.target.result;
            imagePreviewContainer.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

// Send user message
function sendMessage() {
    const userInput = document.getElementById('user-input');
    const message = userInput.value.trim();
    
    if (!message) return;
    
    // Display user message
    displayUserMessage(message);
    
    // Create FormData
    const formData = new FormData();
    formData.append('message', message);
    
    // Send to backend
    fetch('https://chatbot-by2l.onrender.com/chat', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.response) {
            displayBotMessage(data.response);
        } else if (data.error) {
            displayBotMessage('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        displayBotMessage('Sorry, I encountered an error processing your request.');
    });
    
    // Clear input
    userInput.value = '';
}

// Display user message in chat
function displayUserMessage(message) {
    const chatMessages = document.getElementById('chat-messages');
    const userMessage = document.createElement('div');
    userMessage.className = 'message user-message';
    userMessage.textContent = message;
    chatMessages.appendChild(userMessage);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Display user image in chat
function displayUserImagePreview(imageFile) {
    const chatMessages = document.getElementById('chat-messages');
    const userMessage = document.createElement('div');
    userMessage.className = 'message user-message';
    
    const imgElement = document.createElement('img');
    imgElement.src = URL.createObjectURL(imageFile);
    imgElement.style.maxWidth = '200px';
    imgElement.style.borderRadius = '8px';
    imgElement.style.marginTop = '5px';
    
    userMessage.appendChild(imgElement);
    chatMessages.appendChild(userMessage);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Display bot response in chat
function displayBotMessage(message) {
    const chatMessages = document.getElementById('chat-messages');
    const botMessage = document.createElement('div');
    botMessage.className = 'message bot-message';
    
    // Convert markdown-style bold (**text**) to HTML bold tags
    const formattedMessage = message.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    
    // Use innerHTML instead of textContent to render HTML tags
    botMessage.innerHTML = formattedMessage;
    
    chatMessages.appendChild(botMessage);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Allow "Enter" key to send messages
document.getElementById('user-input').addEventListener('keypress', function(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        sendMessage();
    }
});

// Attach event listener for image preview
document.getElementById('image-input').addEventListener('change', previewImage);

