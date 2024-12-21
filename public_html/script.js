
const sendButton = document.getElementById('send-button');
if (sendButton) {
    sendButton.addEventListener('click', sendMessage);
}

const chatInput = document.getElementById('chat-input');
if (chatInput) {
    chatInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
}

function sendMessage() {
const input = document.getElementById('chat-input');
const message = input.value.trim();
if (message === '') return;

appendMessage('user', message);
input.value = '';

// Simulate bot response for demonstration purposes
setTimeout(() => {
    appendMessage('bot', 'This is a simulated response.');
}, 1000);
}

function appendMessage(sender, message) {
const chatWindow = document.getElementById('chat-window');
const messageElement = document.createElement('div');
messageElement.classList.add('chat-message', sender);
messageElement.innerHTML = `<p>${message}</p>`;
chatWindow.appendChild(messageElement);
chatWindow.scrollTop = chatWindow.scrollHeight;
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
