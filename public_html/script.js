// Chat window functionality
const chatWindow = document.getElementById('chat-window');
const minimizeBtn = document.getElementById('minimize-chat');
const chatInput = document.getElementById('chat-input');
const sendBtn = document.getElementById('send-message');
const chatMessages = document.querySelector('.chat-messages');
const suggestionContent = document.querySelector('.suggestion-content');

const suggestions = [
  "AI/ML Projects",
  "Game Dev",
  "AWS Experience",
  "Research",
  "Full Stack"
];

const questions = {
  'AI/ML Projects': "What projects have you worked on with AI/ML?",
  'Game Dev': "Tell me about your game development experience",
  'AWS Experience': "What's your experience with AWS and cloud services?",
  'Research': "What are your current research interests?",
  'Full Stack': "What technologies do you use for full-stack development?"
};

let currentIndex = 0;

function applyWaveGlow(text) {
  // Clear previous content
  suggestionContent.textContent = '';
  
  // Create spans for each letter
  text.split('').forEach((letter, index) => {
    const span = document.createElement('span');
    span.textContent = letter;
    span.className = 'glow-letter';
    span.style.animationDelay = `${index * 0.05}s`;
    // Add proper spacing for space characters
    if (letter === ' ') {
      span.style.width = '0.25em';
      span.style.display = 'inline-block';
      span.style.verticalAlign = 'bottom';
    }
    suggestionContent.appendChild(span);
  });
}

function cycleSuggestion() {
  // Update text with wave glow
  currentIndex = (currentIndex + 1) % suggestions.length;
  applyWaveGlow(suggestions[currentIndex]);
}

// Start cycling suggestions
setInterval(cycleSuggestion, 3000);

// Add click handler to suggestion
document.getElementById('cycling-suggestion').addEventListener('click', () => {
  const question = questions[suggestions[currentIndex]];
  if (question) {
    chatInput.value = question;
    chatInput.style.height = 'auto';
    chatInput.style.height = (chatInput.scrollHeight) + 'px';
    chatInput.focus();
  }
});

// Auto-resize textarea
chatInput.addEventListener('input', function() {
  this.style.height = 'auto';
  this.style.height = (this.scrollHeight) + 'px';
});

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
  if (message) {
    // Add user message
    addMessage(message, 'user');
    chatInput.value = '';
    chatInput.style.height = 'auto';
    
    // Hide suggestions after first message
    const suggestionsContainer = document.querySelector('.suggestions-container');
    if (suggestionsContainer) {
      suggestionsContainer.style.display = 'none';
    }
    
    try {
      const response = await fetch('https://portfolio-backend-ruddy-phi.vercel.app/api/chat', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ message }),
      });

      if (!response.ok) {
        throw new Error('Network response was not ok');
      }

      const data = await response.json();
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

// Suggested questions cycling
function cycleQuestions() {
  const questions = document.querySelectorAll('.question-slide');
  let currentIndex = 0;

  function showNextQuestion() {
    questions[currentIndex].classList.remove('active');
    currentIndex = (currentIndex + 1) % questions.length;
    questions[currentIndex].classList.add('active');
  }

  // Change question every 3 seconds
  setInterval(showNextQuestion, 3000);

  // Add click handlers to questions
  questions.forEach(question => {
    question.addEventListener('click', () => {
      document.getElementById('chat-input').value = question.textContent;
      document.getElementById('chat-input').focus();
    });
  });
}

// Initialize question cycling when chat is opened
document.getElementById('minimize-chat').addEventListener('click', () => {
  const chatWindow = document.getElementById('chat-window');
  if (chatWindow.classList.contains('minimized')) {
    cycleQuestions();
  }
});

// Initial cycle if chat is not minimized
if (!document.getElementById('chat-window').classList.contains('minimized')) {
  cycleQuestions();
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
