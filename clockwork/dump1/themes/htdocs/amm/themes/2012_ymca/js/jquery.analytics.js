/* 
Author: Kevin O'Brien
Email: kevin@clockwork.net
Company: Clockwork Acive Media Systems
Version: 1.8.2
Last Modified: 12.16.11
*****************************
ref:	Cross Domain: http://code.google.com/apis/analytics/docs/tracking/gaTrackingSite.html
		Commerce: http://www.google.com/support/forum/p/Google%20Analytics/thread?tid=5003ffffedf3ee6c&hl=en
		Cookies: http://code.google.com/apis/analytics/docs/concepts/gaConceptsCookies.html
Uses
****
== V1.0 ==
* one account (this.s.UA = '')
* multiple accounts (this.s.UA = [])
* cross domain (this.s.domains.length > 1)
* cross domain links that open in a new window
* cross domain across non combined root level domains (no objects in this.s.domains)
* cross domain across non combined sub domains (no objects in this.s.domains)
* cross domain across combined sub domains (objects in this.s.domains using {entity:'',subs:['']})
	* this resets to domain property to the entity level and doesn't need to pass longer url on links and forms
* setting to set subdomains to track as their parent
* cross domain on multiple accounts
* All cross domain and multiple accounts work in same or new window
* virtual page views for:
	* external links (this.s.track_external_links)
	* assets (this.s.asset_extentions)
	* assets track in AMM preview
* All virtual page views in same window or new or frame name
* All virtual page views work for multiple accounts
* ecommerce (also for multiple accounts)
* forms across multiple domains in same window
* forms across multiple domains in new window / iframe
* forms across multiple domains with multiple accounts same / new window / frame
* In all domain matching ://www. = :// (this.s.domains, links, forms)
* Track event to all active ua accounts through method
== V1.1 ==
* Only track specific domains
* Exclude sub domains from tracking
== V1.2 ==
* Track virtual page views for # links
* Disable cross domain tracking if this.o.domains is defined but does not contain the current domain
* Bug fix: links with nested html tags caused an error because of event bubbling
== V1.3 ==
* Special characters no longer through an error
* Don't track links with href starting with "javascript:"
== V1.4 ==
* Adding prefix settings for vpv, asset, hash links, external forms not in cross domain
* Adding asset extensions xlsx, js, css, eot, svg, ttf, woff, otf
* Debug Mode setting for alerting on same page views
* Malto link tracking, extendable for future protocols
* External form submissions not in cross domain definition as vpv
== V1.5 ==
* Fixed Bug in get form cross domain tracking
* Fixed bug recognizing asset links in the amm in preview
* Matching cross domains with ?'s and #'s
== V1.6 ==
* Added this.s.no_track_class
* Imporved matching for disabled cross domain matching for absolute urls to the same domain
* Hash links don't need to offset default action, interferes with js events
== V1.7 ==
* Adding Custom Variable Tracking
* Adding Social tracking method and auto configuration
== V1.8 ==
* Fixing social tracking, updated events listened to
* 1.8.1 fixed same domain matching with cross domain turned on
* 1.8.2 asset absolute links to same domain track as asset not external

TODO
****
* AMM transactions for reg blocks
* *. for subdomains
* setting to track as events instead of pageviews
* Cross Domain for Sub directories
* Corss domain for iframes

Examples
********
// REQUIRED in the <head> of the document
var cwGA = jQuery(document).analytics({
	'UA': ['5653468-4','XXXXX-X'] // required [] or ''
});
// Cross domain tracking example to add to init code:
	'domains': [{entity: 'clockwork.net', subs: ['dev1', 'dev2']}, 'dev3.clockwork.net', 'dev4.clockwork.net']
// Optionally call anywhere after code above
cwGA.track_virtual('asd');
cwGA.track_transaction(
	[
		// Item 1
		['001', '90', 'Product 1', 'Category 1', '32.95', '1'],
		// Item 2
		['001', '92', 'Product 2', 'Category 1', '17.04', '1']
	],
	// Transaction
	['001', 'Example Store', '49.99', '2.30', '5.00', 'Minneapolis', 'Minnesota', 'USA']
); //http://code.google.com/apis/analytics/docs/tracking/gaTrackingEcommerce.html
cwGA.track_event('category', 'action', 'label', 1); //http://code.google.com/apis/analytics/docs/tracking/eventTrackerGuide.html

*/

(function ($) {

	$.fn.analytics = function (s) {
		return new $.analytics(this, s);
	};

	$.analytics = function (el, s) {

		// Settings
		this.s = $.extend({
			UA:						'UA-XXXXX-X',
			d:						window.document.domain,
			domains:				[],
			include_only:			null,
			exclude_subdomains:		['preview', 'dev', 'test', 'stage', 'review', 'demo', 'train'],
			asset_extentions:		['pdf', 'txt', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'jpg', 'jpeg', 'png', 'gif', 'psd', 'ai', 'eps', 'zip', 'xml', 'json', 'avi', 'mp4', 'mp3', 'mov', 'mpeg', 'wmv', 'rtf', 'swf', 'flv', 'js', 'css', 'eot', 'svg', 'ttf', 'woff', 'otf'],
			track_alt_protocols:	['mailto', 'spotify', 'ftp', 'file', 'tel'],
			vpv_prefix:				'/vpv/',
			track_external_links:	true,
			external_prefix:		'external/',
			asset_prefix:			'asset/',
			hash_prefix:			'hash/',
			external_form_prefix:	'form/',
			debug:					false,
			debug_mode:				function(message){ console.info(message); },
			track:					true,
			no_track_class:			'ga_notrack',
			custom_vars:			[],
			auto_social:			false,
			social_page_url:		null,
			enable_facebook:		true,
			enable_twitter:			true
		}, s);

		if(!this.s.track) return;

		// Variables
		var o						= this;
		this.$t						= null;
		window._gaq					= window._gaq || [];
		this.current_domain_state	= null;
		this.track_multi			= this.s.UA.constructor === Array ? true : false;
		this.cross_domain_disabled	= false;

		// Process Variables
		this.s.UA = this.track_multi ? this.s.UA : [this.s.UA];

		// Init
		this.setup();
		$(document).ready(function () { o.$t = $(el); o.init(); });

		return this;
	};

	$.analytics.prototype = {

		setup: function () {
			var self = this;

			if(!this.s.track) return;

			// Include Specific Domains Only
			if(this.s.include_only) {
				var inlcude_domain = this.match_domain(this.s.d, this.s.include_only);
				if(!inlcude_domain[0]) {
					this.s.track = false;
					if(this.s.debug) this.s.debug_mode('Current Domain not part of this.s.include_only');
					return;
				}
			}

			// Exclude Sub Domains
			if(this.s.exclude_subdomains) {
				var domain_parts = this.s.d.split('.');
				var domain_i;
				for(var ex_i = 0; ex_i < this.s.exclude_subdomains.length; ex_i++) {
					for(domain_i = 0; domain_i < domain_parts.length; domain_i++) {
						if(domain_parts[domain_i] == this.s.exclude_subdomains[ex_i]) {
							if(this.s.debug) this.s.debug_mode('Tracking off: current domain contains an excluded sub domain in this.s.exclude_subdomains: '+this.s.exclude_subdomains[ex_i]);
							this.s.track = false;
							return;
						}
					}
				}
			}

			// Activate Each Tracking Code
			for (var i = 0; i < this.s.UA.length; i++) {
				var pre = i == 0 ? '' : 't'+(i+1)+'.';

				// Set profile
				window._gaq.push([pre+'_setAccount', this.s.UA[i]]);

				// Cross domain tracking
				if(this.s.domains.length > 0) {
					this.current_domain_state = this.match_domain(this.s.d);
					if(this.current_domain_state[1]) {
						window._gaq.push([pre+'_setDomainName', this.current_domain_state[1]]);
						if(this.s.debug) this.s.debug_mode('_setDomainName set to: '+this.current_domain_state[1]);
						window._gaq.push([pre+'_setAllowLinker', true]);
						if(this.s.debug) this.s.debug_mode('_setAllowLinker set to true');
						if(this.s.debug) this.s.debug_mode('Cross domain active');
					}else if(this.s.debug) {
						this.cross_domain_disabled = true;
						this.s.debug_mode('Error: Cross domain defined but current domain not included, deactivating cross domain');
						this.s.debug_mode('current domain: '+this.s.d);
						this.s.debug_mode('cross domain definition: '+this.s.domains);
					}
				}
				
				// Track Custom vars BEFORE initial pageview
				for(var i2=0; i2 < this.s.custom_vars.length; i2++) {
					this.track_custom(this.s.custom_vars[i2][0], this.s.custom_vars[i2][1], this.s.custom_vars[i2][2], this.s.custom_vars[i2][3]);
					if(this.s.debug) this.s.debug_mode('tracking custom variable: '+this.s.custom_vars[i2][0]+ ' ' +this.s.custom_vars[i2][1]+ ' ' +this.s.custom_vars[i2][2]+ ' ' +this.s.custom_vars[i2][3]);
				}

				// Track page view for current page
				window._gaq.push([pre+'_trackPageview']);
				if(this.s.debug) this.s.debug_mode(this.s.UA[i]+' active!');
			}

			// Default async embed code
			(function() {
				var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			})();
			
			// Facebook enabling
			// http://developers.facebook.com/docs/reference/javascript/
			if(self.s.auto_social) {
				if(this.s.enable_facebook) {
					window.fbAsyncInit = function () {
						self.setup_social_facebook(self.s.social_page_url);
						$(document).ready(function(){ $('body').prepend('<div id="fb-root"></div>'); })
					};
					(function(d, s, id) {
					  var js, fjs = d.getElementsByTagName(s)[0];
					  if (d.getElementById(id)) return;
					  js = d.createElement(s); js.id = id;
					  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";
					  fjs.parentNode.insertBefore(js, fjs);
					}(document, 'script', 'facebook-jssdk'));
				}
				
				// Twitter enabling
				// https://dev.twitter.com/docs/intents/events
				if(this.s.enable_twitter) {
					window.twttr = (function (d,s,id) {
						var t, js, fjs = d.getElementsByTagName(s)[0];
						if (d.getElementById(id)) return; js=d.createElement(s); js.id=id;
						js.src="//platform.twitter.com/widgets.js"; fjs.parentNode.insertBefore(js, fjs);
						return window.twttr || (t = { _e: [], ready: function(f){ t._e.push(f) } });
					}(document, "script", "twitter-wjs"));
					
					window.twttr.ready(function (twttr) {
						self.setup_social_twitter(self.s.social_page_url);
					});
				}
			}
		},

		init: function() {
			
			if(!this.s.track) return;
			var self = this;
			$(document).ready(function(){

				// Convert all links
				$(self.$t).find('a:not(a[href^="javascript:"], a.'+self.s.no_track_class+')').each(function(){
					self.convert_link(this);
				});

				// Convert all forms
				$(self.$t).find('form:not(form.'+self.s.no_track_class+')').each(function(){
					self.convert_form(this);
				});
				
			});

		},

		convert_link: function(target) {
			
			if(!this.s.track) return;
			target = $(target);
			var href = target.attr('href');
			if(!href) return;
			var parts = href.split('/');
			if(parts[0] == 'http:' || parts[0] == 'https:') {
				var a_status = this.cross_domain_disabled ? [null] : this.match_domain(parts[2]);
			}
			if((parts[0] == 'http:' || parts[0] == 'https:') && !a_status[4]) {
				// External link found
				if(!a_status[0]) {
					// Link domain is NOT part of cross domain definition
					if(!this.s.track_external_links) return;
					parts.splice (0,2);
					parts = this.s.external_prefix+parts.join('/');
					this.link_virtual(target, href, parts)
				}else {
					// Link IS part of cross domain definition
					if(a_status[2] == this.current_domain_state[2]) return;
					if(target.attr('target') && (target.attr('target') != '_self')) target.click(function(e){ e.preventDefault(); window.open(window._gat._getTrackerByName()._getLinkerUrl($(target).attr('href')),$(target).attr('target')); });
						else target.click(function(e){ e.preventDefault(); window._gaq.push(['_link', target.attr('href')]); });
				}
			}else {
				// Virtual page views for assets with extensions in this.s.asset_extentions
				path = href.split('?')[0].split('#')[0];
				// AMM specific preview links
				if(path == '/amm/author/lookup_page.php') path = href.split('&url=')[1].split('&')[0].split('#')[0].split('%3F')[0];
				parts = path.split('.');
				extension = parts[parts.length-1];
				// Set asset links for virtual page views
				for( var i = 0; i < this.s.asset_extentions.length; i++ ) {
					if(extension == this.s.asset_extentions[i]) {
						this.link_virtual(target, href, this.s.asset_prefix+href);
					}
				}
				// Set hash links for virtual page views
				if(href[0] == '#') {
					href = document.location.pathname + document.location.search + href;
					if(!(target.attr('target') && (target.attr('target') != '_self'))) {
						this.link_virtual(target, href, this.s.hash_prefix+href, true);
					}
				}
				// Mailto links
				var protical = href.split(':')[0];
				if(protical) {
					for(var i = 0; i < this.s.track_alt_protocols.length; i++ ) {
						if(protical == this.s.track_alt_protocols[i]) this.link_virtual(target, href, this.s.track_alt_protocols[i]+'/'+href);
					}
				}
			}
		},

		convert_form: function(target) {
			
			if(!this.s.track) return;
			var self = this;
			try {
				target = $(target);
				var action = target.attr('action');
				if(!action) return;
				var parts = action.split('/');
				if(parts[0] == 'http:' || parts[0] == 'https:') {
					var f_status = this.cross_domain_disabled ? [null] : this.match_domain(parts[2]);
					if(f_status[0]) {
						// form IS part of cross domain definition
						if(f_status[2] == this.current_domain_state[2]) return;
						var method = target.attr('method').toLowerCase();
						if( method == 'get' ) {
							target.submit(function(e){ _gaq.push(['_linkByPost', e.target, true]); });
						}else {
							target.submit(function(e){ _gaq.push(['_linkByPost', e.target]); });
						}
					}else {
						if(f_status[4]) {
							if(this.s.debug) this.s.debug_mode('Form skipped, absolute but links to same domain: '+target);
							return;
						}
						target.submit(function(e){
							self.track_virtual(self.s.external_prefix+self.s.external_form_prefix+this.action)
						});
					}
				}
			}catch(err){
				if(self.s.debug) {
					self.s.debug_mode('error converting form: '+err);
					self.s.debug_mode(target);
				}
			}
		},

		match_domain: function(url, against) {
			/* returns:
				[0]:
					1: url matches a parent entity
					2: url matches a sub of a parent entity
					3: url matches a unique domain string
					4: url is absolute to same domain
					null: not part of cross domain tracking defined in this.s.domains
				[1]: domain value to set in GA init code (entity domain)
				[2]: matched index in this.s.domains[]
				[3]: matched sub index of entity
				[4]: url matches current domain
			*/
			if(!this.s.track) return;
			var against = against ? against : this.s.domains;

			url = this.remove_www(url);

			var same_domain = this.remove_www(this.s.d) == url ? true : false;
			
			for( var i = 0; i < against.length; i++ ) {
			
				if(against[i].entity) {
					var entity = this.remove_www(against[i].entity)
					if(entity == url) {
						// Matches a parent domain with sub domains tracked as part of it
						return [1, entity, i, false, same_domain];
						break;
					}else {
						var matches_sub = false;
						for( var i2 = 0; i2 < against[i].subs.length; i2++ ) {
							if((against[i].subs[i2]+'.'+entity) == url) {
								// Matches a sub domain that tracks as part of it's parent
								return [2, entity, i, i2, same_domain];
								break
							}
						}
						if(matches_sub) break;
					}
				}else {
					if(this.remove_www(against[i]) == url) {
						// Matches a domain with no grouped subdomains
						return [3, 'none', i, false, same_domain];
						break;
					}
				}
			}
			return [null, null, null, null, same_domain];

		},
		
		remove_www: function(url) {
			var url_parts = url.split('.');
			if(url_parts[0] == 'www') {
				url_parts.splice(0,1)
				url = url_parts.join('.');
			}
			url = url.split('/')[0].split('?')[0].split('#')[0];
			
			return url;
		},

		link_virtual: function(target, destination, view, dont_wait) {
			/*
				target: Link Element to listen to
				destination: Location to send the user to (actual link)
				view: What Google Analytics shows the view as (including prefixes except for s.vpv_prefix)
			*/
			if(!this.s.track) return;
			var self = this;
			target = $(target);
			view = view ? view : destination;
			var track_view = '';
			// First: link in new tab; Second: link in same tab
			if(target.attr('target') && (target.attr('target') != '_self')) {
				target.click(function(e){
					e.preventDefault();
					for( var i = 0; i < self.s.UA.length; i++ ) {
						var pre = i == 0 ? '' : 't'+(i+1)+'.';
						window._gaq.push([pre+"_trackPageview", self.s.vpv_prefix+view]);
					}
					if(self.s.debug) self.s.debug_mode('virtual link for new window tracked to: '+self.s.vpv_prefix+view);
					window.open(destination, target.attr('target'));
				});
			}else {
				target.click(function(e){
					for( var i = 0; i < self.s.UA.length; i++ ) {
						var pre = i == 0 ? '' : 't'+(i+1)+'.';
						window._gaq.push([pre+"_trackPageview", self.s.vpv_prefix+view]);
					}
					if(self.s.debug) self.s.debug_mode('virtual link tracked to: '+self.s.vpv_prefix+view);
					if(!dont_wait) {
						e.preventDefault();
						setTimeout(function(){ document.location = destination; }, 100);
					}
				});
			}
		},
		
		// For Social tracking twitter events
		extract_param_from_uri: function(uri, paramName) {
			if (!uri) return;
			
			var query = decodeURI(uri.split('#')[1]);
			// Find url param.
			paramName += '=';
			var params = query.split('&');
			for (var i = 0, param; param = params[i]; ++i) {
				if (param.indexOf(paramName) === 0) {
					return unescape(param.split('=')[1]);
				}
			}
		},
		
		setup_social_facebook: function(opt_pageUrl) {
			var self = this;
			
			// Setup Facebook
			// http://developers.facebook.com/docs/reference/plugins/like/
			// <div class="fb-like" data-send="true" data-width="450" data-show-faces="true"></div>
			if(self.s.enable_facebook) {
				try {
					FB.Event.subscribe('edge.create', function(targetUrl) {
						self.track_social('facebook', 'like', targetUrl, opt_pageUrl);
						if(self.s.debug) self.s.debug_mode('Facebook Like captured');
					});
					FB.Event.subscribe('edge.remove', function(targetUrl) {
						self.track_social('facebook', 'unlike', targetUrl, opt_pageUrl);
						if(self.s.debug) self.s.debug_mode('Facebook Unlike captured');
					});
					FB.Event.subscribe('message.send', function(targetUrl) {
						self.track_social('facebook', 'send', targetUrl, opt_pageUrl);
						if(self.s.debug) self.s.debug_mode('Facebook Send captured');
					});
				} catch(e) {
					if(self.s.debug) self.s.debug_mode('Social Tracking enabled but Facebook has an error:' + e);
				}
			}
		},
		
		
		setup_social_twitter: function(opt_pageUrl) {
			var self = this;
			
			// Setup Twitter
			// https://dev.twitter.com/docs/tweet-button
			// https://dev.twitter.com/docs/intents/events
			// <a href="https://twitter.com/share" class="twitter-share-button" data-lang="en">Tweet</a>
			if(self.s.enable_twitter) {
				try {
					twttr.events.bind('tweet', function(intent_event) {
						if (intent_event) {
							var targetUrl = document.location;
							if (intent_event.target && intent_event.target.nodeName == 'IFRAME') {
								targetUrl = self.extract_param_from_uri(intent_event.target.src, 'url');
							}
							self.track_social('twitter', 'tweet', targetUrl, opt_pageUrl);
							if(self.s.debug) self.s.debug_mode('Twitter event captured: '+targetUrl);
						}
					});
					twttr.events.bind('click', function(intent_event){
						if (intent_event) {
							self.track_social('twitter', 'click', document.location, opt_pageUrl);
							if(self.s.debug) self.s.debug_mode('Twitter click event');
						}
					});
					twttr.events.bind('retweet', function(intent_event){
						if (intent_event) {
							var label = intent_event.data.source_tweet_id;
							self.track_social('twitter', 'retweet', label, opt_pageUrl);
							if(self.s.debug) self.s.debug_mode('Twitter retweet event for tweet id '+label);
						};
					});
					twttr.events.bind('favorite', function(intent_event){
						if (intent_event) {
							var label = intent_event.data.tweet_id;
							self.track_social('twitter', 'favorite', label, opt_pageUrl);
							if(self.s.debug) self.s.debug_mode('Twitter favorite event for tweet id '+label);
						};
					});
					twttr.events.bind('follow', function(intent_event){
						if (intent_event) {
							var label = intent_event.data.user_id + " (" + intent_event.data.screen_name + ")";
							self.track_social('twitter', 'follow', label, opt_pageUrl);
							if(self.s.debug) self.s.debug_mode('Twitter follow event for user '+label);
						};
					});
				} catch (e) {
					if(this.s.debug) this.s.debug_mode('Social Tracking enabled Twitter had an error:' + e);
				}
			}
		},

		/******
		Here On:
			Available for anyone to track to all active UA codes easily.
		********************/
		
		track_virtual: function(view) {
			if(!this.s.track || !view) return;
			for( var i = 0; i < this.s.UA.length; i++ ) {
				var pre = i == 0 ? '' : 't'+(i+1)+'.';
				window._gaq.push([pre+"_trackPageview", this.s.vpv_prefix+view]);
			}
			if(this.s.debug) this.s.debug_mode('virtual page view tracked to: '+this.s.vpv_prefix+view);
		},

		track_event: function(category, action, label, value) {
			/* 
				category (required) String The name you supply for the group of objects you want to track. ex. 'Videos'
				action (required) String A string that is uniquely paired with each category, and commonly used to define the type of user interaction for the web object. ex. 'Play'
				label (optional) String An optional string to provide additional dimensions to the event data. ex. 'Baby\'s First Birthday'
				value (optional) Int An integer that you can use to provide numerical data about the user event. ex. 1 (added into a total in reporting)
			*/
			if(!this.s.track) return;
			for( var i = 0; i < this.s.UA.length; i++ ) {
				var pre = i == 0 ? '' : 't'+(i+1)+'.';
				window._gaq.push([pre+"_trackEvent", category, action, label, value]);
			}
		},

		track_custom: function(index, name, value, opt_scope) {
			/* 
				http://code.google.com/apis/analytics/docs/tracking/gaTrackingCustomVariables.html
				**** Call the _setCustomVar() function when it can be set prior to a pageview or event GIF request.
					In certain cases this might not be possible, and you will need to set another _trackPageview() request after setting a custom variable. This is typically only necessary in those situations where the user triggers a session- or visit-level custom var, where it is not possible to bundle that method with a pageview, event, or ecommerce tracking call.
				index (required) Int The slot for the custom variable. This is a number whose value can range from 1 - 5, inclusive. A custom variable should be placed in one slot only and not be re-used across different slots.
				name (required) String The name for the custom variable. This is a string that identifies the custom variable and appears in the top-level Custom Variables report of the Analytics reports.
				value (required) String The value for the custom variable. This is a string that is paired with a name. You can pair a number of values with a custom variable name. The value appears in the table list of the UI for a selected variable name. Typically, you will have two or more values for a given name. For example, you might define a custom variable name gender and supply male and female as two possible values.
				opt_scope (optional) Int The scope for the custom variable. As described above, the scope defines the level of user engagement with your site. It is a number whose possible values are 1 (visitor-level), 2 (session-level), or 3 (page-level). When left undefined, the custom variable scope defaults to page-level interaction.
			*/
			if(!this.s.track) return;
			for( var i = 0; i < this.s.UA.length; i++ ) {
				var pre = i == 0 ? '' : 't'+(i+1)+'.';
				window._gaq.push([pre+"_setCustomVar", index, name, value, opt_scope]);
			}
		},

		track_social: function(network, socialAction, opt_target, opt_pagePath) {
			/* 
				http://code.google.com/apis/analytics/docs/tracking/gaTrackingSocial.html
				network (required) String Representing the social network being tracked (e.g. Facebook, Twitter, LinkedIn)
				socialAction (required) String Representing the social action being tracked (e.g. Like, Share, Tweet)
				opt_target (optional) String Representing the URL (or resource) which receives the action. For example, if a user clicks the Like button on a page on a site, the the opt_target might be set to the title of the page, or an ID used to identify the page in a content management system. In many cases, the page you Like is the same page you are on. So if this parameter is undefined, or omitted, the tracking code defaults to using document.location.href.
				opt_pagePath (optional) String Representing the page by path (including parameters) from which the action occurred. For example, if you click a Like button on http://code.google.com/apis/analytics/docs/index.html, then opt_pagePath should be set to /apis/analytics/docs/index.html. Almost always, the path of the page is the source of the social action. So if this parameter is undefined or omitted, the tracking code defaults to using location.pathname plus location.search. You generally only need to set this if you are tracking virtual pageviews by modifying the optional page path parameter with the Google Analytics _trackPageview method.
			*/
			if(!this.s.track) return;
			for( var i = 0; i < this.s.UA.length; i++ ) {
				var pre = i == 0 ? '' : 't'+(i+1)+'.';
				window._gaq.push([pre+"_trackSocial", network, socialAction, opt_target, opt_pagePath]);
				if(!this.s.auto_social && self.s.debug) { self.s.debug_mode('NOTE: auto_social is off but track_social method called, make sure the api is included in the html'); }
			}
		},

		track_transaction: function(items, transaction) {
			if(!this.s.track) return;
			for( var i = 0; i < this.s.UA.length; i++ ) {
				var pre = i == 0 ? '' : 't'+(i+1)+'.';
				
				for( var item_i = 0; item_i < items.length; item_i++ ) {
					this.add_item(items[item_i], pre);
				}
				_gaq.push([pre+'_addTrans',
					transaction[0],	// order ID - required
					transaction[1],	// affiliation or store name
					transaction[2],	// total - required
					transaction[3],	// tax
					transaction[4],	// shipping
					transaction[5],	// city
					transaction[6],	// state or province
					transaction[7]	// country
				]);
				
				_gaq.push([pre+'_trackTrans']);
			}
		},

		add_item: function(item, pre) {
			if(!this.s.track) return;
			_gaq.push([pre+'_addItem',
				item[0],	// order ID - required
				item[1],	// SKU/code - required
				item[2],	// product name
				item[3],	// category or variation
				item[4],	// unit price - required
				item[5]		// quantity - required
			  ]);
		}

	}

})(jQuery);