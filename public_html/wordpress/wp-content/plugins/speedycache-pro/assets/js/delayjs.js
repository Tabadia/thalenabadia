let speedycache_js_events = ['mouseover','click','keydown','wheel','touchmove','touchstart'];

speedycache_js_events.forEach((event) => {
	window.addEventListener(event, speedycache_delay_event, {passive: true});
});

function speedycache_delay_event(){
	
	speedycache_js_events.forEach((event) => {
		window.removeEventListener(event, speedycache_delay_event, {passive: true});
	});
	
	document.querySelectorAll('script[type="speedycache/javascript"]').forEach(async e => {
		await new Promise(resolve => speedycache_load_js(e, resolve));
	});
}

function speedycache_load_js(js, resolve){
	async_js = document.createElement('script');
	
	attr = js.getAttributeNames();
	attr.forEach(name => {
		if(name === 'type'){
			return;
		}
		
		async_js.setAttribute(name == 'data-src' ? 'src' : name, js.getAttribute(name));
	});
	
	async_js.setAttribute('type', 'text/javascript');

	async_js.addEventListener('load', resolve);

	js.after(async_js);
	js.remove();

}