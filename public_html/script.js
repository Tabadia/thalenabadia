// Chat window functionality
const chatWindow = document.getElementById('chat-window');
const minimizeBtn = document.getElementById('minimize-chat');
const chatInput = document.getElementById('chat-input');
const sendBtn = document.getElementById('send-message');
const chatMessages = document.querySelector('.chat-messages');

// Minimize/maximize chat window
minimizeBtn.addEventListener('click', () => {
  chatWindow.classList.toggle('minimized');
  minimizeBtn.querySelector('i').classList.toggle('fa-minus');
  minimizeBtn.querySelector('i').classList.toggle('fa-plus');
});

// Send message on button click
sendBtn.addEventListener('click', sendMessage);

// Send message on Enter key
chatInput.addEventListener('keypress', (e) => {
  if (e.key === 'Enter') {
    sendMessage();
  }
});

function sendMessage() {
  const message = chatInput.value.trim();
  if (message) {
    // Add user message
    addMessage(message, 'user');
    chatInput.value = '';
    
    // TODO: Add your AWS Bedrock integration here
    // For now, just echo a response
    setTimeout(() => {
      addMessage("I'm sorry, I'm not connected to AWS Bedrock yet. This is just a placeholder response.", 'bot');
    }, 1000);
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
