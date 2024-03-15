=== SpeedyCache - Cache, Optimization, Performance ===
Contributors: softaculous
Tags: cache, minify, pagespeed, seo, cdn, wordpress cache, website cache, lazy loading, mobile cache, minify css, combine css, minify js, combine js, gzip, browser caching, render blocking js, preload, widget cache, softaculous, speedycache, performance
Requires at least: 4.7
Tested up to: 6.4
Requires PHP: 7.3
Stable tag: 1.1.4
License: LGPL v2.1
License URI: http://www.gnu.org/licenses/lgpl-2.1.html


== Description ==

SpeedyCache is a WordPress cache plugin that helps you improve performace of your WordPress site by caching, minifying, and compressing your website.

You can find our official documentation at [https://speedycache.com/docs](https://speedycache.com/docs). We are also active in our community support forums on wordpress.org if you are one of our free users. Our Premium Support Ticket System is at [https://softaculous.deskuss.com](https://softaculous.deskuss.com)

[Home Page](https://speedycache.com "SpeedyCache Homepage") | [Support](https://softaculous.deskuss.com "SpeedyCache Support") | [Documents](http://speedycache.com/docs "Documents")

Other than caching SpeedyCache can also do the following:-
1. It can minify and combine CSS/JS giving even better optimization as it reduces the file sizes and reduces the HTTP requests to the server.
2. Eliminate render-blocking Javascript resources helping your website to load faster.
3. Lazy load images so that the images can be requested only if they come into the viewport.
4. GZIP files to reduce the size of the file sent from the server.

== Free Features ==
* **Caching:** Storing copies of the web pages of the desktop version. Caching happens when a user visits a webpage on your website
* **Precache:** Precache creates a cache at regular intervals of time reducing the load on the server to cache files.
* **Combine CSS:** Combines CSS files present in the header of the page reducing HTTP requests.
* **Minify CSS:** Reduces the size of the CSS files.
* **Test Mode:** Test Cache settings before pushing it to live site.
* **Automatic Cache:** If enabled it will cache immediately after a page/post is created/updated.
* **Combine JS:** Combines JS files present in the header of the page reducing HTTP requests.
* **GZIP:** It applies GZIP compression on the files hence reducing the file size sent from the server.
* **Browser Caching:** Caches the website on the browser of the client for repeating visitors.
* **DNS-Prefetch:** DNS prefetch is a technique that improves website performance by resolving domain names in advance, before they are needed.
* **Disable Emojis:** You can remove the emoji inline CSS and wp-emoji-release.min.js.
* **Exclude:** You can exclude certain Pages, User-Agent, Cookies, CSS, or JS from being cached.
* **CDN:** CDN helps improve website speed by placing the static files of your cached on their network of servers hence helping deliver content faster at any point in the world.
* **Display Swap:** Adds dispaly swap to Google font URL, so when Google font loads the font will stay visible.
* **Purge Varnish:** If enabled it will purge Varnish cache, whenever cache from SpeedyCache is purged.
* **Gravatar Cache:** Host Gravatars on your server.
* *Improve Font Rendering:** Adding CSS property of text-rendering to prioritize speed of render of Fonts.

== GET SUPPORT AND PRO FEATURES == 
Get professional support and more features to make your website load faster with [SpeedyCache](https://speedycache.com/pricing)

== Pro Features:- ==

* **Image Optimization:** Image optimization is a way to convert an image to next-gen image formats like webp which load images faster on the web.
* **Instant Page:** It loads the page just before user clicks the link, reducing the page load time.
* **Google Fonts:** Google fonts are also seen as render-blocking so this feature helps load Google fonts asynchronously.
* **Local Google Fonts:** Cache the Google fonts to be compliant with the GDPR rules.
* **Lazy Load:** Loading all assets at once can make the page load slower hence lazy load helps by only loading certain resources when they come into the viewport.
* **Minify HTML:** It removes empty lines, line breaks, minifies inline Js And Css, removes comments and space in Tags
* **Advanced Minify CSS:** Reduces CSS file size.
* **Minify JS:** Reduces JS file size.
* **Delay JS:** Delays the JS to load on user interaction to reduce or remove the issue of Unused JS.
* **Advanced Combine JS:** Combines JS files placed in the footer section helping reduce HTTP calls.
* **Render blocking JS:** Before rendering a page the browser parses the HTML to create a DOM tree and if an external script comes it has to stop and wait for the script to execute hence the rendering of the page ends up taking time, hence Render blocking JS feature helps in deferring the load of JS after the render has happened hence the first load get faster.
* **Delete Cache Stats:** Provides statistics about the cached files of Desktop or Mobile version and combined/ minified version of CSS and JS.
* **Mobile Theme:** Caches the mobile version of your website and shows that version on mobile devices.
* **Database Cleanup:** Database cleanup helps you free up your database storage from temporary data, trashed contents, and post revisions which may take a lot of your database storage.
* **PreConnect:** Preconnect improves website loading times by establishing early connections to third-party domains.
* **Preload:** Preload improves website performance by downloading resources in advance, before they are needed.
* **Critical CSS:** Extracts the CSS used by the page in the visible viewport at the time of load.
* **Unused CSS:** Removes the unused CSS, keeping the CSS that is being used by the paged, which reduces the size of CSS used on the page.
* **Object Cache:** It makes the object to persist by using redis, to improve availibility of the cache.
* **Bloat Remover:** Options to remove unnecessary features of WordPress or WooCommerce.
* **Image Dimension:** Adds dimensions to the image tag which does not have width or height, to reduce (CLS)Cumulative Layout Shift.
* **Lazy Render HTML:** User can lazy render HTML elements which are not in view-port.
* **Preload Critical Images:** Preload above-the-fold images to improve LCP(Largest Contentful paint).


== Caching ==
SpeedyCache caches the website by creating static files on the server and delivers those static files to most of the users who visit the website, The static files eliminate the heavy load of Querying the database for data hence the load of your website is faster.

You can preload as many pages as you want, and preloading caches the website in regular intervals of time to reduce the load on the server.

Deleting Cache on New/updating Post.
You can decide to delete the cache on the creation or updating of a post so that the cache can always stay updated.


== Minifying/Combining CSS and JS ==
SpeedyCache helps minify the JS and CSS hence it reduces the file sizes.
Combining JS combines the CSS and JS fines reducing the file count and making the server handle lesser requests.


== Cache Lifespan ==
Cache Lifespan is a way to schedule the deletion of cache.


== Exclude ==
Exclude is a way to prevent SpeedyCache from caching certain files/ user-agents/ cookies.


== CDN (Content Delivery Network) ==
CDN helps you host your static content on a distributed network optimized to deliver internet content faster it's not a replacement to a web host. It caches your files on the network edge and delivers the content to the user through the closest and fastest server.
SpeedyCache helps you integrate a CDN by rewriting the URLs of the static files you want to host on the CDN or in the Case of Cloudflare it helps with the purging of the cache on the Cloudflare servers.


== [Pro] Image Optimization ==
Image optimization is a way to convert your images from old formats like JPG and PNG to the new next-gen formats like webp which is designed with the web as the target platform to load images faster. webp images result in smaller and richer images that make the web faster.
We provide 3 ways to convert your images to webp.
GD(a PHP extension), Imagick(a PHP extension), and cwebp(a webp conversion utility from Google).

== [Pro] Bloat Remover ==
SpeedyCache has 12 bloat removal options which are listed below.
1. Disable Dashicons
2. Update Heartbeat
3. Limit Post Revisions
4. Disable XML-RPC
5. Disable Google Fonts
6. Disable jQuery Migrate
7. Disable RSS feeds
8. Disable Gutenberg
9. Disable OEmbeds
10. Disable Block Editor CSS
11. Disable Cart Fragments
12. Disable WooCommerce Assets


== [Pro] Database Cleanup ==
Data cleanup cleans the database by removing the following data:-
1. Post Revisions
2. Trashed Content
3. Trashed and Spam comments
4. Trackbacks and pingbacks
5. All Transient options
6. Expired Transient Options

== Frequently Asked Questions ==

= How to install SpeedyCache =
Go To your WordPress install -> Plugins -> Add New Button -> In Search Box, Look For SpeedyCache -> Click on Install.

= How will I know if my website got cached =
You can either go to the Delete Cache Tab where you will find Stats about the Cache or you can just visit your website in incognito mode and Inspect the HTML and at the last, you will find a comment saying that page got cached with time.

== Screenshots ==

1. SpeedyCache Settings page
2. SpeedyCache Delete Cache page
3. CDN integration page
4. SpeedyCache Exclude page

== Changelog ==

= 1.1.4 (15th December 2023) =
* [Security] There was a privilege check which has been fixed. (Reported by Lucio Sá)
* [Bug-Fix] There were some warnings when deleting the cache which have been fixed.
* [Improvement] While reverting all optimized images if the image count was 50+ then the images were queued one image per schedule. Which has been changed to a batch of 100 images per schedule. And the limit for schedule has been increased to 100 from 50.

= 1.1.3 (30th November 2023) =
* [Security] A subscriber could trigger a create cache request which has been fixed.
* [Bug-Fix] There was an issue with Object Cache not able to save data in Redis that has been fixed.

= 1.1.2 (16th November 2023) =
* [Task] Tested on WordPress 6.4.
* [Bug-Fix] In Lazy Load DOMSubTreeModified(which is a deprecated Browser API) has been changed to Mutation Observer.
* [Bug-Fix] Dynamic Property warnings have been fixed for PHP 8.2.

= 1.1.1 (19th October 2023) =
* [Structural Change] SpeedyCache Pro will now require the free version to be installed fore it to to work.
* [Feature] Text Rendering: It tells the browser to prioritize rendering speed over legibility and geometric precision.
* [Feature] DNS-Prefetch: It is a technique that tells the browser to resolve domain names in advance, which can speed up website loading.
* [Pro-Feature] Preconnect: It is a technique that tells the browser to establish connections to external resources in advance, which can speed up website loading.
* [Pro-Feature] Preload Resources: It is a technique that tells the browser to start downloading resources in advance.
* [Pro-Feature] Unused CSS: Removes the unused CSS to reduce the size of CSS being loaded on the page.
* [Task] We have created a new section in Settings tab "Preloading" it contains all the options related to preloading. Hence Instant Page, Preloading Critical Images, have been shifted to this section.
* [Task] Added option to set number of images to skip from lazy loading from top of the page.
* [Task] Instant page has been upgraded and improved.
* [Bug-Fix] When Mobile Theme was enabled a .mobile file was being created as a cache which has been fixed.

= 1.1.0 (8th August 2023) =
* [Feature] Localize Gravatar: Caches Gravatar on your server.
* [Pro-Feature] Preload Critical Images: Preloads above-the-fold images to improve LCP(Largest Contentful paint).
* [Pro-Feature] Lazy Render HTML elements: It helps in reducing the rendering time of the HTML elements, which are not in the viewport.

= 1.0.9 (28th July 2023) =
* [Pro-Feature] Image Dimensions: Add Image dimensions to Local Images if height or width is not present for it.
* [Bug-Fix] Precache was causing a fatal error for some users while uploading via media, which has been fixed now.
* [Bug-Fix] Delay JS was breaking HTML for some users that has been fixed.

= 1.0.8 =
* [Tweak] Updated a nag, to notify user to test Page Speed.
* [Bug-Fix] There was an issue with Combine CSS that has been fixed.

= 1.0.7 =
* [Feature] Added Test Mode, for a user to test the options before pushing it to the live site.
* [Feature] Test Score, now user can get Pageinsight scores of their website on how it will score with the cache settings enabled.
* [Feature][Pro] Bloat Remover, we have added more than 10 options to remove bloat and speed up your WordPress site.
* [Bug-Fix] There was an issue with Minify JS it was breaking some sites, but that has been fixed.
* [Bug-Fix] Instant Page was conflicting with JS of other files which has been fixed.

= 1.0.6 =
* [Bug-Fix] There was an issue while activating the Plugin for some users, default settings weren't getting saved properly.
* [Bug-Fix] SpeedyCache was minifying already minified CSS/JS files, that has been fixed.
* [Tweak] Added a reminder to enable SpeedyCache if it is activated.

= 1.0.5 =
* [Feature][PRO] Object Cache: Reduce the count of SQL queries you need to make by caching it in a persistent Cache like Redis.
* [Bug-Fix] There was an issue in Preload settings in which the order of the preload was not getting updates as expected which has been fixed.

= 1.0.4 =
* [Bug-Fix] There was an undefined index issue with Plugins that have custom post types that have been fixed.
* [Bug-Fix] WP_User_Query::query was called incorrectly PHP Notice has been fixed.
* [Bug-Fix] There was a warning related to an index Brand Data.
* [Bug-Fix] [Pro] The License status was not updating immediately after the license was submitted that has been fixed.
* [Task] Tested with WordPress 6.2.

= 1.0.3 =
* [Bug-Fix] When saving a product in Woocommerce if the cache was enabled on SpeedyCache, the user was getting redirected to a JSON response page, which has been fixed.
* [Bug-Fix] When saving a product there was a Security Check failed warning, that has been fixed. 
* [Bug-Fix] There was an issue with deleting SpeedyCache, it has been fixed.
* [Bug-Fix] Disabling the Cache for the single page was not working, that has been fixed.

= 1.0.2 =
* [Feature] Purging Varnish cache when cache from SpeedyCache is purged.
* [Feature][Pro] Display swap for Google fonts to keep fonts visible on a load of Google fonts.
* [Feature][Pro] Critical CSS: Now SpeedyCache can extact critical CSS from your page to remove Render Blocking CSS to improve page speed.
* [Tweak] The Cache folder structure has been updated, to follow common practice.
* [Bug-Fix] Cloudflare prompt use to pop-up continuously if user website was being proxied through Cloudflare that has been fixed by converting that popup to an alert.
* [Bug-Fix] PHP 8.2 warnings and deprecations have been fixed.
* [Bug-Fix] There was an issue while clearing Cache if you have diabled cache in a metabox.
* [Bug-Fix][Pro] There was an issue with LazyLoading Iframes that has been fixed.
* [Bug-Fix][Pro] There was an issue with Delete Cache logs that has been fixed.
* [Bug-Fix][Pro] There was an issue with linking License that has been fixed.

= 1.0.1 =
* [Bug-Fix] Cache folder was not being created at activation that has been fixed.
* [Bug-Fix] There was a PHP Warning that has been fixed.

= 1.0.0 =
* First release
