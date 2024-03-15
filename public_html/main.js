let vh = window.innerHeight * 0.01;
document.documentElement.style.setProperty('--vh', `${vh}px`);
var typing = function(el, toRotate, period) {
	this.toRotate = toRotate;
	this.el = el;
	this.loopNum = 0;
	this.period = parseInt(period, 10) || 2000;
	this.txt = '';
	this.tick();
	this.isDeleting = false;
};
typing.prototype.tick = function() {
	var i = this.loopNum % this.toRotate.length;
	var fullTxt = this.toRotate[i];
	if (this.isDeleting) {
		this.txt = fullTxt.substring(0, this.txt.length - 1);
	} else {
		this.txt = fullTxt.substring(0, this.txt.length + 1);
	}
	this.el.innerHTML = '<span class="wrap">' + this.txt + '</span>';
	var that = this;
	var delta = 200 - Math.random() * 100;
	if (this.isDeleting) {
		delta /= 2;
	}
	if (!this.isDeleting && this.txt === fullTxt) {
		delta = this.period;
		this.isDeleting = true;
	} else if (this.isDeleting && this.txt === '') {
		this.isDeleting = false;
		this.loopNum++;
		delta = 500;
	}
	setTimeout(function() {
		that.tick();
	}, delta);
};
window.onload = function() {
	var elements = document.getElementsByClassName('typewrite');
	for (var i = 0; i < elements.length; i++) {
		var toRotate = elements[i].getAttribute('data-type');
		var period = elements[i].getAttribute('data-period');
		if (toRotate) {
			new typing(elements[i], JSON.parse(toRotate), period);
		}
	}
	var css = document.createElement("style");
	css.type = "text/css";
	css.innerHTML = ".typewrite > .wrap { border-right: 0.08em solid #D1D5DB}";
	document.body.appendChild(css);
};
$("section[id^='skills']:visible").mouseover(function() {
	$("a[id^='skillsNav'").addClass("underline");
	$("a[id^='projectsNav'").removeClass("underline");
	$("a[id^='contactNav'").removeClass("underline");
});
$("section[id^='projects']:visible").mouseover(function() {
	$("a[id^='skillsNav'").removeClass("underline");
	$("a[id^='projectsNav'").addClass("underline");
	$("a[id^='contactNav'").removeClass("underline");
});
$("section[id^='contact']:visible").mouseover(function() {
	$("a[id^='skillsNav'").removeClass("underline");
	$("a[id^='projectsNav'").removeClass("underline");
	$("a[id^='contactNav'").addClass("underline");
});

function reveal() {
	var reveals = document.querySelectorAll(".reveal");
	for (var i = 0; i < reveals.length; i++) {
		var windowHeight = window.innerHeight;
		var elementTop = reveals[i].getBoundingClientRect().top;
		var elementVisible = 50;
		if (elementTop < windowHeight - elementVisible) {
			reveals[i].classList.add("active");
		} else {
			reveals[i].classList.remove("active");
		}
	}
}

window.addEventListener("scroll", reveal);



// To check the scroll position on page load

reveal();

var cube = document.querySelector('.cube');
var cubeNav = document.querySelector('.cube-nav');
// var radioGroup = document.querySelector('.radio-group');
var currentClass = '';

function changeSide(showClass) {
    showClass = 'show-' + showClass;
    //console.log(showClass);
    if ( currentClass ) {
        cube.classList.remove( currentClass );
    }
    cube.classList.add( showClass );
    currentClass = showClass;
}
// set initial side
changeSide();

// radioGroup.addEventListener( 'change', changeSide );
// cubeNav.addEventListener('click', changeSide);