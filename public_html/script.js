// Chat window functionality
const chatWindow = document.getElementById('chat-window');
const minimizeBtn = document.getElementById('minimize-chat');
const chatInput = document.getElementById('chat-input');
const sendBtn = document.getElementById('send-message');
const chatMessages = document.querySelector('.chat-messages');
const charCount = document.getElementById('char-count');

let conversationHistory = [];


// Function to update character count
function updateCharCount() {
  const currentLength = chatInput.value.length;
  charCount.textContent = currentLength;
  
  // Add visual feedback for character limit
  if (currentLength >= 450) {
    charCount.style.color = '#ff6b6b'; // Red when approaching limit
  } else if (currentLength >= 400) {
    charCount.style.color = '#ffa726'; // Orange when getting close
  } else {
    charCount.style.color = '#666'; // Default gray
  }
}

// Auto-resize textarea and update character count
chatInput.addEventListener('input', function() {
  this.style.height = 'auto';
  this.style.height = (this.scrollHeight) + 'px';
  updateCharCount();
});

// Initialize character count on page load
updateCharCount();

// Minimize/maximize chat window
minimizeBtn.addEventListener('click', () => {
  chatWindow.classList.toggle('minimized');
  minimizeBtn.querySelector('i').classList.toggle('fa-caret-up');
  minimizeBtn.querySelector('i').classList.toggle('fa-caret-down');
});

// Send message on button click
sendBtn.addEventListener('click', sendMessage);

// Send message on Enter key (but allow Shift+Enter for new line)
chatInput.addEventListener('keydown', (e) => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
});

async function sendMessage() {
  const message = chatInput.value.trim();
  
  // Check character limit
  if (message.length > 500) {
    addMessage("Message is too long. Please keep it under 500 characters.", 'bot');
    return;
  }
  
  if (message) {
    // Add user message to conversation history
    conversationHistory.push({ role: 'user', content: message });
    
    // Add user message to UI
    addMessage(message, 'user');
    chatInput.value = '';
    chatInput.style.height = 'auto';
    updateCharCount(); // Reset character count
    
    
    try {
      const response = await fetch('https://portfolio-backend-ruddy-phi.vercel.app/api/chat', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
          message,
          conversationHistory: conversationHistory.slice(-10) // Keep last 10 exchanges for context
        }),
      });

      if (!response.ok) {
        throw new Error('Network response was not ok');
      }

      const data = await response.json();
      
      // Add bot response to conversation history
      conversationHistory.push({ role: 'assistant', content: data.response });
      
      // Add bot message to UI
      addMessage(data.response, 'bot');
    } catch (error) {
      console.error('Error:', error);
      addMessage("I'm sorry, there was an error processing your request. Please try again later.", 'bot');
    }
  }
}

function addMessage(text, sender) {
  const messageDiv = document.createElement('div');
  messageDiv.className = `message ${sender}`;
  messageDiv.innerHTML = `<p>${text}</p>`;
  chatMessages.appendChild(messageDiv);
  chatMessages.scrollTop = chatMessages.scrollHeight;
}


// ! function () {
//     "use strict";
//     var e = document.querySelector(".scroll-to-top");
//     e && window.addEventListener("scroll", (function () {
//        var o = window.pageYOffset;
//        e.style.display = o > 100 ? "block" : "none"
//     }));
//     var o = document.querySelector("#mainNav");
//     if (o) {
//        var n = o.querySelector(".navbar-collapse");
//        if (n) {
//           var t = new bootstrap.Collapse(n, {
//                 toggle: !1
//              }),
//              r = n.querySelectorAll("a");
//           for (var a of r) a.addEventListener("click", (function (e) {
//              t.hide()
//           }))
//        }
//        var c = function () {
//           (void 0 !== window.pageYOffset ? window.pageYOffset : (document.documentElement || document.body.parentNode || document.body).scrollTop) > 100 ? o.classList.add("navbar-shrink") : o.classList.remove("navbar-shrink")
//        };
//        c(), document.addEventListener("scroll", c)
//     }
//  }();
