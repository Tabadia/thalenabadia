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

	$('#titleNav').css('text-decoration', 'underline');
	$('.titleNavDot').css('border-width', '4px');

	var sideNavWidth = document.getElementById('sideNav').offsetWidth;
	document.getElementById('page').style.paddingLeft = `${sideNavWidth}px`;
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
$("section[id^='title']:visible").mouseover(function() {
	$("a[id^='titleNav'").addClass("underline");
	$("a[id^='skillsNav'").removeClass("underline");
	$("a[id^='projectsNav'").removeClass("underline");
	$("a[id^='contactNav'").removeClass("underline");

});
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

var cubes = document.querySelectorAll('.cube');

var currentClass = '';

function changeSide(showClass) {
	showClass = 'show-' + showClass;
	if (currentClass) {
		cubes.forEach(cube => {
			cube.classList.remove(currentClass);
		});
	}
	cubes.forEach(cube => {
		cube.classList.add(showClass);
	});
	currentClass = showClass;
}
changeSide();

// radioGroup.addEventListener( 'change', changeSide );
// cubeNav.addEventListener('click', changeSide);

$(document).ready(function() {
	$(window).on('scroll', function() {
	  var scrollPos = $(document).scrollTop();
	  var windowHeight = $(window).height();
  
	  // Adjusted scroll positions for accurate detection
	  var titlePos = $('#title').offset().top - 50;
	//   var aboutPos = $('#about').offset().top - 50;
	  var skillsPos = $('#skills').offset().top - 50;
	  var projectsPos = $('#projects').offset().top - 50;
	  var contactPos = $('#contact').offset().top - windowHeight + 50;
  
	  if (scrollPos >= contactPos) {
		// Underline "Contact" in sidebar
		$('.cubeNav').css('text-decoration', 'none'); // Remove underline from all sidebar items
		$('.navDot').css('border-width', '2px');
		$('#contactNav').css('text-decoration', 'underline');
		$('.contactNavDot').css('border-width', '4px');
		changeSide('top') 
	  } else if (scrollPos >= projectsPos) {
		// Underline "Projects" in sidebar
		$('.cubeNav').css('text-decoration', 'none'); // Remove underline from all sidebar items
		$('.navDot').css('border-width', '2px');
		$('#projectsNav').css('text-decoration', 'underline');
		$('.projectsNavDot').css('border-width', '4px');
		changeSide('right') 
	  } else if (scrollPos >= skillsPos) {
		// Underline "Skills" in sidebar
		$('.cubeNav').css('text-decoration', 'none'); // Remove underline from all sidebar items
		$('.navDot').css('border-width', '2px');
		$('#skillsNav').css('text-decoration', 'underline');
		$('.skillsNavDot').css('border-width', '4px');
		changeSide('bottom') 
	//   } else if (scrollPos >= aboutPos) {
	// 	// Underline "About" in sidebar
	// 	$('.cubeNav').css('text-decoration', 'none'); // Remove underline from all sidebar items
	// 	$('.navDot').css('border-width', '2px');
	// 	$('#aboutNav').css('text-decoration', 'underline');
	// 	$('.aboutNavDot').css('border-width', '4px');
	  } else {
		// Underline "Title" in sidebar by default when at the top
		$('.cubeNav').css('text-decoration', 'none'); // Remove underline from all sidebar items
		$('.navDot').css('border-width', '2px');
		$('#titleNav').css('text-decoration', 'underline');
		$('.titleNavDot').css('border-width', '4px');
		changeSide('front') 
	  }
	});
  }
);
  
function createDashedBorderDiv() {
	// Create new div and add dashed-border class
	var div = document.createElement('div');
	div.className = 'dashed-border';

	// Get the body's dimensions
	var body = document.body;
	var bodyRect = body.getBoundingClientRect();
	var sideNavWidth = document.getElementById('sideNav').offsetWidth;

	// Set random size and position within the body
	div.style.width = Math.random() * 100 + 'px'; // Shorter average grid size
	div.style.height = Math.random() * 100 + 'px'; // Shorter average grid size
	div.style.right = Math.random() * (bodyRect.width - 100 - sideNavWidth) + 'px'; // Within body width
	div.style.top = Math.random() * (bodyRect.height - 100) + 'px'; // Within body height

	// Set random border
	var borders = [['borderLeft', 'borderTop'], ['borderTop', 'borderRight'], ['borderRight', 'borderBottom'], ['borderBottom', 'borderLeft']];
	var borderPair = borders[Math.floor(Math.random() * borders.length)];
	var borderCount = Math.random() > 0.75 ? 2 : 1; // Mostly one border, sometimes two
	for (var i = 0; i < borderCount; i++) {
		div.style[borderPair[i]] = '1px dashed #fff';
	}

	div.style.zIndex = -1;

	// Add to body
	body.appendChild(div);

	// Fade in
	setTimeout(function() {
		div.style.opacity = 1;
	}, 0);

	// Fade out and remove after 1 second
	setTimeout(function() {
		div.style.opacity = 0;
		setTimeout(function() {
			body.removeChild(div);
		}, 750); // Shorter fade out time
	}, 2000); // Shorter lifespan

	// Create a new div more frequently
	setTimeout(createDashedBorderDiv, 100); // Quarter second delay
}

// Start the process
createDashedBorderDiv();