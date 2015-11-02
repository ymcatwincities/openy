// Avoid `console` errors in browsers that lack a console.
(function() {
    var method;
    var noop = function () {};
    var methods = [
        'assert', 'clear', 'count', 'debug', 'dir', 'dirxml', 'error',
        'exception', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log',
        'markTimeline', 'profile', 'profileEnd', 'table', 'time', 'timeEnd',
        'timeStamp', 'trace', 'warn'
    ];
    var length = methods.length;
    var console = (window.console = window.console || {});

    while (length--) {
        method = methods[length];

        // Only stub undefined methods.
        if (!console[method]) {
            console[method] = noop;
        }
    }
}());

// Place any jQuery/helper plugins in here.

if (!('forEach' in Array.prototype)) {
    Array.prototype.forEach= function(action, that /*opt*/) {
        for (var i= 0, n= this.length; i<n; i++)
            if (i in this)
                action.call(that, this[i], i, this);
    };
}

/*! http://mths.be/placeholder v2.0.8 by @mathias EDITED 5/20/2014 by @drobotij : Added trim() for comparison (IE8/9) bug. */
(function(window, document, $) {

	// Opera Mini v7 doesn’t support placeholder although its DOM seems to indicate so
	var isOperaMini = Object.prototype.toString.call(window.operamini) == '[object OperaMini]';
	var isInputSupported = 'placeholder' in document.createElement('input') && !isOperaMini;
	var isTextareaSupported = 'placeholder' in document.createElement('textarea') && !isOperaMini;
	var prototype = $.fn;
	var valHooks = $.valHooks;
	var propHooks = $.propHooks;
	var hooks;
	var placeholder;

	if (isInputSupported && isTextareaSupported) {

		placeholder = prototype.placeholder = function() {
			return this;
		};

		placeholder.input = placeholder.textarea = true;

	} else {

		placeholder = prototype.placeholder = function() {
			var $this = this;
			$this
				.filter((isInputSupported ? 'textarea' : ':input') + '[placeholder]')
				.not('.placeholder')
				.bind({
					'focus.placeholder': clearPlaceholder,
					'blur.placeholder': setPlaceholder
				})
				.data('placeholder-enabled', true)
				.trigger('blur.placeholder');
			return $this;
		};

		placeholder.input = isInputSupported;
		placeholder.textarea = isTextareaSupported;

		hooks = {
			'get': function(element) {
				var $element = $(element);

				var $passwordInput = $element.data('placeholder-password');
				if ($passwordInput) {
					return $passwordInput[0].value;
				}

				return $element.data('placeholder-enabled') && $element.hasClass('placeholder') ? '' : element.value;
			},
			'set': function(element, value) {
				var $element = $(element);

				var $passwordInput = $element.data('placeholder-password');
				if ($passwordInput) {
					return $passwordInput[0].value = value;
				}

				if (!$element.data('placeholder-enabled')) {
					return element.value = value;
				}
				if (value == '') {
					element.value = value;
					// Issue #56: Setting the placeholder causes problems if the element continues to have focus.
					if (element != safeActiveElement()) {
						// We can't use `triggerHandler` here because of dummy text/password inputs :(
						setPlaceholder.call(element);
					}
				} else if ($element.hasClass('placeholder')) {
					clearPlaceholder.call(element, true, value) || (element.value = value);
				} else {
					element.value = value;
				}
				// `set` can not return `undefined`; see http://jsapi.info/jquery/1.7.1/val#L2363
				return $element;
			}
		};

		if (!isInputSupported) {
			valHooks.input = hooks;
			propHooks.value = hooks;
		}
		if (!isTextareaSupported) {
			valHooks.textarea = hooks;
			propHooks.value = hooks;
		}

		$(function() {
			// Look for forms
			$(document).delegate('form', 'submit.placeholder', function() {
				// Clear the placeholder values so they don't get submitted
				var $inputs = $('.placeholder', this).each(clearPlaceholder);
				setTimeout(function() {
					$inputs.each(setPlaceholder);
				}, 10);
			});
		});

		// Clear placeholder values upon page reload
		$(window).bind('beforeunload.placeholder', function() {
			$('.placeholder').each(function() {
				this.value = '';
			});
		});

	}

	function args(elem) {
		// Return an object of element attributes
		var newAttrs = {};
		var rinlinejQuery = /^jQuery\d+$/;
		$.each(elem.attributes, function(i, attr) {
			if (attr.specified && !rinlinejQuery.test(attr.name)) {
				newAttrs[attr.name] = attr.value;
			}
		});
		return newAttrs;
	}

	function clearPlaceholder(event, value) {
		var input = this;
		var $input = $(input);
		if ( ($.trim($input.attr('placeholder')) == $.trim(String(input.value))) && $input.hasClass('placeholder'))
		{
			if ($input.data('placeholder-password')) {
				$input = $input.hide().next().show().attr('id', $input.removeAttr('id').data('placeholder-id'));
				// If `clearPlaceholder` was called from `$.valHooks.input.set`
				if (event === true) {
					return $input[0].value = value;
				}
				$input.focus();
			} else {
				input.value = '';
				$input.removeClass('placeholder');
				input == safeActiveElement() && input.select();
			}
		}
	}

	function setPlaceholder() {
		var $replacement;
		var input = this;
		var $input = $(input);
		var id = this.id;
		if (input.value == '') {
			if (input.type == 'password') {
				if (!$input.data('placeholder-textinput')) {
					try {
						$replacement = $input.clone().attr({ 'type': 'text' });
					} catch(e) {
						$replacement = $('<input>').attr($.extend(args(this), { 'type': 'text' }));
					}
					$replacement
						.removeAttr('name')
						.data({
							'placeholder-password': $input,
							'placeholder-id': id
						})
						.bind('focus.placeholder', clearPlaceholder);
					$input
						.data({
							'placeholder-textinput': $replacement,
							'placeholder-id': id
						})
						.before($replacement);
				}
				$input = $input.removeAttr('id').hide().prev().attr('id', id).show();
				// Note: `$input[0] != input` now!
			}
			$input.addClass('placeholder');
			$input[0].value = $input.attr('placeholder');
		} else {
			$input.removeClass('placeholder');
		}
	}

	function safeActiveElement() {
		// Avoid IE9 `document.activeElement` of death
		// https://github.com/mathiasbynens/jquery-placeholder/pull/99
		try {
			return document.activeElement;
		} catch (exception) {}
	}

}(this, document, jQuery));


/*

Holder - 2.1 - client side image placeholders
(c) 2012-2013 Ivan Malopinsky / http://imsky.co

Provided under the MIT License.
Commercial use requires attribution.

*/

var Holder = Holder || {};
(function (app, win) {

var preempted = false,
fallback = false,
canvas = document.createElement('canvas');

if (!canvas.getContext) {
    fallback = true;
} else {
    if (canvas.toDataURL("image/png")
        .indexOf("data:image/png") < 0) {
        //Android doesn't support data URI
        fallback = true;
    } else {
        var ctx = canvas.getContext("2d");
    }
}

var dpr = 1, bsr = 1;
    
if(!fallback){
    dpr = window.devicePixelRatio || 1,
    bsr = ctx.webkitBackingStorePixelRatio || ctx.mozBackingStorePixelRatio || ctx.msBackingStorePixelRatio || ctx.oBackingStorePixelRatio || ctx.backingStorePixelRatio || 1;
}

var ratio = dpr / bsr;

//getElementsByClassName polyfill
document.getElementsByClassName||(document.getElementsByClassName=function(e){var t=document,n,r,i,s=[];if(t.querySelectorAll)return t.querySelectorAll("."+e);if(t.evaluate){r=".//*[contains(concat(' ', @class, ' '), ' "+e+" ')]",n=t.evaluate(r,t,null,0,null);while(i=n.iterateNext())s.push(i)}else{n=t.getElementsByTagName("*"),r=new RegExp("(^|\\s)"+e+"(\\s|$)");for(i=0;i<n.length;i++)r.test(n[i].className)&&s.push(n[i])}return s})

//getComputedStyle polyfill
window.getComputedStyle||(window.getComputedStyle=function(e){return this.el=e,this.getPropertyValue=function(t){var n=/(\-([a-z]){1})/g;return t=="float"&&(t="styleFloat"),n.test(t)&&(t=t.replace(n,function(){return arguments[2].toUpperCase()})),e.currentStyle[t]?e.currentStyle[t]:null},this})

//http://javascript.nwbox.com/ContentLoaded by Diego Perini with modifications
function contentLoaded(n,t){var l="complete",s="readystatechange",u=!1,h=u,c=!0,i=n.document,a=i.documentElement,e=i.addEventListener?"addEventListener":"attachEvent",v=i.addEventListener?"removeEventListener":"detachEvent",f=i.addEventListener?"":"on",r=function(e){(e.type!=s||i.readyState==l)&&((e.type=="load"?n:i)[v](f+e.type,r,u),!h&&(h=!0)&&t.call(n,null))},o=function(){try{a.doScroll("left")}catch(n){setTimeout(o,50);return}r("poll")};if(i.readyState==l)t.call(n,"lazy");else{if(i.createEventObject&&a.doScroll){try{c=!n.frameElement}catch(y){}c&&o()}i[e](f+"DOMContentLoaded",r,u),i[e](f+s,r,u),n[e](f+"load",r,u)}}

//https://gist.github.com/991057 by Jed Schmidt with modifications
function selector(a){
    a=a.match(/^(\W)?(.*)/);var b=document["getElement"+(a[1]?a[1]=="#"?"ById":"sByClassName":"sByTagName")](a[2]);
    var ret=[]; b!==null&&(b.length?ret=b:b.length===0?ret=b:ret=[b]);  return ret;
}

//shallow object property extend
function extend(a,b){var c={};for(var d in a)c[d]=a[d];for(var e in b)c[e]=b[e];return c}

//hasOwnProperty polyfill
if (!Object.prototype.hasOwnProperty)
    /*jshint -W001, -W103 */
    Object.prototype.hasOwnProperty = function(prop) {
        var proto = this.__proto__ || this.constructor.prototype;
        return (prop in this) && (!(prop in proto) || proto[prop] !== this[prop]);
    }
    /*jshint +W001, +W103 */

function text_size(width, height, template) {
    height = parseInt(height, 10);
    width = parseInt(width, 10);
    var bigSide = Math.max(height, width)
    var smallSide = Math.min(height, width)
    var scale = 1 / 12;
    var newHeight = Math.min(smallSide * 0.75, 0.75 * bigSide * scale);
    return {
        height: Math.round(Math.max(template.size, newHeight))
    }
}

function draw(ctx, dimensions, template, ratio, literal) {
    var ts = text_size(dimensions.width, dimensions.height, template);
    var text_height = ts.height;
    var width = dimensions.width * ratio,
        height = dimensions.height * ratio;
    var font = template.font ? template.font : "sans-serif";
    canvas.width = width;
    canvas.height = height;
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    ctx.fillStyle = template.background;
    ctx.fillRect(0, 0, width, height);
    ctx.fillStyle = template.foreground;
    ctx.font = "bold " + text_height + "px " + font;
    var text = template.text ? template.text : (Math.floor(dimensions.width) + "x" + Math.floor(dimensions.height));
    if (literal) {
        text = template.literalText;
    }
    var text_width = ctx.measureText(text).width;
    if (text_width / width >= 0.75) {
        text_height = Math.floor(text_height * 0.75 * (width / text_width));
    }
    //Resetting font size if necessary
    ctx.font = "bold " + (text_height * ratio) + "px " + font;
    ctx.fillText(text, (width / 2), (height / 2), width);
    return canvas.toDataURL("image/png");
}

function render(mode, el, holder, src) {
    var dimensions = holder.dimensions,
        theme = holder.theme,
        text = holder.text ? decodeURIComponent(holder.text) : holder.text;
    var dimensions_caption = dimensions.width + "x" + dimensions.height;
    theme = (text ? extend(theme, {
        text: text
    }) : theme);
    theme = (holder.font ? extend(theme, {
        font: holder.font
    }) : theme);
    el.setAttribute("data-src", src);
    theme.literalText = dimensions_caption;
    holder.originalTheme = holder.theme;
    holder.theme = theme;

    if (mode == "image") {
        el.setAttribute("alt", text ? text : theme.text ? theme.text + " [" + dimensions_caption + "]" : dimensions_caption);
        if (fallback || !holder.auto) {
            el.style.width = dimensions.width + "px";
            el.style.height = dimensions.height + "px";
        }
        if (fallback) {
            el.style.backgroundColor = theme.background;
        } else {
            el.setAttribute("src", draw(ctx, dimensions, theme, ratio));
        }
    } else if (mode == "background") {
        if (!fallback) {
            el.style.backgroundImage = "url(" + draw(ctx, dimensions, theme, ratio) + ")";
            el.style.backgroundSize = dimensions.width + "px " + dimensions.height + "px";
        }
    } else if (mode == "fluid") {
        el.setAttribute("alt", text ? text : theme.text ? theme.text + " [" + dimensions_caption + "]" : dimensions_caption);
        if (dimensions.height.slice(-1) == "%") {
            el.style.height = dimensions.height
        } else {
            el.style.height = dimensions.height + "px"
        }
        if (dimensions.width.slice(-1) == "%") {
            el.style.width = dimensions.width
        } else {
            el.style.width = dimensions.width + "px"
        }
        if (el.style.display == "inline" || el.style.display === "") {
            el.style.display = "block";
        }
        if (fallback) {
            el.style.backgroundColor = theme.background;
        } else {
            el.holderData = holder;
            fluid_images.push(el);
            fluid_update(el);
        }
    }
}

function fluid_update(element) {
    var images;
    if (element.nodeType == null) {
        images = fluid_images;
    } else {
        images = [element]
    }
    for (var i in images) {
        var el = images[i]
        if (el.holderData) {
            var holder = el.holderData;
            el.setAttribute("src", draw(ctx, {
                height: el.clientHeight,
                width: el.clientWidth
            }, holder.theme, ratio, !! holder.literal));
        }
    }
}

function parse_flags(flags, options) {
    var ret = {
        theme: settings.themes.gray
    };
    var render = false;
    for (sl = flags.length, j = 0; j < sl; j++) {
        var flag = flags[j];
        if (app.flags.dimensions.match(flag)) {
            render = true;
            ret.dimensions = app.flags.dimensions.output(flag);
        } else if (app.flags.fluid.match(flag)) {
            render = true;
            ret.dimensions = app.flags.fluid.output(flag);
            ret.fluid = true;
        } else if (app.flags.literal.match(flag)) {
            ret.literal = true;
        } else if (app.flags.colors.match(flag)) {
            ret.theme = app.flags.colors.output(flag);
        } else if (options.themes[flag]) {
            //If a theme is specified, it will override custom colors
            ret.theme = options.themes[flag];
        } else if (app.flags.font.match(flag)) {
            ret.font = app.flags.font.output(flag);
        } else if (app.flags.auto.match(flag)) {
            ret.auto = true;
        } else if (app.flags.text.match(flag)) {
            ret.text = app.flags.text.output(flag);
        }
    }
    return render ? ret : false;
}
var fluid_images = [];
var settings = {
    domain: "holder.js",
    images: "img",
    bgnodes: ".holderjs",
    themes: {
        "gray": {
            background: "#eee",
            foreground: "#aaa",
            size: 12
        },
        "social": {
            background: "#3a5a97",
            foreground: "#fff",
            size: 12
        },
        "industrial": {
            background: "#434A52",
            foreground: "#C2F200",
            size: 12
        }
    },
    stylesheet: ""
};
app.flags = {
    dimensions: {
        regex: /^(\d+)x(\d+)$/,
        output: function (val) {
            var exec = this.regex.exec(val);
            return {
                width: +exec[1],
                height: +exec[2]
            }
        }
    },
    fluid: {
        regex: /^([0-9%]+)x([0-9%]+)$/,
        output: function (val) {
            var exec = this.regex.exec(val);
            return {
                width: exec[1],
                height: exec[2]
            }
        }
    },
    colors: {
        regex: /#([0-9a-f]{3,})\:#([0-9a-f]{3,})/i,
        output: function (val) {
            var exec = this.regex.exec(val);
            return {
                size: settings.themes.gray.size,
                foreground: "#" + exec[2],
                background: "#" + exec[1]
            }
        }
    },
    text: {
        regex: /text\:(.*)/,
        output: function (val) {
            return this.regex.exec(val)[1];
        }
    },
    font: {
        regex: /font\:(.*)/,
        output: function (val) {
            return this.regex.exec(val)[1];
        }
    },
    auto: {
        regex: /^auto$/
    },
    literal: {
        regex: /^literal$/
    }
}
for (var flag in app.flags) {
    if (!app.flags.hasOwnProperty(flag)) continue;
    app.flags[flag].match = function (val) {
        return val.match(this.regex)
    }
}
app.add_theme = function (name, theme) {
    name != null && theme != null && (settings.themes[name] = theme);
    return app;
};
app.add_image = function (src, el) {
    var node = selector(el);
    if (node.length) {
        for (var i = 0, l = node.length; i < l; i++) {
            var img = document.createElement("img")
            img.setAttribute("data-src", src);
            node[i].appendChild(img);
        }
    }
    return app;
};
app.run = function (o) {
    var options = extend(settings, o),
        images = [],
        imageNodes = [],
        bgnodes = [];
    if (typeof (options.images) == "string") {
        imageNodes = selector(options.images);
    } else if (window.NodeList && options.images instanceof window.NodeList) {
        imageNodes = options.images;
    } else if (window.Node && options.images instanceof window.Node) {
        imageNodes = [options.images];
    }
    if (typeof (options.bgnodes) == "string") {
        bgnodes = selector(options.bgnodes);
    } else if (window.NodeList && options.elements instanceof window.NodeList) {
        bgnodes = options.bgnodes;
    } else if (window.Node && options.bgnodes instanceof window.Node) {
        bgnodes = [options.bgnodes];
    }
    preempted = true;
    for (i = 0, l = imageNodes.length; i < l; i++) images.push(imageNodes[i]);
    var holdercss = document.getElementById("holderjs-style");
    if (!holdercss) {
        holdercss = document.createElement("style");
        holdercss.setAttribute("id", "holderjs-style");
        holdercss.type = "text/css";
        document.getElementsByTagName("head")[0].appendChild(holdercss);
    }
    if (!options.nocss) {
        if (holdercss.styleSheet) {
            holdercss.styleSheet.cssText += options.stylesheet;
        } else {
            holdercss.appendChild(document.createTextNode(options.stylesheet));
        }
    }
    var cssregex = new RegExp(options.domain + "\/(.*?)\"?\\)");
    for (var l = bgnodes.length, i = 0; i < l; i++) {
        var src = window.getComputedStyle(bgnodes[i], null)
            .getPropertyValue("background-image");
        var flags = src.match(cssregex);
        var bgsrc = bgnodes[i].getAttribute("data-background-src");
        if (flags) {
            var holder = parse_flags(flags[1].split("/"), options);
            if (holder) {
                render("background", bgnodes[i], holder, src);
            }
        } else if (bgsrc != null) {
            var holder = parse_flags(bgsrc.substr(bgsrc.lastIndexOf(options.domain) + options.domain.length + 1)
                .split("/"), options);
            if (holder) {
                render("background", bgnodes[i], holder, src);
            }
        }
    }
    for (l = images.length, i = 0; i < l; i++) {
        var attr_data_src, attr_src;
        attr_src = attr_data_src = src = null;
        try {
            attr_src = images[i].getAttribute("src");
            attr_datasrc = images[i].getAttribute("data-src");
        } catch (e) {}
        if (attr_datasrc == null && !! attr_src && attr_src.indexOf(options.domain) >= 0) {
            src = attr_src;
        } else if ( !! attr_datasrc && attr_datasrc.indexOf(options.domain) >= 0) {
            src = attr_datasrc;
        }
        if (src) {
            var holder = parse_flags(src.substr(src.lastIndexOf(options.domain) + options.domain.length + 1)
                .split("/"), options);
            if (holder) {
                if (holder.fluid) {
                    render("fluid", images[i], holder, src)
                } else {
                    render("image", images[i], holder, src);
                }
            }
        }
    }
    return app;
};
contentLoaded(win, function () {
    if (window.addEventListener) {
        window.addEventListener("resize", fluid_update, false);
        window.addEventListener("orientationchange", fluid_update, false);
    } else {
        window.attachEvent("onresize", fluid_update)
    }
    preempted || app.run();
});
if (typeof define === "function" && define.amd) {
    define([], function () {
        return app;
    });
}

})(Holder, window);

/* =========================================================
 * bootstrap-datepicker.js
 * http://www.eyecon.ro/bootstrap-datepicker
 * =========================================================
 * Copyright 2012 Stefan Petre
 * Improvements by Andrew Rowls
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ========================================================= */

(function( $ ) {

    var $window = $(window);

    function UTCDate(){
        return new Date(Date.UTC.apply(Date, arguments));
    }
    function UTCToday(){
        var today = new Date();
        return UTCDate(today.getUTCFullYear(), today.getUTCMonth(), today.getUTCDate());
    }


    // Picker object

    var Datepicker = function(element, options) {
        var that = this;

        this._process_options(options);

        this.element = $(element);
        this.isInline = false;
        this.isInput = this.element.is('input');
        this.component = this.element.is('.date') ? this.element.find('.add-on, .btn') : false;
        this.hasInput = this.component && this.element.find('input').length;
        if(this.component && this.component.length === 0)
            this.component = false;

        this.picker = $(DPGlobal.template);
        this._buildEvents();
        this._attachEvents();

        if(this.isInline) {
            this.picker.addClass('datepicker-inline').appendTo(this.element);
        } else {
            this.picker.addClass('datepicker-dropdown dropdown-menu');
        }

        if (this.o.rtl){
            this.picker.addClass('datepicker-rtl');
            this.picker.find('.prev i, .next i')
                        .toggleClass('icon-arrow-left icon-arrow-right');
        }


        this.viewMode = this.o.startView;

        if (this.o.calendarWeeks)
            this.picker.find('tfoot th.today')
                        .attr('colspan', function(i, val){
                            return parseInt(val) + 1;
                        });

        this._allow_update = false;

        this.setStartDate(this._o.startDate);
        this.setEndDate(this._o.endDate);
        this.setDaysOfWeekDisabled(this.o.daysOfWeekDisabled);

        this.fillDow();
        this.fillMonths();

        this._allow_update = true;

        this.update();
        this.showMode();

        if(this.isInline) {
            this.show();
        }
    };

    Datepicker.prototype = {
        constructor: Datepicker,

        _process_options: function(opts){
            // Store raw options for reference
            this._o = $.extend({}, this._o, opts);
            // Processed options
            var o = this.o = $.extend({}, this._o);

            // Check if "de-DE" style date is available, if not language should
            // fallback to 2 letter code eg "de"
            var lang = o.language;
            if (!dates[lang]) {
                lang = lang.split('-')[0];
                if (!dates[lang])
                    lang = defaults.language;
            }
            o.language = lang;

            switch(o.startView){
                case 2:
                case 'decade':
                    o.startView = 2;
                    break;
                case 1:
                case 'year':
                    o.startView = 1;
                    break;
                default:
                    o.startView = 0;
            }

            switch (o.minViewMode) {
                case 1:
                case 'months':
                    o.minViewMode = 1;
                    break;
                case 2:
                case 'years':
                    o.minViewMode = 2;
                    break;
                default:
                    o.minViewMode = 0;
            }

            o.startView = Math.max(o.startView, o.minViewMode);

            o.weekStart %= 7;
            o.weekEnd = ((o.weekStart + 6) % 7);

            var format = DPGlobal.parseFormat(o.format);
            if (o.startDate !== -Infinity) {
                if (!!o.startDate) {
                    if (o.startDate instanceof Date)
                        o.startDate = this._local_to_utc(this._zero_time(o.startDate));
                    else
                        o.startDate = DPGlobal.parseDate(o.startDate, format, o.language);
                } else {
                    o.startDate = -Infinity;
                }
            }
            if (o.endDate !== Infinity) {
                if (!!o.endDate) {
                    if (o.endDate instanceof Date)
                        o.endDate = this._local_to_utc(this._zero_time(o.endDate));
                    else
                        o.endDate = DPGlobal.parseDate(o.endDate, format, o.language);
                } else {
                    o.endDate = Infinity;
                }
            }

            o.daysOfWeekDisabled = o.daysOfWeekDisabled||[];
            if (!$.isArray(o.daysOfWeekDisabled))
                o.daysOfWeekDisabled = o.daysOfWeekDisabled.split(/[,\s]*/);
            o.daysOfWeekDisabled = $.map(o.daysOfWeekDisabled, function (d) {
                return parseInt(d, 10);
            });

            var plc = String(o.orientation).toLowerCase().split(/\s+/g),
                _plc = o.orientation.toLowerCase();
            plc = $.grep(plc, function(word){
                return (/^auto|left|right|top|bottom$/).test(word);
            });
            o.orientation = {x: 'auto', y: 'auto'};
            if (!_plc || _plc === 'auto')
                ; // no action
            else if (plc.length === 1){
                switch(plc[0]){
                    case 'top':
                    case 'bottom':
                        o.orientation.y = plc[0];
                        break;
                    case 'left':
                    case 'right':
                        o.orientation.x = plc[0];
                        break;
                }
            }
            else {
                _plc = $.grep(plc, function(word){
                    return (/^left|right$/).test(word);
                });
                o.orientation.x = _plc[0] || 'auto';

                _plc = $.grep(plc, function(word){
                    return (/^top|bottom$/).test(word);
                });
                o.orientation.y = _plc[0] || 'auto';
            }
        },
        _events: [],
        _secondaryEvents: [],
        _applyEvents: function(evs){
            for (var i=0, el, ev; i<evs.length; i++){
                el = evs[i][0];
                ev = evs[i][1];
                el.on(ev);
            }
        },
        _unapplyEvents: function(evs){
            for (var i=0, el, ev; i<evs.length; i++){
                el = evs[i][0];
                ev = evs[i][1];
                el.off(ev);
            }
        },
        _buildEvents: function(){
            if (this.isInput) { // single input
                this._events = [
                    [this.element, {
                        focus: $.proxy(this.show, this),
                        keyup: $.proxy(this.update, this),
                        keydown: $.proxy(this.keydown, this)
                    }]
                ];
            }
            else if (this.component && this.hasInput){ // component: input + button
                this._events = [
                    // For components that are not readonly, allow keyboard nav
                    [this.element.find('input'), {
                        focus: $.proxy(this.show, this),
                        keyup: $.proxy(this.update, this),
                        keydown: $.proxy(this.keydown, this)
                    }],
                    [this.component, {
                        click: $.proxy(this.show, this)
                    }]
                ];
            }
            else if (this.element.is('div')) {  // inline datepicker
                this.isInline = true;
            }
            else {
                this._events = [
                    [this.element, {
                        click: $.proxy(this.show, this)
                    }]
                ];
            }

            this._secondaryEvents = [
                [this.picker, {
                    click: $.proxy(this.click, this)
                }],
                [$(window), {
                    resize: $.proxy(this.place, this)
                }],
                [$(document), {
                    mousedown: $.proxy(function (e) {
                        // Clicked outside the datepicker, hide it
                        if (!(
                            this.element.is(e.target) ||
                            this.element.find(e.target).length ||
                            this.picker.is(e.target) ||
                            this.picker.find(e.target).length
                        )) {
                            this.hide();
                        }
                    }, this)
                }]
            ];
        },
        _attachEvents: function(){
            this._detachEvents();
            this._applyEvents(this._events);
        },
        _detachEvents: function(){
            this._unapplyEvents(this._events);
        },
        _attachSecondaryEvents: function(){
            this._detachSecondaryEvents();
            this._applyEvents(this._secondaryEvents);
        },
        _detachSecondaryEvents: function(){
            this._unapplyEvents(this._secondaryEvents);
        },
        _trigger: function(event, altdate){
            var date = altdate || this.date,
                local_date = this._utc_to_local(date);

            this.element.trigger({
                type: event,
                date: local_date,
                format: $.proxy(function(altformat){
                    var format = altformat || this.o.format;
                    return DPGlobal.formatDate(date, format, this.o.language);
                }, this)
            });
        },

        show: function(e) {
            if (!this.isInline)
                this.picker.appendTo('body');
            this.picker.show();
            this.height = this.component ? this.component.outerHeight() : this.element.outerHeight();
            this.place();
            this._attachSecondaryEvents();
            if (e) {
                e.preventDefault();
            }
            this._trigger('show');
        },

        hide: function(e){
            if(this.isInline) return;
            if (!this.picker.is(':visible')) return;
            this.picker.hide().detach();
            this._detachSecondaryEvents();
            this.viewMode = this.o.startView;
            this.showMode();

            if (
                this.o.forceParse &&
                (
                    this.isInput && this.element.val() ||
                    this.hasInput && this.element.find('input').val()
                )
            )
                this.setValue();
            this._trigger('hide');
        },

        remove: function() {
            this.hide();
            this._detachEvents();
            this._detachSecondaryEvents();
            this.picker.remove();
            delete this.element.data().datepicker;
            if (!this.isInput) {
                delete this.element.data().date;
            }
        },

        _utc_to_local: function(utc){
            return new Date(utc.getTime() + (utc.getTimezoneOffset()*60000));
        },
        _local_to_utc: function(local){
            return new Date(local.getTime() - (local.getTimezoneOffset()*60000));
        },
        _zero_time: function(local){
            return new Date(local.getFullYear(), local.getMonth(), local.getDate());
        },
        _zero_utc_time: function(utc){
            return new Date(Date.UTC(utc.getUTCFullYear(), utc.getUTCMonth(), utc.getUTCDate()));
        },

        getDate: function() {
            return this._utc_to_local(this.getUTCDate());
        },

        getUTCDate: function() {
            return this.date;
        },

        setDate: function(d) {
            this.setUTCDate(this._local_to_utc(d));
        },

        setUTCDate: function(d) {
            this.date = d;
            this.setValue();
        },

        setValue: function() {
            var formatted = this.getFormattedDate();
            if (!this.isInput) {
                if (this.component){
                    this.element.find('input').val(formatted).change();
                }
            } else {
                this.element.val(formatted).change();
            }
        },

        getFormattedDate: function(format) {
            if (format === undefined)
                format = this.o.format;
            return DPGlobal.formatDate(this.date, format, this.o.language);
        },

        setStartDate: function(startDate){
            this._process_options({startDate: startDate});
            this.update();
            this.updateNavArrows();
        },

        setEndDate: function(endDate){
            this._process_options({endDate: endDate});
            this.update();
            this.updateNavArrows();
        },

        setDaysOfWeekDisabled: function(daysOfWeekDisabled){
            this._process_options({daysOfWeekDisabled: daysOfWeekDisabled});
            this.update();
            this.updateNavArrows();
        },

        place: function(){
                        if(this.isInline) return;
            var calendarWidth = this.picker.outerWidth(),
                calendarHeight = this.picker.outerHeight(),
                visualPadding = 10,
                windowWidth = $window.width(),
                windowHeight = $window.height(),
                scrollTop = $window.scrollTop();

            var zIndex = parseInt(this.element.parents().filter(function() {
                            return $(this).css('z-index') != 'auto';
                        }).first().css('z-index'))+10;

            var offset = this.component ? this.component.parents('.input-group').offset() : this.element.offset();
            var height = this.component ? this.component.outerHeight(true) : this.element.outerHeight(false);
            var width = this.component ? this.component.outerWidth(true) : this.element.outerWidth(false);
            var left = offset.left,
                top = offset.top;

            this.picker.removeClass(
                'datepicker-orient-top datepicker-orient-bottom '+
                'datepicker-orient-right datepicker-orient-left'
            );

            if (this.o.orientation.x !== 'auto') {
                this.picker.addClass('datepicker-orient-' + this.o.orientation.x);
                if (this.o.orientation.x === 'right')
                    left -= calendarWidth - width;
            }
            // auto x orientation is best-placement: if it crosses a window
            // edge, fudge it sideways
            else {
                // Default to left
                this.picker.addClass('datepicker-orient-left');
                if (offset.left < 0)
                    left -= offset.left - visualPadding;
                else if (offset.left + calendarWidth > windowWidth)
                    left = windowWidth - calendarWidth - visualPadding;
            }

            // auto y orientation is best-situation: top or bottom, no fudging,
            // decision based on which shows more of the calendar
            var yorient = this.o.orientation.y,
                top_overflow, bottom_overflow;
            if (yorient === 'auto') {
                top_overflow = -scrollTop + offset.top - calendarHeight;
                bottom_overflow = scrollTop + windowHeight - (offset.top + height + calendarHeight);
                if (Math.max(top_overflow, bottom_overflow) === bottom_overflow)
                    yorient = 'top';
                else
                    yorient = 'bottom';
            }
            this.picker.addClass('datepicker-orient-' + yorient);
            if (yorient === 'top')
                top += height;
            else
                top -= calendarHeight + parseInt(this.picker.css('padding-top'));

            this.picker.css({
                top: top,
                left: left,
                zIndex: zIndex
            });
        },

        _allow_update: true,
        update: function(){
            if (!this._allow_update) return;

            var oldDate = new Date(this.date),
                date, fromArgs = false;
            if(arguments && arguments.length && (typeof arguments[0] === 'string' || arguments[0] instanceof Date)) {
                date = arguments[0];
                if (date instanceof Date)
                    date = this._local_to_utc(date);
                fromArgs = true;
            } else {
                date = this.isInput ? this.element.val() : this.element.data('date') || this.element.find('input').val();
                delete this.element.data().date;
            }

            this.date = DPGlobal.parseDate(date, this.o.format, this.o.language);

            if (fromArgs) {
                // setting date by clicking
                this.setValue();
            } else if (date) {
                // setting date by typing
                if (oldDate.getTime() !== this.date.getTime())
                    this._trigger('changeDate');
            } else {
                // clearing date
                this._trigger('clearDate');
            }

            if (this.date < this.o.startDate) {
                this.viewDate = new Date(this.o.startDate);
                this.date = new Date(this.o.startDate);
            } else if (this.date > this.o.endDate) {
                this.viewDate = new Date(this.o.endDate);
                this.date = new Date(this.o.endDate);
            } else {
                this.viewDate = new Date(this.date);
                this.date = new Date(this.date);
            }
            this.fill();
        },

        fillDow: function(){
            var dowCnt = this.o.weekStart,
            html = '<tr>';
            if(this.o.calendarWeeks){
                var cell = '<th class="cw">&nbsp;</th>';
                html += cell;
                this.picker.find('.datepicker-days thead tr:first-child').prepend(cell);
            }
            while (dowCnt < this.o.weekStart + 7) {
                html += '<th class="dow">'+dates[this.o.language].daysMin[(dowCnt++)%7]+'</th>';
            }
            html += '</tr>';
            this.picker.find('.datepicker-days thead').append(html);
        },

        fillMonths: function(){
            var html = '',
            i = 0;
            while (i < 12) {
                html += '<span class="month">'+dates[this.o.language].monthsShort[i++]+'</span>';
            }
            this.picker.find('.datepicker-months td').html(html);
        },

        setRange: function(range){
            if (!range || !range.length)
                delete this.range;
            else
                this.range = $.map(range, function(d){ return d.valueOf(); });
            this.fill();
        },

        getClassNames: function(date){
            var cls = [],
                year = this.viewDate.getUTCFullYear(),
                month = this.viewDate.getUTCMonth(),
                currentDate = this.date.valueOf(),
                today = new Date();
            if (date.getUTCFullYear() < year || (date.getUTCFullYear() == year && date.getUTCMonth() < month)) {
                cls.push('old');
            } else if (date.getUTCFullYear() > year || (date.getUTCFullYear() == year && date.getUTCMonth() > month)) {
                cls.push('new');
            }
            // Compare internal UTC date with local today, not UTC today
            if (this.o.todayHighlight &&
                date.getUTCFullYear() == today.getFullYear() &&
                date.getUTCMonth() == today.getMonth() &&
                date.getUTCDate() == today.getDate()) {
                cls.push('today');
            }
            if (currentDate && date.valueOf() == currentDate) {
                cls.push('active');
            }
            if (date.valueOf() < this.o.startDate || date.valueOf() > this.o.endDate ||
                $.inArray(date.getUTCDay(), this.o.daysOfWeekDisabled) !== -1) {
                cls.push('disabled');
            }
            if (this.range){
                if (date > this.range[0] && date < this.range[this.range.length-1]){
                    cls.push('range');
                }
                if ($.inArray(date.valueOf(), this.range) != -1){
                    cls.push('selected');
                }
            }
            return cls;
        },

        fill: function() {
            var d = new Date(this.viewDate),
                year = d.getUTCFullYear(),
                month = d.getUTCMonth(),
                startYear = this.o.startDate !== -Infinity ? this.o.startDate.getUTCFullYear() : -Infinity,
                startMonth = this.o.startDate !== -Infinity ? this.o.startDate.getUTCMonth() : -Infinity,
                endYear = this.o.endDate !== Infinity ? this.o.endDate.getUTCFullYear() : Infinity,
                endMonth = this.o.endDate !== Infinity ? this.o.endDate.getUTCMonth() : Infinity,
                currentDate = this.date && this.date.valueOf(),
                tooltip;
            this.picker.find('.datepicker-days thead th.datepicker-switch')
                        .text(dates[this.o.language].months[month]+' '+year);
            this.picker.find('tfoot th.today')
                        .text(dates[this.o.language].today)
                        .toggle(this.o.todayBtn !== false);
            this.picker.find('tfoot th.clear')
                        .text(dates[this.o.language].clear)
                        .toggle(this.o.clearBtn !== false);
            this.updateNavArrows();
            this.fillMonths();
            var prevMonth = UTCDate(year, month-1, 28,0,0,0,0),
                day = DPGlobal.getDaysInMonth(prevMonth.getUTCFullYear(), prevMonth.getUTCMonth());
            prevMonth.setUTCDate(day);
            prevMonth.setUTCDate(day - (prevMonth.getUTCDay() - this.o.weekStart + 7)%7);
            var nextMonth = new Date(prevMonth);
            nextMonth.setUTCDate(nextMonth.getUTCDate() + 42);
            nextMonth = nextMonth.valueOf();
            var html = [];
            var clsName;
            while(prevMonth.valueOf() < nextMonth) {
                if (prevMonth.getUTCDay() == this.o.weekStart) {
                    html.push('<tr>');
                    if(this.o.calendarWeeks){
                        // ISO 8601: First week contains first thursday.
                        // ISO also states week starts on Monday, but we can be more abstract here.
                        var
                            // Start of current week: based on weekstart/current date
                            ws = new Date(+prevMonth + (this.o.weekStart - prevMonth.getUTCDay() - 7) % 7 * 864e5),
                            // Thursday of this week
                            th = new Date(+ws + (7 + 4 - ws.getUTCDay()) % 7 * 864e5),
                            // First Thursday of year, year from thursday
                            yth = new Date(+(yth = UTCDate(th.getUTCFullYear(), 0, 1)) + (7 + 4 - yth.getUTCDay())%7*864e5),
                            // Calendar week: ms between thursdays, div ms per day, div 7 days
                            calWeek =  (th - yth) / 864e5 / 7 + 1;
                        html.push('<td class="cw">'+ calWeek +'</td>');

                    }
                }
                clsName = this.getClassNames(prevMonth);
                clsName.push('day');

                if (this.o.beforeShowDay !== $.noop){
                    var before = this.o.beforeShowDay(this._utc_to_local(prevMonth));
                    if (before === undefined)
                        before = {};
                    else if (typeof(before) === 'boolean')
                        before = {enabled: before};
                    else if (typeof(before) === 'string')
                        before = {classes: before};
                    if (before.enabled === false)
                        clsName.push('disabled');
                    if (before.classes)
                        clsName = clsName.concat(before.classes.split(/\s+/));
                    if (before.tooltip)
                        tooltip = before.tooltip;
                }

                clsName = $.unique(clsName);
                html.push('<td class="'+clsName.join(' ')+'"' + (tooltip ? ' title="'+tooltip+'"' : '') + '>'+prevMonth.getUTCDate() + '</td>');
                if (prevMonth.getUTCDay() == this.o.weekEnd) {
                    html.push('</tr>');
                }
                prevMonth.setUTCDate(prevMonth.getUTCDate()+1);
            }
            this.picker.find('.datepicker-days tbody').empty().append(html.join(''));
            var currentYear = this.date && this.date.getUTCFullYear();

            var months = this.picker.find('.datepicker-months')
                        .find('th:eq(1)')
                            .text(year)
                            .end()
                        .find('span').removeClass('active');
            if (currentYear && currentYear == year) {
                months.eq(this.date.getUTCMonth()).addClass('active');
            }
            if (year < startYear || year > endYear) {
                months.addClass('disabled');
            }
            if (year == startYear) {
                months.slice(0, startMonth).addClass('disabled');
            }
            if (year == endYear) {
                months.slice(endMonth+1).addClass('disabled');
            }

            html = '';
            year = parseInt(year/10, 10) * 10;
            var yearCont = this.picker.find('.datepicker-years')
                                .find('th:eq(1)')
                                    .text(year + '-' + (year + 9))
                                    .end()
                                .find('td');
            year -= 1;
            for (var i = -1; i < 11; i++) {
                html += '<span class="year'+(i == -1 ? ' old' : i == 10 ? ' new' : '')+(currentYear == year ? ' active' : '')+(year < startYear || year > endYear ? ' disabled' : '')+'">'+year+'</span>';
                year += 1;
            }
            yearCont.html(html);
        },

        updateNavArrows: function() {
            if (!this._allow_update) return;

            var d = new Date(this.viewDate),
                year = d.getUTCFullYear(),
                month = d.getUTCMonth();
            switch (this.viewMode) {
                case 0:
                    if (this.o.startDate !== -Infinity && year <= this.o.startDate.getUTCFullYear() && month <= this.o.startDate.getUTCMonth()) {
                        this.picker.find('.prev').css({visibility: 'hidden'});
                    } else {
                        this.picker.find('.prev').css({visibility: 'visible'});
                    }
                    if (this.o.endDate !== Infinity && year >= this.o.endDate.getUTCFullYear() && month >= this.o.endDate.getUTCMonth()) {
                        this.picker.find('.next').css({visibility: 'hidden'});
                    } else {
                        this.picker.find('.next').css({visibility: 'visible'});
                    }
                    break;
                case 1:
                case 2:
                    if (this.o.startDate !== -Infinity && year <= this.o.startDate.getUTCFullYear()) {
                        this.picker.find('.prev').css({visibility: 'hidden'});
                    } else {
                        this.picker.find('.prev').css({visibility: 'visible'});
                    }
                    if (this.o.endDate !== Infinity && year >= this.o.endDate.getUTCFullYear()) {
                        this.picker.find('.next').css({visibility: 'hidden'});
                    } else {
                        this.picker.find('.next').css({visibility: 'visible'});
                    }
                    break;
            }
        },

        click: function(e) {
            e.preventDefault();
            var target = $(e.target).closest('span, td, th');
            if (target.length == 1) {
                switch(target[0].nodeName.toLowerCase()) {
                    case 'th':
                        switch(target[0].className) {
                            case 'datepicker-switch':
                                this.showMode(1);
                                break;
                            case 'prev':
                            case 'next':
                                var dir = DPGlobal.modes[this.viewMode].navStep * (target[0].className == 'prev' ? -1 : 1);
                                switch(this.viewMode){
                                    case 0:
                                        this.viewDate = this.moveMonth(this.viewDate, dir);
                                        this._trigger('changeMonth', this.viewDate);
                                        break;
                                    case 1:
                                    case 2:
                                        this.viewDate = this.moveYear(this.viewDate, dir);
                                        if (this.viewMode === 1)
                                            this._trigger('changeYear', this.viewDate);
                                        break;
                                }
                                this.fill();
                                break;
                            case 'today':
                                var date = new Date();
                                date = UTCDate(date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0);

                                this.showMode(-2);
                                var which = this.o.todayBtn == 'linked' ? null : 'view';
                                this._setDate(date, which);
                                break;
                            case 'clear':
                                var element;
                                if (this.isInput)
                                    element = this.element;
                                else if (this.component)
                                    element = this.element.find('input');
                                if (element)
                                    element.val("").change();
                                this._trigger('changeDate');
                                this.update();
                                if (this.o.autoclose)
                                    this.hide();
                                break;
                        }
                        break;
                    case 'span':
                        if (!target.is('.disabled')) {
                            this.viewDate.setUTCDate(1);
                            if (target.is('.month')) {
                                var day = 1;
                                var month = target.parent().find('span').index(target);
                                var year = this.viewDate.getUTCFullYear();
                                this.viewDate.setUTCMonth(month);
                                this._trigger('changeMonth', this.viewDate);
                                if (this.o.minViewMode === 1) {
                                    this._setDate(UTCDate(year, month, day,0,0,0,0));
                                }
                            } else {
                                var year = parseInt(target.text(), 10)||0;
                                var day = 1;
                                var month = 0;
                                this.viewDate.setUTCFullYear(year);
                                this._trigger('changeYear', this.viewDate);
                                if (this.o.minViewMode === 2) {
                                    this._setDate(UTCDate(year, month, day,0,0,0,0));
                                }
                            }
                            this.showMode(-1);
                            this.fill();
                        }
                        break;
                    case 'td':
                        if (target.is('.day') && !target.is('.disabled')){
                            var day = parseInt(target.text(), 10)||1;
                            var year = this.viewDate.getUTCFullYear(),
                                month = this.viewDate.getUTCMonth();
                            if (target.is('.old')) {
                                if (month === 0) {
                                    month = 11;
                                    year -= 1;
                                } else {
                                    month -= 1;
                                }
                            } else if (target.is('.new')) {
                                if (month == 11) {
                                    month = 0;
                                    year += 1;
                                } else {
                                    month += 1;
                                }
                            }
                            this._setDate(UTCDate(year, month, day,0,0,0,0));
                        }
                        break;
                }
            }
        },

        _setDate: function(date, which){
            if (!which || which == 'date')
                this.date = new Date(date);
            if (!which || which  == 'view')
                this.viewDate = new Date(date);
            this.fill();
            this.setValue();
            this._trigger('changeDate');
            var element;
            if (this.isInput) {
                element = this.element;
            } else if (this.component){
                element = this.element.find('input');
            }
            if (element) {
                element.change();
            }
            if (this.o.autoclose && (!which || which == 'date')) {
                this.hide();
            }
        },

        moveMonth: function(date, dir){
            if (!dir) return date;
            var new_date = new Date(date.valueOf()),
                day = new_date.getUTCDate(),
                month = new_date.getUTCMonth(),
                mag = Math.abs(dir),
                new_month, test;
            dir = dir > 0 ? 1 : -1;
            if (mag == 1){
                test = dir == -1
                    // If going back one month, make sure month is not current month
                    // (eg, Mar 31 -> Feb 31 == Feb 28, not Mar 02)
                    ? function(){ return new_date.getUTCMonth() == month; }
                    // If going forward one month, make sure month is as expected
                    // (eg, Jan 31 -> Feb 31 == Feb 28, not Mar 02)
                    : function(){ return new_date.getUTCMonth() != new_month; };
                new_month = month + dir;
                new_date.setUTCMonth(new_month);
                // Dec -> Jan (12) or Jan -> Dec (-1) -- limit expected date to 0-11
                if (new_month < 0 || new_month > 11)
                    new_month = (new_month + 12) % 12;
            } else {
                // For magnitudes >1, move one month at a time...
                for (var i=0; i<mag; i++)
                    // ...which might decrease the day (eg, Jan 31 to Feb 28, etc)...
                    new_date = this.moveMonth(new_date, dir);
                // ...then reset the day, keeping it in the new month
                new_month = new_date.getUTCMonth();
                new_date.setUTCDate(day);
                test = function(){ return new_month != new_date.getUTCMonth(); };
            }
            // Common date-resetting loop -- if date is beyond end of month, make it
            // end of month
            while (test()){
                new_date.setUTCDate(--day);
                new_date.setUTCMonth(new_month);
            }
            return new_date;
        },

        moveYear: function(date, dir){
            return this.moveMonth(date, dir*12);
        },

        dateWithinRange: function(date){
            return date >= this.o.startDate && date <= this.o.endDate;
        },

        keydown: function(e){
            if (this.picker.is(':not(:visible)')){
                if (e.keyCode == 27) // allow escape to hide and re-show picker
                    this.show();
                return;
            }
            var dateChanged = false,
                dir, day, month,
                newDate, newViewDate;
            switch(e.keyCode){
                case 27: // escape
                    this.hide();
                    e.preventDefault();
                    break;
                case 37: // left
                case 39: // right
                    if (!this.o.keyboardNavigation) break;
                    dir = e.keyCode == 37 ? -1 : 1;
                    if (e.ctrlKey){
                        newDate = this.moveYear(this.date, dir);
                        newViewDate = this.moveYear(this.viewDate, dir);
                        this._trigger('changeYear', this.viewDate);
                    } else if (e.shiftKey){
                        newDate = this.moveMonth(this.date, dir);
                        newViewDate = this.moveMonth(this.viewDate, dir);
                        this._trigger('changeMonth', this.viewDate);
                    } else {
                        newDate = new Date(this.date);
                        newDate.setUTCDate(this.date.getUTCDate() + dir);
                        newViewDate = new Date(this.viewDate);
                        newViewDate.setUTCDate(this.viewDate.getUTCDate() + dir);
                    }
                    if (this.dateWithinRange(newDate)){
                        this.date = newDate;
                        this.viewDate = newViewDate;
                        this.setValue();
                        this.update();
                        e.preventDefault();
                        dateChanged = true;
                    }
                    break;
                case 38: // up
                case 40: // down
                    if (!this.o.keyboardNavigation) break;
                    dir = e.keyCode == 38 ? -1 : 1;
                    if (e.ctrlKey){
                        newDate = this.moveYear(this.date, dir);
                        newViewDate = this.moveYear(this.viewDate, dir);
                        this._trigger('changeYear', this.viewDate);
                    } else if (e.shiftKey){
                        newDate = this.moveMonth(this.date, dir);
                        newViewDate = this.moveMonth(this.viewDate, dir);
                        this._trigger('changeMonth', this.viewDate);
                    } else {
                        newDate = new Date(this.date);
                        newDate.setUTCDate(this.date.getUTCDate() + dir * 7);
                        newViewDate = new Date(this.viewDate);
                        newViewDate.setUTCDate(this.viewDate.getUTCDate() + dir * 7);
                    }
                    if (this.dateWithinRange(newDate)){
                        this.date = newDate;
                        this.viewDate = newViewDate;
                        this.setValue();
                        this.update();
                        e.preventDefault();
                        dateChanged = true;
                    }
                    break;
                case 13: // enter
                    this.hide();
                    e.preventDefault();
                    break;
                case 9: // tab
                    this.hide();
                    break;
            }
            if (dateChanged){
                this._trigger('changeDate');
                var element;
                if (this.isInput) {
                    element = this.element;
                } else if (this.component){
                    element = this.element.find('input');
                }
                if (element) {
                    element.change();
                }
            }
        },

        showMode: function(dir) {
            if (dir) {
                this.viewMode = Math.max(this.o.minViewMode, Math.min(2, this.viewMode + dir));
            }
            /*
                vitalets: fixing bug of very special conditions:
                jquery 1.7.1 + webkit + show inline datepicker in bootstrap popover.
                Method show() does not set display css correctly and datepicker is not shown.
                Changed to .css('display', 'block') solve the problem.
                See https://github.com/vitalets/x-editable/issues/37

                In jquery 1.7.2+ everything works fine.
            */
            //this.picker.find('>div').hide().filter('.datepicker-'+DPGlobal.modes[this.viewMode].clsName).show();
            this.picker.find('>div').hide().filter('.datepicker-'+DPGlobal.modes[this.viewMode].clsName).css('display', 'block');
            this.updateNavArrows();
        }
    };

    var DateRangePicker = function(element, options){
        this.element = $(element);
        this.inputs = $.map(options.inputs, function(i){ return i.jquery ? i[0] : i; });
        delete options.inputs;

        $(this.inputs)
            .datepicker(options)
            .bind('changeDate', $.proxy(this.dateUpdated, this));

        this.pickers = $.map(this.inputs, function(i){ return $(i).data('datepicker'); });
        this.updateDates();
    };
    DateRangePicker.prototype = {
        updateDates: function(){
            this.dates = $.map(this.pickers, function(i){ return i.date; });
            this.updateRanges();
        },
        updateRanges: function(){
            var range = $.map(this.dates, function(d){ return d.valueOf(); });
            $.each(this.pickers, function(i, p){
                p.setRange(range);
            });
        },
        dateUpdated: function(e){
            var dp = $(e.target).data('datepicker'),
                new_date = dp.getUTCDate(),
                i = $.inArray(e.target, this.inputs),
                l = this.inputs.length;
            if (i == -1) return;

            if (new_date < this.dates[i]){
                // Date being moved earlier/left
                while (i>=0 && new_date < this.dates[i]){
                    this.pickers[i--].setUTCDate(new_date);
                }
            }
            else if (new_date > this.dates[i]){
                // Date being moved later/right
                while (i<l && new_date > this.dates[i]){
                    this.pickers[i++].setUTCDate(new_date);
                }
            }
            this.updateDates();
        },
        remove: function(){
            $.map(this.pickers, function(p){ p.remove(); });
            delete this.element.data().datepicker;
        }
    };

    function opts_from_el(el, prefix){
        // Derive options from element data-attrs
        var data = $(el).data(),
            out = {}, inkey,
            replace = new RegExp('^' + prefix.toLowerCase() + '([A-Z])'),
            prefix = new RegExp('^' + prefix.toLowerCase());
        for (var key in data)
            if (prefix.test(key)){
                inkey = key.replace(replace, function(_,a){ return a.toLowerCase(); });
                out[inkey] = data[key];
            }
        return out;
    }

    function opts_from_locale(lang){
        // Derive options from locale plugins
        var out = {};
        // Check if "de-DE" style date is available, if not language should
        // fallback to 2 letter code eg "de"
        if (!dates[lang]) {
            lang = lang.split('-')[0]
            if (!dates[lang])
                return;
        }
        var d = dates[lang];
        $.each(locale_opts, function(i,k){
            if (k in d)
                out[k] = d[k];
        });
        return out;
    }

    var old = $.fn.datepicker;
    $.fn.datepicker = function ( option ) {
        var args = Array.apply(null, arguments);
        args.shift();
        var internal_return,
            this_return;
        this.each(function () {
            var $this = $(this),
                data = $this.data('datepicker'),
                options = typeof option == 'object' && option;
            if (!data) {
                var elopts = opts_from_el(this, 'date'),
                    // Preliminary otions
                    xopts = $.extend({}, defaults, elopts, options),
                    locopts = opts_from_locale(xopts.language),
                    // Options priority: js args, data-attrs, locales, defaults
                    opts = $.extend({}, defaults, locopts, elopts, options);
                if ($this.is('.input-daterange') || opts.inputs){
                    var ropts = {
                        inputs: opts.inputs || $this.find('input').toArray()
                    };
                    $this.data('datepicker', (data = new DateRangePicker(this, $.extend(opts, ropts))));
                }
                else{
                    $this.data('datepicker', (data = new Datepicker(this, opts)));
                }
            }
            if (typeof option == 'string' && typeof data[option] == 'function') {
                internal_return = data[option].apply(data, args);
                if (internal_return !== undefined)
                    return false;
            }
        });
        if (internal_return !== undefined)
            return internal_return;
        else
            return this;
    };

    var defaults = $.fn.datepicker.defaults = {
        autoclose: false,
        beforeShowDay: $.noop,
        calendarWeeks: false,
        clearBtn: false,
        daysOfWeekDisabled: [],
        endDate: Infinity,
        forceParse: true,
        format: 'mm/dd/yyyy',
        keyboardNavigation: true,
        language: 'en',
        minViewMode: 0,
        orientation: "auto",
        rtl: false,
        startDate: -Infinity,
        startView: 0,
        todayBtn: false,
        todayHighlight: false,
        weekStart: 0
    };
    var locale_opts = $.fn.datepicker.locale_opts = [
        'format',
        'rtl',
        'weekStart'
    ];
    $.fn.datepicker.Constructor = Datepicker;
    var dates = $.fn.datepicker.dates = {
        en: {
            days: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
            daysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
            daysMin: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa", "Su"],
            months: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
            monthsShort: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
            today: "Today",
            clear: "Clear"
        }
    };

    var DPGlobal = {
        modes: [
            {
                clsName: 'days',
                navFnc: 'Month',
                navStep: 1
            },
            {
                clsName: 'months',
                navFnc: 'FullYear',
                navStep: 1
            },
            {
                clsName: 'years',
                navFnc: 'FullYear',
                navStep: 10
        }],
        isLeapYear: function (year) {
            return (((year % 4 === 0) && (year % 100 !== 0)) || (year % 400 === 0));
        },
        getDaysInMonth: function (year, month) {
            return [31, (DPGlobal.isLeapYear(year) ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][month];
        },
        validParts: /dd?|DD?|mm?|MM?|yy(?:yy)?/g,
        nonpunctuation: /[^ -\/:-@\[\u3400-\u9fff-`{-~\t\n\r]+/g,
        parseFormat: function(format){
            // IE treats \0 as a string end in inputs (truncating the value),
            // so it's a bad format delimiter, anyway
            var separators = format.replace(this.validParts, '\0').split('\0'),
                parts = format.match(this.validParts);
            if (!separators || !separators.length || !parts || parts.length === 0){
                throw new Error("Invalid date format.");
            }
            return {separators: separators, parts: parts};
        },
        parseDate: function(date, format, language) {
            if (date instanceof Date) return date;
            if (typeof format === 'string')
                format = DPGlobal.parseFormat(format);
            if (/^[\-+]\d+[dmwy]([\s,]+[\-+]\d+[dmwy])*$/.test(date)) {
                var part_re = /([\-+]\d+)([dmwy])/,
                    parts = date.match(/([\-+]\d+)([dmwy])/g),
                    part, dir;
                date = new Date();
                for (var i=0; i<parts.length; i++) {
                    part = part_re.exec(parts[i]);
                    dir = parseInt(part[1]);
                    switch(part[2]){
                        case 'd':
                            date.setUTCDate(date.getUTCDate() + dir);
                            break;
                        case 'm':
                            date = Datepicker.prototype.moveMonth.call(Datepicker.prototype, date, dir);
                            break;
                        case 'w':
                            date.setUTCDate(date.getUTCDate() + dir * 7);
                            break;
                        case 'y':
                            date = Datepicker.prototype.moveYear.call(Datepicker.prototype, date, dir);
                            break;
                    }
                }
                return UTCDate(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate(), 0, 0, 0);
            }
            var parts = date && date.match(this.nonpunctuation) || [],
                date = new Date(),
                parsed = {},
                setters_order = ['yyyy', 'yy', 'M', 'MM', 'm', 'mm', 'd', 'dd'],
                setters_map = {
                    yyyy: function(d,v){ return d.setUTCFullYear(v); },
                    yy: function(d,v){ return d.setUTCFullYear(
                        function(){
                        var year = v.toString(),
                            lastTwo = year.match(/\d{2}$/);

                        if(year.length < 3) return 2000+v;

                        lastTwo = (year.length == 3) ?
                                    year.match(/\d{1}$/)[0]+'0' : lastTwo;
                        return parseInt('20'+lastTwo);
                        }()
                    )},
                    m: function(d,v){
                        if (isNaN(d))
                            return d;
                        v -= 1;
                        while (v<0) v += 12;
                        v %= 12;
                        d.setUTCMonth(v);
                        while (d.getUTCMonth() != v)
                            d.setUTCDate(d.getUTCDate()-1);
                        return d;
                    },
                    d: function(d,v){ return d.setUTCDate(v); }
                },
                val, filtered, part;
            setters_map['M'] = setters_map['MM'] = setters_map['mm'] = setters_map['m'];
            setters_map['dd'] = setters_map['d'];
            date = UTCDate(date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0);
            var fparts = format.parts.slice();
            // Remove noop parts
            if (parts.length != fparts.length) {
                fparts = $(fparts).filter(function(i,p){
                    return $.inArray(p, setters_order) !== -1;
                }).toArray();
            }
            // Process remainder
            if (parts.length == fparts.length) {
                for (var i=0, cnt = fparts.length; i < cnt; i++) {
                    val = parseInt(parts[i], 10);
                    part = fparts[i];
                    if (isNaN(val)) {
                        switch(part) {
                            case 'MM':
                                filtered = $(dates[language].months).filter(function(){
                                    var m = this.slice(0, parts[i].length),
                                        p = parts[i].slice(0, m.length);
                                    return m == p;
                                });
                                val = $.inArray(filtered[0], dates[language].months) + 1;
                                break;
                            case 'M':
                                filtered = $(dates[language].monthsShort).filter(function(){
                                    var m = this.slice(0, parts[i].length),
                                        p = parts[i].slice(0, m.length);
                                    return m == p;
                                });
                                val = $.inArray(filtered[0], dates[language].monthsShort) + 1;
                                break;
                        }
                    }
                    parsed[part] = val;
                }
                for (var i=0, _date, s; i<setters_order.length; i++){
                    s = setters_order[i];
                    if (s in parsed && !isNaN(parsed[s])){
                        _date = new Date(date);
                        setters_map[s](_date, parsed[s]);
                        if (!isNaN(_date))
                            date = _date;
                    }
                }
            }
            return date;
        },
        formatDate: function(date, format, language){
            if (typeof format === 'string')
                format = DPGlobal.parseFormat(format);
            var val = {
                d: date.getUTCDate(),
                D: dates[language].daysShort[date.getUTCDay()],
                DD: dates[language].days[date.getUTCDay()],
                m: date.getUTCMonth() + 1,
                M: dates[language].monthsShort[date.getUTCMonth()],
                MM: dates[language].months[date.getUTCMonth()],
                yy: date.getUTCFullYear().toString().substring(2),
                yyyy: date.getUTCFullYear()
            };
            val.dd = (val.d < 10 ? '0' : '') + val.d;
            val.mm = (val.m < 10 ? '0' : '') + val.m;
            var date = [],
                seps = $.extend([], format.separators);
            for (var i=0, cnt = format.parts.length; i <= cnt; i++) {
                if (seps.length)
                    date.push(seps.shift());
                date.push(val[format.parts[i]]);
            }
            return date.join('');
        },
        headTemplate: '<thead>'+
                            '<tr>'+
                                '<th class="prev">&laquo;</th>'+
                                '<th colspan="5" class="datepicker-switch"></th>'+
                                '<th class="next">&raquo;</th>'+
                            '</tr>'+
                        '</thead>',
        contTemplate: '<tbody><tr><td colspan="7"></td></tr></tbody>',
        footTemplate: '<tfoot><tr><th colspan="7" class="today"></th></tr><tr><th colspan="7" class="clear"></th></tr></tfoot>'
    };
    DPGlobal.template = '<div class="datepicker">'+
                            '<div class="datepicker-days">'+
                                '<table class=" table-condensed">'+
                                    DPGlobal.headTemplate+
                                    '<tbody></tbody>'+
                                    DPGlobal.footTemplate+
                                '</table>'+
                            '</div>'+
                            '<div class="datepicker-months">'+
                                '<table class="table-condensed">'+
                                    DPGlobal.headTemplate+
                                    DPGlobal.contTemplate+
                                    DPGlobal.footTemplate+
                                '</table>'+
                            '</div>'+
                            '<div class="datepicker-years">'+
                                '<table class="table-condensed">'+
                                    DPGlobal.headTemplate+
                                    DPGlobal.contTemplate+
                                    DPGlobal.footTemplate+
                                '</table>'+
                            '</div>'+
                        '</div>';

    $.fn.datepicker.DPGlobal = DPGlobal;


    /* DATEPICKER NO CONFLICT
    * =================== */

    $.fn.datepicker.noConflict = function(){
        $.fn.datepicker = old;
        return this;
    };


    /* DATEPICKER DATA-API
    * ================== */

    $(document).on(
        'focus.datepicker.data-api click.datepicker.data-api',
        '[data-provide="datepicker"]',
        function(e){
            var $this = $(this);
            if ($this.data('datepicker')) return;
            e.preventDefault();
            // component click requires us to explicitly show it
            $this.datepicker('show');
        }
    );
    $(function(){
        $('[data-provide="datepicker-inline"]').datepicker();
    });

}( window.jQuery ));

/*
* @fileOverview TouchSwipe - jQuery Plugin
* @version 1.6.5
*
* @author Matt Bryson http://www.github.com/mattbryson
* @see https://github.com/mattbryson/TouchSwipe-Jquery-Plugin
* @see http://labs.skinkers.com/touchSwipe/
* @see http://plugins.jquery.com/project/touchSwipe
*
* Copyright (c) 2010 Matt Bryson
* Dual licensed under the MIT or GPL Version 2 licenses.
*
*/

(function (factory) {
    if (typeof define === 'function' && define.amd && define.amd.jQuery) {
        // AMD. Register as anonymous module.
        define(['jquery'], factory);
    } else {
        // Browser globals.
        factory(jQuery);
    }
}(function ($) {
    "use strict";

    //Constants
    var LEFT = "left",
        RIGHT = "right",
        UP = "up",
        DOWN = "down",
        IN = "in",
        OUT = "out",

        NONE = "none",
        AUTO = "auto",
        
        SWIPE = "swipe",
        PINCH = "pinch",
        TAP = "tap",
        DOUBLE_TAP = "doubletap",
        LONG_TAP = "longtap",
        
        HORIZONTAL = "horizontal",
        VERTICAL = "vertical",

        ALL_FINGERS = "all",
        
        DOUBLE_TAP_THRESHOLD = 10,

        PHASE_START = "start",
        PHASE_MOVE = "move",
        PHASE_END = "end",
        PHASE_CANCEL = "cancel",

        SUPPORTS_TOUCH = 'ontouchstart' in window,

        PLUGIN_NS = 'TouchSwipe';



    /**
    * The default configuration, and available options to configure touch swipe with.
    * You can set the default values by updating any of the properties prior to instantiation.
    * @name $.fn.swipe.defaults
    * @namespace
    * @property {int} [fingers=1] The number of fingers to detect in a swipe. Any swipes that do not meet this requirement will NOT trigger swipe handlers.
    * @property {int} [threshold=75] The number of pixels that the user must move their finger by before it is considered a swipe. 
    * @property {int} [cancelThreshold=null] The number of pixels that the user must move their finger back from the original swipe direction to cancel the gesture.
    * @property {int} [pinchThreshold=20] The number of pixels that the user must pinch their finger by before it is considered a pinch. 
    * @property {int} [maxTimeThreshold=null] Time, in milliseconds, between touchStart and touchEnd must NOT exceed in order to be considered a swipe. 
    * @property {int} [fingerReleaseThreshold=250] Time in milliseconds between releasing multiple fingers.  If 2 fingers are down, and are released one after the other, if they are within this threshold, it counts as a simultaneous release. 
    * @property {int} [longTapThreshold=500] Time in milliseconds between tap and release for a long tap
    * @property {int} [doubleTapThreshold=200] Time in milliseconds between 2 taps to count as a double tap
    * @property {function} [swipe=null] A handler to catch all swipes. See {@link $.fn.swipe#event:swipe}
    * @property {function} [swipeLeft=null] A handler that is triggered for "left" swipes. See {@link $.fn.swipe#event:swipeLeft}
    * @property {function} [swipeRight=null] A handler that is triggered for "right" swipes. See {@link $.fn.swipe#event:swipeRight}
    * @property {function} [swipeUp=null] A handler that is triggered for "up" swipes. See {@link $.fn.swipe#event:swipeUp}
    * @property {function} [swipeDown=null] A handler that is triggered for "down" swipes. See {@link $.fn.swipe#event:swipeDown}
    * @property {function} [swipeStatus=null] A handler triggered for every phase of the swipe. See {@link $.fn.swipe#event:swipeStatus}
    * @property {function} [pinchIn=null] A handler triggered for pinch in events. See {@link $.fn.swipe#event:pinchIn}
    * @property {function} [pinchOut=null] A handler triggered for pinch out events. See {@link $.fn.swipe#event:pinchOut}
    * @property {function} [pinchStatus=null] A handler triggered for every phase of a pinch. See {@link $.fn.swipe#event:pinchStatus}
    * @property {function} [tap=null] A handler triggered when a user just taps on the item, rather than swipes it. If they do not move, tap is triggered, if they do move, it is not. 
    * @property {function} [doubleTap=null] A handler triggered when a user double taps on the item. The delay between taps can be set with the doubleTapThreshold property. See {@link $.fn.swipe.defaults#doubleTapThreshold}
    * @property {function} [longTap=null] A handler triggered when a user long taps on the item. The delay between start and end can be set with the longTapThreshold property. See {@link $.fn.swipe.defaults#doubleTapThreshold}
    * @property {boolean} [triggerOnTouchEnd=true] If true, the swipe events are triggered when the touch end event is received (user releases finger).  If false, it will be triggered on reaching the threshold, and then cancel the touch event automatically. 
    * @property {boolean} [triggerOnTouchLeave=false] If true, then when the user leaves the swipe object, the swipe will end and trigger appropriate handlers. 
    * @property {string|undefined} [allowPageScroll='auto'] How the browser handles page scrolls when the user is swiping on a touchSwipe object. See {@link $.fn.swipe.pageScroll}.  <br/><br/>
                                        <code>"auto"</code> : all undefined swipes will cause the page to scroll in that direction. <br/>
                                        <code>"none"</code> : the page will not scroll when user swipes. <br/>
                                        <code>"horizontal"</code> : will force page to scroll on horizontal swipes. <br/>
                                        <code>"vertical"</code> : will force page to scroll on vertical swipes. <br/>
    * @property {boolean} [fallbackToMouseEvents=true] If true mouse events are used when run on a non touch device, false will stop swipes being triggered by mouse events on non tocuh devices. 
    * @property {string} [excludedElements="button, input, select, textarea, a, .noSwipe"] A jquery selector that specifies child elements that do NOT trigger swipes. By default this excludes all form, input, select, button, anchor and .noSwipe elements. 
    
    */
    var defaults = {
        fingers: 1,         
        threshold: 75,  
        cancelThreshold:null,   
        pinchThreshold:20,
        maxTimeThreshold: null, 
        fingerReleaseThreshold:250, 
        longTapThreshold:500,
        doubleTapThreshold:200,
        swipe: null,        
        swipeLeft: null,    
        swipeRight: null,   
        swipeUp: null,      
        swipeDown: null,    
        swipeStatus: null,  
        pinchIn:null,       
        pinchOut:null,      
        pinchStatus:null,   
        click:null, //Deprecated since 1.6.2
        tap:null,
        doubleTap:null,
        longTap:null,       
        triggerOnTouchEnd: true, 
        triggerOnTouchLeave:false, 
        allowPageScroll: "auto", 
        fallbackToMouseEvents: true,    
        excludedElements:"label, button, input, select, textarea, a, .noSwipe"
    };



    /**
    * Applies TouchSwipe behaviour to one or more jQuery objects.
    * The TouchSwipe plugin can be instantiated via this method, or methods within 
    * TouchSwipe can be executed via this method as per jQuery plugin architecture.
    * @see TouchSwipe
    * @class
    * @param {Mixed} method If the current DOMNode is a TouchSwipe object, and <code>method</code> is a TouchSwipe method, then
    * the <code>method</code> is executed, and any following arguments are passed to the TouchSwipe method.
    * If <code>method</code> is an object, then the TouchSwipe class is instantiated on the current DOMNode, passing the 
    * configuration properties defined in the object. See TouchSwipe
    *
    */
    $.fn.swipe = function (method) {
        var $this = $(this),
            plugin = $this.data(PLUGIN_NS);

        //Check if we are already instantiated and trying to execute a method   
        if (plugin && typeof method === 'string') {
            if (plugin[method]) {
                return plugin[method].apply(this, Array.prototype.slice.call(arguments, 1));
            } else {
                $.error('Method ' + method + ' does not exist on jQuery.swipe');
            }
        }
        //Else not instantiated and trying to pass init object (or nothing)
        else if (!plugin && (typeof method === 'object' || !method)) {
            return init.apply(this, arguments);
        }

        return $this;
    };

    //Expose our defaults so a user could override the plugin defaults
    $.fn.swipe.defaults = defaults;

    /**
    * The phases that a touch event goes through.  The <code>phase</code> is passed to the event handlers. 
    * These properties are read only, attempting to change them will not alter the values passed to the event handlers.
    * @namespace
    * @readonly
    * @property {string} PHASE_START Constant indicating the start phase of the touch event. Value is <code>"start"</code>.
    * @property {string} PHASE_MOVE Constant indicating the move phase of the touch event. Value is <code>"move"</code>.
    * @property {string} PHASE_END Constant indicating the end phase of the touch event. Value is <code>"end"</code>.
    * @property {string} PHASE_CANCEL Constant indicating the cancel phase of the touch event. Value is <code>"cancel"</code>.
    */
    $.fn.swipe.phases = {
        PHASE_START: PHASE_START,
        PHASE_MOVE: PHASE_MOVE,
        PHASE_END: PHASE_END,
        PHASE_CANCEL: PHASE_CANCEL
    };

    /**
    * The direction constants that are passed to the event handlers. 
    * These properties are read only, attempting to change them will not alter the values passed to the event handlers.
    * @namespace
    * @readonly
    * @property {string} LEFT Constant indicating the left direction. Value is <code>"left"</code>.
    * @property {string} RIGHT Constant indicating the right direction. Value is <code>"right"</code>.
    * @property {string} UP Constant indicating the up direction. Value is <code>"up"</code>.
    * @property {string} DOWN Constant indicating the down direction. Value is <code>"cancel"</code>.
    * @property {string} IN Constant indicating the in direction. Value is <code>"in"</code>.
    * @property {string} OUT Constant indicating the out direction. Value is <code>"out"</code>.
    */
    $.fn.swipe.directions = {
        LEFT: LEFT,
        RIGHT: RIGHT,
        UP: UP,
        DOWN: DOWN,
        IN : IN,
        OUT: OUT
    };
    
    /**
    * The page scroll constants that can be used to set the value of <code>allowPageScroll</code> option
    * These properties are read only
    * @namespace
    * @readonly
    * @see $.fn.swipe.defaults#allowPageScroll
    * @property {string} NONE Constant indicating no page scrolling is allowed. Value is <code>"none"</code>.
    * @property {string} HORIZONTAL Constant indicating horizontal page scrolling is allowed. Value is <code>"horizontal"</code>.
    * @property {string} VERTICAL Constant indicating vertical page scrolling is allowed. Value is <code>"vertical"</code>.
    * @property {string} AUTO Constant indicating either horizontal or vertical will be allowed, depending on the swipe handlers registered. Value is <code>"auto"</code>.
    */
    $.fn.swipe.pageScroll = {
        NONE: NONE,
        HORIZONTAL: HORIZONTAL,
        VERTICAL: VERTICAL,
        AUTO: AUTO
    };

    /**
    * Constants representing the number of fingers used in a swipe.  These are used to set both the value of <code>fingers</code> in the 
    * options object, as well as the value of the <code>fingers</code> event property.
    * These properties are read only, attempting to change them will not alter the values passed to the event handlers.
    * @namespace
    * @readonly
    * @see $.fn.swipe.defaults#fingers
    * @property {string} ONE Constant indicating 1 finger is to be detected / was detected. Value is <code>1</code>.
    * @property {string} TWO Constant indicating 2 fingers are to be detected / were detected. Value is <code>1</code>.
    * @property {string} THREE Constant indicating 3 finger are to be detected / were detected. Value is <code>1</code>.
    * @property {string} ALL Constant indicating any combination of finger are to be detected.  Value is <code>"all"</code>.
    */
    $.fn.swipe.fingers = {
        ONE: 1,
        TWO: 2,
        THREE: 3,
        ALL: ALL_FINGERS
    };

    /**
    * Initialise the plugin for each DOM element matched
    * This creates a new instance of the main TouchSwipe class for each DOM element, and then
    * saves a reference to that instance in the elements data property.
    * @internal
    */
    function init(options) {
        //Prep and extend the options
        if (options && (options.allowPageScroll === undefined && (options.swipe !== undefined || options.swipeStatus !== undefined))) {
            options.allowPageScroll = NONE;
        }
        
        //Check for deprecated options
        //Ensure that any old click handlers are assigned to the new tap, unless we have a tap
        if(options.click!==undefined && options.tap===undefined) {
            options.tap = options.click;
        }

        if (!options) {
            options = {};
        }
        
        //pass empty object so we dont modify the defaults
        options = $.extend({}, $.fn.swipe.defaults, options);

        //For each element instantiate the plugin
        return this.each(function () {
            var $this = $(this);

            //Check we havent already initialised the plugin
            var plugin = $this.data(PLUGIN_NS);

            if (!plugin) {
                plugin = new TouchSwipe(this, options);
                $this.data(PLUGIN_NS, plugin);
            }
        });
    }

    /**
    * Main TouchSwipe Plugin Class.
    * Do not use this to construct your TouchSwipe object, use the jQuery plugin method $.fn.swipe(); {@link $.fn.swipe}
    * @private
    * @name TouchSwipe
    * @param {DOMNode} element The HTML DOM object to apply to plugin to
    * @param {Object} options The options to configure the plugin with.  @link {$.fn.swipe.defaults}
    * @see $.fh.swipe.defaults
    * @see $.fh.swipe
    * @class
    */
    function TouchSwipe(element, options) {
        var useTouchEvents = (SUPPORTS_TOUCH || !options.fallbackToMouseEvents),
            START_EV = useTouchEvents ? 'touchstart' : 'mousedown',
            MOVE_EV = useTouchEvents ? 'touchmove' : 'mousemove',
            END_EV = useTouchEvents ? 'touchend' : 'mouseup',
            LEAVE_EV = useTouchEvents ? null : 'mouseleave', //we manually detect leave on touch devices, so null event here
            CANCEL_EV = 'touchcancel';



        //touch properties
        var distance = 0,
            direction = null,
            duration = 0,
            startTouchesDistance = 0,
            endTouchesDistance = 0,
            pinchZoom = 1,
            pinchDistance = 0,
            pinchDirection = 0,
            maximumsMap=null;

        
        
        //jQuery wrapped element for this instance
        var $element = $(element);
        
        //Current phase of th touch cycle
        var phase = "start";

        // the current number of fingers being used.
        var fingerCount = 0;            

        //track mouse points / delta
        var fingerData=null;

        //track times
        var startTime = 0,
            endTime = 0,
            previousTouchEndTime=0,
            previousTouchFingerCount=0,
            doubleTapStartTime=0;

        //Timeouts
        var singleTapTimeout=null;
        
        // Add gestures to all swipable areas if supported
        try {
            $element.bind(START_EV, touchStart);
            $element.bind(CANCEL_EV, touchCancel);
        }
        catch (e) {
            $.error('events not supported ' + START_EV + ',' + CANCEL_EV + ' on jQuery.swipe');
        }

        //
        //Public methods
        //
        
        /**
        * re-enables the swipe plugin with the previous configuration
        * @function
        * @name $.fn.swipe#enable
        * @return {DOMNode} The Dom element that was registered with TouchSwipe 
        * @example $("#element").swipe("enable");
        */
        this.enable = function () {
            $element.bind(START_EV, touchStart);
            $element.bind(CANCEL_EV, touchCancel);
            return $element;
        };

        /**
        * disables the swipe plugin
        * @function
        * @name $.fn.swipe#disable
        * @return {DOMNode} The Dom element that is now registered with TouchSwipe
        * @example $("#element").swipe("disable");
        */
        this.disable = function () {
            removeListeners();
            return $element;
        };

        /**
        * Destroy the swipe plugin completely. To use any swipe methods, you must re initialise the plugin.
        * @function
        * @name $.fn.swipe#destroy
        * @return {DOMNode} The Dom element that was registered with TouchSwipe 
        * @example $("#element").swipe("destroy");
        */
        this.destroy = function () {
            removeListeners();
            $element.data(PLUGIN_NS, null);
            return $element;
        };


        /**
         * Allows run time updating of the swipe configuration options.
         * @function
         * @name $.fn.swipe#option
         * @param {String} property The option property to get or set
         * @param {Object} [value] The value to set the property to
         * @return {Object} If only a property name is passed, then that property value is returned.
         * @example $("#element").swipe("option", "threshold"); // return the threshold
         * @example $("#element").swipe("option", "threshold", 100); // set the threshold after init
         * @see $.fn.swipe.defaults
         *
         */
        this.option = function (property, value) {
            if(options[property]!==undefined) {
                if(value===undefined) {
                    return options[property];
                } else {
                    options[property] = value;
                }
            } else {
                $.error('Option ' + property + ' does not exist on jQuery.swipe.options');
            }

            return null;
        }

        //
        // Private methods
        //
        
        //
        // EVENTS
        //
        /**
        * Event handler for a touch start event.
        * Stops the default click event from triggering and stores where we touched
        * @inner
        * @param {object} jqEvent The normalised jQuery event object.
        */
        function touchStart(jqEvent) {
            //If we already in a touch event (a finger already in use) then ignore subsequent ones..
            if( getTouchInProgress() )
                return;
            
            //Check if this element matches any in the excluded elements selectors,  or its parent is excluded, if so, DON'T swipe
            if( $(jqEvent.target).closest( options.excludedElements, $element ).length>0 ) 
                return;
                
            //As we use Jquery bind for events, we need to target the original event object
            //If these events are being programmatically triggered, we don't have an original event object, so use the Jq one.
            var event = jqEvent.originalEvent ? jqEvent.originalEvent : jqEvent;
            
            var ret,
                evt = SUPPORTS_TOUCH ? event.touches[0] : event;

            phase = PHASE_START;

            //If we support touches, get the finger count
            if (SUPPORTS_TOUCH) {
                // get the total number of fingers touching the screen
                fingerCount = event.touches.length;
            }
            //Else this is the desktop, so stop the browser from dragging the image
            else {
                jqEvent.preventDefault(); //call this on jq event so we are cross browser
            }

            //clear vars..
            distance = 0;
            direction = null;
            pinchDirection=null;
            duration = 0;
            startTouchesDistance=0;
            endTouchesDistance=0;
            pinchZoom = 1;
            pinchDistance = 0;
            fingerData=createAllFingerData();
            maximumsMap=createMaximumsData();
            cancelMultiFingerRelease();

            
            // check the number of fingers is what we are looking for, or we are capturing pinches
            if (!SUPPORTS_TOUCH || (fingerCount === options.fingers || options.fingers === ALL_FINGERS) || hasPinches()) {
                // get the coordinates of the touch
                createFingerData( 0, evt );
                startTime = getTimeStamp();
                
                if(fingerCount==2) {
                    //Keep track of the initial pinch distance, so we can calculate the diff later
                    //Store second finger data as start
                    createFingerData( 1, event.touches[1] );
                    startTouchesDistance = endTouchesDistance = calculateTouchesDistance(fingerData[0].start, fingerData[1].start);
                }
                
                if (options.swipeStatus || options.pinchStatus) {
                    ret = triggerHandler(event, phase);
                }
            }
            else {
                //A touch with more or less than the fingers we are looking for, so cancel
                ret = false; 
            }

            //If we have a return value from the users handler, then return and cancel
            if (ret === false) {
                phase = PHASE_CANCEL;
                triggerHandler(event, phase);
                return ret;
            }
            else {
                setTouchInProgress(true);
            }

            return null;
        };
        
        
        
        /**
        * Event handler for a touch move event. 
        * If we change fingers during move, then cancel the event
        * @inner
        * @param {object} jqEvent The normalised jQuery event object.
        */
        function touchMove(jqEvent) {
            
            //As we use Jquery bind for events, we need to target the original event object
            //If these events are being programmatically triggered, we don't have an original event object, so use the Jq one.
            var event = jqEvent.originalEvent ? jqEvent.originalEvent : jqEvent;
            
            //If we are ending, cancelling, or within the threshold of 2 fingers being released, don't track anything..
            if (phase === PHASE_END || phase === PHASE_CANCEL || inMultiFingerRelease())
                return;

            var ret,
                evt = SUPPORTS_TOUCH ? event.touches[0] : event;
            

            //Update the  finger data 
            var currentFinger = updateFingerData(evt);
            endTime = getTimeStamp();
            
            if (SUPPORTS_TOUCH) {
                fingerCount = event.touches.length;
            }

            phase = PHASE_MOVE;

            //If we have 2 fingers get Touches distance as well
            if(fingerCount==2) {
                
                //Keep track of the initial pinch distance, so we can calculate the diff later
                //We do this here as well as the start event, in case they start with 1 finger, and the press 2 fingers
                if(startTouchesDistance==0) {
                    //Create second finger if this is the first time...
                    createFingerData( 1, event.touches[1] );
                    
                    startTouchesDistance = endTouchesDistance = calculateTouchesDistance(fingerData[0].start, fingerData[1].start);
                } else {
                    //Else just update the second finger
                    updateFingerData(event.touches[1]);
                
                    endTouchesDistance = calculateTouchesDistance(fingerData[0].end, fingerData[1].end);
                    pinchDirection = calculatePinchDirection(fingerData[0].end, fingerData[1].end);
                }
                
                
                pinchZoom = calculatePinchZoom(startTouchesDistance, endTouchesDistance);
                pinchDistance = Math.abs(startTouchesDistance - endTouchesDistance);
            }
            
            
            if ( (fingerCount === options.fingers || options.fingers === ALL_FINGERS) || !SUPPORTS_TOUCH || hasPinches() ) {
                
                direction = calculateDirection(currentFinger.start, currentFinger.end);
                
                //Check if we need to prevent default event (page scroll / pinch zoom) or not
                validateDefaultEvent(jqEvent, direction);

                //Distance and duration are all off the main finger
                distance = calculateDistance(currentFinger.start, currentFinger.end);
                duration = calculateDuration();

                //Cache the maximum distance we made in this direction
                setMaxDistance(direction, distance);


                if (options.swipeStatus || options.pinchStatus) {
                    ret = triggerHandler(event, phase);
                }
                
                
                //If we trigger end events when threshold are met, or trigger events when touch leaves element
                if(!options.triggerOnTouchEnd || options.triggerOnTouchLeave) {
                    
                    var inBounds = true;
                    
                    //If checking if we leave the element, run the bounds check (we can use touchleave as its not supported on webkit)
                    if(options.triggerOnTouchLeave) {
                        var bounds = getbounds( this );
                        inBounds = isInBounds( currentFinger.end, bounds );
                    }
                    
                    //Trigger end handles as we swipe if thresholds met or if we have left the element if the user has asked to check these..
                    if(!options.triggerOnTouchEnd && inBounds) {
                        phase = getNextPhase( PHASE_MOVE );
                    } 
                    //We end if out of bounds here, so set current phase to END, and check if its modified 
                    else if(options.triggerOnTouchLeave && !inBounds ) {
                        phase = getNextPhase( PHASE_END );
                    }
                        
                    if(phase==PHASE_CANCEL || phase==PHASE_END) {
                        triggerHandler(event, phase);
                    }               
                }
            }
            else {
                phase = PHASE_CANCEL;
                triggerHandler(event, phase);
            }

            if (ret === false) {
                phase = PHASE_CANCEL;
                triggerHandler(event, phase);
            }
        }



        /**
        * Event handler for a touch end event. 
        * Calculate the direction and trigger events
        * @inner
        * @param {object} jqEvent The normalised jQuery event object.
        */
        function touchEnd(jqEvent) {
            //As we use Jquery bind for events, we need to target the original event object
            var event = jqEvent.originalEvent;
                

            //If we are still in a touch with another finger return
            //This allows us to wait a fraction and see if the other finger comes up, if it does within the threshold, then we treat it as a multi release, not a single release.
            if (SUPPORTS_TOUCH) {
                if(event.touches.length>0) {
                    startMultiFingerRelease();
                    return true;
                }
            }
            
            //If a previous finger has been released, check how long ago, if within the threshold, then assume it was a multifinger release.
            //This is used to allow 2 fingers to release fractionally after each other, whilst maintainig the event as containg 2 fingers, not 1
            if(inMultiFingerRelease()) {    
                fingerCount=previousTouchFingerCount;
            }   
                 
            //call this on jq event so we are cross browser 
            jqEvent.preventDefault(); 
            
            //Set end of swipe
            endTime = getTimeStamp();
            
            //Get duration incase move was never fired
            duration = calculateDuration();
            
            //If we trigger handlers at end of swipe OR, we trigger during, but they didnt trigger and we are still in the move phase
            if(didSwipeBackToCancel()) {
                phase = PHASE_CANCEL;
                triggerHandler(event, phase);
            } else if (options.triggerOnTouchEnd || (options.triggerOnTouchEnd == false && phase === PHASE_MOVE)) {
                phase = PHASE_END;
                triggerHandler(event, phase);
            }
            //Special cases - A tap should always fire on touch end regardless,
            //So here we manually trigger the tap end handler by itself
            //We dont run trigger handler as it will re-trigger events that may have fired already
            else if (!options.triggerOnTouchEnd && hasTap()) {
                //Trigger the pinch events...
                phase = PHASE_END;
                triggerHandlerForGesture(event, phase, TAP);
            }
            else if (phase === PHASE_MOVE) {
                phase = PHASE_CANCEL;
                triggerHandler(event, phase);
            }

            setTouchInProgress(false);

            return null;
        }



        /**
        * Event handler for a touch cancel event. 
        * Clears current vars
        * @inner
        */
        function touchCancel() {
            // reset the variables back to default values
            fingerCount = 0;
            endTime = 0;
            startTime = 0;
            startTouchesDistance=0;
            endTouchesDistance=0;
            pinchZoom=1;
            
            //If we were in progress of tracking a possible multi touch end, then re set it.
            cancelMultiFingerRelease();
            
            setTouchInProgress(false);
        }
        
        
        /**
        * Event handler for a touch leave event. 
        * This is only triggered on desktops, in touch we work this out manually
        * as the touchleave event is not supported in webkit
        * @inner
        */
        function touchLeave(jqEvent) {
            var event = jqEvent.originalEvent;
            
            //If we have the trigger on leave property set....
            if(options.triggerOnTouchLeave) {
                phase = getNextPhase( PHASE_END );
                triggerHandler(event, phase);
            }
        }
        
        /**
        * Removes all listeners that were associated with the plugin
        * @inner
        */
        function removeListeners() {
            $element.unbind(START_EV, touchStart);
            $element.unbind(CANCEL_EV, touchCancel);
            $element.unbind(MOVE_EV, touchMove);
            $element.unbind(END_EV, touchEnd);
            
            //we only have leave events on desktop, we manually calculate leave on touch as its not supported in webkit
            if(LEAVE_EV) { 
                $element.unbind(LEAVE_EV, touchLeave);
            }
            
            setTouchInProgress(false);
        }

        
        /**
         * Checks if the time and distance thresholds have been met, and if so then the appropriate handlers are fired.
         */
        function getNextPhase(currentPhase) {
            
            var nextPhase = currentPhase;
            
            // Ensure we have valid swipe (under time and over distance  and check if we are out of bound...)
            var validTime = validateSwipeTime();
            var validDistance = validateSwipeDistance();
            var didCancel = didSwipeBackToCancel();
                        
            //If we have exceeded our time, then cancel 
            if(!validTime || didCancel) {
                nextPhase = PHASE_CANCEL;
            }
            //Else if we are moving, and have reached distance then end
            else if (validDistance && currentPhase == PHASE_MOVE && (!options.triggerOnTouchEnd || options.triggerOnTouchLeave) ) {
                nextPhase = PHASE_END;
            } 
            //Else if we have ended by leaving and didn't reach distance, then cancel
            else if (!validDistance && currentPhase==PHASE_END && options.triggerOnTouchLeave) {
                nextPhase = PHASE_CANCEL;
            }
            
            return nextPhase;
        }
        
        
        /**
        * Trigger the relevant event handler
        * The handlers are passed the original event, the element that was swiped, and in the case of the catch all handler, the direction that was swiped, "left", "right", "up", or "down"
        * @param {object} event the original event object
        * @param {string} phase the phase of the swipe (start, end cancel etc) {@link $.fn.swipe.phases}
        * @inner
        */
        function triggerHandler(event, phase) {
            
            var ret = undefined;
            
            // SWIPE GESTURES
            if(didSwipe() || hasSwipes()) { //hasSwipes as status needs to fire even if swipe is invalid
                //Trigger the swipe events...
                ret = triggerHandlerForGesture(event, phase, SWIPE);
            } 
            
            // PINCH GESTURES (if the above didn't cancel)
            else if((didPinch() || hasPinches()) && ret!==false) {
                //Trigger the pinch events...
                ret = triggerHandlerForGesture(event, phase, PINCH);
            }
            
            // CLICK / TAP (if the above didn't cancel)
            if(didDoubleTap() && ret!==false) {
                //Trigger the tap events...
                ret = triggerHandlerForGesture(event, phase, DOUBLE_TAP);
            }
            
            // CLICK / TAP (if the above didn't cancel)
            else if(didLongTap() && ret!==false) {
                //Trigger the tap events...
                ret = triggerHandlerForGesture(event, phase, LONG_TAP);
            }

            // CLICK / TAP (if the above didn't cancel)
            else if(didTap() && ret!==false) {
                //Trigger the tap event..
                ret = triggerHandlerForGesture(event, phase, TAP);
            }
            
            
            
            // If we are cancelling the gesture, then manually trigger the reset handler
            if (phase === PHASE_CANCEL) {
                touchCancel(event);
            }
            
            // If we are ending the gesture, then manually trigger the reset handler IF all fingers are off
            if(phase === PHASE_END) {
                //If we support touch, then check that all fingers are off before we cancel
                if (SUPPORTS_TOUCH) {
                    if(event.touches.length==0) {
                        touchCancel(event); 
                    }
                } 
                else {
                    touchCancel(event);
                }
            }
                    
            return ret;
        }
        
        
        
        /**
        * Trigger the relevant event handler
        * The handlers are passed the original event, the element that was swiped, and in the case of the catch all handler, the direction that was swiped, "left", "right", "up", or "down"
        * @param {object} event the original event object
        * @param {string} phase the phase of the swipe (start, end cancel etc) {@link $.fn.swipe.phases}
        * @param {string} gesture the gesture to trigger a handler for : PINCH or SWIPE {@link $.fn.swipe.gestures}
        * @return Boolean False, to indicate that the event should stop propagation, or void.
        * @inner
        */
        function triggerHandlerForGesture(event, phase, gesture) {  
            
            var ret=undefined;
            
            //SWIPES....
            if(gesture==SWIPE) {
                //Trigger status every time..
                
                //Trigger the event...
                $element.trigger('swipeStatus', [phase, direction || null, distance || 0, duration || 0, fingerCount]);
                
                //Fire the callback
                if (options.swipeStatus) {
                    ret = options.swipeStatus.call($element, event, phase, direction || null, distance || 0, duration || 0, fingerCount);
                    //If the status cancels, then dont run the subsequent event handlers..
                    if(ret===false) return false;
                }
                
                
                
                
                if (phase == PHASE_END && validateSwipe()) {
                    //Fire the catch all event
                    $element.trigger('swipe', [direction, distance, duration, fingerCount]);
                    
                    //Fire catch all callback
                    if (options.swipe) {
                        ret = options.swipe.call($element, event, direction, distance, duration, fingerCount);
                        //If the status cancels, then dont run the subsequent event handlers..
                        if(ret===false) return false;
                    }
                    
                    //trigger direction specific event handlers 
                    switch (direction) {
                        case LEFT:
                            //Trigger the event
                            $element.trigger('swipeLeft', [direction, distance, duration, fingerCount]);
                    
                            //Fire the callback
                            if (options.swipeLeft) {
                                ret = options.swipeLeft.call($element, event, direction, distance, duration, fingerCount);
                            }
                            break;
    
                        case RIGHT:
                            //Trigger the event
                            $element.trigger('swipeRight', [direction, distance, duration, fingerCount]);
                    
                            //Fire the callback
                            if (options.swipeRight) {
                                ret = options.swipeRight.call($element, event, direction, distance, duration, fingerCount);
                            }
                            break;
    
                        case UP:
                            //Trigger the event
                            $element.trigger('swipeUp', [direction, distance, duration, fingerCount]);
                    
                            //Fire the callback
                            if (options.swipeUp) {
                                ret = options.swipeUp.call($element, event, direction, distance, duration, fingerCount);
                            }
                            break;
    
                        case DOWN:
                            //Trigger the event
                            $element.trigger('swipeDown', [direction, distance, duration, fingerCount]);
                    
                            //Fire the callback
                            if (options.swipeDown) {
                                ret = options.swipeDown.call($element, event, direction, distance, duration, fingerCount);
                            }
                            break;
                    }
                }
            }
            
            
            //PINCHES....
            if(gesture==PINCH) {
                //Trigger the event
                 $element.trigger('pinchStatus', [phase, pinchDirection || null, pinchDistance || 0, duration || 0, fingerCount, pinchZoom]);
                    
                //Fire the callback
                if (options.pinchStatus) {
                    ret = options.pinchStatus.call($element, event, phase, pinchDirection || null, pinchDistance || 0, duration || 0, fingerCount, pinchZoom);
                    //If the status cancels, then dont run the subsequent event handlers..
                    if(ret===false) return false;
                }
                
                if(phase==PHASE_END && validatePinch()) {
                    
                    switch (pinchDirection) {
                        case IN:
                            //Trigger the event
                            $element.trigger('pinchIn', [pinchDirection || null, pinchDistance || 0, duration || 0, fingerCount, pinchZoom]);
                    
                            //Fire the callback
                            if (options.pinchIn) {
                                ret = options.pinchIn.call($element, event, pinchDirection || null, pinchDistance || 0, duration || 0, fingerCount, pinchZoom);
                            }
                            break;
                        
                        case OUT:
                            //Trigger the event
                            $element.trigger('pinchOut', [pinchDirection || null, pinchDistance || 0, duration || 0, fingerCount, pinchZoom]);
                    
                            //Fire the callback
                            if (options.pinchOut) {
                                ret = options.pinchOut.call($element, event, pinchDirection || null, pinchDistance || 0, duration || 0, fingerCount, pinchZoom);
                            }
                            break;  
                    }
                }
            }
            


                
                
            if(gesture==TAP) {
                if(phase === PHASE_CANCEL || phase === PHASE_END) {
                    
                    
                    //Cancel any existing double tap
                    clearTimeout(singleTapTimeout);
                           
                    //If we are also looking for doubelTaps, wait incase this is one...
                    if(hasDoubleTap() && !inDoubleTap()) {
                        //Cache the time of this tap
                        doubleTapStartTime = getTimeStamp();
                       
                        //Now wait for the double tap timeout, and trigger this single tap
                        //if its not cancelled by a double tap
                        singleTapTimeout = setTimeout($.proxy(function() {
                            doubleTapStartTime=null;
                            //Trigger the event
                            $element.trigger('tap', [event.target]);

                        
                            //Fire the callback
                            if(options.tap) {
                                ret = options.tap.call($element, event, event.target);
                            }
                        }, this), options.doubleTapThreshold );
                        
                    } else {
                        doubleTapStartTime=null;
                        
                        //Trigger the event
                        $element.trigger('tap', [event.target]);

                        
                        //Fire the callback
                        if(options.tap) {
                            ret = options.tap.call($element, event, event.target);
                        }
                    }
                }
            }
            
            else if (gesture==DOUBLE_TAP) {
                if(phase === PHASE_CANCEL || phase === PHASE_END) {
                    //Cancel any pending singletap 
                    clearTimeout(singleTapTimeout);
                    doubleTapStartTime=null;
                        
                    //Trigger the event
                    $element.trigger('doubletap', [event.target]);
                
                    //Fire the callback
                    if(options.doubleTap) {
                        ret = options.doubleTap.call($element, event, event.target);
                    }
                }
            }
            
            else if (gesture==LONG_TAP) {
                if(phase === PHASE_CANCEL || phase === PHASE_END) {
                    //Cancel any pending singletap (shouldnt be one)
                    clearTimeout(singleTapTimeout);
                    doubleTapStartTime=null;
                        
                    //Trigger the event
                    $element.trigger('longtap', [event.target]);
                
                    //Fire the callback
                    if(options.longTap) {
                        ret = options.longTap.call($element, event, event.target);
                    }
                }
            }               
                
            return ret;
        }



        
        //
        // GESTURE VALIDATION
        //
        
        /**
        * Checks the user has swipe far enough
        * @return Boolean if <code>threshold</code> has been set, return true if the threshold was met, else false.
        * If no threshold was set, then we return true.
        * @inner
        */
        function validateSwipeDistance() {
            var valid = true;
            //If we made it past the min swipe distance..
            if (options.threshold !== null) {
                valid = distance >= options.threshold;
            }
            
            return valid;
        }
        
        /**
        * Checks the user has swiped back to cancel.
        * @return Boolean if <code>cancelThreshold</code> has been set, return true if the cancelThreshold was met, else false.
        * If no cancelThreshold was set, then we return true.
        * @inner
        */
        function didSwipeBackToCancel() {
            var cancelled = false;
            if(options.cancelThreshold !== null && direction !==null)  {
                cancelled =  (getMaxDistance( direction ) - distance) >= options.cancelThreshold;
            }
            
            return cancelled;
        }

        /**
        * Checks the user has pinched far enough
        * @return Boolean if <code>pinchThreshold</code> has been set, return true if the threshold was met, else false.
        * If no threshold was set, then we return true.
        * @inner
        */
        function validatePinchDistance() {
            if (options.pinchThreshold !== null) {
                return pinchDistance >= options.pinchThreshold;
            }
            return true;
        }

        /**
        * Checks that the time taken to swipe meets the minimum / maximum requirements
        * @return Boolean
        * @inner
        */
        function validateSwipeTime() {
            var result;
            //If no time set, then return true

            if (options.maxTimeThreshold) {
                if (duration >= options.maxTimeThreshold) {
                    result = false;
                } else {
                    result = true;
                }
            }
            else {
                result = true;
            }

            return result;
        }


        /**
        * Checks direction of the swipe and the value allowPageScroll to see if we should allow or prevent the default behaviour from occurring.
        * This will essentially allow page scrolling or not when the user is swiping on a touchSwipe object.
        * @param {object} jqEvent The normalised jQuery representation of the event object.
        * @param {string} direction The direction of the event. See {@link $.fn.swipe.directions}
        * @see $.fn.swipe.directions
        * @inner
        */
        function validateDefaultEvent(jqEvent, direction) {
            if (options.allowPageScroll === NONE || hasPinches()) {
                jqEvent.preventDefault();
            } else {
                var auto = options.allowPageScroll === AUTO;

                switch (direction) {
                    case LEFT:
                        if ((options.swipeLeft && auto) || (!auto && options.allowPageScroll != HORIZONTAL)) {
                            jqEvent.preventDefault();
                        }
                        break;

                    case RIGHT:
                        if ((options.swipeRight && auto) || (!auto && options.allowPageScroll != HORIZONTAL)) {
                            jqEvent.preventDefault();
                        }
                        break;

                    case UP:
                        if ((options.swipeUp && auto) || (!auto && options.allowPageScroll != VERTICAL)) {
                            jqEvent.preventDefault();
                        }
                        break;

                    case DOWN:
                        if ((options.swipeDown && auto) || (!auto && options.allowPageScroll != VERTICAL)) {
                            jqEvent.preventDefault();
                        }
                        break;
                }
            }

        }


        // PINCHES
        /**
         * Returns true of the current pinch meets the thresholds
         * @return Boolean
         * @inner
        */
        function validatePinch() {
            var hasCorrectFingerCount = validateFingers();
            var hasEndPoint = validateEndPoint();
            var hasCorrectDistance = validatePinchDistance();
            return hasCorrectFingerCount && hasEndPoint && hasCorrectDistance;
            
        }
        
        /**
         * Returns true if any Pinch events have been registered
         * @return Boolean
         * @inner
        */
        function hasPinches() {
            //Enure we dont return 0 or null for false values
            return !!(options.pinchStatus || options.pinchIn || options.pinchOut);
        }
        
        /**
         * Returns true if we are detecting pinches, and have one
         * @return Boolean
         * @inner
         */
        function didPinch() {
            //Enure we dont return 0 or null for false values
            return !!(validatePinch() && hasPinches());
        }




        // SWIPES
        /**
         * Returns true if the current swipe meets the thresholds
         * @return Boolean
         * @inner
        */
        function validateSwipe() {
            //Check validity of swipe
            var hasValidTime = validateSwipeTime();
            var hasValidDistance = validateSwipeDistance(); 
            var hasCorrectFingerCount = validateFingers();
            var hasEndPoint = validateEndPoint();
            var didCancel = didSwipeBackToCancel(); 
            
            // if the user swiped more than the minimum length, perform the appropriate action
            // hasValidDistance is null when no distance is set 
            var valid =  !didCancel && hasEndPoint && hasCorrectFingerCount && hasValidDistance && hasValidTime;
            
            return valid;
        }
        
        /**
         * Returns true if any Swipe events have been registered
         * @return Boolean
         * @inner
        */
        function hasSwipes() {
            //Enure we dont return 0 or null for false values
            return !!(options.swipe || options.swipeStatus || options.swipeLeft || options.swipeRight || options.swipeUp || options.swipeDown);
        }
        
        
        /**
         * Returns true if we are detecting swipes and have one
         * @return Boolean
         * @inner
        */
        function didSwipe() {
            //Enure we dont return 0 or null for false values
            return !!(validateSwipe() && hasSwipes());
        }

        /**
         * Returns true if we have matched the number of fingers we are looking for
         * @return Boolean
         * @inner
        */
        function validateFingers() {
            //The number of fingers we want were matched, or on desktop we ignore
            return ((fingerCount === options.fingers || options.fingers === ALL_FINGERS) || !SUPPORTS_TOUCH);
        }
        
        /**
         * Returns true if we have an end point for the swipe
         * @return Boolean
         * @inner
        */
        function validateEndPoint() {
            //We have an end value for the finger
            return fingerData[0].end.x !== 0;
        }

        // TAP / CLICK
        /**
         * Returns true if a click / tap events have been registered
         * @return Boolean
         * @inner
        */
        function hasTap() {
            //Enure we dont return 0 or null for false values
            return !!(options.tap) ;
        }
        
        /**
         * Returns true if a double tap events have been registered
         * @return Boolean
         * @inner
        */
        function hasDoubleTap() {
            //Enure we dont return 0 or null for false values
            return !!(options.doubleTap) ;
        }
        
        /**
         * Returns true if any long tap events have been registered
         * @return Boolean
         * @inner
        */
        function hasLongTap() {
            //Enure we dont return 0 or null for false values
            return !!(options.longTap) ;
        }
        
        /**
         * Returns true if we could be in the process of a double tap (one tap has occurred, we are listening for double taps, and the threshold hasn't past.
         * @return Boolean
         * @inner
        */
        function validateDoubleTap() {
            if(doubleTapStartTime==null){
                return false;
            }
            var now = getTimeStamp();
            return (hasDoubleTap() && ((now-doubleTapStartTime) <= options.doubleTapThreshold));
        }
        
        /**
         * Returns true if we could be in the process of a double tap (one tap has occurred, we are listening for double taps, and the threshold hasn't past.
         * @return Boolean
         * @inner
        */
        function inDoubleTap() {
            return validateDoubleTap();
        }
        
        
        /**
         * Returns true if we have a valid tap
         * @return Boolean
         * @inner
        */
        function validateTap() {
            return ((fingerCount === 1 || !SUPPORTS_TOUCH) && (isNaN(distance) || distance === 0));
        }
        
        /**
         * Returns true if we have a valid long tap
         * @return Boolean
         * @inner
        */
        function validateLongTap() {
            //slight threshold on moving finger
            return ((duration > options.longTapThreshold) && (distance < DOUBLE_TAP_THRESHOLD)); 
        }
        
        /**
         * Returns true if we are detecting taps and have one
         * @return Boolean
         * @inner
        */
        function didTap() {
            //Enure we dont return 0 or null for false values
            return !!(validateTap() && hasTap());
        }
        
        
        /**
         * Returns true if we are detecting double taps and have one
         * @return Boolean
         * @inner
        */
        function didDoubleTap() {
            //Enure we dont return 0 or null for false values
            return !!(validateDoubleTap() && hasDoubleTap());
        }
        
        /**
         * Returns true if we are detecting long taps and have one
         * @return Boolean
         * @inner
        */
        function didLongTap() {
            //Enure we dont return 0 or null for false values
            return !!(validateLongTap() && hasLongTap());
        }
        
        
        
        
        // MULTI FINGER TOUCH
        /**
         * Starts tracking the time between 2 finger releases, and keeps track of how many fingers we initially had up
         * @inner
        */
        function startMultiFingerRelease() {
            previousTouchEndTime = getTimeStamp();
            previousTouchFingerCount = event.touches.length+1;
        }
        
        /**
         * Cancels the tracking of time between 2 finger releases, and resets counters
         * @inner
        */
        function cancelMultiFingerRelease() {
            previousTouchEndTime = 0;
            previousTouchFingerCount = 0;
        }
        
        /**
         * Checks if we are in the threshold between 2 fingers being released 
         * @return Boolean
         * @inner
        */
        function inMultiFingerRelease() {
            
            var withinThreshold = false;
            
            if(previousTouchEndTime) {  
                var diff = getTimeStamp() - previousTouchEndTime    
                if( diff<=options.fingerReleaseThreshold ) {
                    withinThreshold = true;
                }
            }
            
            return withinThreshold; 
        }
        

        /**
        * gets a data flag to indicate that a touch is in progress
        * @return Boolean
        * @inner
        */
        function getTouchInProgress() {
            //strict equality to ensure only true and false are returned
            return !!($element.data(PLUGIN_NS+'_intouch') === true);
        }
        
        /**
        * Sets a data flag to indicate that a touch is in progress
        * @param {boolean} val The value to set the property to
        * @inner
        */
        function setTouchInProgress(val) {
            
            //Add or remove event listeners depending on touch status
            if(val===true) {
                $element.bind(MOVE_EV, touchMove);
                $element.bind(END_EV, touchEnd);
                
                //we only have leave events on desktop, we manually calcuate leave on touch as its not supported in webkit
                if(LEAVE_EV) { 
                    $element.bind(LEAVE_EV, touchLeave);
                }
            } else {
                $element.unbind(MOVE_EV, touchMove, false);
                $element.unbind(END_EV, touchEnd, false);
            
                //we only have leave events on desktop, we manually calcuate leave on touch as its not supported in webkit
                if(LEAVE_EV) { 
                    $element.unbind(LEAVE_EV, touchLeave, false);
                }
            }
            
        
            //strict equality to ensure only true and false can update the value
            $element.data(PLUGIN_NS+'_intouch', val === true);
        }
        
        
        /**
         * Creates the finger data for the touch/finger in the event object.
         * @param {int} index The index in the array to store the finger data (usually the order the fingers were pressed)
         * @param {object} evt The event object containing finger data
         * @return finger data object
         * @inner
        */
        function createFingerData( index, evt ) {
            var id = evt.identifier!==undefined ? evt.identifier : 0; 
            
            fingerData[index].identifier = id;
            fingerData[index].start.x = fingerData[index].end.x = evt.pageX||evt.clientX;
            fingerData[index].start.y = fingerData[index].end.y = evt.pageY||evt.clientY;
            
            return fingerData[index];
        }
        
        /**
         * Updates the finger data for a particular event object
         * @param {object} evt The event object containing the touch/finger data to upadte
         * @return a finger data object.
         * @inner
        */
        function updateFingerData(evt) {
            
            var id = evt.identifier!==undefined ? evt.identifier : 0; 
            var f = getFingerData( id );
            
            f.end.x = evt.pageX||evt.clientX;
            f.end.y = evt.pageY||evt.clientY;
            
            return f;
        }
        
        /**
         * Returns a finger data object by its event ID.
         * Each touch event has an identifier property, which is used 
         * to track repeat touches
         * @param {int} id The unique id of the finger in the sequence of touch events.
         * @return a finger data object.
         * @inner
        */
        function getFingerData( id ) {
            for(var i=0; i<fingerData.length; i++) {
                if(fingerData[i].identifier == id) {
                    return fingerData[i];   
                }
            }
        }
        
        /**
         * Creats all the finger onjects and returns an array of finger data
         * @return Array of finger objects
         * @inner
        */
        function createAllFingerData() {
            var fingerData=[];
            for (var i=0; i<=5; i++) {
                fingerData.push({
                    start:{ x: 0, y: 0 },
                    end:{ x: 0, y: 0 },
                    identifier:0
                });
            }
            
            return fingerData;
        }
        
        /**
         * Sets the maximum distance swiped in the given direction. 
         * If the new value is lower than the current value, the max value is not changed.
         * @param {string}  direction The direction of the swipe
         * @param {int}  distance The distance of the swipe
         * @inner
        */
        function setMaxDistance(direction, distance) {
            distance = Math.max(distance, getMaxDistance(direction) );
            maximumsMap[direction].distance = distance;
        }
        
        /**
         * gets the maximum distance swiped in the given direction. 
         * @param {string}  direction The direction of the swipe
         * @return int  The distance of the swipe
         * @inner
        */        
        function getMaxDistance(direction) {
            if (maximumsMap[direction]) return maximumsMap[direction].distance;
            return undefined;
        }
        
        /**
         * Creats a map of directions to maximum swiped values.
         * @return Object A dictionary of maximum values, indexed by direction.
         * @inner
        */
        function createMaximumsData() {
            var maxData={};
            maxData[LEFT]=createMaximumVO(LEFT);
            maxData[RIGHT]=createMaximumVO(RIGHT);
            maxData[UP]=createMaximumVO(UP);
            maxData[DOWN]=createMaximumVO(DOWN);
            
            return maxData;
        }
        
        /**
         * Creates a map maximum swiped values for a given swipe direction
         * @param {string} The direction that these values will be associated with
         * @return Object Maximum values
         * @inner
        */
        function createMaximumVO(dir) {
            return { 
                direction:dir, 
                distance:0
            }
        }
        
        
        //
        // MATHS / UTILS
        //

        /**
        * Calculate the duration of the swipe
        * @return int
        * @inner
        */
        function calculateDuration() {
            return endTime - startTime;
        }
        
        /**
        * Calculate the distance between 2 touches (pinch)
        * @param {point} startPoint A point object containing x and y co-ordinates
        * @param {point} endPoint A point object containing x and y co-ordinates
        * @return int;
        * @inner
        */
        function calculateTouchesDistance(startPoint, endPoint) {
            var diffX = Math.abs(startPoint.x - endPoint.x);
            var diffY = Math.abs(startPoint.y - endPoint.y);
                
            return Math.round(Math.sqrt(diffX*diffX+diffY*diffY));
        }
        
        /**
        * Calculate the zoom factor between the start and end distances
        * @param {int} startDistance Distance (between 2 fingers) the user started pinching at
        * @param {int} endDistance Distance (between 2 fingers) the user ended pinching at
        * @return float The zoom value from 0 to 1.
        * @inner
        */
        function calculatePinchZoom(startDistance, endDistance) {
            var percent = (endDistance/startDistance) * 1;
            return percent.toFixed(2);
        }
        
        
        /**
        * Returns the pinch direction, either IN or OUT for the given points
        * @return string Either {@link $.fn.swipe.directions.IN} or {@link $.fn.swipe.directions.OUT}
        * @see $.fn.swipe.directions
        * @inner
        */
        function calculatePinchDirection() {
            if(pinchZoom<1) {
                return OUT;
            }
            else {
                return IN;
            }
        }
        
        
        /**
        * Calculate the length / distance of the swipe
        * @param {point} startPoint A point object containing x and y co-ordinates
        * @param {point} endPoint A point object containing x and y co-ordinates
        * @return int
        * @inner
        */
        function calculateDistance(startPoint, endPoint) {
            return Math.round(Math.sqrt(Math.pow(endPoint.x - startPoint.x, 2) + Math.pow(endPoint.y - startPoint.y, 2)));
        }

        /**
        * Calculate the angle of the swipe
        * @param {point} startPoint A point object containing x and y co-ordinates
        * @param {point} endPoint A point object containing x and y co-ordinates
        * @return int
        * @inner
        */
        function calculateAngle(startPoint, endPoint) {
            var x = startPoint.x - endPoint.x;
            var y = endPoint.y - startPoint.y;
            var r = Math.atan2(y, x); //radians
            var angle = Math.round(r * 180 / Math.PI); //degrees

            //ensure value is positive
            if (angle < 0) {
                angle = 360 - Math.abs(angle);
            }

            return angle;
        }

        /**
        * Calculate the direction of the swipe
        * This will also call calculateAngle to get the latest angle of swipe
        * @param {point} startPoint A point object containing x and y co-ordinates
        * @param {point} endPoint A point object containing x and y co-ordinates
        * @return string Either {@link $.fn.swipe.directions.LEFT} / {@link $.fn.swipe.directions.RIGHT} / {@link $.fn.swipe.directions.DOWN} / {@link $.fn.swipe.directions.UP}
        * @see $.fn.swipe.directions
        * @inner
        */
        function calculateDirection(startPoint, endPoint ) {
            var angle = calculateAngle(startPoint, endPoint);

            if ((angle <= 45) && (angle >= 0)) {
                return LEFT;
            } else if ((angle <= 360) && (angle >= 315)) {
                return LEFT;
            } else if ((angle >= 135) && (angle <= 225)) {
                return RIGHT;
            } else if ((angle > 45) && (angle < 135)) {
                return DOWN;
            } else {
                return UP;
            }
        }
        

        /**
        * Returns a MS time stamp of the current time
        * @return int
        * @inner
        */
        function getTimeStamp() {
            var now = new Date();
            return now.getTime();
        }
        
        
        
        /**
         * Returns a bounds object with left, right, top and bottom properties for the element specified.
         * @param {DomNode} The DOM node to get the bounds for.
         */
        function getbounds( el ) {
            el = $(el);
            var offset = el.offset();
            
            var bounds = {  
                    left:offset.left,
                    right:offset.left+el.outerWidth(),
                    top:offset.top,
                    bottom:offset.top+el.outerHeight()
                    }
            
            return bounds;  
        }
        
        
        /**
         * Checks if the point object is in the bounds object.
         * @param {object} point A point object.
         * @param {int} point.x The x value of the point.
         * @param {int} point.y The x value of the point.
         * @param {object} bounds The bounds object to test
         * @param {int} bounds.left The leftmost value
         * @param {int} bounds.right The righttmost value
         * @param {int} bounds.top The topmost value
        * @param {int} bounds.bottom The bottommost value
         */
        function isInBounds(point, bounds) {
            return (point.x > bounds.left && point.x < bounds.right && point.y > bounds.top && point.y < bounds.bottom);
        };
    
    
    }
    
    


/**
 * A catch all handler that is triggered for all swipe directions. 
 * @name $.fn.swipe#swipe
 * @event
 * @default null
 * @param {EventObject} event The original event object
 * @param {int} direction The direction the user swiped in. See {@link $.fn.swipe.directions}
 * @param {int} distance The distance the user swiped
 * @param {int} duration The duration of the swipe in milliseconds
 * @param {int} fingerCount The number of fingers used. See {@link $.fn.swipe.fingers}
 */
 



/**
 * A handler that is triggered for "left" swipes.
 * @name $.fn.swipe#swipeLeft
 * @event
 * @default null
 * @param {EventObject} event The original event object
 * @param {int} direction The direction the user swiped in. See {@link $.fn.swipe.directions}
 * @param {int} distance The distance the user swiped
 * @param {int} duration The duration of the swipe in milliseconds
 * @param {int} fingerCount The number of fingers used. See {@link $.fn.swipe.fingers}
 */
 
/**
 * A handler that is triggered for "right" swipes.
 * @name $.fn.swipe#swipeRight
 * @event
 * @default null
 * @param {EventObject} event The original event object
 * @param {int} direction The direction the user swiped in. See {@link $.fn.swipe.directions}
 * @param {int} distance The distance the user swiped
 * @param {int} duration The duration of the swipe in milliseconds
 * @param {int} fingerCount The number of fingers used. See {@link $.fn.swipe.fingers}
 */

/**
 * A handler that is triggered for "up" swipes.
 * @name $.fn.swipe#swipeUp
 * @event
 * @default null
 * @param {EventObject} event The original event object
 * @param {int} direction The direction the user swiped in. See {@link $.fn.swipe.directions}
 * @param {int} distance The distance the user swiped
 * @param {int} duration The duration of the swipe in milliseconds
 * @param {int} fingerCount The number of fingers used. See {@link $.fn.swipe.fingers}
 */
 
/**
 * A handler that is triggered for "down" swipes.
 * @name $.fn.swipe#swipeDown
 * @event
 * @default null
 * @param {EventObject} event The original event object
 * @param {int} direction The direction the user swiped in. See {@link $.fn.swipe.directions}
 * @param {int} distance The distance the user swiped
 * @param {int} duration The duration of the swipe in milliseconds
 * @param {int} fingerCount The number of fingers used. See {@link $.fn.swipe.fingers}
 */
 
/**
 * A handler triggered for every phase of the swipe. This handler is constantly fired for the duration of the pinch.
 * This is triggered regardless of swipe thresholds.
 * @name $.fn.swipe#swipeStatus
 * @event
 * @default null
 * @param {EventObject} event The original event object
 * @param {string} phase The phase of the swipe event. See {@link $.fn.swipe.phases}
 * @param {string} direction The direction the user swiped in. This is null if the user has yet to move. See {@link $.fn.swipe.directions}
 * @param {int} distance The distance the user swiped. This is 0 if the user has yet to move.
 * @param {int} duration The duration of the swipe in milliseconds
 * @param {int} fingerCount The number of fingers used. See {@link $.fn.swipe.fingers}
 */
 
/**
 * A handler triggered for pinch in events.
 * @name $.fn.swipe#pinchIn
 * @event
 * @default null
 * @param {EventObject} event The original event object
 * @param {int} direction The direction the user pinched in. See {@link $.fn.swipe.directions}
 * @param {int} distance The distance the user pinched
 * @param {int} duration The duration of the swipe in milliseconds
 * @param {int} fingerCount The number of fingers used. See {@link $.fn.swipe.fingers}
 * @param {int} zoom The zoom/scale level the user pinched too, 0-1.
 */

/**
 * A handler triggered for pinch out events.
 * @name $.fn.swipe#pinchOut
 * @event
 * @default null
 * @param {EventObject} event The original event object
 * @param {int} direction The direction the user pinched in. See {@link $.fn.swipe.directions}
 * @param {int} distance The distance the user pinched
 * @param {int} duration The duration of the swipe in milliseconds
 * @param {int} fingerCount The number of fingers used. See {@link $.fn.swipe.fingers}
 * @param {int} zoom The zoom/scale level the user pinched too, 0-1.
 */ 

/**
 * A handler triggered for all pinch events. This handler is constantly fired for the duration of the pinch. This is triggered regardless of thresholds.
 * @name $.fn.swipe#pinchStatus
 * @event
 * @default null
 * @param {EventObject} event The original event object
 * @param {int} direction The direction the user pinched in. See {@link $.fn.swipe.directions}
 * @param {int} distance The distance the user pinched
 * @param {int} duration The duration of the swipe in milliseconds
 * @param {int} fingerCount The number of fingers used. See {@link $.fn.swipe.fingers}
 * @param {int} zoom The zoom/scale level the user pinched too, 0-1.
 */

/**
 * A click handler triggered when a user simply clicks, rather than swipes on an element.
 * This is deprecated since version 1.6.2, any assignment to click will be assigned to the tap handler.
 * You cannot use <code>on</code> to bind to this event as the default jQ <code>click</code> event will be triggered.
 * Use the <code>tap</code> event instead.
 * @name $.fn.swipe#click
 * @event
 * @deprecated since version 1.6.2, please use {@link $.fn.swipe#tap} instead 
 * @default null
 * @param {EventObject} event The original event object
 * @param {DomObject} target The element clicked on.
 */
 
 /**
 * A click / tap handler triggered when a user simply clicks or taps, rather than swipes on an element.
 * @name $.fn.swipe#tap
 * @event
 * @default null
 * @param {EventObject} event The original event object
 * @param {DomObject} target The element clicked on.
 */
 
/**
 * A double tap handler triggered when a user double clicks or taps on an element.
 * You can set the time delay for a double tap with the {@link $.fn.swipe.defaults#doubleTapThreshold} property. 
 * Note: If you set both <code>doubleTap</code> and <code>tap</code> handlers, the <code>tap</code> event will be delayed by the <code>doubleTapThreshold</code>
 * as the script needs to check if its a double tap.
 * @name $.fn.swipe#doubleTap
 * @see  $.fn.swipe.defaults#doubleTapThreshold
 * @event
 * @default null
 * @param {EventObject} event The original event object
 * @param {DomObject} target The element clicked on.
 */
 
 /**
 * A long tap handler triggered when a user long clicks or taps on an element.
 * You can set the time delay for a long tap with the {@link $.fn.swipe.defaults#longTapThreshold} property. 
 * @name $.fn.swipe#longTap
 * @see  $.fn.swipe.defaults#longTapThreshold
 * @event
 * @default null
 * @param {EventObject} event The original event object
 * @param {DomObject} target The element clicked on.
 */

}));




/* =========================================================
 * bootstrap-slider.js v2.0.0
 * http://www.eyecon.ro/bootstrap-slider
 * =========================================================
 * Copyright 2012 Stefan Petre
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ========================================================= */
 
(function( $ ) {

    var ErrorMsgs = {
        formatInvalidInputErrorMsg : function(input) {
            return "Invalid input value '" + input + "' passed in";
        }
    };

    var Slider = function(element, options) {
        var el = this.element = $(element).hide();
        var origWidth = el.outerWidth();

        var updateSlider = false;
        var parent = this.element.parent();


        if (parent.hasClass('slider') === true) {
            updateSlider = true;
            this.picker = parent;
        } else {
            this.picker = $('<div class="slider">'+
                                '<div class="slider-track">'+
                                    '<div class="slider-selection"></div>'+
                                    '<div class="slider-handle"></div>'+
                                    '<div class="slider-handle"></div>'+
                                '</div>'+
                                '<div class="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>'+
                            '</div>')
                                .insertBefore(this.element)
                                .append(this.element);
        }

        this.id = this.element.data('slider-id')||options.id;
        if (this.id) {
            this.picker[0].id = this.id;
        }

        if (typeof Modernizr !== 'undefined' && Modernizr.touch) {
            this.touchCapable = true;
        }

        var tooltip = this.element.data('slider-tooltip')||options.tooltip;

        this.tooltip = this.picker.find('.tooltip');
        this.tooltipInner = this.tooltip.find('div.tooltip-inner');

        this.orientation = this.element.data('slider-orientation')||options.orientation;
        switch(this.orientation) {
            case 'vertical':
                this.picker.addClass('slider-vertical');
                this.stylePos = 'top';
                this.mousePos = 'pageY';
                this.sizePos = 'offsetHeight';
                this.tooltip.addClass('right')[0].style.left = '100%';
                break;
            default:
                this.picker
                    .addClass('slider-horizontal')
                    .css('width', origWidth);
                this.orientation = 'horizontal';
                this.stylePos = 'left';
                this.mousePos = 'pageX';
                this.sizePos = 'offsetWidth';
                this.tooltip.addClass('top')[0].style.top = -this.tooltip.outerHeight() - 14 + 'px';
                break;
        }

        ['min', 'max', 'step', 'value'].forEach(function(attr) {
            this[attr] = jQuery(el).attr('data-slider-' + attr) || options[attr] || el.prop(attr);
        }, this);

        if (this.value instanceof Array) {
            this.range = true;
        }

        this.selection = this.element.data('slider-selection')||options.selection;
        this.selectionEl = this.picker.find('.slider-selection');
        if (this.selection === 'none') {
            this.selectionEl.addClass('hide');
        }
        this.selectionElStyle = this.selectionEl[0].style;


        this.handle1 = this.picker.find('.slider-handle:first');
        this.handle1Stype = this.handle1[0].style;
        this.handle2 = this.picker.find('.slider-handle:last');
        this.handle2Stype = this.handle2[0].style;

        var handle = this.element.data('slider-handle')||options.handle;
        switch(handle) {
            case 'round':
                this.handle1.addClass('round');
                this.handle2.addClass('round');
                break;
            case 'triangle':
                this.handle1.addClass('triangle');
                this.handle2.addClass('triangle');
                break;
        }

        if (this.range) {
            this.value[0] = Math.max(this.min, Math.min(this.max, this.value[0]));
            this.value[1] = Math.max(this.min, Math.min(this.max, this.value[1]));
        } else {
            this.value = [ Math.max(this.min, Math.min(this.max, this.value))];
            this.handle2.addClass('hide');
            if (this.selection === 'after') {
                this.value[1] = this.max;
            } else {
                this.value[1] = this.min;
            }
        }
        this.diff = this.max - this.min;
        this.percentage = [
            (this.value[0]-this.min)*100/this.diff,
            (this.value[1]-this.min)*100/this.diff,
            this.step*100/this.diff
        ];

        this.offset = this.picker.offset();
        this.size = this.picker[0][this.sizePos];

        this.formater = options.formater;

        this.reversed = this.element.data('slider-reversed')||options.reversed;

        this.layout();

        if (this.touchCapable) {
            // Touch: Bind touch events:
            this.picker.on({
                touchstart: $.proxy(this.mousedown, this)
            });
        } else {
            this.picker.on({
                mousedown: $.proxy(this.mousedown, this)
            });
        }

        if (tooltip === 'show') {
            this.picker.on({
                mouseenter: $.proxy(this.showTooltip, this),
                mouseleave: $.proxy(this.hideTooltip, this)
            });
        } else {
            this.tooltip.addClass('hide');
        }

        if (updateSlider === true) {
            var old = this.getValue();
            var val = this.calculateValue();
            this.element
                .trigger({
                    type: 'slide',
                    value: val
                })
                .data('value', val)
                .prop('value', val);

            if (old !== val) {
                this.element
                    .trigger({
                        type: 'slideChange',
                        newVal: val,
                        old: old
                    })
                    .data('value', val)
                    .prop('value', val);
            }
        }

        this.enabled = options.enabled && 
                        (this.element.data('slider-enabled') === undefined || this.element.data('slider-enabled') === true);
        if(!this.enabled)
        {
            this.disable();
        }
    };

    Slider.prototype = {
        constructor: Slider,

        over: false,
        inDrag: false,
        
        showTooltip: function(){
            this.tooltip.addClass('in');
            //var left = Math.round(this.percent*this.width);
            //this.tooltip.css('left', left - this.tooltip.outerWidth()/2);
            this.over = true;
        },
        
        hideTooltip: function(){
            if (this.inDrag === false) {
                this.tooltip.removeClass('in');
            }
            this.over = false;
        },

        layout: function(){
            var positionPercentages;

            if(this.reversed) {
            positionPercentages = [ 100 - this.percentage[0], this.percentage[1] ];
            } else {
            positionPercentages = [ this.percentage[0], this.percentage[1] ];
            }

            this.handle1Stype[this.stylePos] = positionPercentages[0]+'%';
            this.handle2Stype[this.stylePos] = positionPercentages[1]+'%';

            if (this.orientation === 'vertical') {
            this.selectionElStyle.top = Math.min(positionPercentages[0], positionPercentages[1]) +'%';
            this.selectionElStyle.height = Math.abs(positionPercentages[0] - positionPercentages[1]) +'%';
            } else {
            this.selectionElStyle.left = Math.min(positionPercentages[0], positionPercentages[1]) +'%';
            this.selectionElStyle.width = Math.abs(positionPercentages[0] - positionPercentages[1]) +'%';
            }

            if (this.range) {
            this.tooltipInner.text(
                this.formater(this.value[0]) + ' : ' + this.formater(this.value[1])
            );
            this.tooltip[0].style[this.stylePos] = this.size * (positionPercentages[0] + (positionPercentages[1] - positionPercentages[0])/2)/100 - (this.orientation === 'vertical' ? this.tooltip.outerHeight()/2 : this.tooltip.outerWidth()/2) +'px';
            } else {
            this.tooltipInner.text(
                this.formater(this.value[0])
            );
            this.tooltip[0].style[this.stylePos] = this.size * positionPercentages[0]/100 - (this.orientation === 'vertical' ? this.tooltip.outerHeight()/2 : this.tooltip.outerWidth()/2) +'px';
            }
        },

        mousedown: function(ev) {
            if(this.picker.hasClass('slider-disabled')) {
                return false;
            }
            // Touch: Get the original event:
            if (this.touchCapable && ev.type === 'touchstart') {
                ev = ev.originalEvent;
            }

            this.offset = this.picker.offset();
            this.size = this.picker[0][this.sizePos];

            var percentage = this.getPercentage(ev);

            if (this.range) {
                var diff1 = Math.abs(this.percentage[0] - percentage);
                var diff2 = Math.abs(this.percentage[1] - percentage);
                this.dragged = (diff1 < diff2) ? 0 : 1;
            } else {
                this.dragged = 0;
            }

            this.percentage[this.dragged] = this.reversed ? 100 - percentage : percentage;
            this.layout();

            if (this.touchCapable) {
                // Touch: Bind touch events:
                $(document).on({
                    touchmove: $.proxy(this.mousemove, this),
                    touchend: $.proxy(this.mouseup, this)
                });
            } else {
                $(document).on({
                    mousemove: $.proxy(this.mousemove, this),
                    mouseup: $.proxy(this.mouseup, this)
                });
            }

            this.inDrag = true;
            var val = this.calculateValue();
            this.setValue(val);
            this.element.trigger({
                    type: 'slideStart',
                    value: val
                }).trigger({
                    type: 'slide',
                    value: val
                });
            return false;
        },

        mousemove: function(ev) {
            if(this.picker.hasClass('slider-disabled')) {
                return false;
            }
            // Touch: Get the original event:
            if (this.touchCapable && ev.type === 'touchmove') {
                ev = ev.originalEvent;
            }

            var percentage = this.getPercentage(ev);
            if (this.range) {
                if (this.dragged === 0 && this.percentage[1] < percentage) {
                    this.percentage[0] = this.percentage[1];
                    this.dragged = 1;
                } else if (this.dragged === 1 && this.percentage[0] > percentage) {
                    this.percentage[1] = this.percentage[0];
                    this.dragged = 0;
                }
            }
            this.percentage[this.dragged] = this.reversed ? 100 - percentage : percentage;
            this.layout();
            var val = this.calculateValue();
            this.setValue(val);
            this.element
                .trigger({
                    type: 'slide',
                    value: val
                })
                .data('value', val)
                .prop('value', val);
            return false;
        },

        mouseup: function() {
            if(this.picker.hasClass('slider-disabled')) {
                return false;
            }
            if (this.touchCapable) {
                // Touch: Bind touch events:
                $(document).off({
                    touchmove: this.mousemove,
                    touchend: this.mouseup
                });
            } else {
                $(document).off({
                    mousemove: this.mousemove,
                    mouseup: this.mouseup
                });
            }

            this.inDrag = false;
            if (this.over === false) {
                this.hideTooltip();
            }
            var val = this.calculateValue();
            this.layout();
            this.element
                .trigger({
                    type: 'slideStop',
                    value: val
                })
                .data('value', val)
                .prop('value', val);
            return false;
        },

        calculateValue: function() {
            var val;
            if (this.range) {
                val = [
                    (Math.max(this.min, this.min + Math.round((this.diff * this.percentage[0]/100)/this.step)*this.step)),
                    (Math.min(this.max, this.min + Math.round((this.diff * this.percentage[1]/100)/this.step)*this.step))
                ];
                this.value = val;
            } else {
                val = (this.min + Math.round((this.diff * this.percentage[0]/100)/this.step)*this.step);
                if (val < this.min) {
                    val = this.min;
                }
                else if (val > this.max) {
                    val = this.max;
                }
                val = parseFloat(val);
                this.value = [val, this.value[1]];
            }
            return val;
        },

        getPercentage: function(ev) {
            if (this.touchCapable) {
                ev = ev.touches[0];
            }
            var percentage = (ev[this.mousePos] - this.offset[this.stylePos])*100/this.size;
            percentage = Math.round(percentage/this.percentage[2])*this.percentage[2];
            return Math.max(0, Math.min(100, percentage));
        },

        getValue: function() {
            if (this.range) {
                return this.value;
            }
            return this.value[0];
        },

        setValue: function(val) {
            this.value = this.validateInputValue(val);

            if (this.range) {
                this.value[0] = Math.max(this.min, Math.min(this.max, this.value[0]));
                this.value[1] = Math.max(this.min, Math.min(this.max, this.value[1]));
            } else {
                this.value = [ Math.max(this.min, Math.min(this.max, this.value))];
                this.handle2.addClass('hide');
                if (this.selection === 'after') {
                    this.value[1] = this.max;
                } else {
                    this.value[1] = this.min;
                }
            }
            this.diff = this.max - this.min;
            this.percentage = [
                (this.value[0]-this.min)*100/this.diff,
                (this.value[1]-this.min)*100/this.diff,
                this.step*100/this.diff
            ];
            this.layout();
        },

        validateInputValue : function(val) {
            if(typeof val === 'number') {
                return val;
            } else if(val instanceof Array) {
                val.forEach(function(input) { if (typeof input !== 'number') { throw new Error( ErrorMsgs.formatInvalidInputErrorMsg(input) ); }});
                return val;
            } else {
                throw new Error( ErrorMsgs.formatInvalidInputErrorMsg(val) );
            }
        },

        destroy: function(){
            this.element.show().insertBefore(this.picker);
            this.picker.remove();
            $(this.element).removeData('slider');
            $(this.element).off();
        },

        disable: function() {
            this.enabled = false;
            this.picker.addClass('slider-disabled');
            this.element.trigger('slideDisabled');
        },

        enable: function() {
            this.enabled = true;
            this.picker.removeClass('slider-disabled');
            this.element.trigger('slideEnabled');
        },

        toggle: function() {
            if(this.enabled) {
                this.disable();
            }
            else {
                this.enable();
            }
        },

        isEnabled: function() {
            return this.enabled;
        }
    };

    var publicMethods = {
        getValue : Slider.prototype.getValue,
        setValue : Slider.prototype.setValue,
        destroy : Slider.prototype.destroy,
        disable : Slider.prototype.disable,
        enable : Slider.prototype.enable,
        toggle : Slider.prototype.toggle,
        isEnabled: Slider.prototype.isEnabled
    };

    $.fn.slider = function (option) {
        if (typeof option === 'string') {
            var args = Array.prototype.slice.call(arguments, 1);
            return invokePublicMethod.call(this, option, args);
        } else {
            return createNewSliderInstance.call(this, option);
        }
    };

    function invokePublicMethod(methodName, args) {
        if(publicMethods[methodName]) {
            var sliderObject = $(this).data('slider');
            return publicMethods[methodName].apply(sliderObject, args);
        } else {
            throw new Error("method '" + methodName + "()' does not exist for slider.");
        }
    }

    function createNewSliderInstance(opts) {
        var $this = $(this),
             data = $this.data('slider'),
             options = typeof opts === 'object' && opts;
        if (!$this.length) return $this;
        if (!data)  {
            $this.data('slider', (data = new Slider(this, $.extend({}, $.fn.slider.defaults,options))));
        }
        return $this;
    }

    $.fn.slider.defaults = {
        min: 0,
        max: 10,
        step: 1,
        orientation: 'horizontal',
        value: 5,
        selection: 'before',
        tooltip: 'show',
        handle: 'round',
        reversed : false,
        enabled: true,
        formater: function(value) {
            return value;
        }
    };

    $.fn.slider.Constructor = Slider;

})( window.jQuery );







/*

Copyright (C) 2011 by Yehuda Katz

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

*/

// lib/handlebars/browser-prefix.js
var Handlebars = {};

(function(Handlebars, undefined) {
;
// lib/handlebars/base.js

Handlebars.VERSION = "1.0.0";
Handlebars.COMPILER_REVISION = 4;

Handlebars.REVISION_CHANGES = {
  1: '<= 1.0.rc.2', // 1.0.rc.2 is actually rev2 but doesn't report it
  2: '== 1.0.0-rc.3',
  3: '== 1.0.0-rc.4',
  4: '>= 1.0.0'
};

Handlebars.helpers  = {};
Handlebars.partials = {};

var toString = Object.prototype.toString,
    functionType = '[object Function]',
    objectType = '[object Object]';

Handlebars.registerHelper = function(name, fn, inverse) {
  if (toString.call(name) === objectType) {
    if (inverse || fn) { throw new Handlebars.Exception('Arg not supported with multiple helpers'); }
    Handlebars.Utils.extend(this.helpers, name);
  } else {
    if (inverse) { fn.not = inverse; }
    this.helpers[name] = fn;
  }
};

Handlebars.registerPartial = function(name, str) {
  if (toString.call(name) === objectType) {
    Handlebars.Utils.extend(this.partials,  name);
  } else {
    this.partials[name] = str;
  }
};

Handlebars.registerHelper('helperMissing', function(arg) {
  if(arguments.length === 2) {
    return undefined;
  } else {
    throw new Error("Missing helper: '" + arg + "'");
  }
});

Handlebars.registerHelper('blockHelperMissing', function(context, options) {
  var inverse = options.inverse || function() {}, fn = options.fn;

  var type = toString.call(context);

  if(type === functionType) { context = context.call(this); }

  if(context === true) {
    return fn(this);
  } else if(context === false || context == null) {
    return inverse(this);
  } else if(type === "[object Array]") {
    if(context.length > 0) {
      return Handlebars.helpers.each(context, options);
    } else {
      return inverse(this);
    }
  } else {
    return fn(context);
  }
});

Handlebars.K = function() {};

Handlebars.createFrame = Object.create || function(object) {
  Handlebars.K.prototype = object;
  var obj = new Handlebars.K();
  Handlebars.K.prototype = null;
  return obj;
};

Handlebars.logger = {
  DEBUG: 0, INFO: 1, WARN: 2, ERROR: 3, level: 3,

  methodMap: {0: 'debug', 1: 'info', 2: 'warn', 3: 'error'},

  // can be overridden in the host environment
  log: function(level, obj) {
    if (Handlebars.logger.level <= level) {
      var method = Handlebars.logger.methodMap[level];
      if (typeof console !== 'undefined' && console[method]) {
        console[method].call(console, obj);
      }
    }
  }
};

Handlebars.log = function(level, obj) { Handlebars.logger.log(level, obj); };

Handlebars.registerHelper('each', function(context, options) {
  var fn = options.fn, inverse = options.inverse;
  var i = 0, ret = "", data;

  var type = toString.call(context);
  if(type === functionType) { context = context.call(this); }

  if (options.data) {
    data = Handlebars.createFrame(options.data);
  }

  if(context && typeof context === 'object') {
    if(context instanceof Array){
      for(var j = context.length; i<j; i++) {
        if (data) { data.index = i; }
        ret = ret + fn(context[i], { data: data });
      }
    } else {
      for(var key in context) {
        if(context.hasOwnProperty(key)) {
          if(data) { data.key = key; }
          ret = ret + fn(context[key], {data: data});
          i++;
        }
      }
    }
  }

  if(i === 0){
    ret = inverse(this);
  }

  return ret;
});

Handlebars.registerHelper('if', function(conditional, options) {
  var type = toString.call(conditional);
  if(type === functionType) { conditional = conditional.call(this); }

  if(!conditional || Handlebars.Utils.isEmpty(conditional)) {
    return options.inverse(this);
  } else {
    return options.fn(this);
  }
});

Handlebars.registerHelper('unless', function(conditional, options) {
  return Handlebars.helpers['if'].call(this, conditional, {fn: options.inverse, inverse: options.fn});
});

Handlebars.registerHelper('with', function(context, options) {
  var type = toString.call(context);
  if(type === functionType) { context = context.call(this); }

  if (!Handlebars.Utils.isEmpty(context)) return options.fn(context);
});

Handlebars.registerHelper('log', function(context, options) {
  var level = options.data && options.data.level != null ? parseInt(options.data.level, 10) : 1;
  Handlebars.log(level, context);
});
;
// lib/handlebars/compiler/parser.js
/* Jison generated parser */
var handlebars = (function(){
var parser = {trace: function trace() { },
yy: {},
symbols_: {"error":2,"root":3,"program":4,"EOF":5,"simpleInverse":6,"statements":7,"statement":8,"openInverse":9,"closeBlock":10,"openBlock":11,"mustache":12,"partial":13,"CONTENT":14,"COMMENT":15,"OPEN_BLOCK":16,"inMustache":17,"CLOSE":18,"OPEN_INVERSE":19,"OPEN_ENDBLOCK":20,"path":21,"OPEN":22,"OPEN_UNESCAPED":23,"CLOSE_UNESCAPED":24,"OPEN_PARTIAL":25,"partialName":26,"params":27,"hash":28,"dataName":29,"param":30,"STRING":31,"INTEGER":32,"BOOLEAN":33,"hashSegments":34,"hashSegment":35,"ID":36,"EQUALS":37,"DATA":38,"pathSegments":39,"SEP":40,"$accept":0,"$end":1},
terminals_: {2:"error",5:"EOF",14:"CONTENT",15:"COMMENT",16:"OPEN_BLOCK",18:"CLOSE",19:"OPEN_INVERSE",20:"OPEN_ENDBLOCK",22:"OPEN",23:"OPEN_UNESCAPED",24:"CLOSE_UNESCAPED",25:"OPEN_PARTIAL",31:"STRING",32:"INTEGER",33:"BOOLEAN",36:"ID",37:"EQUALS",38:"DATA",40:"SEP"},
productions_: [0,[3,2],[4,2],[4,3],[4,2],[4,1],[4,1],[4,0],[7,1],[7,2],[8,3],[8,3],[8,1],[8,1],[8,1],[8,1],[11,3],[9,3],[10,3],[12,3],[12,3],[13,3],[13,4],[6,2],[17,3],[17,2],[17,2],[17,1],[17,1],[27,2],[27,1],[30,1],[30,1],[30,1],[30,1],[30,1],[28,1],[34,2],[34,1],[35,3],[35,3],[35,3],[35,3],[35,3],[26,1],[26,1],[26,1],[29,2],[21,1],[39,3],[39,1]],
performAction: function anonymous(yytext,yyleng,yylineno,yy,yystate,$$,_$) {

var $0 = $$.length - 1;
switch (yystate) {
case 1: return $$[$0-1]; 
break;
case 2: this.$ = new yy.ProgramNode([], $$[$0]); 
break;
case 3: this.$ = new yy.ProgramNode($$[$0-2], $$[$0]); 
break;
case 4: this.$ = new yy.ProgramNode($$[$0-1], []); 
break;
case 5: this.$ = new yy.ProgramNode($$[$0]); 
break;
case 6: this.$ = new yy.ProgramNode([], []); 
break;
case 7: this.$ = new yy.ProgramNode([]); 
break;
case 8: this.$ = [$$[$0]]; 
break;
case 9: $$[$0-1].push($$[$0]); this.$ = $$[$0-1]; 
break;
case 10: this.$ = new yy.BlockNode($$[$0-2], $$[$0-1].inverse, $$[$0-1], $$[$0]); 
break;
case 11: this.$ = new yy.BlockNode($$[$0-2], $$[$0-1], $$[$0-1].inverse, $$[$0]); 
break;
case 12: this.$ = $$[$0]; 
break;
case 13: this.$ = $$[$0]; 
break;
case 14: this.$ = new yy.ContentNode($$[$0]); 
break;
case 15: this.$ = new yy.CommentNode($$[$0]); 
break;
case 16: this.$ = new yy.MustacheNode($$[$0-1][0], $$[$0-1][1]); 
break;
case 17: this.$ = new yy.MustacheNode($$[$0-1][0], $$[$0-1][1]); 
break;
case 18: this.$ = $$[$0-1]; 
break;
case 19:
    // Parsing out the '&' escape token at this level saves ~500 bytes after min due to the removal of one parser node.
    this.$ = new yy.MustacheNode($$[$0-1][0], $$[$0-1][1], $$[$0-2][2] === '&');
  
break;
case 20: this.$ = new yy.MustacheNode($$[$0-1][0], $$[$0-1][1], true); 
break;
case 21: this.$ = new yy.PartialNode($$[$0-1]); 
break;
case 22: this.$ = new yy.PartialNode($$[$0-2], $$[$0-1]); 
break;
case 23: 
break;
case 24: this.$ = [[$$[$0-2]].concat($$[$0-1]), $$[$0]]; 
break;
case 25: this.$ = [[$$[$0-1]].concat($$[$0]), null]; 
break;
case 26: this.$ = [[$$[$0-1]], $$[$0]]; 
break;
case 27: this.$ = [[$$[$0]], null]; 
break;
case 28: this.$ = [[$$[$0]], null]; 
break;
case 29: $$[$0-1].push($$[$0]); this.$ = $$[$0-1]; 
break;
case 30: this.$ = [$$[$0]]; 
break;
case 31: this.$ = $$[$0]; 
break;
case 32: this.$ = new yy.StringNode($$[$0]); 
break;
case 33: this.$ = new yy.IntegerNode($$[$0]); 
break;
case 34: this.$ = new yy.BooleanNode($$[$0]); 
break;
case 35: this.$ = $$[$0]; 
break;
case 36: this.$ = new yy.HashNode($$[$0]); 
break;
case 37: $$[$0-1].push($$[$0]); this.$ = $$[$0-1]; 
break;
case 38: this.$ = [$$[$0]]; 
break;
case 39: this.$ = [$$[$0-2], $$[$0]]; 
break;
case 40: this.$ = [$$[$0-2], new yy.StringNode($$[$0])]; 
break;
case 41: this.$ = [$$[$0-2], new yy.IntegerNode($$[$0])]; 
break;
case 42: this.$ = [$$[$0-2], new yy.BooleanNode($$[$0])]; 
break;
case 43: this.$ = [$$[$0-2], $$[$0]]; 
break;
case 44: this.$ = new yy.PartialNameNode($$[$0]); 
break;
case 45: this.$ = new yy.PartialNameNode(new yy.StringNode($$[$0])); 
break;
case 46: this.$ = new yy.PartialNameNode(new yy.IntegerNode($$[$0])); 
break;
case 47: this.$ = new yy.DataNode($$[$0]); 
break;
case 48: this.$ = new yy.IdNode($$[$0]); 
break;
case 49: $$[$0-2].push({part: $$[$0], separator: $$[$0-1]}); this.$ = $$[$0-2]; 
break;
case 50: this.$ = [{part: $$[$0]}]; 
break;
}
},
table: [{3:1,4:2,5:[2,7],6:3,7:4,8:6,9:7,11:8,12:9,13:10,14:[1,11],15:[1,12],16:[1,13],19:[1,5],22:[1,14],23:[1,15],25:[1,16]},{1:[3]},{5:[1,17]},{5:[2,6],7:18,8:6,9:7,11:8,12:9,13:10,14:[1,11],15:[1,12],16:[1,13],19:[1,19],20:[2,6],22:[1,14],23:[1,15],25:[1,16]},{5:[2,5],6:20,8:21,9:7,11:8,12:9,13:10,14:[1,11],15:[1,12],16:[1,13],19:[1,5],20:[2,5],22:[1,14],23:[1,15],25:[1,16]},{17:23,18:[1,22],21:24,29:25,36:[1,28],38:[1,27],39:26},{5:[2,8],14:[2,8],15:[2,8],16:[2,8],19:[2,8],20:[2,8],22:[2,8],23:[2,8],25:[2,8]},{4:29,6:3,7:4,8:6,9:7,11:8,12:9,13:10,14:[1,11],15:[1,12],16:[1,13],19:[1,5],20:[2,7],22:[1,14],23:[1,15],25:[1,16]},{4:30,6:3,7:4,8:6,9:7,11:8,12:9,13:10,14:[1,11],15:[1,12],16:[1,13],19:[1,5],20:[2,7],22:[1,14],23:[1,15],25:[1,16]},{5:[2,12],14:[2,12],15:[2,12],16:[2,12],19:[2,12],20:[2,12],22:[2,12],23:[2,12],25:[2,12]},{5:[2,13],14:[2,13],15:[2,13],16:[2,13],19:[2,13],20:[2,13],22:[2,13],23:[2,13],25:[2,13]},{5:[2,14],14:[2,14],15:[2,14],16:[2,14],19:[2,14],20:[2,14],22:[2,14],23:[2,14],25:[2,14]},{5:[2,15],14:[2,15],15:[2,15],16:[2,15],19:[2,15],20:[2,15],22:[2,15],23:[2,15],25:[2,15]},{17:31,21:24,29:25,36:[1,28],38:[1,27],39:26},{17:32,21:24,29:25,36:[1,28],38:[1,27],39:26},{17:33,21:24,29:25,36:[1,28],38:[1,27],39:26},{21:35,26:34,31:[1,36],32:[1,37],36:[1,28],39:26},{1:[2,1]},{5:[2,2],8:21,9:7,11:8,12:9,13:10,14:[1,11],15:[1,12],16:[1,13],19:[1,19],20:[2,2],22:[1,14],23:[1,15],25:[1,16]},{17:23,21:24,29:25,36:[1,28],38:[1,27],39:26},{5:[2,4],7:38,8:6,9:7,11:8,12:9,13:10,14:[1,11],15:[1,12],16:[1,13],19:[1,19],20:[2,4],22:[1,14],23:[1,15],25:[1,16]},{5:[2,9],14:[2,9],15:[2,9],16:[2,9],19:[2,9],20:[2,9],22:[2,9],23:[2,9],25:[2,9]},{5:[2,23],14:[2,23],15:[2,23],16:[2,23],19:[2,23],20:[2,23],22:[2,23],23:[2,23],25:[2,23]},{18:[1,39]},{18:[2,27],21:44,24:[2,27],27:40,28:41,29:48,30:42,31:[1,45],32:[1,46],33:[1,47],34:43,35:49,36:[1,50],38:[1,27],39:26},{18:[2,28],24:[2,28]},{18:[2,48],24:[2,48],31:[2,48],32:[2,48],33:[2,48],36:[2,48],38:[2,48],40:[1,51]},{21:52,36:[1,28],39:26},{18:[2,50],24:[2,50],31:[2,50],32:[2,50],33:[2,50],36:[2,50],38:[2,50],40:[2,50]},{10:53,20:[1,54]},{10:55,20:[1,54]},{18:[1,56]},{18:[1,57]},{24:[1,58]},{18:[1,59],21:60,36:[1,28],39:26},{18:[2,44],36:[2,44]},{18:[2,45],36:[2,45]},{18:[2,46],36:[2,46]},{5:[2,3],8:21,9:7,11:8,12:9,13:10,14:[1,11],15:[1,12],16:[1,13],19:[1,19],20:[2,3],22:[1,14],23:[1,15],25:[1,16]},{14:[2,17],15:[2,17],16:[2,17],19:[2,17],20:[2,17],22:[2,17],23:[2,17],25:[2,17]},{18:[2,25],21:44,24:[2,25],28:61,29:48,30:62,31:[1,45],32:[1,46],33:[1,47],34:43,35:49,36:[1,50],38:[1,27],39:26},{18:[2,26],24:[2,26]},{18:[2,30],24:[2,30],31:[2,30],32:[2,30],33:[2,30],36:[2,30],38:[2,30]},{18:[2,36],24:[2,36],35:63,36:[1,64]},{18:[2,31],24:[2,31],31:[2,31],32:[2,31],33:[2,31],36:[2,31],38:[2,31]},{18:[2,32],24:[2,32],31:[2,32],32:[2,32],33:[2,32],36:[2,32],38:[2,32]},{18:[2,33],24:[2,33],31:[2,33],32:[2,33],33:[2,33],36:[2,33],38:[2,33]},{18:[2,34],24:[2,34],31:[2,34],32:[2,34],33:[2,34],36:[2,34],38:[2,34]},{18:[2,35],24:[2,35],31:[2,35],32:[2,35],33:[2,35],36:[2,35],38:[2,35]},{18:[2,38],24:[2,38],36:[2,38]},{18:[2,50],24:[2,50],31:[2,50],32:[2,50],33:[2,50],36:[2,50],37:[1,65],38:[2,50],40:[2,50]},{36:[1,66]},{18:[2,47],24:[2,47],31:[2,47],32:[2,47],33:[2,47],36:[2,47],38:[2,47]},{5:[2,10],14:[2,10],15:[2,10],16:[2,10],19:[2,10],20:[2,10],22:[2,10],23:[2,10],25:[2,10]},{21:67,36:[1,28],39:26},{5:[2,11],14:[2,11],15:[2,11],16:[2,11],19:[2,11],20:[2,11],22:[2,11],23:[2,11],25:[2,11]},{14:[2,16],15:[2,16],16:[2,16],19:[2,16],20:[2,16],22:[2,16],23:[2,16],25:[2,16]},{5:[2,19],14:[2,19],15:[2,19],16:[2,19],19:[2,19],20:[2,19],22:[2,19],23:[2,19],25:[2,19]},{5:[2,20],14:[2,20],15:[2,20],16:[2,20],19:[2,20],20:[2,20],22:[2,20],23:[2,20],25:[2,20]},{5:[2,21],14:[2,21],15:[2,21],16:[2,21],19:[2,21],20:[2,21],22:[2,21],23:[2,21],25:[2,21]},{18:[1,68]},{18:[2,24],24:[2,24]},{18:[2,29],24:[2,29],31:[2,29],32:[2,29],33:[2,29],36:[2,29],38:[2,29]},{18:[2,37],24:[2,37],36:[2,37]},{37:[1,65]},{21:69,29:73,31:[1,70],32:[1,71],33:[1,72],36:[1,28],38:[1,27],39:26},{18:[2,49],24:[2,49],31:[2,49],32:[2,49],33:[2,49],36:[2,49],38:[2,49],40:[2,49]},{18:[1,74]},{5:[2,22],14:[2,22],15:[2,22],16:[2,22],19:[2,22],20:[2,22],22:[2,22],23:[2,22],25:[2,22]},{18:[2,39],24:[2,39],36:[2,39]},{18:[2,40],24:[2,40],36:[2,40]},{18:[2,41],24:[2,41],36:[2,41]},{18:[2,42],24:[2,42],36:[2,42]},{18:[2,43],24:[2,43],36:[2,43]},{5:[2,18],14:[2,18],15:[2,18],16:[2,18],19:[2,18],20:[2,18],22:[2,18],23:[2,18],25:[2,18]}],
defaultActions: {17:[2,1]},
parseError: function parseError(str, hash) {
    throw new Error(str);
},
parse: function parse(input) {
    var self = this, stack = [0], vstack = [null], lstack = [], table = this.table, yytext = "", yylineno = 0, yyleng = 0, recovering = 0, TERROR = 2, EOF = 1;
    this.lexer.setInput(input);
    this.lexer.yy = this.yy;
    this.yy.lexer = this.lexer;
    this.yy.parser = this;
    if (typeof this.lexer.yylloc == "undefined")
        this.lexer.yylloc = {};
    var yyloc = this.lexer.yylloc;
    lstack.push(yyloc);
    var ranges = this.lexer.options && this.lexer.options.ranges;
    if (typeof this.yy.parseError === "function")
        this.parseError = this.yy.parseError;
    function popStack(n) {
        stack.length = stack.length - 2 * n;
        vstack.length = vstack.length - n;
        lstack.length = lstack.length - n;
    }
    function lex() {
        var token;
        token = self.lexer.lex() || 1;
        if (typeof token !== "number") {
            token = self.symbols_[token] || token;
        }
        return token;
    }
    var symbol, preErrorSymbol, state, action, a, r, yyval = {}, p, len, newState, expected;
    while (true) {
        state = stack[stack.length - 1];
        if (this.defaultActions[state]) {
            action = this.defaultActions[state];
        } else {
            if (symbol === null || typeof symbol == "undefined") {
                symbol = lex();
            }
            action = table[state] && table[state][symbol];
        }
        if (typeof action === "undefined" || !action.length || !action[0]) {
            var errStr = "";
            if (!recovering) {
                expected = [];
                for (p in table[state])
                    if (this.terminals_[p] && p > 2) {
                        expected.push("'" + this.terminals_[p] + "'");
                    }
                if (this.lexer.showPosition) {
                    errStr = "Parse error on line " + (yylineno + 1) + ":\n" + this.lexer.showPosition() + "\nExpecting " + expected.join(", ") + ", got '" + (this.terminals_[symbol] || symbol) + "'";
                } else {
                    errStr = "Parse error on line " + (yylineno + 1) + ": Unexpected " + (symbol == 1?"end of input":"'" + (this.terminals_[symbol] || symbol) + "'");
                }
                this.parseError(errStr, {text: this.lexer.match, token: this.terminals_[symbol] || symbol, line: this.lexer.yylineno, loc: yyloc, expected: expected});
            }
        }
        if (action[0] instanceof Array && action.length > 1) {
            throw new Error("Parse Error: multiple actions possible at state: " + state + ", token: " + symbol);
        }
        switch (action[0]) {
        case 1:
            stack.push(symbol);
            vstack.push(this.lexer.yytext);
            lstack.push(this.lexer.yylloc);
            stack.push(action[1]);
            symbol = null;
            if (!preErrorSymbol) {
                yyleng = this.lexer.yyleng;
                yytext = this.lexer.yytext;
                yylineno = this.lexer.yylineno;
                yyloc = this.lexer.yylloc;
                if (recovering > 0)
                    recovering--;
            } else {
                symbol = preErrorSymbol;
                preErrorSymbol = null;
            }
            break;
        case 2:
            len = this.productions_[action[1]][1];
            yyval.$ = vstack[vstack.length - len];
            yyval._$ = {first_line: lstack[lstack.length - (len || 1)].first_line, last_line: lstack[lstack.length - 1].last_line, first_column: lstack[lstack.length - (len || 1)].first_column, last_column: lstack[lstack.length - 1].last_column};
            if (ranges) {
                yyval._$.range = [lstack[lstack.length - (len || 1)].range[0], lstack[lstack.length - 1].range[1]];
            }
            r = this.performAction.call(yyval, yytext, yyleng, yylineno, this.yy, action[1], vstack, lstack);
            if (typeof r !== "undefined") {
                return r;
            }
            if (len) {
                stack = stack.slice(0, -1 * len * 2);
                vstack = vstack.slice(0, -1 * len);
                lstack = lstack.slice(0, -1 * len);
            }
            stack.push(this.productions_[action[1]][0]);
            vstack.push(yyval.$);
            lstack.push(yyval._$);
            newState = table[stack[stack.length - 2]][stack[stack.length - 1]];
            stack.push(newState);
            break;
        case 3:
            return true;
        }
    }
    return true;
}
};
/* Jison generated lexer */
var lexer = (function(){
var lexer = ({EOF:1,
parseError:function parseError(str, hash) {
        if (this.yy.parser) {
            this.yy.parser.parseError(str, hash);
        } else {
            throw new Error(str);
        }
    },
setInput:function (input) {
        this._input = input;
        this._more = this._less = this.done = false;
        this.yylineno = this.yyleng = 0;
        this.yytext = this.matched = this.match = '';
        this.conditionStack = ['INITIAL'];
        this.yylloc = {first_line:1,first_column:0,last_line:1,last_column:0};
        if (this.options.ranges) this.yylloc.range = [0,0];
        this.offset = 0;
        return this;
    },
input:function () {
        var ch = this._input[0];
        this.yytext += ch;
        this.yyleng++;
        this.offset++;
        this.match += ch;
        this.matched += ch;
        var lines = ch.match(/(?:\r\n?|\n).*/g);
        if (lines) {
            this.yylineno++;
            this.yylloc.last_line++;
        } else {
            this.yylloc.last_column++;
        }
        if (this.options.ranges) this.yylloc.range[1]++;

        this._input = this._input.slice(1);
        return ch;
    },
unput:function (ch) {
        var len = ch.length;
        var lines = ch.split(/(?:\r\n?|\n)/g);

        this._input = ch + this._input;
        this.yytext = this.yytext.substr(0, this.yytext.length-len-1);
        //this.yyleng -= len;
        this.offset -= len;
        var oldLines = this.match.split(/(?:\r\n?|\n)/g);
        this.match = this.match.substr(0, this.match.length-1);
        this.matched = this.matched.substr(0, this.matched.length-1);

        if (lines.length-1) this.yylineno -= lines.length-1;
        var r = this.yylloc.range;

        this.yylloc = {first_line: this.yylloc.first_line,
          last_line: this.yylineno+1,
          first_column: this.yylloc.first_column,
          last_column: lines ?
              (lines.length === oldLines.length ? this.yylloc.first_column : 0) + oldLines[oldLines.length - lines.length].length - lines[0].length:
              this.yylloc.first_column - len
          };

        if (this.options.ranges) {
            this.yylloc.range = [r[0], r[0] + this.yyleng - len];
        }
        return this;
    },
more:function () {
        this._more = true;
        return this;
    },
less:function (n) {
        this.unput(this.match.slice(n));
    },
pastInput:function () {
        var past = this.matched.substr(0, this.matched.length - this.match.length);
        return (past.length > 20 ? '...':'') + past.substr(-20).replace(/\n/g, "");
    },
upcomingInput:function () {
        var next = this.match;
        if (next.length < 20) {
            next += this._input.substr(0, 20-next.length);
        }
        return (next.substr(0,20)+(next.length > 20 ? '...':'')).replace(/\n/g, "");
    },
showPosition:function () {
        var pre = this.pastInput();
        var c = new Array(pre.length + 1).join("-");
        return pre + this.upcomingInput() + "\n" + c+"^";
    },
next:function () {
        if (this.done) {
            return this.EOF;
        }
        if (!this._input) this.done = true;

        var token,
            match,
            tempMatch,
            index,
            col,
            lines;
        if (!this._more) {
            this.yytext = '';
            this.match = '';
        }
        var rules = this._currentRules();
        for (var i=0;i < rules.length; i++) {
            tempMatch = this._input.match(this.rules[rules[i]]);
            if (tempMatch && (!match || tempMatch[0].length > match[0].length)) {
                match = tempMatch;
                index = i;
                if (!this.options.flex) break;
            }
        }
        if (match) {
            lines = match[0].match(/(?:\r\n?|\n).*/g);
            if (lines) this.yylineno += lines.length;
            this.yylloc = {first_line: this.yylloc.last_line,
                           last_line: this.yylineno+1,
                           first_column: this.yylloc.last_column,
                           last_column: lines ? lines[lines.length-1].length-lines[lines.length-1].match(/\r?\n?/)[0].length : this.yylloc.last_column + match[0].length};
            this.yytext += match[0];
            this.match += match[0];
            this.matches = match;
            this.yyleng = this.yytext.length;
            if (this.options.ranges) {
                this.yylloc.range = [this.offset, this.offset += this.yyleng];
            }
            this._more = false;
            this._input = this._input.slice(match[0].length);
            this.matched += match[0];
            token = this.performAction.call(this, this.yy, this, rules[index],this.conditionStack[this.conditionStack.length-1]);
            if (this.done && this._input) this.done = false;
            if (token) return token;
            else return;
        }
        if (this._input === "") {
            return this.EOF;
        } else {
            return this.parseError('Lexical error on line '+(this.yylineno+1)+'. Unrecognized text.\n'+this.showPosition(),
                    {text: "", token: null, line: this.yylineno});
        }
    },
lex:function lex() {
        var r = this.next();
        if (typeof r !== 'undefined') {
            return r;
        } else {
            return this.lex();
        }
    },
begin:function begin(condition) {
        this.conditionStack.push(condition);
    },
popState:function popState() {
        return this.conditionStack.pop();
    },
_currentRules:function _currentRules() {
        return this.conditions[this.conditionStack[this.conditionStack.length-1]].rules;
    },
topState:function () {
        return this.conditionStack[this.conditionStack.length-2];
    },
pushState:function begin(condition) {
        this.begin(condition);
    }});
lexer.options = {};
lexer.performAction = function anonymous(yy,yy_,$avoiding_name_collisions,YY_START) {

var YYSTATE=YY_START
switch($avoiding_name_collisions) {
case 0: yy_.yytext = "\\"; return 14; 
break;
case 1:
                                   if(yy_.yytext.slice(-1) !== "\\") this.begin("mu");
                                   if(yy_.yytext.slice(-1) === "\\") yy_.yytext = yy_.yytext.substr(0,yy_.yyleng-1), this.begin("emu");
                                   if(yy_.yytext) return 14;
                                 
break;
case 2: return 14; 
break;
case 3:
                                   if(yy_.yytext.slice(-1) !== "\\") this.popState();
                                   if(yy_.yytext.slice(-1) === "\\") yy_.yytext = yy_.yytext.substr(0,yy_.yyleng-1);
                                   return 14;
                                 
break;
case 4: yy_.yytext = yy_.yytext.substr(0, yy_.yyleng-4); this.popState(); return 15; 
break;
case 5: return 25; 
break;
case 6: return 16; 
break;
case 7: return 20; 
break;
case 8: return 19; 
break;
case 9: return 19; 
break;
case 10: return 23; 
break;
case 11: return 22; 
break;
case 12: this.popState(); this.begin('com'); 
break;
case 13: yy_.yytext = yy_.yytext.substr(3,yy_.yyleng-5); this.popState(); return 15; 
break;
case 14: return 22; 
break;
case 15: return 37; 
break;
case 16: return 36; 
break;
case 17: return 36; 
break;
case 18: return 40; 
break;
case 19: /*ignore whitespace*/ 
break;
case 20: this.popState(); return 24; 
break;
case 21: this.popState(); return 18; 
break;
case 22: yy_.yytext = yy_.yytext.substr(1,yy_.yyleng-2).replace(/\\"/g,'"'); return 31; 
break;
case 23: yy_.yytext = yy_.yytext.substr(1,yy_.yyleng-2).replace(/\\'/g,"'"); return 31; 
break;
case 24: return 38; 
break;
case 25: return 33; 
break;
case 26: return 33; 
break;
case 27: return 32; 
break;
case 28: return 36; 
break;
case 29: yy_.yytext = yy_.yytext.substr(1, yy_.yyleng-2); return 36; 
break;
case 30: return 'INVALID'; 
break;
case 31: return 5; 
break;
}
};
lexer.rules = [/^(?:\\\\(?=(\{\{)))/,/^(?:[^\x00]*?(?=(\{\{)))/,/^(?:[^\x00]+)/,/^(?:[^\x00]{2,}?(?=(\{\{|$)))/,/^(?:[\s\S]*?--\}\})/,/^(?:\{\{>)/,/^(?:\{\{#)/,/^(?:\{\{\/)/,/^(?:\{\{\^)/,/^(?:\{\{\s*else\b)/,/^(?:\{\{\{)/,/^(?:\{\{&)/,/^(?:\{\{!--)/,/^(?:\{\{![\s\S]*?\}\})/,/^(?:\{\{)/,/^(?:=)/,/^(?:\.(?=[}\/ ]))/,/^(?:\.\.)/,/^(?:[\/.])/,/^(?:\s+)/,/^(?:\}\}\})/,/^(?:\}\})/,/^(?:"(\\["]|[^"])*")/,/^(?:'(\\[']|[^'])*')/,/^(?:@)/,/^(?:true(?=[}\s]))/,/^(?:false(?=[}\s]))/,/^(?:-?[0-9]+(?=[}\s]))/,/^(?:[^\s!"#%-,\.\/;->@\[-\^`\{-~]+(?=[=}\s\/.]))/,/^(?:\[[^\]]*\])/,/^(?:.)/,/^(?:$)/];
lexer.conditions = {"mu":{"rules":[5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31],"inclusive":false},"emu":{"rules":[3],"inclusive":false},"com":{"rules":[4],"inclusive":false},"INITIAL":{"rules":[0,1,2,31],"inclusive":true}};
return lexer;})()
parser.lexer = lexer;
function Parser () { this.yy = {}; }Parser.prototype = parser;parser.Parser = Parser;
return new Parser;
})();;
// lib/handlebars/compiler/base.js

Handlebars.Parser = handlebars;

Handlebars.parse = function(input) {

  // Just return if an already-compile AST was passed in.
  if(input.constructor === Handlebars.AST.ProgramNode) { return input; }

  Handlebars.Parser.yy = Handlebars.AST;
  return Handlebars.Parser.parse(input);
};
;
// lib/handlebars/compiler/ast.js
Handlebars.AST = {};

Handlebars.AST.ProgramNode = function(statements, inverse) {
  this.type = "program";
  this.statements = statements;
  if(inverse) { this.inverse = new Handlebars.AST.ProgramNode(inverse); }
};

Handlebars.AST.MustacheNode = function(rawParams, hash, unescaped) {
  this.type = "mustache";
  this.escaped = !unescaped;
  this.hash = hash;

  var id = this.id = rawParams[0];
  var params = this.params = rawParams.slice(1);

  // a mustache is an eligible helper if:
  // * its id is simple (a single part, not `this` or `..`)
  var eligibleHelper = this.eligibleHelper = id.isSimple;

  // a mustache is definitely a helper if:
  // * it is an eligible helper, and
  // * it has at least one parameter or hash segment
  this.isHelper = eligibleHelper && (params.length || hash);

  // if a mustache is an eligible helper but not a definite
  // helper, it is ambiguous, and will be resolved in a later
  // pass or at runtime.
};

Handlebars.AST.PartialNode = function(partialName, context) {
  this.type         = "partial";
  this.partialName  = partialName;
  this.context      = context;
};

Handlebars.AST.BlockNode = function(mustache, program, inverse, close) {
  var verifyMatch = function(open, close) {
    if(open.original !== close.original) {
      throw new Handlebars.Exception(open.original + " doesn't match " + close.original);
    }
  };

  verifyMatch(mustache.id, close);
  this.type = "block";
  this.mustache = mustache;
  this.program  = program;
  this.inverse  = inverse;

  if (this.inverse && !this.program) {
    this.isInverse = true;
  }
};

Handlebars.AST.ContentNode = function(string) {
  this.type = "content";
  this.string = string;
};

Handlebars.AST.HashNode = function(pairs) {
  this.type = "hash";
  this.pairs = pairs;
};

Handlebars.AST.IdNode = function(parts) {
  this.type = "ID";

  var original = "",
      dig = [],
      depth = 0;

  for(var i=0,l=parts.length; i<l; i++) {
    var part = parts[i].part;
    original += (parts[i].separator || '') + part;

    if (part === ".." || part === "." || part === "this") {
      if (dig.length > 0) { throw new Handlebars.Exception("Invalid path: " + original); }
      else if (part === "..") { depth++; }
      else { this.isScoped = true; }
    }
    else { dig.push(part); }
  }

  this.original = original;
  this.parts    = dig;
  this.string   = dig.join('.');
  this.depth    = depth;

  // an ID is simple if it only has one part, and that part is not
  // `..` or `this`.
  this.isSimple = parts.length === 1 && !this.isScoped && depth === 0;

  this.stringModeValue = this.string;
};

Handlebars.AST.PartialNameNode = function(name) {
  this.type = "PARTIAL_NAME";
  this.name = name.original;
};

Handlebars.AST.DataNode = function(id) {
  this.type = "DATA";
  this.id = id;
};

Handlebars.AST.StringNode = function(string) {
  this.type = "STRING";
  this.original =
    this.string =
    this.stringModeValue = string;
};

Handlebars.AST.IntegerNode = function(integer) {
  this.type = "INTEGER";
  this.original =
    this.integer = integer;
  this.stringModeValue = Number(integer);
};

Handlebars.AST.BooleanNode = function(bool) {
  this.type = "BOOLEAN";
  this.bool = bool;
  this.stringModeValue = bool === "true";
};

Handlebars.AST.CommentNode = function(comment) {
  this.type = "comment";
  this.comment = comment;
};
;
// lib/handlebars/utils.js

var errorProps = ['description', 'fileName', 'lineNumber', 'message', 'name', 'number', 'stack'];

Handlebars.Exception = function(message) {
  var tmp = Error.prototype.constructor.apply(this, arguments);

  // Unfortunately errors are not enumerable in Chrome (at least), so `for prop in tmp` doesn't work.
  for (var idx = 0; idx < errorProps.length; idx++) {
    this[errorProps[idx]] = tmp[errorProps[idx]];
  }
};
Handlebars.Exception.prototype = new Error();

// Build out our basic SafeString type
Handlebars.SafeString = function(string) {
  this.string = string;
};
Handlebars.SafeString.prototype.toString = function() {
  return this.string.toString();
};

var escape = {
  "&": "&amp;",
  "<": "&lt;",
  ">": "&gt;",
  '"': "&quot;",
  "'": "&#x27;",
  "`": "&#x60;"
};

var badChars = /[&<>"'`]/g;
var possible = /[&<>"'`]/;

var escapeChar = function(chr) {
  return escape[chr] || "&amp;";
};

Handlebars.Utils = {
  extend: function(obj, value) {
    for(var key in value) {
      if(value.hasOwnProperty(key)) {
        obj[key] = value[key];
      }
    }
  },

  escapeExpression: function(string) {
    // don't escape SafeStrings, since they're already safe
    if (string instanceof Handlebars.SafeString) {
      return string.toString();
    } else if (string == null || string === false) {
      return "";
    }

    // Force a string conversion as this will be done by the append regardless and
    // the regex test will do this transparently behind the scenes, causing issues if
    // an object's to string has escaped characters in it.
    string = string.toString();

    if(!possible.test(string)) { return string; }
    return string.replace(badChars, escapeChar);
  },

  isEmpty: function(value) {
    if (!value && value !== 0) {
      return true;
    } else if(toString.call(value) === "[object Array]" && value.length === 0) {
      return true;
    } else {
      return false;
    }
  }
};
;
// lib/handlebars/compiler/compiler.js

/*jshint eqnull:true*/
var Compiler = Handlebars.Compiler = function() {};
var JavaScriptCompiler = Handlebars.JavaScriptCompiler = function() {};

// the foundHelper register will disambiguate helper lookup from finding a
// function in a context. This is necessary for mustache compatibility, which
// requires that context functions in blocks are evaluated by blockHelperMissing,
// and then proceed as if the resulting value was provided to blockHelperMissing.

Compiler.prototype = {
  compiler: Compiler,

  disassemble: function() {
    var opcodes = this.opcodes, opcode, out = [], params, param;

    for (var i=0, l=opcodes.length; i<l; i++) {
      opcode = opcodes[i];

      if (opcode.opcode === 'DECLARE') {
        out.push("DECLARE " + opcode.name + "=" + opcode.value);
      } else {
        params = [];
        for (var j=0; j<opcode.args.length; j++) {
          param = opcode.args[j];
          if (typeof param === "string") {
            param = "\"" + param.replace("\n", "\\n") + "\"";
          }
          params.push(param);
        }
        out.push(opcode.opcode + " " + params.join(" "));
      }
    }

    return out.join("\n");
  },
  equals: function(other) {
    var len = this.opcodes.length;
    if (other.opcodes.length !== len) {
      return false;
    }

    for (var i = 0; i < len; i++) {
      var opcode = this.opcodes[i],
          otherOpcode = other.opcodes[i];
      if (opcode.opcode !== otherOpcode.opcode || opcode.args.length !== otherOpcode.args.length) {
        return false;
      }
      for (var j = 0; j < opcode.args.length; j++) {
        if (opcode.args[j] !== otherOpcode.args[j]) {
          return false;
        }
      }
    }

    len = this.children.length;
    if (other.children.length !== len) {
      return false;
    }
    for (i = 0; i < len; i++) {
      if (!this.children[i].equals(other.children[i])) {
        return false;
      }
    }

    return true;
  },

  guid: 0,

  compile: function(program, options) {
    this.children = [];
    this.depths = {list: []};
    this.options = options;

    // These changes will propagate to the other compiler components
    var knownHelpers = this.options.knownHelpers;
    this.options.knownHelpers = {
      'helperMissing': true,
      'blockHelperMissing': true,
      'each': true,
      'if': true,
      'unless': true,
      'with': true,
      'log': true
    };
    if (knownHelpers) {
      for (var name in knownHelpers) {
        this.options.knownHelpers[name] = knownHelpers[name];
      }
    }

    return this.program(program);
  },

  accept: function(node) {
    return this[node.type](node);
  },

  program: function(program) {
    var statements = program.statements, statement;
    this.opcodes = [];

    for(var i=0, l=statements.length; i<l; i++) {
      statement = statements[i];
      this[statement.type](statement);
    }
    this.isSimple = l === 1;

    this.depths.list = this.depths.list.sort(function(a, b) {
      return a - b;
    });

    return this;
  },

  compileProgram: function(program) {
    var result = new this.compiler().compile(program, this.options);
    var guid = this.guid++, depth;

    this.usePartial = this.usePartial || result.usePartial;

    this.children[guid] = result;

    for(var i=0, l=result.depths.list.length; i<l; i++) {
      depth = result.depths.list[i];

      if(depth < 2) { continue; }
      else { this.addDepth(depth - 1); }
    }

    return guid;
  },

  block: function(block) {
    var mustache = block.mustache,
        program = block.program,
        inverse = block.inverse;

    if (program) {
      program = this.compileProgram(program);
    }

    if (inverse) {
      inverse = this.compileProgram(inverse);
    }

    var type = this.classifyMustache(mustache);

    if (type === "helper") {
      this.helperMustache(mustache, program, inverse);
    } else if (type === "simple") {
      this.simpleMustache(mustache);

      // now that the simple mustache is resolved, we need to
      // evaluate it by executing `blockHelperMissing`
      this.opcode('pushProgram', program);
      this.opcode('pushProgram', inverse);
      this.opcode('emptyHash');
      this.opcode('blockValue');
    } else {
      this.ambiguousMustache(mustache, program, inverse);

      // now that the simple mustache is resolved, we need to
      // evaluate it by executing `blockHelperMissing`
      this.opcode('pushProgram', program);
      this.opcode('pushProgram', inverse);
      this.opcode('emptyHash');
      this.opcode('ambiguousBlockValue');
    }

    this.opcode('append');
  },

  hash: function(hash) {
    var pairs = hash.pairs, pair, val;

    this.opcode('pushHash');

    for(var i=0, l=pairs.length; i<l; i++) {
      pair = pairs[i];
      val  = pair[1];

      if (this.options.stringParams) {
        if(val.depth) {
          this.addDepth(val.depth);
        }
        this.opcode('getContext', val.depth || 0);
        this.opcode('pushStringParam', val.stringModeValue, val.type);
      } else {
        this.accept(val);
      }

      this.opcode('assignToHash', pair[0]);
    }
    this.opcode('popHash');
  },

  partial: function(partial) {
    var partialName = partial.partialName;
    this.usePartial = true;

    if(partial.context) {
      this.ID(partial.context);
    } else {
      this.opcode('push', 'depth0');
    }

    this.opcode('invokePartial', partialName.name);
    this.opcode('append');
  },

  content: function(content) {
    this.opcode('appendContent', content.string);
  },

  mustache: function(mustache) {
    var options = this.options;
    var type = this.classifyMustache(mustache);

    if (type === "simple") {
      this.simpleMustache(mustache);
    } else if (type === "helper") {
      this.helperMustache(mustache);
    } else {
      this.ambiguousMustache(mustache);
    }

    if(mustache.escaped && !options.noEscape) {
      this.opcode('appendEscaped');
    } else {
      this.opcode('append');
    }
  },

  ambiguousMustache: function(mustache, program, inverse) {
    var id = mustache.id,
        name = id.parts[0],
        isBlock = program != null || inverse != null;

    this.opcode('getContext', id.depth);

    this.opcode('pushProgram', program);
    this.opcode('pushProgram', inverse);

    this.opcode('invokeAmbiguous', name, isBlock);
  },

  simpleMustache: function(mustache) {
    var id = mustache.id;

    if (id.type === 'DATA') {
      this.DATA(id);
    } else if (id.parts.length) {
      this.ID(id);
    } else {
      // Simplified ID for `this`
      this.addDepth(id.depth);
      this.opcode('getContext', id.depth);
      this.opcode('pushContext');
    }

    this.opcode('resolvePossibleLambda');
  },

  helperMustache: function(mustache, program, inverse) {
    var params = this.setupFullMustacheParams(mustache, program, inverse),
        name = mustache.id.parts[0];

    if (this.options.knownHelpers[name]) {
      this.opcode('invokeKnownHelper', params.length, name);
    } else if (this.options.knownHelpersOnly) {
      throw new Error("You specified knownHelpersOnly, but used the unknown helper " + name);
    } else {
      this.opcode('invokeHelper', params.length, name);
    }
  },

  ID: function(id) {
    this.addDepth(id.depth);
    this.opcode('getContext', id.depth);

    var name = id.parts[0];
    if (!name) {
      this.opcode('pushContext');
    } else {
      this.opcode('lookupOnContext', id.parts[0]);
    }

    for(var i=1, l=id.parts.length; i<l; i++) {
      this.opcode('lookup', id.parts[i]);
    }
  },

  DATA: function(data) {
    this.options.data = true;
    if (data.id.isScoped || data.id.depth) {
      throw new Handlebars.Exception('Scoped data references are not supported: ' + data.original);
    }

    this.opcode('lookupData');
    var parts = data.id.parts;
    for(var i=0, l=parts.length; i<l; i++) {
      this.opcode('lookup', parts[i]);
    }
  },

  STRING: function(string) {
    this.opcode('pushString', string.string);
  },

  INTEGER: function(integer) {
    this.opcode('pushLiteral', integer.integer);
  },

  BOOLEAN: function(bool) {
    this.opcode('pushLiteral', bool.bool);
  },

  comment: function() {},

  // HELPERS
  opcode: function(name) {
    this.opcodes.push({ opcode: name, args: [].slice.call(arguments, 1) });
  },

  declare: function(name, value) {
    this.opcodes.push({ opcode: 'DECLARE', name: name, value: value });
  },

  addDepth: function(depth) {
    if(isNaN(depth)) { throw new Error("EWOT"); }
    if(depth === 0) { return; }

    if(!this.depths[depth]) {
      this.depths[depth] = true;
      this.depths.list.push(depth);
    }
  },

  classifyMustache: function(mustache) {
    var isHelper   = mustache.isHelper;
    var isEligible = mustache.eligibleHelper;
    var options    = this.options;

    // if ambiguous, we can possibly resolve the ambiguity now
    if (isEligible && !isHelper) {
      var name = mustache.id.parts[0];

      if (options.knownHelpers[name]) {
        isHelper = true;
      } else if (options.knownHelpersOnly) {
        isEligible = false;
      }
    }

    if (isHelper) { return "helper"; }
    else if (isEligible) { return "ambiguous"; }
    else { return "simple"; }
  },

  pushParams: function(params) {
    var i = params.length, param;

    while(i--) {
      param = params[i];

      if(this.options.stringParams) {
        if(param.depth) {
          this.addDepth(param.depth);
        }

        this.opcode('getContext', param.depth || 0);
        this.opcode('pushStringParam', param.stringModeValue, param.type);
      } else {
        this[param.type](param);
      }
    }
  },

  setupMustacheParams: function(mustache) {
    var params = mustache.params;
    this.pushParams(params);

    if(mustache.hash) {
      this.hash(mustache.hash);
    } else {
      this.opcode('emptyHash');
    }

    return params;
  },

  // this will replace setupMustacheParams when we're done
  setupFullMustacheParams: function(mustache, program, inverse) {
    var params = mustache.params;
    this.pushParams(params);

    this.opcode('pushProgram', program);
    this.opcode('pushProgram', inverse);

    if(mustache.hash) {
      this.hash(mustache.hash);
    } else {
      this.opcode('emptyHash');
    }

    return params;
  }
};

var Literal = function(value) {
  this.value = value;
};

JavaScriptCompiler.prototype = {
  // PUBLIC API: You can override these methods in a subclass to provide
  // alternative compiled forms for name lookup and buffering semantics
  nameLookup: function(parent, name /* , type*/) {
    if (/^[0-9]+$/.test(name)) {
      return parent + "[" + name + "]";
    } else if (JavaScriptCompiler.isValidJavaScriptVariableName(name)) {
      return parent + "." + name;
    }
    else {
      return parent + "['" + name + "']";
    }
  },

  appendToBuffer: function(string) {
    if (this.environment.isSimple) {
      return "return " + string + ";";
    } else {
      return {
        appendToBuffer: true,
        content: string,
        toString: function() { return "buffer += " + string + ";"; }
      };
    }
  },

  initializeBuffer: function() {
    return this.quotedString("");
  },

  namespace: "Handlebars",
  // END PUBLIC API

  compile: function(environment, options, context, asObject) {
    this.environment = environment;
    this.options = options || {};

    Handlebars.log(Handlebars.logger.DEBUG, this.environment.disassemble() + "\n\n");

    this.name = this.environment.name;
    this.isChild = !!context;
    this.context = context || {
      programs: [],
      environments: [],
      aliases: { }
    };

    this.preamble();

    this.stackSlot = 0;
    this.stackVars = [];
    this.registers = { list: [] };
    this.compileStack = [];
    this.inlineStack = [];

    this.compileChildren(environment, options);

    var opcodes = environment.opcodes, opcode;

    this.i = 0;

    for(l=opcodes.length; this.i<l; this.i++) {
      opcode = opcodes[this.i];

      if(opcode.opcode === 'DECLARE') {
        this[opcode.name] = opcode.value;
      } else {
        this[opcode.opcode].apply(this, opcode.args);
      }
    }

    return this.createFunctionContext(asObject);
  },

  nextOpcode: function() {
    var opcodes = this.environment.opcodes;
    return opcodes[this.i + 1];
  },

  eat: function() {
    this.i = this.i + 1;
  },

  preamble: function() {
    var out = [];

    if (!this.isChild) {
      var namespace = this.namespace;

      var copies = "helpers = this.merge(helpers, " + namespace + ".helpers);";
      if (this.environment.usePartial) { copies = copies + " partials = this.merge(partials, " + namespace + ".partials);"; }
      if (this.options.data) { copies = copies + " data = data || {};"; }
      out.push(copies);
    } else {
      out.push('');
    }

    if (!this.environment.isSimple) {
      out.push(", buffer = " + this.initializeBuffer());
    } else {
      out.push("");
    }

    // track the last context pushed into place to allow skipping the
    // getContext opcode when it would be a noop
    this.lastContext = 0;
    this.source = out;
  },

  createFunctionContext: function(asObject) {
    var locals = this.stackVars.concat(this.registers.list);

    if(locals.length > 0) {
      this.source[1] = this.source[1] + ", " + locals.join(", ");
    }

    // Generate minimizer alias mappings
    if (!this.isChild) {
      for (var alias in this.context.aliases) {
        if (this.context.aliases.hasOwnProperty(alias)) {
          this.source[1] = this.source[1] + ', ' + alias + '=' + this.context.aliases[alias];
        }
      }
    }

    if (this.source[1]) {
      this.source[1] = "var " + this.source[1].substring(2) + ";";
    }

    // Merge children
    if (!this.isChild) {
      this.source[1] += '\n' + this.context.programs.join('\n') + '\n';
    }

    if (!this.environment.isSimple) {
      this.source.push("return buffer;");
    }

    var params = this.isChild ? ["depth0", "data"] : ["Handlebars", "depth0", "helpers", "partials", "data"];

    for(var i=0, l=this.environment.depths.list.length; i<l; i++) {
      params.push("depth" + this.environment.depths.list[i]);
    }

    // Perform a second pass over the output to merge content when possible
    var source = this.mergeSource();

    if (!this.isChild) {
      var revision = Handlebars.COMPILER_REVISION,
          versions = Handlebars.REVISION_CHANGES[revision];
      source = "this.compilerInfo = ["+revision+",'"+versions+"'];\n"+source;
    }

    if (asObject) {
      params.push(source);

      return Function.apply(this, params);
    } else {
      var functionSource = 'function ' + (this.name || '') + '(' + params.join(',') + ') {\n  ' + source + '}';
      Handlebars.log(Handlebars.logger.DEBUG, functionSource + "\n\n");
      return functionSource;
    }
  },
  mergeSource: function() {
    // WARN: We are not handling the case where buffer is still populated as the source should
    // not have buffer append operations as their final action.
    var source = '',
        buffer;
    for (var i = 0, len = this.source.length; i < len; i++) {
      var line = this.source[i];
      if (line.appendToBuffer) {
        if (buffer) {
          buffer = buffer + '\n    + ' + line.content;
        } else {
          buffer = line.content;
        }
      } else {
        if (buffer) {
          source += 'buffer += ' + buffer + ';\n  ';
          buffer = undefined;
        }
        source += line + '\n  ';
      }
    }
    return source;
  },

  // [blockValue]
  //
  // On stack, before: hash, inverse, program, value
  // On stack, after: return value of blockHelperMissing
  //
  // The purpose of this opcode is to take a block of the form
  // `{{#foo}}...{{/foo}}`, resolve the value of `foo`, and
  // replace it on the stack with the result of properly
  // invoking blockHelperMissing.
  blockValue: function() {
    this.context.aliases.blockHelperMissing = 'helpers.blockHelperMissing';

    var params = ["depth0"];
    this.setupParams(0, params);

    this.replaceStack(function(current) {
      params.splice(1, 0, current);
      return "blockHelperMissing.call(" + params.join(", ") + ")";
    });
  },

  // [ambiguousBlockValue]
  //
  // On stack, before: hash, inverse, program, value
  // Compiler value, before: lastHelper=value of last found helper, if any
  // On stack, after, if no lastHelper: same as [blockValue]
  // On stack, after, if lastHelper: value
  ambiguousBlockValue: function() {
    this.context.aliases.blockHelperMissing = 'helpers.blockHelperMissing';

    var params = ["depth0"];
    this.setupParams(0, params);

    var current = this.topStack();
    params.splice(1, 0, current);

    // Use the options value generated from the invocation
    params[params.length-1] = 'options';

    this.source.push("if (!" + this.lastHelper + ") { " + current + " = blockHelperMissing.call(" + params.join(", ") + "); }");
  },

  // [appendContent]
  //
  // On stack, before: ...
  // On stack, after: ...
  //
  // Appends the string value of `content` to the current buffer
  appendContent: function(content) {
    this.source.push(this.appendToBuffer(this.quotedString(content)));
  },

  // [append]
  //
  // On stack, before: value, ...
  // On stack, after: ...
  //
  // Coerces `value` to a String and appends it to the current buffer.
  //
  // If `value` is truthy, or 0, it is coerced into a string and appended
  // Otherwise, the empty string is appended
  append: function() {
    // Force anything that is inlined onto the stack so we don't have duplication
    // when we examine local
    this.flushInline();
    var local = this.popStack();
    this.source.push("if(" + local + " || " + local + " === 0) { " + this.appendToBuffer(local) + " }");
    if (this.environment.isSimple) {
      this.source.push("else { " + this.appendToBuffer("''") + " }");
    }
  },

  // [appendEscaped]
  //
  // On stack, before: value, ...
  // On stack, after: ...
  //
  // Escape `value` and append it to the buffer
  appendEscaped: function() {
    this.context.aliases.escapeExpression = 'this.escapeExpression';

    this.source.push(this.appendToBuffer("escapeExpression(" + this.popStack() + ")"));
  },

  // [getContext]
  //
  // On stack, before: ...
  // On stack, after: ...
  // Compiler value, after: lastContext=depth
  //
  // Set the value of the `lastContext` compiler value to the depth
  getContext: function(depth) {
    if(this.lastContext !== depth) {
      this.lastContext = depth;
    }
  },

  // [lookupOnContext]
  //
  // On stack, before: ...
  // On stack, after: currentContext[name], ...
  //
  // Looks up the value of `name` on the current context and pushes
  // it onto the stack.
  lookupOnContext: function(name) {
    this.push(this.nameLookup('depth' + this.lastContext, name, 'context'));
  },

  // [pushContext]
  //
  // On stack, before: ...
  // On stack, after: currentContext, ...
  //
  // Pushes the value of the current context onto the stack.
  pushContext: function() {
    this.pushStackLiteral('depth' + this.lastContext);
  },

  // [resolvePossibleLambda]
  //
  // On stack, before: value, ...
  // On stack, after: resolved value, ...
  //
  // If the `value` is a lambda, replace it on the stack by
  // the return value of the lambda
  resolvePossibleLambda: function() {
    this.context.aliases.functionType = '"function"';

    this.replaceStack(function(current) {
      return "typeof " + current + " === functionType ? " + current + ".apply(depth0) : " + current;
    });
  },

  // [lookup]
  //
  // On stack, before: value, ...
  // On stack, after: value[name], ...
  //
  // Replace the value on the stack with the result of looking
  // up `name` on `value`
  lookup: function(name) {
    this.replaceStack(function(current) {
      return current + " == null || " + current + " === false ? " + current + " : " + this.nameLookup(current, name, 'context');
    });
  },

  // [lookupData]
  //
  // On stack, before: ...
  // On stack, after: data[id], ...
  //
  // Push the result of looking up `id` on the current data
  lookupData: function(id) {
    this.push('data');
  },

  // [pushStringParam]
  //
  // On stack, before: ...
  // On stack, after: string, currentContext, ...
  //
  // This opcode is designed for use in string mode, which
  // provides the string value of a parameter along with its
  // depth rather than resolving it immediately.
  pushStringParam: function(string, type) {
    this.pushStackLiteral('depth' + this.lastContext);

    this.pushString(type);

    if (typeof string === 'string') {
      this.pushString(string);
    } else {
      this.pushStackLiteral(string);
    }
  },

  emptyHash: function() {
    this.pushStackLiteral('{}');

    if (this.options.stringParams) {
      this.register('hashTypes', '{}');
      this.register('hashContexts', '{}');
    }
  },
  pushHash: function() {
    this.hash = {values: [], types: [], contexts: []};
  },
  popHash: function() {
    var hash = this.hash;
    this.hash = undefined;

    if (this.options.stringParams) {
      this.register('hashContexts', '{' + hash.contexts.join(',') + '}');
      this.register('hashTypes', '{' + hash.types.join(',') + '}');
    }
    this.push('{\n    ' + hash.values.join(',\n    ') + '\n  }');
  },

  // [pushString]
  //
  // On stack, before: ...
  // On stack, after: quotedString(string), ...
  //
  // Push a quoted version of `string` onto the stack
  pushString: function(string) {
    this.pushStackLiteral(this.quotedString(string));
  },

  // [push]
  //
  // On stack, before: ...
  // On stack, after: expr, ...
  //
  // Push an expression onto the stack
  push: function(expr) {
    this.inlineStack.push(expr);
    return expr;
  },

  // [pushLiteral]
  //
  // On stack, before: ...
  // On stack, after: value, ...
  //
  // Pushes a value onto the stack. This operation prevents
  // the compiler from creating a temporary variable to hold
  // it.
  pushLiteral: function(value) {
    this.pushStackLiteral(value);
  },

  // [pushProgram]
  //
  // On stack, before: ...
  // On stack, after: program(guid), ...
  //
  // Push a program expression onto the stack. This takes
  // a compile-time guid and converts it into a runtime-accessible
  // expression.
  pushProgram: function(guid) {
    if (guid != null) {
      this.pushStackLiteral(this.programExpression(guid));
    } else {
      this.pushStackLiteral(null);
    }
  },

  // [invokeHelper]
  //
  // On stack, before: hash, inverse, program, params..., ...
  // On stack, after: result of helper invocation
  //
  // Pops off the helper's parameters, invokes the helper,
  // and pushes the helper's return value onto the stack.
  //
  // If the helper is not found, `helperMissing` is called.
  invokeHelper: function(paramSize, name) {
    this.context.aliases.helperMissing = 'helpers.helperMissing';

    var helper = this.lastHelper = this.setupHelper(paramSize, name, true);
    var nonHelper = this.nameLookup('depth' + this.lastContext, name, 'context');

    this.push(helper.name + ' || ' + nonHelper);
    this.replaceStack(function(name) {
      return name + ' ? ' + name + '.call(' +
          helper.callParams + ") " + ": helperMissing.call(" +
          helper.helperMissingParams + ")";
    });
  },

  // [invokeKnownHelper]
  //
  // On stack, before: hash, inverse, program, params..., ...
  // On stack, after: result of helper invocation
  //
  // This operation is used when the helper is known to exist,
  // so a `helperMissing` fallback is not required.
  invokeKnownHelper: function(paramSize, name) {
    var helper = this.setupHelper(paramSize, name);
    this.push(helper.name + ".call(" + helper.callParams + ")");
  },

  // [invokeAmbiguous]
  //
  // On stack, before: hash, inverse, program, params..., ...
  // On stack, after: result of disambiguation
  //
  // This operation is used when an expression like `{{foo}}`
  // is provided, but we don't know at compile-time whether it
  // is a helper or a path.
  //
  // This operation emits more code than the other options,
  // and can be avoided by passing the `knownHelpers` and
  // `knownHelpersOnly` flags at compile-time.
  invokeAmbiguous: function(name, helperCall) {
    this.context.aliases.functionType = '"function"';

    this.pushStackLiteral('{}');    // Hash value
    var helper = this.setupHelper(0, name, helperCall);

    var helperName = this.lastHelper = this.nameLookup('helpers', name, 'helper');

    var nonHelper = this.nameLookup('depth' + this.lastContext, name, 'context');
    var nextStack = this.nextStack();

    this.source.push('if (' + nextStack + ' = ' + helperName + ') { ' + nextStack + ' = ' + nextStack + '.call(' + helper.callParams + '); }');
    this.source.push('else { ' + nextStack + ' = ' + nonHelper + '; ' + nextStack + ' = typeof ' + nextStack + ' === functionType ? ' + nextStack + '.apply(depth0) : ' + nextStack + '; }');
  },

  // [invokePartial]
  //
  // On stack, before: context, ...
  // On stack after: result of partial invocation
  //
  // This operation pops off a context, invokes a partial with that context,
  // and pushes the result of the invocation back.
  invokePartial: function(name) {
    var params = [this.nameLookup('partials', name, 'partial'), "'" + name + "'", this.popStack(), "helpers", "partials"];

    if (this.options.data) {
      params.push("data");
    }

    this.context.aliases.self = "this";
    this.push("self.invokePartial(" + params.join(", ") + ")");
  },

  // [assignToHash]
  //
  // On stack, before: value, hash, ...
  // On stack, after: hash, ...
  //
  // Pops a value and hash off the stack, assigns `hash[key] = value`
  // and pushes the hash back onto the stack.
  assignToHash: function(key) {
    var value = this.popStack(),
        context,
        type;

    if (this.options.stringParams) {
      type = this.popStack();
      context = this.popStack();
    }

    var hash = this.hash;
    if (context) {
      hash.contexts.push("'" + key + "': " + context);
    }
    if (type) {
      hash.types.push("'" + key + "': " + type);
    }
    hash.values.push("'" + key + "': (" + value + ")");
  },

  // HELPERS

  compiler: JavaScriptCompiler,

  compileChildren: function(environment, options) {
    var children = environment.children, child, compiler;

    for(var i=0, l=children.length; i<l; i++) {
      child = children[i];
      compiler = new this.compiler();

      var index = this.matchExistingProgram(child);

      if (index == null) {
        this.context.programs.push('');     // Placeholder to prevent name conflicts for nested children
        index = this.context.programs.length;
        child.index = index;
        child.name = 'program' + index;
        this.context.programs[index] = compiler.compile(child, options, this.context);
        this.context.environments[index] = child;
      } else {
        child.index = index;
        child.name = 'program' + index;
      }
    }
  },
  matchExistingProgram: function(child) {
    for (var i = 0, len = this.context.environments.length; i < len; i++) {
      var environment = this.context.environments[i];
      if (environment && environment.equals(child)) {
        return i;
      }
    }
  },

  programExpression: function(guid) {
    this.context.aliases.self = "this";

    if(guid == null) {
      return "self.noop";
    }

    var child = this.environment.children[guid],
        depths = child.depths.list, depth;

    var programParams = [child.index, child.name, "data"];

    for(var i=0, l = depths.length; i<l; i++) {
      depth = depths[i];

      if(depth === 1) { programParams.push("depth0"); }
      else { programParams.push("depth" + (depth - 1)); }
    }

    return (depths.length === 0 ? "self.program(" : "self.programWithDepth(") + programParams.join(", ") + ")";
  },

  register: function(name, val) {
    this.useRegister(name);
    this.source.push(name + " = " + val + ";");
  },

  useRegister: function(name) {
    if(!this.registers[name]) {
      this.registers[name] = true;
      this.registers.list.push(name);
    }
  },

  pushStackLiteral: function(item) {
    return this.push(new Literal(item));
  },

  pushStack: function(item) {
    this.flushInline();

    var stack = this.incrStack();
    if (item) {
      this.source.push(stack + " = " + item + ";");
    }
    this.compileStack.push(stack);
    return stack;
  },

  replaceStack: function(callback) {
    var prefix = '',
        inline = this.isInline(),
        stack;

    // If we are currently inline then we want to merge the inline statement into the
    // replacement statement via ','
    if (inline) {
      var top = this.popStack(true);

      if (top instanceof Literal) {
        // Literals do not need to be inlined
        stack = top.value;
      } else {
        // Get or create the current stack name for use by the inline
        var name = this.stackSlot ? this.topStackName() : this.incrStack();

        prefix = '(' + this.push(name) + ' = ' + top + '),';
        stack = this.topStack();
      }
    } else {
      stack = this.topStack();
    }

    var item = callback.call(this, stack);

    if (inline) {
      if (this.inlineStack.length || this.compileStack.length) {
        this.popStack();
      }
      this.push('(' + prefix + item + ')');
    } else {
      // Prevent modification of the context depth variable. Through replaceStack
      if (!/^stack/.test(stack)) {
        stack = this.nextStack();
      }

      this.source.push(stack + " = (" + prefix + item + ");");
    }
    return stack;
  },

  nextStack: function() {
    return this.pushStack();
  },

  incrStack: function() {
    this.stackSlot++;
    if(this.stackSlot > this.stackVars.length) { this.stackVars.push("stack" + this.stackSlot); }
    return this.topStackName();
  },
  topStackName: function() {
    return "stack" + this.stackSlot;
  },
  flushInline: function() {
    var inlineStack = this.inlineStack;
    if (inlineStack.length) {
      this.inlineStack = [];
      for (var i = 0, len = inlineStack.length; i < len; i++) {
        var entry = inlineStack[i];
        if (entry instanceof Literal) {
          this.compileStack.push(entry);
        } else {
          this.pushStack(entry);
        }
      }
    }
  },
  isInline: function() {
    return this.inlineStack.length;
  },

  popStack: function(wrapped) {
    var inline = this.isInline(),
        item = (inline ? this.inlineStack : this.compileStack).pop();

    if (!wrapped && (item instanceof Literal)) {
      return item.value;
    } else {
      if (!inline) {
        this.stackSlot--;
      }
      return item;
    }
  },

  topStack: function(wrapped) {
    var stack = (this.isInline() ? this.inlineStack : this.compileStack),
        item = stack[stack.length - 1];

    if (!wrapped && (item instanceof Literal)) {
      return item.value;
    } else {
      return item;
    }
  },

  quotedString: function(str) {
    return '"' + str
      .replace(/\\/g, '\\\\')
      .replace(/"/g, '\\"')
      .replace(/\n/g, '\\n')
      .replace(/\r/g, '\\r')
      .replace(/\u2028/g, '\\u2028')   // Per Ecma-262 7.3 + 7.8.4
      .replace(/\u2029/g, '\\u2029') + '"';
  },

  setupHelper: function(paramSize, name, missingParams) {
    var params = [];
    this.setupParams(paramSize, params, missingParams);
    var foundHelper = this.nameLookup('helpers', name, 'helper');

    return {
      params: params,
      name: foundHelper,
      callParams: ["depth0"].concat(params).join(", "),
      helperMissingParams: missingParams && ["depth0", this.quotedString(name)].concat(params).join(", ")
    };
  },

  // the params and contexts arguments are passed in arrays
  // to fill in
  setupParams: function(paramSize, params, useRegister) {
    var options = [], contexts = [], types = [], param, inverse, program;

    options.push("hash:" + this.popStack());

    inverse = this.popStack();
    program = this.popStack();

    // Avoid setting fn and inverse if neither are set. This allows
    // helpers to do a check for `if (options.fn)`
    if (program || inverse) {
      if (!program) {
        this.context.aliases.self = "this";
        program = "self.noop";
      }

      if (!inverse) {
       this.context.aliases.self = "this";
        inverse = "self.noop";
      }

      options.push("inverse:" + inverse);
      options.push("fn:" + program);
    }

    for(var i=0; i<paramSize; i++) {
      param = this.popStack();
      params.push(param);

      if(this.options.stringParams) {
        types.push(this.popStack());
        contexts.push(this.popStack());
      }
    }

    if (this.options.stringParams) {
      options.push("contexts:[" + contexts.join(",") + "]");
      options.push("types:[" + types.join(",") + "]");
      options.push("hashContexts:hashContexts");
      options.push("hashTypes:hashTypes");
    }

    if(this.options.data) {
      options.push("data:data");
    }

    options = "{" + options.join(",") + "}";
    if (useRegister) {
      this.register('options', options);
      params.push('options');
    } else {
      params.push(options);
    }
    return params.join(", ");
  }
};

var reservedWords = (
  "break else new var" +
  " case finally return void" +
  " catch for switch while" +
  " continue function this with" +
  " default if throw" +
  " delete in try" +
  " do instanceof typeof" +
  " abstract enum int short" +
  " boolean export interface static" +
  " byte extends long super" +
  " char final native synchronized" +
  " class float package throws" +
  " const goto private transient" +
  " debugger implements protected volatile" +
  " double import public let yield"
).split(" ");

var compilerWords = JavaScriptCompiler.RESERVED_WORDS = {};

for(var i=0, l=reservedWords.length; i<l; i++) {
  compilerWords[reservedWords[i]] = true;
}

JavaScriptCompiler.isValidJavaScriptVariableName = function(name) {
  if(!JavaScriptCompiler.RESERVED_WORDS[name] && /^[a-zA-Z_$][0-9a-zA-Z_$]+$/.test(name)) {
    return true;
  }
  return false;
};

Handlebars.precompile = function(input, options) {
  if (input == null || (typeof input !== 'string' && input.constructor !== Handlebars.AST.ProgramNode)) {
    throw new Handlebars.Exception("You must pass a string or Handlebars AST to Handlebars.precompile. You passed " + input);
  }

  options = options || {};
  if (!('data' in options)) {
    options.data = true;
  }
  var ast = Handlebars.parse(input);
  var environment = new Compiler().compile(ast, options);
  return new JavaScriptCompiler().compile(environment, options);
};

Handlebars.compile = function(input, options) {
  if (input == null || (typeof input !== 'string' && input.constructor !== Handlebars.AST.ProgramNode)) {
    throw new Handlebars.Exception("You must pass a string or Handlebars AST to Handlebars.compile. You passed " + input);
  }

  options = options || {};
  if (!('data' in options)) {
    options.data = true;
  }
  var compiled;
  function compile() {
    var ast = Handlebars.parse(input);
    var environment = new Compiler().compile(ast, options);
    var templateSpec = new JavaScriptCompiler().compile(environment, options, undefined, true);
    return Handlebars.template(templateSpec);
  }

  // Template is only compiled on first use and cached after that point.
  return function(context, options) {
    if (!compiled) {
      compiled = compile();
    }
    return compiled.call(this, context, options);
  };
};

;
// lib/handlebars/runtime.js

Handlebars.VM = {
  template: function(templateSpec) {
    // Just add water
    var container = {
      escapeExpression: Handlebars.Utils.escapeExpression,
      invokePartial: Handlebars.VM.invokePartial,
      programs: [],
      program: function(i, fn, data) {
        var programWrapper = this.programs[i];
        if(data) {
          programWrapper = Handlebars.VM.program(i, fn, data);
        } else if (!programWrapper) {
          programWrapper = this.programs[i] = Handlebars.VM.program(i, fn);
        }
        return programWrapper;
      },
      merge: function(param, common) {
        var ret = param || common;

        if (param && common) {
          ret = {};
          Handlebars.Utils.extend(ret, common);
          Handlebars.Utils.extend(ret, param);
        }
        return ret;
      },
      programWithDepth: Handlebars.VM.programWithDepth,
      noop: Handlebars.VM.noop,
      compilerInfo: null
    };

    return function(context, options) {
      options = options || {};
      var result = templateSpec.call(container, Handlebars, context, options.helpers, options.partials, options.data);

      var compilerInfo = container.compilerInfo || [],
          compilerRevision = compilerInfo[0] || 1,
          currentRevision = Handlebars.COMPILER_REVISION;

      if (compilerRevision !== currentRevision) {
        if (compilerRevision < currentRevision) {
          var runtimeVersions = Handlebars.REVISION_CHANGES[currentRevision],
              compilerVersions = Handlebars.REVISION_CHANGES[compilerRevision];
          throw "Template was precompiled with an older version of Handlebars than the current runtime. "+
                "Please update your precompiler to a newer version ("+runtimeVersions+") or downgrade your runtime to an older version ("+compilerVersions+").";
        } else {
          // Use the embedded version info since the runtime doesn't know about this revision yet
          throw "Template was precompiled with a newer version of Handlebars than the current runtime. "+
                "Please update your runtime to a newer version ("+compilerInfo[1]+").";
        }
      }

      return result;
    };
  },

  programWithDepth: function(i, fn, data /*, $depth */) {
    var args = Array.prototype.slice.call(arguments, 3);

    var program = function(context, options) {
      options = options || {};

      return fn.apply(this, [context, options.data || data].concat(args));
    };
    program.program = i;
    program.depth = args.length;
    return program;
  },
  program: function(i, fn, data) {
    var program = function(context, options) {
      options = options || {};

      return fn(context, options.data || data);
    };
    program.program = i;
    program.depth = 0;
    return program;
  },
  noop: function() { return ""; },
  invokePartial: function(partial, name, context, helpers, partials, data) {
    var options = { helpers: helpers, partials: partials, data: data };

    if(partial === undefined) {
      throw new Handlebars.Exception("The partial " + name + " could not be found");
    } else if(partial instanceof Function) {
      return partial(context, options);
    } else if (!Handlebars.compile) {
      throw new Handlebars.Exception("The partial " + name + " could not be compiled when running in runtime-only mode");
    } else {
      partials[name] = Handlebars.compile(partial, {data: data !== undefined});
      return partials[name](context, options);
    }
  }
};

Handlebars.template = Handlebars.VM.template;
;
// lib/handlebars/browser-suffix.js
})(Handlebars);
;



/* Handlebars Helpers - Dan Harper (http://github.com/danharper) */

/* This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

/**
 *  Following lines make Handlebars helper function to work with all
 *  three such as Direct web, RequireJS AMD and Node JS.
 *  This concepts derived from UMD.
 *  @courtesy - https://github.com/umdjs/umd/blob/master/returnExports.js
 */

(function (root, factory) {
    if (typeof exports === 'object') {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like enviroments that support module.exports,
        // like Node.
        module.exports = factory(require('handlebars'));
    } else if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['handlebars'], factory);
    } else {
        // Browser globals (root is window)
        root.returnExports = factory(root.Handlebars);
    }
}(this, function (Handlebars) {

    /**
     * If Equals
     * if_eq this compare=that
     */
    Handlebars.registerHelper('if_eq', function(context, options) {
        if (context == options.hash.compare)
            return options.fn(this);
        return options.inverse(this);
    });

    /**
     * Unless Equals
     * unless_eq this compare=that
     */
    Handlebars.registerHelper('unless_eq', function(context, options) {
        if (context == options.hash.compare)
            return options.inverse(this);
        return options.fn(this);
    });


    /**
     * If Greater Than
     * if_gt this compare=that
     */
    Handlebars.registerHelper('if_gt', function(context, options) {
        if (context > options.hash.compare)
            return options.fn(this);
        return options.inverse(this);
    });

    /**
     * Unless Greater Than
     * unless_gt this compare=that
     */
    Handlebars.registerHelper('unless_gt', function(context, options) {
        if (context > options.hash.compare)
            return options.inverse(this);
        return options.fn(this);
    });


    /**
     * If Less Than
     * if_lt this compare=that
     */
    Handlebars.registerHelper('if_lt', function(context, options) {
        if (context < options.hash.compare)
            return options.fn(this);
        return options.inverse(this);
    });

    /**
     * Unless Less Than
     * unless_lt this compare=that
     */
    Handlebars.registerHelper('unless_lt', function(context, options) {
        if (context < options.hash.compare)
            return options.inverse(this);
        return options.fn(this);
    });


    /**
     * If Greater Than or Equal To
     * if_gteq this compare=that
     */
    Handlebars.registerHelper('if_gteq', function(context, options) {
        if (context >= options.hash.compare)
            return options.fn(this);
        return options.inverse(this);
    });

    /**
     * Unless Greater Than or Equal To
     * unless_gteq this compare=that
     */
    Handlebars.registerHelper('unless_gteq', function(context, options) {
        if (context >= options.hash.compare)
            return options.inverse(this);
        return options.fn(this);
    });


    /**
     * If Less Than or Equal To
     * if_lteq this compare=that
     */
    Handlebars.registerHelper('if_lteq', function(context, options) {
        if (context <= options.hash.compare)
            return options.fn(this);
        return options.inverse(this);
    });

    /**
     * Unless Less Than or Equal To
     * unless_lteq this compare=that
     */
    Handlebars.registerHelper('unless_lteq', function(context, options) {
        if (context <= options.hash.compare)
            return options.inverse(this);
        return options.fn(this);
    });

    /**
     * Convert new line (\n\r) to <br>
     * from http://phpjs.org/functions/nl2br:480
     */
    Handlebars.registerHelper('nl2br', function(text) {
        text = Handlebars.Utils.escapeExpression(text);
        var nl2br = (text + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + '<br>' + '$2');
        return new Handlebars.SafeString(nl2br);
    });
}));



/**
* change the delimiter tags of Handlebars
* @author Francesco Delacqua
* @param string start a single character for the starting delimiter tag
* @param string end a single character for the ending delimiter tag
*/
Handlebars.setDelimiter = function(start,end){
    //save a reference to the original compile function
    if(!Handlebars.original_compile) Handlebars.original_compile = Handlebars.compile;

    Handlebars.compile = function(source){
        var s = "\\"+start,
            e = "\\"+end,
            RE = new RegExp('('+s+'{2,3})(.*?)('+e+'{2,3})','ig');

            replacedSource = source.replace(RE,function(match, startTags, text, endTags, offset, string){
                var startRE = new RegExp(s,'ig'), endRE = new RegExp(e,'ig');

                startTags = startTags.replace(startRE,'\{');
                endTags = endTags.replace(endRE,'\}');

                return startTags+text+endTags;
            });

        return Handlebars.original_compile(replacedSource);
    };
};






/*!
 * jQuery Cookie Plugin v1.4.0
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2013 Klaus Hartl
 * Released under the MIT license
 */
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as anonymous module.
        define(['jquery'], factory);
    } else {
        // Browser globals.
        factory(jQuery);
    }
}(function ($) {

    var pluses = /\+/g;

    function encode(s) {
        return config.raw ? s : encodeURIComponent(s);
    }

    function decode(s) {
        return config.raw ? s : decodeURIComponent(s);
    }

    function stringifyCookieValue(value) {
        return encode(config.json ? JSON.stringify(value) : String(value));
    }

    function parseCookieValue(s) {
        if (s.indexOf('"') === 0) {
            // This is a quoted cookie as according to RFC2068, unescape...
            s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
        }

        try {
            // Replace server-side written pluses with spaces.
            // If we can't decode the cookie, ignore it, it's unusable.
            // If we can't parse the cookie, ignore it, it's unusable.
            s = decodeURIComponent(s.replace(pluses, ' '));
            return config.json ? JSON.parse(s) : s;
        } catch(e) {}
    }

    function read(s, converter) {
        var value = config.raw ? s : parseCookieValue(s);
        return $.isFunction(converter) ? converter(value) : value;
    }

    var config = $.cookie = function (key, value, options) {

        // Write
        if (value !== undefined && !$.isFunction(value)) {
            options = $.extend({}, config.defaults, options);

            if (typeof options.expires === 'number') {
                var days = options.expires, t = options.expires = new Date();
                t.setDate(t.getDate() + days);
            }

            return (document.cookie = [
                encode(key), '=', stringifyCookieValue(value),
                options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
                options.path    ? '; path=' + options.path : '',
                options.domain  ? '; domain=' + options.domain : '',
                options.secure  ? '; secure' : ''
            ].join(''));
        }

        // Read

        var result = key ? undefined : {};

        // To prevent the for loop in the first place assign an empty array
        // in case there are no cookies at all. Also prevents odd result when
        // calling $.cookie().
        var cookies = document.cookie ? document.cookie.split('; ') : [];

        for (var i = 0, l = cookies.length; i < l; i++) {
            var parts = cookies[i].split('=');
            var name = decode(parts.shift());
            var cookie = parts.join('=');

            if (key && key === name) {
                // If second argument (value) is a function it's a converter...
                result = read(cookie, value);
                break;
            }

            // Prevent storing a cookie that we couldn't decode.
            if (!key && (cookie = read(cookie)) !== undefined) {
                result[name] = cookie;
            }
        }

        return result;
    };

    config.defaults = {};

    $.removeCookie = function (key, options) {
        if ($.cookie(key) === undefined) {
            return false;
        }

        // Must not alter options, thus extending a fresh object...
        $.cookie(key, '', $.extend({}, options, { expires: -1 }));
        return !$.cookie(key);
    };

}));



/*
 * jQuery Easing v1.3 - http://gsgd.co.uk/sandbox/jquery/easing/
 *
 * Uses the built in easing capabilities added In jQuery 1.1
 * to offer multiple easing options
 *
 * TERMS OF USE - jQuery Easing
 *
 * Open source under the BSD License.
 *
 * Copyright © 2008 George McGinley Smith
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * Redistributions of source code must retain the above copyright notice, this list of
 * conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list
 * of conditions and the following disclaimer in the documentation and/or other materials
 * provided with the distribution.
 *
 * Neither the name of the author nor the names of contributors may be used to endorse
 * or promote products derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 *  COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 *  EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE
 *  GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 *  NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
 * OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

// t: current time, b: begInnIng value, c: change In value, d: duration
jQuery.easing['jswing'] = jQuery.easing['swing'];

jQuery.extend( jQuery.easing,
	{
		def: 'easeOutQuad',
		swing: function (x, t, b, c, d) {
			//alert(jQuery.easing.default);
			return jQuery.easing[jQuery.easing.def](x, t, b, c, d);
		},
		easeInQuad: function (x, t, b, c, d) {
			return c*(t/=d)*t + b;
		},
		easeOutQuad: function (x, t, b, c, d) {
			return -c *(t/=d)*(t-2) + b;
		},
		easeInOutQuad: function (x, t, b, c, d) {
			if ((t/=d/2) < 1) return c/2*t*t + b;
			return -c/2 * ((--t)*(t-2) - 1) + b;
		},
		easeInCubic: function (x, t, b, c, d) {
			return c*(t/=d)*t*t + b;
		},
		easeOutCubic: function (x, t, b, c, d) {
			return c*((t=t/d-1)*t*t + 1) + b;
		},
		easeInOutCubic: function (x, t, b, c, d) {
			if ((t/=d/2) < 1) return c/2*t*t*t + b;
			return c/2*((t-=2)*t*t + 2) + b;
		},
		easeInQuart: function (x, t, b, c, d) {
			return c*(t/=d)*t*t*t + b;
		},
		easeOutQuart: function (x, t, b, c, d) {
			return -c * ((t=t/d-1)*t*t*t - 1) + b;
		},
		easeInOutQuart: function (x, t, b, c, d) {
			if ((t/=d/2) < 1) return c/2*t*t*t*t + b;
			return -c/2 * ((t-=2)*t*t*t - 2) + b;
		},
		easeInQuint: function (x, t, b, c, d) {
			return c*(t/=d)*t*t*t*t + b;
		},
		easeOutQuint: function (x, t, b, c, d) {
			return c*((t=t/d-1)*t*t*t*t + 1) + b;
		},
		easeInOutQuint: function (x, t, b, c, d) {
			if ((t/=d/2) < 1) return c/2*t*t*t*t*t + b;
			return c/2*((t-=2)*t*t*t*t + 2) + b;
		},
		easeInSine: function (x, t, b, c, d) {
			return -c * Math.cos(t/d * (Math.PI/2)) + c + b;
		},
		easeOutSine: function (x, t, b, c, d) {
			return c * Math.sin(t/d * (Math.PI/2)) + b;
		},
		easeInOutSine: function (x, t, b, c, d) {
			return -c/2 * (Math.cos(Math.PI*t/d) - 1) + b;
		},
		easeInExpo: function (x, t, b, c, d) {
			return (t==0) ? b : c * Math.pow(2, 10 * (t/d - 1)) + b;
		},
		easeOutExpo: function (x, t, b, c, d) {
			return (t==d) ? b+c : c * (-Math.pow(2, -10 * t/d) + 1) + b;
		},
		easeInOutExpo: function (x, t, b, c, d) {
			if (t==0) return b;
			if (t==d) return b+c;
			if ((t/=d/2) < 1) return c/2 * Math.pow(2, 10 * (t - 1)) + b;
			return c/2 * (-Math.pow(2, -10 * --t) + 2) + b;
		},
		easeInCirc: function (x, t, b, c, d) {
			return -c * (Math.sqrt(1 - (t/=d)*t) - 1) + b;
		},
		easeOutCirc: function (x, t, b, c, d) {
			return c * Math.sqrt(1 - (t=t/d-1)*t) + b;
		},
		easeInOutCirc: function (x, t, b, c, d) {
			if ((t/=d/2) < 1) return -c/2 * (Math.sqrt(1 - t*t) - 1) + b;
			return c/2 * (Math.sqrt(1 - (t-=2)*t) + 1) + b;
		},
		easeInElastic: function (x, t, b, c, d) {
			var s=1.70158;var p=0;var a=c;
			if (t==0) return b;  if ((t/=d)==1) return b+c;  if (!p) p=d*.3;
			if (a < Math.abs(c)) { a=c; var s=p/4; }
			else var s = p/(2*Math.PI) * Math.asin (c/a);
			return -(a*Math.pow(2,10*(t-=1)) * Math.sin( (t*d-s)*(2*Math.PI)/p )) + b;
		},
		easeOutElastic: function (x, t, b, c, d) {
			var s=1.70158;var p=0;var a=c;
			if (t==0) return b;  if ((t/=d)==1) return b+c;  if (!p) p=d*.3;
			if (a < Math.abs(c)) { a=c; var s=p/4; }
			else var s = p/(2*Math.PI) * Math.asin (c/a);
			return a*Math.pow(2,-10*t) * Math.sin( (t*d-s)*(2*Math.PI)/p ) + c + b;
		},
		easeInOutElastic: function (x, t, b, c, d) {
			var s=1.70158;var p=0;var a=c;
			if (t==0) return b;  if ((t/=d/2)==2) return b+c;  if (!p) p=d*(.3*1.5);
			if (a < Math.abs(c)) { a=c; var s=p/4; }
			else var s = p/(2*Math.PI) * Math.asin (c/a);
			if (t < 1) return -.5*(a*Math.pow(2,10*(t-=1)) * Math.sin( (t*d-s)*(2*Math.PI)/p )) + b;
			return a*Math.pow(2,-10*(t-=1)) * Math.sin( (t*d-s)*(2*Math.PI)/p )*.5 + c + b;
		},
		easeInBack: function (x, t, b, c, d, s) {
			if (s == undefined) s = 1.70158;
			return c*(t/=d)*t*((s+1)*t - s) + b;
		},
		easeOutBack: function (x, t, b, c, d, s) {
			if (s == undefined) s = 1.70158;
			return c*((t=t/d-1)*t*((s+1)*t + s) + 1) + b;
		},
		easeInOutBack: function (x, t, b, c, d, s) {
			if (s == undefined) s = 1.70158;
			if ((t/=d/2) < 1) return c/2*(t*t*(((s*=(1.525))+1)*t - s)) + b;
			return c/2*((t-=2)*t*(((s*=(1.525))+1)*t + s) + 2) + b;
		},
		easeInBounce: function (x, t, b, c, d) {
			return c - jQuery.easing.easeOutBounce (x, d-t, 0, c, d) + b;
		},
		easeOutBounce: function (x, t, b, c, d) {
			if ((t/=d) < (1/2.75)) {
				return c*(7.5625*t*t) + b;
			} else if (t < (2/2.75)) {
				return c*(7.5625*(t-=(1.5/2.75))*t + .75) + b;
			} else if (t < (2.5/2.75)) {
				return c*(7.5625*(t-=(2.25/2.75))*t + .9375) + b;
			} else {
				return c*(7.5625*(t-=(2.625/2.75))*t + .984375) + b;
			}
		},
		easeInOutBounce: function (x, t, b, c, d) {
			if (t < d/2) return jQuery.easing.easeInBounce (x, t*2, 0, c, d) * .5 + b;
			return jQuery.easing.easeOutBounce (x, t*2-d, 0, c, d) * .5 + c*.5 + b;
		}
	});

/*
 *
 * TERMS OF USE - EASING EQUATIONS
 *
 * Open source under the BSD License.
 *
 * Copyright © 2001 Robert Penner
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * Redistributions of source code must retain the above copyright notice, this list of
 * conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list
 * of conditions and the following disclaimer in the documentation and/or other materials
 * provided with the distribution.
 *
 * Neither the name of the author nor the names of contributors may be used to endorse
 * or promote products derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 *  COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 *  EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE
 *  GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 *  NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
 * OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

/*!
 * jQuery Cycle2; version: 2.1.5 build: 20140415
 * http://jquery.malsup.com/cycle2/
 * Copyright (c) 2014 M. Alsup; Dual licensed: MIT/GPL
 */

/* Cycle2 core engine */
;(function($) {
	"use strict";

	var version = '2.1.5';

	$.fn.cycle = function( options ) {
		// fix mistakes with the ready state
		var o;
		if ( this.length === 0 && !$.isReady ) {
			o = { s: this.selector, c: this.context };
			$.fn.cycle.log('requeuing slideshow (dom not ready)');
			$(function() {
				$( o.s, o.c ).cycle(options);
			});
			return this;
		}

		return this.each(function() {
			var data, opts, shortName, val;
			var container = $(this);
			var log = $.fn.cycle.log;

			if ( container.data('cycle.opts') )
				return; // already initialized

			if ( container.data('cycle-log') === false ||
				( options && options.log === false ) ||
				( opts && opts.log === false) ) {
				log = $.noop;
			}

			log('--c2 init--');
			data = container.data();
			for (var p in data) {
				// allow props to be accessed sans 'cycle' prefix and log the overrides
				if (data.hasOwnProperty(p) && /^cycle[A-Z]+/.test(p) ) {
					val = data[p];
					shortName = p.match(/^cycle(.*)/)[1].replace(/^[A-Z]/, lowerCase);
					log(shortName+':', val, '('+typeof val +')');
					data[shortName] = val;
				}
			}

			opts = $.extend( {}, $.fn.cycle.defaults, data, options || {});

			opts.timeoutId = 0;
			opts.paused = opts.paused || false; // #57
			opts.container = container;
			opts._maxZ = opts.maxZ;

			opts.API = $.extend ( { _container: container }, $.fn.cycle.API );
			opts.API.log = log;
			opts.API.trigger = function( eventName, args ) {
				opts.container.trigger( eventName, args );
				return opts.API;
			};

			container.data( 'cycle.opts', opts );
			container.data( 'cycle.API', opts.API );

			// opportunity for plugins to modify opts and API
			opts.API.trigger('cycle-bootstrap', [ opts, opts.API ]);

			opts.API.addInitialSlides();
			opts.API.preInitSlideshow();

			if ( opts.slides.length )
				opts.API.initSlideshow();
		});
	};

	$.fn.cycle.API = {
		opts: function() {
			return this._container.data( 'cycle.opts' );
		},
		addInitialSlides: function() {
			var opts = this.opts();
			var slides = opts.slides;
			opts.slideCount = 0;
			opts.slides = $(); // empty set

			// add slides that already exist
			slides = slides.jquery ? slides : opts.container.find( slides );

			if ( opts.random ) {
				slides.sort(function() {return Math.random() - 0.5;});
			}

			opts.API.add( slides );
		},

		preInitSlideshow: function() {
			var opts = this.opts();
			opts.API.trigger('cycle-pre-initialize', [ opts ]);
			var tx = $.fn.cycle.transitions[opts.fx];
			if (tx && $.isFunction(tx.preInit))
				tx.preInit( opts );
			opts._preInitialized = true;
		},

		postInitSlideshow: function() {
			var opts = this.opts();
			opts.API.trigger('cycle-post-initialize', [ opts ]);
			var tx = $.fn.cycle.transitions[opts.fx];
			if (tx && $.isFunction(tx.postInit))
				tx.postInit( opts );
		},

		initSlideshow: function() {
			var opts = this.opts();
			var pauseObj = opts.container;
			var slideOpts;
			opts.API.calcFirstSlide();

			if ( opts.container.css('position') == 'static' )
				opts.container.css('position', 'relative');

			$(opts.slides[opts.currSlide]).css({
				opacity: 1,
				display: 'block',
				visibility: 'visible'
			});
			opts.API.stackSlides( opts.slides[opts.currSlide], opts.slides[opts.nextSlide], !opts.reverse );

			if ( opts.pauseOnHover ) {
				// allow pauseOnHover to specify an element
				if ( opts.pauseOnHover !== true )
					pauseObj = $( opts.pauseOnHover );

				pauseObj.hover(
					function(){ opts.API.pause( true ); },
					function(){ opts.API.resume( true ); }
				);
			}

			// stage initial transition
			if ( opts.timeout ) {
				slideOpts = opts.API.getSlideOpts( opts.currSlide );
				opts.API.queueTransition( slideOpts, slideOpts.timeout + opts.delay );
			}

			opts._initialized = true;
			opts.API.updateView( true );
			opts.API.trigger('cycle-initialized', [ opts ]);
			opts.API.postInitSlideshow();
		},

		pause: function( hover ) {
			var opts = this.opts(),
				slideOpts = opts.API.getSlideOpts(),
				alreadyPaused = opts.hoverPaused || opts.paused;

			if ( hover )
				opts.hoverPaused = true;
			else
				opts.paused = true;

			if ( ! alreadyPaused ) {
				opts.container.addClass('cycle-paused');
				opts.API.trigger('cycle-paused', [ opts ]).log('cycle-paused');

				if ( slideOpts.timeout ) {
					clearTimeout( opts.timeoutId );
					opts.timeoutId = 0;

					// determine how much time is left for the current slide
					opts._remainingTimeout -= ( $.now() - opts._lastQueue );
					if ( opts._remainingTimeout < 0 || isNaN(opts._remainingTimeout) )
						opts._remainingTimeout = undefined;
				}
			}
		},

		resume: function( hover ) {
			var opts = this.opts(),
				alreadyResumed = !opts.hoverPaused && !opts.paused,
				remaining;

			if ( hover )
				opts.hoverPaused = false;
			else
				opts.paused = false;


			if ( ! alreadyResumed ) {
				opts.container.removeClass('cycle-paused');
				// #gh-230; if an animation is in progress then don't queue a new transition; it will
				// happen naturally
				if ( opts.slides.filter(':animated').length === 0 )
					opts.API.queueTransition( opts.API.getSlideOpts(), opts._remainingTimeout );
				opts.API.trigger('cycle-resumed', [ opts, opts._remainingTimeout ] ).log('cycle-resumed');
			}
		},

		add: function( slides, prepend ) {
			var opts = this.opts();
			var oldSlideCount = opts.slideCount;
			var startSlideshow = false;
			var len;

			if ( $.type(slides) == 'string')
				slides = $.trim( slides );

			$( slides ).each(function(i) {
				var slideOpts;
				var slide = $(this);

				if ( prepend )
					opts.container.prepend( slide );
				else
					opts.container.append( slide );

				opts.slideCount++;
				slideOpts = opts.API.buildSlideOpts( slide );

				if ( prepend )
					opts.slides = $( slide ).add( opts.slides );
				else
					opts.slides = opts.slides.add( slide );

				opts.API.initSlide( slideOpts, slide, --opts._maxZ );

				slide.data('cycle.opts', slideOpts);
				opts.API.trigger('cycle-slide-added', [ opts, slideOpts, slide ]);
			});

			opts.API.updateView( true );

			startSlideshow = opts._preInitialized && (oldSlideCount < 2 && opts.slideCount >= 1);
			if ( startSlideshow ) {
				if ( !opts._initialized )
					opts.API.initSlideshow();
				else if ( opts.timeout ) {
					len = opts.slides.length;
					opts.nextSlide = opts.reverse ? len - 1 : 1;
					if ( !opts.timeoutId ) {
						opts.API.queueTransition( opts );
					}
				}
			}
		},

		calcFirstSlide: function() {
			var opts = this.opts();
			var firstSlideIndex;
			firstSlideIndex = parseInt( opts.startingSlide || 0, 10 );
			if (firstSlideIndex >= opts.slides.length || firstSlideIndex < 0)
				firstSlideIndex = 0;

			opts.currSlide = firstSlideIndex;
			if ( opts.reverse ) {
				opts.nextSlide = firstSlideIndex - 1;
				if (opts.nextSlide < 0)
					opts.nextSlide = opts.slides.length - 1;
			}
			else {
				opts.nextSlide = firstSlideIndex + 1;
				if (opts.nextSlide == opts.slides.length)
					opts.nextSlide = 0;
			}
		},

		calcNextSlide: function() {
			var opts = this.opts();
			var roll;
			if ( opts.reverse ) {
				roll = (opts.nextSlide - 1) < 0;
				opts.nextSlide = roll ? opts.slideCount - 1 : opts.nextSlide-1;
				opts.currSlide = roll ? 0 : opts.nextSlide+1;
			}
			else {
				roll = (opts.nextSlide + 1) == opts.slides.length;
				opts.nextSlide = roll ? 0 : opts.nextSlide+1;
				opts.currSlide = roll ? opts.slides.length-1 : opts.nextSlide-1;
			}
		},

		calcTx: function( slideOpts, manual ) {
			var opts = slideOpts;
			var tx;

			if ( opts._tempFx )
				tx = $.fn.cycle.transitions[opts._tempFx];
			else if ( manual && opts.manualFx )
				tx = $.fn.cycle.transitions[opts.manualFx];

			if ( !tx )
				tx = $.fn.cycle.transitions[opts.fx];

			opts._tempFx = null;
			this.opts()._tempFx = null;

			if (!tx) {
				tx = $.fn.cycle.transitions.fade;
				opts.API.log('Transition "' + opts.fx + '" not found.  Using fade.');
			}
			return tx;
		},

		prepareTx: function( manual, fwd ) {
			var opts = this.opts();
			var after, curr, next, slideOpts, tx;

			if ( opts.slideCount < 2 ) {
				opts.timeoutId = 0;
				return;
			}
			if ( manual && ( !opts.busy || opts.manualTrump ) ) {
				opts.API.stopTransition();
				opts.busy = false;
				clearTimeout(opts.timeoutId);
				opts.timeoutId = 0;
			}
			if ( opts.busy )
				return;
			if ( opts.timeoutId === 0 && !manual )
				return;

			curr = opts.slides[opts.currSlide];
			next = opts.slides[opts.nextSlide];
			slideOpts = opts.API.getSlideOpts( opts.nextSlide );
			tx = opts.API.calcTx( slideOpts, manual );

			opts._tx = tx;

			if ( manual && slideOpts.manualSpeed !== undefined )
				slideOpts.speed = slideOpts.manualSpeed;

			// if ( opts.nextSlide === opts.currSlide )
			//     opts.API.calcNextSlide();

			// ensure that:
			//      1. advancing to a different slide
			//      2. this is either a manual event (prev/next, pager, cmd) or
			//              a timer event and slideshow is not paused
			if ( opts.nextSlide != opts.currSlide &&
				(manual || (!opts.paused && !opts.hoverPaused && opts.timeout) )) { // #62

				opts.API.trigger('cycle-before', [ slideOpts, curr, next, fwd ]);
				if ( tx.before )
					tx.before( slideOpts, curr, next, fwd );

				after = function() {
					opts.busy = false;
					// #76; bail if slideshow has been destroyed
					if (! opts.container.data( 'cycle.opts' ) )
						return;

					if (tx.after)
						tx.after( slideOpts, curr, next, fwd );
					opts.API.trigger('cycle-after', [ slideOpts, curr, next, fwd ]);
					opts.API.queueTransition( slideOpts);
					opts.API.updateView( true );
				};

				opts.busy = true;
				if (tx.transition)
					tx.transition(slideOpts, curr, next, fwd, after);
				else
					opts.API.doTransition( slideOpts, curr, next, fwd, after);

				opts.API.calcNextSlide();
				opts.API.updateView();
			} else {
				opts.API.queueTransition( slideOpts );
			}
		},

		// perform the actual animation
		doTransition: function( slideOpts, currEl, nextEl, fwd, callback) {
			var opts = slideOpts;
			var curr = $(currEl), next = $(nextEl);
			var fn = function() {
				// make sure animIn has something so that callback doesn't trigger immediately
				next.animate(opts.animIn || { opacity: 1}, opts.speed, opts.easeIn || opts.easing, callback);
			};

			next.css(opts.cssBefore || {});
			curr.animate(opts.animOut || {}, opts.speed, opts.easeOut || opts.easing, function() {
				curr.css(opts.cssAfter || {});
				if (!opts.sync) {
					fn();
				}
			});
			if (opts.sync) {
				fn();
			}
		},

		queueTransition: function( slideOpts, specificTimeout ) {
			var opts = this.opts();
			var timeout = specificTimeout !== undefined ? specificTimeout : slideOpts.timeout;
			if (opts.nextSlide === 0 && --opts.loop === 0) {
				opts.API.log('terminating; loop=0');
				opts.timeout = 0;
				if ( timeout ) {
					setTimeout(function() {
						opts.API.trigger('cycle-finished', [ opts ]);
					}, timeout);
				}
				else {
					opts.API.trigger('cycle-finished', [ opts ]);
				}
				// reset nextSlide
				opts.nextSlide = opts.currSlide;
				return;
			}
			if ( opts.continueAuto !== undefined ) {
				if ( opts.continueAuto === false ||
					($.isFunction(opts.continueAuto) && opts.continueAuto() === false )) {
					opts.API.log('terminating automatic transitions');
					opts.timeout = 0;
					if ( opts.timeoutId )
						clearTimeout(opts.timeoutId);
					return;
				}
			}
			if ( timeout ) {
				opts._lastQueue = $.now();
				if ( specificTimeout === undefined )
					opts._remainingTimeout = slideOpts.timeout;

				if ( !opts.paused && ! opts.hoverPaused ) {
					opts.timeoutId = setTimeout(function() {
						opts.API.prepareTx( false, !opts.reverse );
					}, timeout );
				}
			}
		},

		stopTransition: function() {
			var opts = this.opts();
			if ( opts.slides.filter(':animated').length ) {
				opts.slides.stop(false, true);
				opts.API.trigger('cycle-transition-stopped', [ opts ]);
			}

			if ( opts._tx && opts._tx.stopTransition )
				opts._tx.stopTransition( opts );
		},

		// advance slide forward or back
		advanceSlide: function( val ) {
			var opts = this.opts();
			clearTimeout(opts.timeoutId);
			opts.timeoutId = 0;
			opts.nextSlide = opts.currSlide + val;

			if (opts.nextSlide < 0)
				opts.nextSlide = opts.slides.length - 1;
			else if (opts.nextSlide >= opts.slides.length)
				opts.nextSlide = 0;

			opts.API.prepareTx( true,  val >= 0 );
			return false;
		},

		buildSlideOpts: function( slide ) {
			var opts = this.opts();
			var val, shortName;
			var slideOpts = slide.data() || {};
			for (var p in slideOpts) {
				// allow props to be accessed sans 'cycle' prefix and log the overrides
				if (slideOpts.hasOwnProperty(p) && /^cycle[A-Z]+/.test(p) ) {
					val = slideOpts[p];
					shortName = p.match(/^cycle(.*)/)[1].replace(/^[A-Z]/, lowerCase);
					opts.API.log('['+(opts.slideCount-1)+']', shortName+':', val, '('+typeof val +')');
					slideOpts[shortName] = val;
				}
			}

			slideOpts = $.extend( {}, $.fn.cycle.defaults, opts, slideOpts );
			slideOpts.slideNum = opts.slideCount;

			try {
				// these props should always be read from the master state object
				delete slideOpts.API;
				delete slideOpts.slideCount;
				delete slideOpts.currSlide;
				delete slideOpts.nextSlide;
				delete slideOpts.slides;
			} catch(e) {
				// no op
			}
			return slideOpts;
		},

		getSlideOpts: function( index ) {
			var opts = this.opts();
			if ( index === undefined )
				index = opts.currSlide;

			var slide = opts.slides[index];
			var slideOpts = $(slide).data('cycle.opts');
			return $.extend( {}, opts, slideOpts );
		},

		initSlide: function( slideOpts, slide, suggestedZindex ) {
			var opts = this.opts();
			slide.css( slideOpts.slideCss || {} );
			if ( suggestedZindex > 0 )
				slide.css( 'zIndex', suggestedZindex );

			// ensure that speed settings are sane
			if ( isNaN( slideOpts.speed ) )
				slideOpts.speed = $.fx.speeds[slideOpts.speed] || $.fx.speeds._default;
			if ( !slideOpts.sync )
				slideOpts.speed = slideOpts.speed / 2;

			slide.addClass( opts.slideClass );
		},

		updateView: function( isAfter, isDuring, forceEvent ) {
			var opts = this.opts();
			if ( !opts._initialized )
				return;
			var slideOpts = opts.API.getSlideOpts();
			var currSlide = opts.slides[ opts.currSlide ];

			if ( ! isAfter && isDuring !== true ) {
				opts.API.trigger('cycle-update-view-before', [ opts, slideOpts, currSlide ]);
				if ( opts.updateView < 0 )
					return;
			}

			if ( opts.slideActiveClass ) {
				opts.slides.removeClass( opts.slideActiveClass )
					.eq( opts.currSlide ).addClass( opts.slideActiveClass );
			}

			if ( isAfter && opts.hideNonActive )
				opts.slides.filter( ':not(.' + opts.slideActiveClass + ')' ).css('visibility', 'hidden');

			if ( opts.updateView === 0 ) {
				setTimeout(function() {
					opts.API.trigger('cycle-update-view', [ opts, slideOpts, currSlide, isAfter ]);
				}, slideOpts.speed / (opts.sync ? 2 : 1) );
			}

			if ( opts.updateView !== 0 )
				opts.API.trigger('cycle-update-view', [ opts, slideOpts, currSlide, isAfter ]);

			if ( isAfter )
				opts.API.trigger('cycle-update-view-after', [ opts, slideOpts, currSlide ]);
		},

		getComponent: function( name ) {
			var opts = this.opts();
			var selector = opts[name];
			if (typeof selector === 'string') {
				// if selector is a child, sibling combinator, adjancent selector then use find, otherwise query full dom
				return (/^\s*[\>|\+|~]/).test( selector ) ? opts.container.find( selector ) : $( selector );
			}
			if (selector.jquery)
				return selector;

			return $(selector);
		},

		stackSlides: function( curr, next, fwd ) {
			var opts = this.opts();
			if ( !curr ) {
				curr = opts.slides[opts.currSlide];
				next = opts.slides[opts.nextSlide];
				fwd = !opts.reverse;
			}

			// reset the zIndex for the common case:
			// curr slide on top,  next slide beneath, and the rest in order to be shown
			$(curr).css('zIndex', opts.maxZ);

			var i;
			var z = opts.maxZ - 2;
			var len = opts.slideCount;
			if (fwd) {
				for ( i = opts.currSlide + 1; i < len; i++ )
					$( opts.slides[i] ).css( 'zIndex', z-- );
				for ( i = 0; i < opts.currSlide; i++ )
					$( opts.slides[i] ).css( 'zIndex', z-- );
			}
			else {
				for ( i = opts.currSlide - 1; i >= 0; i-- )
					$( opts.slides[i] ).css( 'zIndex', z-- );
				for ( i = len - 1; i > opts.currSlide; i-- )
					$( opts.slides[i] ).css( 'zIndex', z-- );
			}

			$(next).css('zIndex', opts.maxZ - 1);
		},

		getSlideIndex: function( el ) {
			return this.opts().slides.index( el );
		}

	}; // API

// default logger
	$.fn.cycle.log = function log() {
		/*global console:true */
		if (window.console && console.log)
			console.log('[cycle2] ' + Array.prototype.join.call(arguments, ' ') );
	};

	$.fn.cycle.version = function() { return 'Cycle2: ' + version; };

// helper functions

	function lowerCase(s) {
		return (s || '').toLowerCase();
	}

// expose transition object
	$.fn.cycle.transitions = {
		custom: {
		},
		none: {
			before: function( opts, curr, next, fwd ) {
				opts.API.stackSlides( next, curr, fwd );
				opts.cssBefore = { opacity: 1, visibility: 'visible', display: 'block' };
			}
		},
		fade: {
			before: function( opts, curr, next, fwd ) {
				var css = opts.API.getSlideOpts( opts.nextSlide ).slideCss || {};
				opts.API.stackSlides( curr, next, fwd );
				opts.cssBefore = $.extend(css, { opacity: 0, visibility: 'visible', display: 'block' });
				opts.animIn = { opacity: 1 };
				opts.animOut = { opacity: 0 };
			}
		},
		fadeout: {
			before: function( opts , curr, next, fwd ) {
				var css = opts.API.getSlideOpts( opts.nextSlide ).slideCss || {};
				opts.API.stackSlides( curr, next, fwd );
				opts.cssBefore = $.extend(css, { opacity: 1, visibility: 'visible', display: 'block' });
				opts.animOut = { opacity: 0 };
			}
		},
		scrollHorz: {
			before: function( opts, curr, next, fwd ) {
				opts.API.stackSlides( curr, next, fwd );
				var w = opts.container.css('overflow','hidden').width();
				opts.cssBefore = { left: fwd ? w : - w, top: 0, opacity: 1, visibility: 'visible', display: 'block' };
				opts.cssAfter = { zIndex: opts._maxZ - 2, left: 0 };
				opts.animIn = { left: 0 };
				opts.animOut = { left: fwd ? -w : w };
			}
		}
	};

// @see: http://jquery.malsup.com/cycle2/api
	$.fn.cycle.defaults = {
		allowWrap:        true,
		autoSelector:     '.cycle-slideshow[data-cycle-auto-init!=false]',
		delay:            0,
		easing:           null,
		fx:              'fade',
		hideNonActive:    true,
		loop:             0,
		manualFx:         undefined,
		manualSpeed:      undefined,
		manualTrump:      true,
		maxZ:             100,
		pauseOnHover:     false,
		reverse:          false,
		slideActiveClass: 'cycle-slide-active',
		slideClass:       'cycle-slide',
		slideCss:         { position: 'absolute', top: 0, left: 0 },
		slides:          '> img',
		speed:            500,
		startingSlide:    0,
		sync:             true,
		timeout:          4000,
		updateView:       0
	};

// automatically find and run slideshows
	$(document).ready(function() {
		$( $.fn.cycle.defaults.autoSelector ).cycle();
	});

})(jQuery);

/*! Cycle2 autoheight plugin; Copyright (c) M.Alsup, 2012; version: 20130913 */
(function($) {
	"use strict";

	$.extend($.fn.cycle.defaults, {
		autoHeight: 0, // setting this option to false disables autoHeight logic
		autoHeightSpeed: 250,
		autoHeightEasing: null
	});

	$(document).on( 'cycle-initialized', function( e, opts ) {
		var autoHeight = opts.autoHeight;
		var t = $.type( autoHeight );
		var resizeThrottle = null;
		var ratio;

		if ( t !== 'string' && t !== 'number' )
			return;

		// bind events
		opts.container.on( 'cycle-slide-added cycle-slide-removed', initAutoHeight );
		opts.container.on( 'cycle-destroyed', onDestroy );

		if ( autoHeight == 'container' ) {
			opts.container.on( 'cycle-before', onBefore );
		}
		else if ( t === 'string' && /\d+\:\d+/.test( autoHeight ) ) {
			// use ratio
			ratio = autoHeight.match(/(\d+)\:(\d+)/);
			ratio = ratio[1] / ratio[2];
			opts._autoHeightRatio = ratio;
		}

		// if autoHeight is a number then we don't need to recalculate the sentinel
		// index on resize
		if ( t !== 'number' ) {
			// bind unique resize handler per slideshow (so it can be 'off-ed' in onDestroy)
			opts._autoHeightOnResize = function () {
				clearTimeout( resizeThrottle );
				resizeThrottle = setTimeout( onResize, 50 );
			};

			$(window).on( 'resize orientationchange', opts._autoHeightOnResize );
		}

		setTimeout( onResize, 30 );

		function onResize() {
			initAutoHeight( e, opts );
		}
	});

	function initAutoHeight( e, opts ) {
		var clone, height, sentinelIndex;
		var autoHeight = opts.autoHeight;

		if ( autoHeight == 'container' ) {
			height = $( opts.slides[ opts.currSlide ] ).outerHeight();
			opts.container.height( height );
		}
		else if ( opts._autoHeightRatio ) {
			opts.container.height( opts.container.width() / opts._autoHeightRatio );
		}
		else if ( autoHeight === 'calc' || ( $.type( autoHeight ) == 'number' && autoHeight >= 0 ) ) {
			if ( autoHeight === 'calc' )
				sentinelIndex = calcSentinelIndex( e, opts );
			else if ( autoHeight >= opts.slides.length )
				sentinelIndex = 0;
			else
				sentinelIndex = autoHeight;

			// only recreate sentinel if index is different
			if ( sentinelIndex == opts._sentinelIndex )
				return;

			opts._sentinelIndex = sentinelIndex;
			if ( opts._sentinel )
				opts._sentinel.remove();

			// clone existing slide as sentinel
			clone = $( opts.slides[ sentinelIndex ].cloneNode(true) );

			// #50; remove special attributes from cloned content
			clone.removeAttr( 'id name rel' ).find( '[id],[name],[rel]' ).removeAttr( 'id name rel' );

			clone.css({
				position: 'static',
				visibility: 'hidden',
				display: 'block'
			}).prependTo( opts.container ).addClass('cycle-sentinel cycle-slide').removeClass('cycle-slide-active');
			clone.find( '*' ).css( 'visibility', 'hidden' );

			opts._sentinel = clone;
		}
	}

	function calcSentinelIndex( e, opts ) {
		var index = 0, max = -1;

		// calculate tallest slide index
		opts.slides.each(function(i) {
			var h = $(this).height();
			if ( h > max ) {
				max = h;
				index = i;
			}
		});
		return index;
	}

	function onBefore( e, opts, outgoing, incoming, forward ) {
		var h = $(incoming).outerHeight();
		opts.container.animate( { height: h }, opts.autoHeightSpeed, opts.autoHeightEasing );
	}

	function onDestroy( e, opts ) {
		if ( opts._autoHeightOnResize ) {
			$(window).off( 'resize orientationchange', opts._autoHeightOnResize );
			opts._autoHeightOnResize = null;
		}
		opts.container.off( 'cycle-slide-added cycle-slide-removed', initAutoHeight );
		opts.container.off( 'cycle-destroyed', onDestroy );
		opts.container.off( 'cycle-before', onBefore );

		if ( opts._sentinel ) {
			opts._sentinel.remove();
			opts._sentinel = null;
		}
	}

})(jQuery);

/*! caption plugin for Cycle2;  version: 20130306 */
(function($) {
	"use strict";

	$.extend($.fn.cycle.defaults, {
		caption:          '> .cycle-caption',
		captionTemplate:  '{{slideNum}} / {{slideCount}}',
		overlay:          '> .cycle-overlay',
		overlayTemplate:  '<div>{{title}}</div><div>{{desc}}</div>',
		captionModule:    'caption'
	});

	$(document).on( 'cycle-update-view', function( e, opts, slideOpts, currSlide ) {
		if ( opts.captionModule !== 'caption' )
			return;
		var el;
		$.each(['caption','overlay'], function() {
			var name = this;
			var template = slideOpts[name+'Template'];
			var el = opts.API.getComponent( name );
			if( el.length && template ) {
				el.html( opts.API.tmpl( template, slideOpts, opts, currSlide ) );
				el.show();
			}
			else {
				el.hide();
			}
		});
	});

	$(document).on( 'cycle-destroyed', function( e, opts ) {
		var el;
		$.each(['caption','overlay'], function() {
			var name = this, template = opts[name+'Template'];
			if ( opts[name] && template ) {
				el = opts.API.getComponent( 'caption' );
				el.empty();
			}
		});
	});

})(jQuery);

/*! command plugin for Cycle2;  version: 20140415 */
(function($) {
	"use strict";

	var c2 = $.fn.cycle;

	$.fn.cycle = function( options ) {
		var cmd, cmdFn, opts;
		var args = $.makeArray( arguments );

		if ( $.type( options ) == 'number' ) {
			return this.cycle( 'goto', options );
		}

		if ( $.type( options ) == 'string' ) {
			return this.each(function() {
				var cmdArgs;
				cmd = options;
				opts = $(this).data('cycle.opts');

				if ( opts === undefined ) {
					c2.log('slideshow must be initialized before sending commands; "' + cmd + '" ignored');
					return;
				}
				else {
					cmd = cmd == 'goto' ? 'jump' : cmd; // issue #3; change 'goto' to 'jump' internally
					cmdFn = opts.API[ cmd ];
					if ( $.isFunction( cmdFn )) {
						cmdArgs = $.makeArray( args );
						cmdArgs.shift();
						return cmdFn.apply( opts.API, cmdArgs );
					}
					else {
						c2.log( 'unknown command: ', cmd );
					}
				}
			});
		}
		else {
			return c2.apply( this, arguments );
		}
	};

// copy props
	$.extend( $.fn.cycle, c2 );

	$.extend( c2.API, {
		next: function() {
			var opts = this.opts();
			if ( opts.busy && ! opts.manualTrump )
				return;

			var count = opts.reverse ? -1 : 1;
			if ( opts.allowWrap === false && ( opts.currSlide + count ) >= opts.slideCount )
				return;

			opts.API.advanceSlide( count );
			opts.API.trigger('cycle-next', [ opts ]).log('cycle-next');
		},

		prev: function() {
			var opts = this.opts();
			if ( opts.busy && ! opts.manualTrump )
				return;
			var count = opts.reverse ? 1 : -1;
			if ( opts.allowWrap === false && ( opts.currSlide + count ) < 0 )
				return;

			opts.API.advanceSlide( count );
			opts.API.trigger('cycle-prev', [ opts ]).log('cycle-prev');
		},

		destroy: function() {
			this.stop(); //#204

			var opts = this.opts();
			var clean = $.isFunction( $._data ) ? $._data : $.noop;  // hack for #184 and #201
			clearTimeout(opts.timeoutId);
			opts.timeoutId = 0;
			opts.API.stop();
			opts.API.trigger( 'cycle-destroyed', [ opts ] ).log('cycle-destroyed');
			opts.container.removeData();
			clean( opts.container[0], 'parsedAttrs', false );

			// #75; remove inline styles
			if ( ! opts.retainStylesOnDestroy ) {
				opts.container.removeAttr( 'style' );
				opts.slides.removeAttr( 'style' );
				opts.slides.removeClass( opts.slideActiveClass );
			}
			opts.slides.each(function() {
				$(this).removeData();
				clean( this, 'parsedAttrs', false );
			});
		},

		jump: function( index, fx ) {
			// go to the requested slide
			var fwd;
			var opts = this.opts();
			if ( opts.busy && ! opts.manualTrump )
				return;
			var num = parseInt( index, 10 );
			if (isNaN(num) || num < 0 || num >= opts.slides.length) {
				opts.API.log('goto: invalid slide index: ' + num);
				return;
			}
			if (num == opts.currSlide) {
				opts.API.log('goto: skipping, already on slide', num);
				return;
			}
			opts.nextSlide = num;
			clearTimeout(opts.timeoutId);
			opts.timeoutId = 0;
			opts.API.log('goto: ', num, ' (zero-index)');
			fwd = opts.currSlide < opts.nextSlide;
			opts._tempFx = fx;
			opts.API.prepareTx( true, fwd );
		},

		stop: function() {
			var opts = this.opts();
			var pauseObj = opts.container;
			clearTimeout(opts.timeoutId);
			opts.timeoutId = 0;
			opts.API.stopTransition();
			if ( opts.pauseOnHover ) {
				if ( opts.pauseOnHover !== true )
					pauseObj = $( opts.pauseOnHover );
				pauseObj.off('mouseenter mouseleave');
			}
			opts.API.trigger('cycle-stopped', [ opts ]).log('cycle-stopped');
		},

		reinit: function() {
			var opts = this.opts();
			opts.API.destroy();
			opts.container.cycle();
		},

		remove: function( index ) {
			var opts = this.opts();
			var slide, slideToRemove, slides = [], slideNum = 1;
			for ( var i=0; i < opts.slides.length; i++ ) {
				slide = opts.slides[i];
				if ( i == index ) {
					slideToRemove = slide;
				}
				else {
					slides.push( slide );
					$( slide ).data('cycle.opts').slideNum = slideNum;
					slideNum++;
				}
			}
			if ( slideToRemove ) {
				opts.slides = $( slides );
				opts.slideCount--;
				$( slideToRemove ).remove();
				if (index == opts.currSlide)
					opts.API.advanceSlide( 1 );
				else if ( index < opts.currSlide )
					opts.currSlide--;
				else
					opts.currSlide++;

				opts.API.trigger('cycle-slide-removed', [ opts, index, slideToRemove ]).log('cycle-slide-removed');
				opts.API.updateView();
			}
		}

	});

// listen for clicks on elements with data-cycle-cmd attribute
	$(document).on('click.cycle', '[data-cycle-cmd]', function(e) {
		// issue cycle command
		e.preventDefault();
		var el = $(this);
		var command = el.data('cycle-cmd');
		var context = el.data('cycle-context') || '.cycle-slideshow';
		$(context).cycle(command, el.data('cycle-arg'));
	});


})(jQuery);

/*! hash plugin for Cycle2;  version: 20130905 */
(function($) {
	"use strict";

	$(document).on( 'cycle-pre-initialize', function( e, opts ) {
		onHashChange( opts, true );

		opts._onHashChange = function() {
			onHashChange( opts, false );
		};

		$( window ).on( 'hashchange', opts._onHashChange);
	});

	$(document).on( 'cycle-update-view', function( e, opts, slideOpts ) {
		if ( slideOpts.hash && ( '#' + slideOpts.hash ) != window.location.hash ) {
			opts._hashFence = true;
			window.location.hash = slideOpts.hash;
		}
	});

	$(document).on( 'cycle-destroyed', function( e, opts) {
		if ( opts._onHashChange ) {
			$( window ).off( 'hashchange', opts._onHashChange );
		}
	});

	function onHashChange( opts, setStartingSlide ) {
		var hash;
		if ( opts._hashFence ) {
			opts._hashFence = false;
			return;
		}

		hash = window.location.hash.substring(1);

		opts.slides.each(function(i) {
			if ( $(this).data( 'cycle-hash' ) == hash ) {
				if ( setStartingSlide === true ) {
					opts.startingSlide = i;
				}
				else {
					var fwd = opts.currSlide < i;
					opts.nextSlide = i;
					opts.API.prepareTx( true, fwd );
				}
				return false;
			}
		});
	}

})(jQuery);

/*! loader plugin for Cycle2;  version: 20131121 */
(function($) {
	"use strict";

	$.extend($.fn.cycle.defaults, {
		loader: false
	});

	$(document).on( 'cycle-bootstrap', function( e, opts ) {
		var addFn;

		if ( !opts.loader )
			return;

		// override API.add for this slideshow
		addFn = opts.API.add;
		opts.API.add = add;

		function add( slides, prepend ) {
			var slideArr = [];
			if ( $.type( slides ) == 'string' )
				slides = $.trim( slides );
			else if ( $.type( slides) === 'array' ) {
				for (var i=0; i < slides.length; i++ )
					slides[i] = $(slides[i])[0];
			}

			slides = $( slides );
			var slideCount = slides.length;

			if ( ! slideCount )
				return;

			slides.css('visibility','hidden').appendTo('body').each(function(i) { // appendTo fixes #56
				var count = 0;
				var slide = $(this);
				var images = slide.is('img') ? slide : slide.find('img');
				slide.data('index', i);
				// allow some images to be marked as unimportant (and filter out images w/o src value)
				images = images.filter(':not(.cycle-loader-ignore)').filter(':not([src=""])');
				if ( ! images.length ) {
					--slideCount;
					slideArr.push( slide );
					return;
				}

				count = images.length;
				images.each(function() {
					// add images that are already loaded
					if ( this.complete ) {
						imageLoaded();
					}
					else {
						$(this).load(function() {
							imageLoaded();
						}).on("error", function() {
							if ( --count === 0 ) {
								// ignore this slide
								opts.API.log('slide skipped; img not loaded:', this.src);
								if ( --slideCount === 0 && opts.loader == 'wait') {
									addFn.apply( opts.API, [ slideArr, prepend ] );
								}
							}
						});
					}
				});

				function imageLoaded() {
					if ( --count === 0 ) {
						--slideCount;
						addSlide( slide );
					}
				}
			});

			if ( slideCount )
				opts.container.addClass('cycle-loading');


			function addSlide( slide ) {
				var curr;
				if ( opts.loader == 'wait' ) {
					slideArr.push( slide );
					if ( slideCount === 0 ) {
						// #59; sort slides into original markup order
						slideArr.sort( sorter );
						addFn.apply( opts.API, [ slideArr, prepend ] );
						opts.container.removeClass('cycle-loading');
					}
				}
				else {
					curr = $(opts.slides[opts.currSlide]);
					addFn.apply( opts.API, [ slide, prepend ] );
					curr.show();
					opts.container.removeClass('cycle-loading');
				}
			}

			function sorter(a, b) {
				return a.data('index') - b.data('index');
			}
		}
	});

})(jQuery);

/*! pager plugin for Cycle2;  version: 20140415 */
(function($) {
	"use strict";

	$.extend($.fn.cycle.defaults, {
		pager:            '> .cycle-pager',
		pagerActiveClass: 'cycle-pager-active',
		pagerEvent:       'click.cycle',
		pagerEventBubble: undefined,
		pagerTemplate:    '<span>&bull;</span>'
	});

	$(document).on( 'cycle-bootstrap', function( e, opts, API ) {
		// add method to API
		API.buildPagerLink = buildPagerLink;
	});

	$(document).on( 'cycle-slide-added', function( e, opts, slideOpts, slideAdded ) {
		if ( opts.pager ) {
			opts.API.buildPagerLink ( opts, slideOpts, slideAdded );
			opts.API.page = page;
		}
	});

	$(document).on( 'cycle-slide-removed', function( e, opts, index, slideRemoved ) {
		if ( opts.pager ) {
			var pagers = opts.API.getComponent( 'pager' );
			pagers.each(function() {
				var pager = $(this);
				$( pager.children()[index] ).remove();
			});
		}
	});

	$(document).on( 'cycle-update-view', function( e, opts, slideOpts ) {
		var pagers;

		if ( opts.pager ) {
			pagers = opts.API.getComponent( 'pager' );
			pagers.each(function() {
				$(this).children().removeClass( opts.pagerActiveClass )
					.eq( opts.currSlide ).addClass( opts.pagerActiveClass );
			});
		}
	});

	$(document).on( 'cycle-destroyed', function( e, opts ) {
		var pager = opts.API.getComponent( 'pager' );

		if ( pager ) {
			pager.children().off( opts.pagerEvent ); // #202
			if ( opts.pagerTemplate )
				pager.empty();
		}
	});

	function buildPagerLink( opts, slideOpts, slide ) {
		var pagerLink;
		var pagers = opts.API.getComponent( 'pager' );
		pagers.each(function() {
			var pager = $(this);
			if ( slideOpts.pagerTemplate ) {
				var markup = opts.API.tmpl( slideOpts.pagerTemplate, slideOpts, opts, slide[0] );
				pagerLink = $( markup ).appendTo( pager );
			}
			else {
				pagerLink = pager.children().eq( opts.slideCount - 1 );
			}
			pagerLink.on( opts.pagerEvent, function(e) {
				if ( ! opts.pagerEventBubble )
					e.preventDefault();
				opts.API.page( pager, e.currentTarget);
			});
		});
	}

	function page( pager, target ) {
		/*jshint validthis:true */
		var opts = this.opts();
		if ( opts.busy && ! opts.manualTrump )
			return;

		var index = pager.children().index( target );
		var nextSlide = index;
		var fwd = opts.currSlide < nextSlide;
		if (opts.currSlide == nextSlide) {
			return; // no op, clicked pager for the currently displayed slide
		}
		opts.nextSlide = nextSlide;
		opts._tempFx = opts.pagerFx;
		opts.API.prepareTx( true, fwd );
		opts.API.trigger('cycle-pager-activated', [opts, pager, target ]);
	}

})(jQuery);

/*! prevnext plugin for Cycle2;  version: 20140408 */
(function($) {
	"use strict";

	$.extend($.fn.cycle.defaults, {
		next:           '> .cycle-next',
		nextEvent:      'click.cycle',
		disabledClass:  'disabled',
		prev:           '> .cycle-prev',
		prevEvent:      'click.cycle',
		swipe:          false
	});

	$(document).on( 'cycle-initialized', function( e, opts ) {
		opts.API.getComponent( 'next' ).on( opts.nextEvent, function(e) {
			e.preventDefault();
			opts.API.next();
		});

		opts.API.getComponent( 'prev' ).on( opts.prevEvent, function(e) {
			e.preventDefault();
			opts.API.prev();
		});

		if ( opts.swipe ) {
			var nextEvent = opts.swipeVert ? 'swipeUp.cycle' : 'swipeLeft.cycle swipeleft.cycle';
			var prevEvent = opts.swipeVert ? 'swipeDown.cycle' : 'swipeRight.cycle swiperight.cycle';
			opts.container.on( nextEvent, function(e) {
				opts._tempFx = opts.swipeFx;
				opts.API.next();
			});
			opts.container.on( prevEvent, function() {
				opts._tempFx = opts.swipeFx;
				opts.API.prev();
			});
		}
	});

	$(document).on( 'cycle-update-view', function( e, opts, slideOpts, currSlide ) {
		if ( opts.allowWrap )
			return;

		var cls = opts.disabledClass;
		var next = opts.API.getComponent( 'next' );
		var prev = opts.API.getComponent( 'prev' );
		var prevBoundry = opts._prevBoundry || 0;
		var nextBoundry = (opts._nextBoundry !== undefined)?opts._nextBoundry:opts.slideCount - 1;

		if ( opts.currSlide == nextBoundry )
			next.addClass( cls ).prop( 'disabled', true );
		else
			next.removeClass( cls ).prop( 'disabled', false );

		if ( opts.currSlide === prevBoundry )
			prev.addClass( cls ).prop( 'disabled', true );
		else
			prev.removeClass( cls ).prop( 'disabled', false );
	});


	$(document).on( 'cycle-destroyed', function( e, opts ) {
		opts.API.getComponent( 'prev' ).off( opts.nextEvent );
		opts.API.getComponent( 'next' ).off( opts.prevEvent );
		opts.container.off( 'swipeleft.cycle swiperight.cycle swipeLeft.cycle swipeRight.cycle swipeUp.cycle swipeDown.cycle' );
	});

})(jQuery);

/*! progressive loader plugin for Cycle2;  version: 20130315 */
(function($) {
	"use strict";

	$.extend($.fn.cycle.defaults, {
		progressive: false
	});

	$(document).on( 'cycle-pre-initialize', function( e, opts ) {
		if ( !opts.progressive )
			return;

		var API = opts.API;
		var nextFn = API.next;
		var prevFn = API.prev;
		var prepareTxFn = API.prepareTx;
		var type = $.type( opts.progressive );
		var slides, scriptEl;

		if ( type == 'array' ) {
			slides = opts.progressive;
		}
		else if ($.isFunction( opts.progressive ) ) {
			slides = opts.progressive( opts );
		}
		else if ( type == 'string' ) {
			scriptEl = $( opts.progressive );
			slides = $.trim( scriptEl.html() );
			if ( !slides )
				return;
			// is it json array?
			if ( /^(\[)/.test( slides ) ) {
				try {
					slides = $.parseJSON( slides );
				}
				catch(err) {
					API.log( 'error parsing progressive slides', err );
					return;
				}
			}
			else {
				// plain text, split on delimeter
				slides = slides.split( new RegExp( scriptEl.data('cycle-split') || '\n') );

				// #95; look for empty slide
				if ( ! slides[ slides.length - 1 ] )
					slides.pop();
			}
		}



		if ( prepareTxFn ) {
			API.prepareTx = function( manual, fwd ) {
				var index, slide;

				if ( manual || slides.length === 0 ) {
					prepareTxFn.apply( opts.API, [ manual, fwd ] );
					return;
				}

				if ( fwd && opts.currSlide == ( opts.slideCount-1) ) {
					slide = slides[ 0 ];
					slides = slides.slice( 1 );
					opts.container.one('cycle-slide-added', function(e, opts ) {
						setTimeout(function() {
							opts.API.advanceSlide( 1 );
						},50);
					});
					opts.API.add( slide );
				}
				else if ( !fwd && opts.currSlide === 0 ) {
					index = slides.length-1;
					slide = slides[ index ];
					slides = slides.slice( 0, index );
					opts.container.one('cycle-slide-added', function(e, opts ) {
						setTimeout(function() {
							opts.currSlide = 1;
							opts.API.advanceSlide( -1 );
						},50);
					});
					opts.API.add( slide, true );
				}
				else {
					prepareTxFn.apply( opts.API, [ manual, fwd ] );
				}
			};
		}

		if ( nextFn ) {
			API.next = function() {
				var opts = this.opts();
				if ( slides.length && opts.currSlide == ( opts.slideCount - 1 ) ) {
					var slide = slides[ 0 ];
					slides = slides.slice( 1 );
					opts.container.one('cycle-slide-added', function(e, opts ) {
						nextFn.apply( opts.API );
						opts.container.removeClass('cycle-loading');
					});
					opts.container.addClass('cycle-loading');
					opts.API.add( slide );
				}
				else {
					nextFn.apply( opts.API );
				}
			};
		}

		if ( prevFn ) {
			API.prev = function() {
				var opts = this.opts();
				if ( slides.length && opts.currSlide === 0 ) {
					var index = slides.length-1;
					var slide = slides[ index ];
					slides = slides.slice( 0, index );
					opts.container.one('cycle-slide-added', function(e, opts ) {
						opts.currSlide = 1;
						opts.API.advanceSlide( -1 );
						opts.container.removeClass('cycle-loading');
					});
					opts.container.addClass('cycle-loading');
					opts.API.add( slide, true );
				}
				else {
					prevFn.apply( opts.API );
				}
			};
		}
	});

})(jQuery);

/*! tmpl plugin for Cycle2;  version: 20121227 */
(function($) {
	"use strict";

	$.extend($.fn.cycle.defaults, {
		tmplRegex: '{{((.)?.*?)}}'
	});

	$.extend($.fn.cycle.API, {
		tmpl: function( str, opts /*, ... */) {
			var regex = new RegExp( opts.tmplRegex || $.fn.cycle.defaults.tmplRegex, 'g' );
			var args = $.makeArray( arguments );
			args.shift();
			return str.replace(regex, function(_, str) {
				var i, j, obj, prop, names = str.split('.');
				for (i=0; i < args.length; i++) {
					obj = args[i];
					if ( ! obj )
						continue;
					if (names.length > 1) {
						prop = obj;
						for (j=0; j < names.length; j++) {
							obj = prop;
							prop = prop[ names[j] ] || str;
						}
					} else {
						prop = obj[str];
					}

					if ($.isFunction(prop))
						return prop.apply(obj, args);
					if (prop !== undefined && prop !== null && prop != str)
						return prop;
				}
				return str;
			});
		}
	});

})(jQuery);


// Generated by CoffeeScript 1.6.2
/*!
 jQuery Waypoints - v2.0.5
 Copyright (c) 2011-2014 Caleb Troughton
 Licensed under the MIT license.
 https://github.com/imakewebthings/jquery-waypoints/blob/master/licenses.txt
 */
(function($) {
	(function(){var t=[].indexOf||function(t){for(var e=0,n=this.length;e<n;e++){if(e in this&&this[e]===t)return e}return-1},e=[].slice;(function(t,e){if(typeof define==="function"&&define.amd){return define("waypoints",["jquery"],function(n){return e(n,t)})}else{return e(t.jQuery,t)}})(window,function(n,r){var i,o,l,s,f,u,c,a,h,d,p,y,v,w,g,m;i=n(r);a=t.call(r,"ontouchstart")>=0;s={horizontal:{},vertical:{}};f=1;c={};u="waypoints-context-id";p="resize.waypoints";y="scroll.waypoints";v=1;w="waypoints-waypoint-ids";g="waypoint";m="waypoints";o=function(){function t(t){var e=this;this.$element=t;this.element=t[0];this.didResize=false;this.didScroll=false;this.id="context"+f++;this.oldScroll={x:t.scrollLeft(),y:t.scrollTop()};this.waypoints={horizontal:{},vertical:{}};this.element[u]=this.id;c[this.id]=this;t.bind(y,function(){var t;if(!(e.didScroll||a)){e.didScroll=true;t=function(){e.doScroll();return e.didScroll=false};return r.setTimeout(t,n[m].settings.scrollThrottle)}});t.bind(p,function(){var t;if(!e.didResize){e.didResize=true;t=function(){n[m]("refresh");return e.didResize=false};return r.setTimeout(t,n[m].settings.resizeThrottle)}})}t.prototype.doScroll=function(){var t,e=this;t={horizontal:{newScroll:this.$element.scrollLeft(),oldScroll:this.oldScroll.x,forward:"right",backward:"left"},vertical:{newScroll:this.$element.scrollTop(),oldScroll:this.oldScroll.y,forward:"down",backward:"up"}};if(a&&(!t.vertical.oldScroll||!t.vertical.newScroll)){n[m]("refresh")}n.each(t,function(t,r){var i,o,l;l=[];o=r.newScroll>r.oldScroll;i=o?r.forward:r.backward;n.each(e.waypoints[t],function(t,e){var n,i;if(r.oldScroll<(n=e.offset)&&n<=r.newScroll){return l.push(e)}else if(r.newScroll<(i=e.offset)&&i<=r.oldScroll){return l.push(e)}});l.sort(function(t,e){return t.offset-e.offset});if(!o){l.reverse()}return n.each(l,function(t,e){if(e.options.continuous||t===l.length-1){return e.trigger([i])}})});return this.oldScroll={x:t.horizontal.newScroll,y:t.vertical.newScroll}};t.prototype.refresh=function(){var t,e,r,i=this;r=n.isWindow(this.element);e=this.$element.offset();this.doScroll();t={horizontal:{contextOffset:r?0:e.left,contextScroll:r?0:this.oldScroll.x,contextDimension:this.$element.width(),oldScroll:this.oldScroll.x,forward:"right",backward:"left",offsetProp:"left"},vertical:{contextOffset:r?0:e.top,contextScroll:r?0:this.oldScroll.y,contextDimension:r?n[m]("viewportHeight"):this.$element.height(),oldScroll:this.oldScroll.y,forward:"down",backward:"up",offsetProp:"top"}};return n.each(t,function(t,e){return n.each(i.waypoints[t],function(t,r){var i,o,l,s,f;i=r.options.offset;l=r.offset;o=n.isWindow(r.element)?0:r.$element.offset()[e.offsetProp];if(n.isFunction(i)){i=i.apply(r.element)}else if(typeof i==="string"){i=parseFloat(i);if(r.options.offset.indexOf("%")>-1){i=Math.ceil(e.contextDimension*i/100)}}r.offset=o-e.contextOffset+e.contextScroll-i;if(r.options.onlyOnScroll&&l!=null||!r.enabled){return}if(l!==null&&l<(s=e.oldScroll)&&s<=r.offset){return r.trigger([e.backward])}else if(l!==null&&l>(f=e.oldScroll)&&f>=r.offset){return r.trigger([e.forward])}else if(l===null&&e.oldScroll>=r.offset){return r.trigger([e.forward])}})})};t.prototype.checkEmpty=function(){if(n.isEmptyObject(this.waypoints.horizontal)&&n.isEmptyObject(this.waypoints.vertical)){this.$element.unbind([p,y].join(" "));return delete c[this.id]}};return t}();l=function(){function t(t,e,r){var i,o;if(r.offset==="bottom-in-view"){r.offset=function(){var t;t=n[m]("viewportHeight");if(!n.isWindow(e.element)){t=e.$element.height()}return t-n(this).outerHeight()}}this.$element=t;this.element=t[0];this.axis=r.horizontal?"horizontal":"vertical";this.callback=r.handler;this.context=e;this.enabled=r.enabled;this.id="waypoints"+v++;this.offset=null;this.options=r;e.waypoints[this.axis][this.id]=this;s[this.axis][this.id]=this;i=(o=this.element[w])!=null?o:[];i.push(this.id);this.element[w]=i}t.prototype.trigger=function(t){if(!this.enabled){return}if(this.callback!=null){this.callback.apply(this.element,t)}if(this.options.triggerOnce){return this.destroy()}};t.prototype.disable=function(){return this.enabled=false};t.prototype.enable=function(){this.context.refresh();return this.enabled=true};t.prototype.destroy=function(){delete s[this.axis][this.id];delete this.context.waypoints[this.axis][this.id];return this.context.checkEmpty()};t.getWaypointsByElement=function(t){var e,r;r=t[w];if(!r){return[]}e=n.extend({},s.horizontal,s.vertical);return n.map(r,function(t){return e[t]})};return t}();d={init:function(t,e){var r;e=n.extend({},n.fn[g].defaults,e);if((r=e.handler)==null){e.handler=t}this.each(function(){var t,r,i,s;t=n(this);i=(s=e.context)!=null?s:n.fn[g].defaults.context;if(!n.isWindow(i)){i=t.closest(i)}i=n(i);r=c[i[0][u]];if(!r){r=new o(i)}return new l(t,r,e)});n[m]("refresh");return this},disable:function(){return d._invoke.call(this,"disable")},enable:function(){return d._invoke.call(this,"enable")},destroy:function(){return d._invoke.call(this,"destroy")},prev:function(t,e){return d._traverse.call(this,t,e,function(t,e,n){if(e>0){return t.push(n[e-1])}})},next:function(t,e){return d._traverse.call(this,t,e,function(t,e,n){if(e<n.length-1){return t.push(n[e+1])}})},_traverse:function(t,e,i){var o,l;if(t==null){t="vertical"}if(e==null){e=r}l=h.aggregate(e);o=[];this.each(function(){var e;e=n.inArray(this,l[t]);return i(o,e,l[t])});return this.pushStack(o)},_invoke:function(t){this.each(function(){var e;e=l.getWaypointsByElement(this);return n.each(e,function(e,n){n[t]();return true})});return this}};n.fn[g]=function(){var t,r;r=arguments[0],t=2<=arguments.length?e.call(arguments,1):[];if(d[r]){return d[r].apply(this,t)}else if(n.isFunction(r)){return d.init.apply(this,arguments)}else if(n.isPlainObject(r)){return d.init.apply(this,[null,r])}else if(!r){return n.error("jQuery Waypoints needs a callback function or handler option.")}else{return n.error("The "+r+" method does not exist in jQuery Waypoints.")}};n.fn[g].defaults={context:r,continuous:true,enabled:true,horizontal:false,offset:0,triggerOnce:false};h={refresh:function(){return n.each(c,function(t,e){return e.refresh()})},viewportHeight:function(){var t;return(t=r.innerHeight)!=null?t:i.height()},aggregate:function(t){var e,r,i;e=s;if(t){e=(i=c[n(t)[0][u]])!=null?i.waypoints:void 0}if(!e){return[]}r={horizontal:[],vertical:[]};n.each(r,function(t,i){n.each(e[t],function(t,e){return i.push(e)});i.sort(function(t,e){return t.offset-e.offset});r[t]=n.map(i,function(t){return t.element});return r[t]=n.unique(r[t])});return r},above:function(t){if(t==null){t=r}return h._filter(t,"vertical",function(t,e){return e.offset<=t.oldScroll.y})},below:function(t){if(t==null){t=r}return h._filter(t,"vertical",function(t,e){return e.offset>t.oldScroll.y})},left:function(t){if(t==null){t=r}return h._filter(t,"horizontal",function(t,e){return e.offset<=t.oldScroll.x})},right:function(t){if(t==null){t=r}return h._filter(t,"horizontal",function(t,e){return e.offset>t.oldScroll.x})},enable:function(){return h._invoke("enable")},disable:function(){return h._invoke("disable")},destroy:function(){return h._invoke("destroy")},extendFn:function(t,e){return d[t]=e},_invoke:function(t){var e;e=n.extend({},s.vertical,s.horizontal);return n.each(e,function(e,n){n[t]();return true})},_filter:function(t,e,r){var i,o;i=c[n(t)[0][u]];if(!i){return[]}o=[];n.each(i.waypoints[e],function(t,e){if(r(i,e)){return o.push(e)}});o.sort(function(t,e){return t.offset-e.offset});return n.map(o,function(t){return t.element})}};n[m]=function(){var t,n;n=arguments[0],t=2<=arguments.length?e.call(arguments,1):[];if(h[n]){return h[n].apply(null,t)}else{return h.aggregate.call(null,n)}};n[m].settings={resizeThrottle:100,scrollThrottle:30};return i.on("load.waypoints",function(){return n[m]("refresh")})})}).call(this);
})(jQuery);


