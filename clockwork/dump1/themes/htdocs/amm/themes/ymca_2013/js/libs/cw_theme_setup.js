/*
CW Theme Setup - v1.0.0

Useage:

CWjQuery('body').cw_theme_setup();

https://trac.clockwork.net/projects/amm/wiki/CwThemeSetup
*/

(function ($) {

	"use strict";

	var data_key               =  'CW_default_theme_setup-options',
		event_suffix           =  '.CW_default_theme_setup',
		log_to_console         =  false,
		is_mobile              =  false;

	var methods  =  {

		init  :  function (settings) {
			var $el  =  $(this),
				options  =  $.extend({
					safe_console_log         :  true,
					cookie_detection         :  true,
					cookie_message           :  'Cookies have to be enabled to view this site. Learn <a href="http://support.google.com/accounts/bin/answer.py?hl=en&answer=61416" target="_blank">how to enable cookies</a>.',
					stop_double_click_forms  :  true,
					layout_events            :  true,
					mobile_nav_target        :  null,
					breakpoint               :  599,
					site_width               :  960,
					full_site_selectors      :  {},
					mobile_hoist_array       : [],
					mobile_lower_array       : [],
					debug                    :  false,
					complete                 :  function() {  }
				}, settings);

			$el.data(data_key, options);
			log_to_console = options.debug;
			$el.trigger('initialized' + event_suffix);
			$el.cw_theme_setup('call_plugins');
			methods._logger( options);
			return this;
		},

		// Check options and setup each feature. We trigger an event and call the 
		// callback function once we've gone through all the options. 

		call_plugins  :  function () {
			var	$el      =  $(this),
				options  =  $el.data(data_key);

			$(window).load(methods._on_window_load);


			if( options.safe_console_log | log_to_console === true ) {

				methods._safe_console_log();
			}

			if( options.cookie_detection ) {
				methods._cookie_detection(options.cookie_message);
			}

			if( options.stop_double_click_forms ) {
				methods._stop_dbl_click();
			}
			if(options.mobile_nav_target !== null) {
				methods._make_mobile_nav_toggle( $( options.mobile_nav_target ) );
			}

			if(options.content_prioritization | options.layout_events) {

				$(window).on('resize', function(evt) {
					methods._evaluate_trigger_layout_events( options.breakpoint );
				});
				methods._logger('Enabled Layout Events');
			}

			if( options.mobile_hoist_array.length >= 1 | options.mobile_lower_array.length >= 1  ) {

				$(window).on('layout_change', function(evt, data) {
					methods._content_prioritization({
						layout   :  data.type,
						mobile_hoist   :  options.mobile_hoist_array,
						mobile_lower   :  options.mobile_lower_array

					});
				});
				methods._logger('Enabled Content Prioritization');
			}

			if( typeof options.full_site_selectors === 'object'
				& options.full_site_selectors.container !== undefined
				& options.full_site_selectors.view_full !== undefined
				& options.full_site_selectors.view_responsive !== undefined ) {
				methods._show_full_site_link(options.site_width, options.breakpoint, options.full_site_selectors );
			}
			$el.trigger('complete' + event_suffix);
		},

		_on_window_load  :  function( ) {
			// init required by AMM for preview interface, cannot be disabled
			window.init();
		},

		// make it safe to use console.log always
		_safe_console_log  : function () {

			var method;
			var noop = function () {};
			var console_methods = [
				'assert', 'clear', 'count', 'debug', 'dir', 'dirxml', 'error',
				'exception', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log',
				'markTimeline', 'profile', 'profileEnd', 'table', 'time', 'timeEnd',
				'timeStamp', 'trace', 'warn'
			];
			var length = console_methods.length;
			var console = (window.console = window.console || {});

			while (length--) {
				method = console_methods[length];

				// Only stub undefined methods.
				if (!console[method]) {
				console[method] = noop;
				}
			}

			methods._logger('Enabled Safe Console Logging');

		},

		// Cookie & No JS Detection
		// kevin@clockwork.net
		_cookie_detection : function(message) {

			$(document).ready(function(){

				var cookieEnabled=(navigator.cookieEnabled)? true : false;

				if( typeof navigator.cookieEnabled == "undefined" && !cookieEnabled ){
					document.cookie = "testcookie";
					cookieEnabled = (document.cookie.indexOf("testcookie")!=-1) ? true : false;
				}

				if( !cookieEnabled ) {
					$('#support')
						.append('<p id="support-cookies">' + message +'</p>')
						.show();
				}
			});

			methods._logger('Enabled Cookie and JS Detection');
		},

		// Forms - Stop Double Click Submit Twice
		// kevin@clockwork.net
		_stop_dbl_click : function( ) {

			$(document).on('submit', 'form', prevent_submit);

			function prevent_submit(evt) {

				var $t = $(this),
					cl = 'double_submit_prevention';
				if(!$t.hasClass(cl)) {
					$t
					.bind('submit', stop_submit)
					.addClass(cl);

					setTimeout(function(){
						$t
						.unbind('submit', stop_submit)
						.removeClass(cl);
					}, 1000);
				}
			}

			function stop_submit(evt) {

				evt.preventDefault();
				evt.stopPropagation();
			}

			methods._logger('Enabled Stop Double Click Forms Submission');
		},

		/* Trigger Layout Change Events
		 * kevin@clockwork.net
		*******************************************************/

		_evaluate_trigger_layout_events : function(breakpoint) {

			var adjusted_breakpoint = breakpoint;
			if($.browser.msie && ($.browser.version < 9)) {
				return;
			}
			if($.browser.msie && ($.browser.version < 10)) {
				adjusted_breakpoint -= 17;
			}
			if(($(window).width() <= adjusted_breakpoint) && (is_mobile === false)) {
				is_mobile = true;
				$(window).trigger('layout_change', {'type': 'viewport-small'});
			}
			else if(($(window).outerWidth() > adjusted_breakpoint) && (is_mobile === true)) {
				is_mobile = false;
				$(window).trigger('layout_change', {'type': 'viewport-large'});
			}
		},

		/* Change Content Priority by Context
		 * kevin@clockwork.net
		*******************************************************/
		_content_prioritization :function ( config ) {
			var $main_content_placeholder = $('<div id="main_content_placeholder"></div>');
			if(config.layout === 'viewport-small') {

				for(var i=0; i<config.mobile_hoist.length; i++) {
					var $target = $('#'+config.mobile_hoist[i]);
					$target.find('script').remove();
					$('<div id="content_prioritization_'+config.mobile_hoist[i]+'"></div>').insertAfter($target);
					$target.prependTo($target.parent());
				}

				for(var i2=0; i2<config.mobile_lower.length; i2++) {
					var $target = $('#'+config.mobile_lower[i2]);
					$target.find('script').remove();
					$('<div id="content_prioritization_'+config.mobile_lower[i2]+'"></div>').insertAfter($target);
					$target.appendTo($target.parent());
				}

			}

			else if(config.layout === 'viewport-large') {

				for(var i=0; i<config.mobile_hoist.length; i++) {
					$('#'+config.mobile_hoist[i]).prependTo($('#content_prioritization_'+config.mobile_hoist[i])).unwrap();
				}
				for(var i2=0; i2<config.mobile_lower.length; i2++) {
					$('#'+config.mobile_lower[i2]).prependTo($('#content_prioritization_'+config.mobile_lower[i2])).unwrap();
				}
			}
			methods._logger('Enabled Content Prioritization');
		},

		/* Main Nagivation Slide Toggle in #page-head
		 * kevin@clockwork.net
		*******************************************************/
		_make_mobile_nav_toggle : function($target) {

			$target
				.on('click', '.show_main_nav', methods._mobile_toggle_main_nav)
				.find('ul:first').addClass('mobile_nav_active');
			methods._logger('Enabled Mobile Slide Toggle');
		},

		_mobile_toggle_main_nav : function (evt) {

			var $nav = $(this).parent(),
				$ul = $nav.find('ul:first'),
				$action = $nav.find('.navigation_action');

			if($action.html() === $action.attr('data-action_default')) {
				$action.html($action.attr('data-action_alt'));
			}

			else {
				$action.html($action.attr('data-action_default'));
			}

			$ul.slideToggle();
			evt.preventDefault();
		},
		/* Allows Mobile Devices to view full site even with responsive themes
		 * kevin@clockwork.net
		*******************************************************/
		_show_full_site_link : function( site_width, breakpoint, selectors ) {

			var is_responsive = 'isResponsive',
				mod_supported = 'viewport_mod_supported';

			try {
				if (localStorage[mod_supported] === '0') {
					return;
				}
			} catch (err) {
				return;
			}

			// viewport stuff
			var viewport       = $('meta[name="viewport"]'),
				init_win_width = $(window).outerWidth();

			// check to see if local storage value is set on page load


			var full_site_methods = {

				full_site_init : function() {
					if(localStorage[is_responsive] === undefined) {
						if(init_win_width <= breakpoint) {
							localStorage[is_responsive] = '1';
						}else {
							localStorage[is_responsive] = '0';
						}
					}

					full_site_methods.checkPref();
				},

				checkPref : function() {
					if(localStorage[is_responsive] == '0'){
						full_site_methods.showFullSite();
					}else if(localStorage[is_responsive] == '1') {
						full_site_methods.showMobileOptimized();
					}

				},

				showFullSite : function(){
					if(init_win_width <= site_width) {
						viewport.attr('content', 'width=' + site_width);
					}else {
						viewport.attr('content', 'width=' + init_win_width);
					}
					localStorage[is_responsive] = '0';
				},

				showMobileOptimized : function(){
					if(init_win_width < breakpoint) {
						if(viewport.attr('content') === ('width=' + site_width)) {
							viewport.attr('content', 'width=' + init_win_width);
						}else {
							viewport.attr('content', '');
						}
					}else {
						viewport.attr('content', 'width=' + breakpoint);
					}
					localStorage[is_responsive] = '1';
				},

				test_viewport : function() {
					setTimeout(function(){
						var win_width_test = $(window).outerWidth();
						if(win_width_test === init_win_width) {
							if((win_width_test <= breakpoint) && ((win_width_test + 1) > breakpoint)) {
								viewport.attr('content', 'width=' + (init_win_width-1));
							}
							else {
								viewport.attr('content', 'width=' + (init_win_width+1));
							}
						}
						else {
							localStorage[mod_supported] = '1';
							$(selectors.container).show();
							return;
						}
						setTimeout(function(){
							if(init_win_width !== $(window).outerWidth()) {
								localStorage[mod_supported] = '1';
								$(selectors.container).show();
								viewport.attr('content', 'width=' + win_width_test);
							}
							else {
								localStorage[mod_supported] = '0';
							}
						}, 0);
					}, 0);
				}


			};

			if (viewport.length === 0) {
				viewport = $('<meta name="viewport"/>').appendTo('head');
			}
			if (localStorage[mod_supported] === undefined) {
				full_site_methods.test_viewport();
			} else {
				full_site_methods.full_site_init();
				$(selectors.container).show();
			}

			$(selectors.view_full).on("click", function(){
				full_site_methods.showFullSite();
			});

			$(selectors.view_responsive).on("click", function(){
				full_site_methods.showMobileOptimized();
			});
			methods._logger('Enabled Full Site Link');

		},
		// We want to be able to print out log messages if necessary, 
		// but we don't do this by default

		_logger : function( message ) {
			if(log_to_console) {
				window.console.info('CW_theme_setup: ', message);
			}
			return;
		}

	};

	$.fn.cw_theme_setup  =  function (method) {
	var args = arguments;
        if (methods[method] && method.charAt(0) !== '_') {
            return $(this).map(function(i, val) { return methods[method].apply(this, Array.prototype.slice.call(args, 1)); });
        } else if (typeof method === 'object' || !method) {
            return $(this).map(function(i, val) { return methods.init.apply(this, args); });
        }
	};

}(CWjQuery));