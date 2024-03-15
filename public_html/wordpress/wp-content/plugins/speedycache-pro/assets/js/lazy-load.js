var speedycache_lazy_load = {
	sources: [],
	osl: 0,
	scroll: false,
	init: function(){
		speedycache_lazy_load.set_source();

		window.addEventListener('load', function(){
			let observer = new MutationObserver((mutations) => {
				speedycache_lazy_load.osl = speedycache_lazy_load.sources.length;
				speedycache_lazy_load.set_source();
		
				if(speedycache_lazy_load.sources.length > speedycache_lazy_load.osl){
					speedycache_lazy_load.load_sources(false);
				}
			});

			observer.observe(document.getElementsByTagName('html')[0], {childList: true, attributes: true, subtree: true, attributeFilter: ["src"], attributeOldValue: false, characterDataOldValue: false});
			
			speedycache_lazy_load.load_sources(true);
		});
		
		let js_events = ['scroll', 'resize', 'click'];
		
		js_events.forEach((event) => {
			window.addEventListener(event, function(){
				speedycache_lazy_load.scroll=true;
				speedycache_lazy_load.load_sources(false);
			});
		});
	},
	c: function(e, pageload){
		var winH = document.documentElement.clientHeight || body.clientHeight;
		var number = 0;

		if(pageload){
			number = 0;
		}else{
			number = (winH > 800) ? 800 : 200;
			number = speedycache_lazy_load.scroll ? 800 : number;
		}

		var elemRect = e.getBoundingClientRect();
		var top = 0;
		var parentOfE = e.parentNode ? e.parentNode : false;

		if(typeof parentOfE.getBoundingClientRect == 'undefined'){
			var parentRect = false;
		}else{
			var parentRect = parentOfE.getBoundingClientRect();
		}

		if(elemRect.x == 0 && elemRect.y == 0){
			for(var i = 0; i < 10; i++){
				if(parentOfE){
					if(parentRect.x == 0 && parentRect.y == 0){
						if(parentOfE.parentNode){
							parentOfE = parentOfE.parentNode;
						}

						if(typeof parentOfE.getBoundingClientRect == 'undefined'){
							parentRect = false;
						}else{
							parentRect = parentOfE.getBoundingClientRect();
						}
					}else{
						top = parentRect.top;
						break;
					}
				}
			};
		}else{
			top = elemRect.top;
		}

		if(winH - top + number > 0){
			return true;
		}

		return false;
	},
	r: function(e, pageload){
		var self = this;
		var originalsrc,originalsrcset;

		try{
			
			originalsrc = e.getAttribute('data-speedycache-original-src');
			originalsrcset = e.getAttribute('data-speedycache-original-srcset');
			originalsizes = e.getAttribute('data-speedycache-original-sizes');
			
			if(self.c(e, pageload)){
				if(originalsrc || originalsrcset){
					if(e.tagName == 'DIV' || e.tagName == 'A' || e.tagName == 'SPAN'){
						e.style.backgroundImage = 'url(' + originalsrc + ')';
						e.removeAttribute('data-speedycache-original-src');
						e.removeAttribute('data-speedycache-original-srcset');
						e.removeAttribute('onload');
						
					}else{
						if(originalsrc){
							e.setAttribute('src', originalsrc);
						}

						if(originalsrcset){
							e.setAttribute('srcset', originalsrcset);
						}

						if(originalsizes){
							e.setAttribute('sizes', originalsizes);
						}

						if(e.getAttribute('alt') && e.getAttribute('alt') == 'blank'){
							e.removeAttribute('alt');
						}

						e.removeAttribute('data-speedycache-original-src');
						e.removeAttribute('data-speedycache-original-srcset');
						e.removeAttribute('data-speedycache-original-sizes');
						e.removeAttribute('onload');

						if(e.tagName == 'IFRAME'){
							var y = "https://www.youtube.com/embed/";

							if(navigator.userAgent.match(/\sEdge?\/\d/i)){
								e.setAttribute('src',e.getAttribute('src').replace(/.+\/main\/youtube\.html\#/, y));
							}
							
							e.onload = function(){
								if(typeof window.jQuery != 'undefined'){if(jQuery.fn.fitVids){jQuery(e).parent().fitVids({ customSelector: 'iframe[src]'});}}

								var s = e.getAttribute('src').match(/main\/youtube\.html\#(.+)/);
								if(s){
									try{
										var i = e.contentDocument || e.contentWindow;
										if(i.location.href == 'about:blank'){
											e.setAttribute('src',y+s[1]);
										}
									}catch(err){
										e.setAttribute('src',y+s[1]);
									}
								}
							}
						}
					}
				}else{
					if(e.tagName == 'NOSCRIPT'){
						if(e.getAttribute('data-type') == 'speedycache'){
							e.removeAttribute('data-type');
							e.after(e.textContent);
						}
					}
				}
			}

		}catch(error){
			console.log(error);
			console.log('==>', e);
		}
	},
	set_source: function(){
		var i = [].slice.call(document.getElementsByTagName('img'));
		var f = [].slice.call(document.getElementsByTagName('iframe'));
		var d = [].slice.call(document.getElementsByTagName('div'));
		var a = [].slice.call(document.getElementsByTagName('a'));
		var s = [].slice.call(document.getElementsByTagName('span'));
		var n = [].slice.call(document.getElementsByTagName('noscript'));

		this.sources = i.concat(f).concat(d).concat(a).concat(s).concat(n);
	},
	load_sources: function(pageload){
		var self = this;

		[].forEach.call(self.sources, function(e, index) {
			self.r(e, pageload);
		});
	}
};

document.addEventListener('DOMContentLoaded',function(){
	speedycache_lazy_load.init();
});