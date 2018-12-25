/* Author: */
function renderDropdownNav(){
	jQuery('.locations #hidden-level-2, .camps #hidden-level-3').each(function(){
		var camps = jQuery('body.camps').length, 
            picker = jQuery(innerShiv('<nav id="subnav-picker"><h4>Select another'+(camps?' camp':' location')+'</h4><select/></nav>'));
		picker = jQuery(this).after(picker).next(picker);
		picker
			.find('select')
			.change(function(){
				document.location = jQuery(this).find(':selected').val();
			});
		jQuery(this).find('.level_2, .level_3').children('a').each(function(){
			//var selected = jQuery(this).hasClass('active') ? 'selected': '';
			var selected = jQuery.trim(jQuery(this).text()) == jQuery.trim(jQuery('#subnav-utility h3').text()) ? 'selected': '';
			picker.find('select').append('<option value="'+jQuery(this).attr('href')+'" '+selected+' >'+jQuery(this).text()+'</option>');
		});
		jQuery(this).remove();
	});
    if(jQuery.browser.msie)
        jQuery('nav#subnav-utility').wrapInner('<div class="bg"/>');
}
jQuery(function(){
	if(jQuery.browser.msie){
		jQuery('.group-ex.results .day div').css('width', function(){
			return jQuery(this).width();
		})
	}

    jQuery('iframe').each(function(){
           var url = jQuery(this).attr("src");
            var char = "?";
          if(url.indexOf("?") != -1){
                  var char = "&";
           }
            jQuery(this).attr("src",url+char+"wmode=transparent");
    });
	jQuery('.group-ex article')
		.hover(function(){
			jQuery(this).find('p.description').toggleClass('hidden');
		})
		.find('p.description')
		.css('height', function(){
			return jQuery(this).outerHeight();
		})
		.addClass('hidden');
		
	jQuery('nav.primary > ul > li').each(function(i, el){
		if(i == 5) jQuery(this).addClass('last');
		if(i >= 6) jQuery(this).remove();
	})

	// jQuery('.group-ex .location a').live('click', function(){
	//	var maplocation = jQuery(this).attr('href').match(/(-?\d+\.\d+)/g),
	//		locationtitle = jQuery(this).attr('href').match(/\((.*)\)/)[1],
	//		address = jQuery(this).attr('href').match(/q=(.*)\+(.*)\(/),
	//		latlng = new google.maps.LatLng(maplocation[0], maplocation[1]),
	//		myOptions = {
	//						zoom: 10,
	//						center: latlng,
	//						mapTypeId: google.maps.MapTypeId.ROADMAP
	//					},
	//		mapwindow = window.open('', 'map', 'width=500,height=350'),
	//		html =	'<html><head><title>View Location</title>'+
	//				'<link rel="stylesheet" type="text/css" href="/amm/themes/2011_ymca/css/style.css"></head>'+
	//				'<link href="//fast.fonts.com/cssapi/8c6351e2-e650-494f-a5ef-d84a9b6c8751.css" rel="stylesheet" type="text/css" />'+
	//				'<body style="height: 100%"><a target="_blank" style="position: absolute; right: 10px; z-index: 300; background: white; padding: 2px 3px; border-radius: 3px; bottom: 20px; font-weight: bold;" href="'+jQuery(this).attr('href')+'">View in Google Maps</a><div id="gmap"/></body></html>';
	//	mapwindow.document.write(html);
	//	jQuery(mapwindow.document).find('#gmap').height('100%').width('100%');
	//	var map = new google.maps.Map(jQuery(mapwindow.document).find('#gmap')[0], myOptions),
	//		marker = new google.maps.Marker({
	//		position: latlng,
	//		title: locationtitle
	//		}),
	//	info  =  new google.maps.InfoWindow({content:'<h3>'+locationtitle+'</h3><p>'+address[1]+'<br/>'+address[2]+'</p>'}).open(map, marker);
	//	marker.setMap(map);
	//	return false;
	// });
	if(jQuery('#main.home').length > 0) jQuery('body').addClass('home');
	if(jQuery.browser.msie){
		jQuery('.home .promo, article.schedules, .module, #subnav-promos div.promos .promo, nav#subnav, #promos .promo').wrapInner('<div class="bg"/>');
		jQuery('.button a, nav.primary header, .button-blue a, nav.primary ul ul .active a, '+
				'.membership .utility .membership a, .locations .utility .locations a, .home .utility .home a,'+
				'.schedules__events .utility .schedules__events a, .about .utility .about a, '+
				'.contact .utility .contact a, .sign_in .utility .sign_in a, button').wrapInner('<span/>');
		jQuery('body > footer section li:last-child').addClass('last');
		jQuery('blockquote').prepend('<span class="quote">&ldquo;</span>')
		jQuery('.ie7 nav.primary a:contains(Health & Fitness)').width(112);
		jQuery('.ie7 body > footer section li:first-child').addClass('first');
		jQuery('.ie7 .breadcrumbs li:not(li:last)').append(	' >');
	}
	var d = new Date();
	// jQuery('#subnav-utility li.level_2.active, #subnav li.level_3.active').each(function(){
	// 		var result = jQuery(this).clone(),
	// 			parent = jQuery(this).parent('ul');
	// 		jQuery(this).removeClass('active current');
	// 		result.css('margin', '20px 0').prependTo(parent);
	// 	})
	
	jQuery('.ie7 nav.primary table.UL').wrap('<div class="table"/>')
	jQuery('.ie #slideshow').append('<span class="tl corner"/><span class="tr corner"/><span class="bl corner"/><span class="br corner"/><span class="overlay" />');
	jQuery('nav.primary > ul > li.level_1').each(function(i, el){
		var pic = jQuery(this).prepend('<span class="pic"/>').find('span.pic'),
			clone,
			height = jQuery(this).find('ul, .table').first().outerHeight();
		if(jQuery.browser.msie) jQuery(this).addClass('child-'+(i+1));
		clone = pic
			.clone()
			.addClass('clone')
			.css('opacity', 0)
			.appendTo(pic);

		jQuery(this).each(function(){
			var mask = jQuery(this).children('div'),
				list = mask.find('ul, .table').first(),
				item = jQuery(this),
				otherLists = jQuery('nav.primary li > div > ul, nav.primary li > div > .UL')
								.not(list);
			jQuery(this)
				.mouseenter(function(e){
					if(mask.length <= 0) return;
					mask.css('height',height);
					if(!Modernizr.csstransitions){
						otherLists
							.add(list)
							.clearQueue();
						otherLists.animate({top:-450}, 300);
						list.animate({top:0}, 300);
					}
				})
				.mouseleave(function(e){
					if(list.length <= 0) return;
					setTimeout(
						function(){
							mask.css('height',0);
					}, 0);
					if(!Modernizr.csstransitions){
						otherLists
							.add(list)
							.clearQueue();
						otherLists.animate({top:-450}, 300);
						list.animate({top:-450}, 300);
					}
				});
		});
			
			
			// setTimeout(function(){
            //
			// 	jQuery('nav.primary li.showing')
			// 		.not(el)
			// 		.removeClass('showing')
			// 		.find('div').first()
			// 		.css('height', 0);
			// }, timeout)
			// item.toggleClass('showing');
			
		jQuery(this).hover(function(){
			pic.show();
			clone.animate({
				opacity: 1
			},{
				queue: false,
				duration: 100,
				specialEasing: 'swing'
			});
		},
		function(){
			pic.show();
			clone.animate({
				opacity: 0
			},{
				queue: false,
				duration: 200,
				specialEasing: 'swing'
			});
		});
	});

	jQuery('.no-csstransitions nav.primary li > div').append('<div class="shadow"/>');
	jQuery('.content_wrapper.schedules h2').append('<span class="cal">'+d.getDate()+'</span>');
	jQuery('#slideshow .text-link a:contains("Directions")').addClass('place');
	jQuery('#slideshow .text-link a:contains("Photo")').addClass('photo');
	jQuery('.ie #slideshow .text-link a').wrapInner('<span/>');
	jQuery('.group-ex.results table:nth-child(2n+1)').addClass('odd');
	jQuery('#slideshow .text-link a:contains("Gallery")').addClass('gallery');
	jQuery('article.schedules form').each(function(){
		if(jQuery(this).find('.filter').length == 1){
			var form = jQuery(this);
			jQuery(this)
				.find('select').change(function()
				{
					form.submit();
				}
			);
			jQuery(this).find('button, label').hide();
		}
	})
	
	// CFInstall.check({
	// 			mode: 'overlay',
	// 			url: '//www.google.com/chromeframe?extra=devchannel&user=true'
	// 			});
	
})


function draw(graphic, target, targetWidth){
	target.each(function(){
		jQuery(this).html('');
		var paper = Raphael(this,1,1);
		var set = paper.set();
		jQuery(graphic).each(function(index,value){
			var attrs = Object();
			jQuery.each(this,function(i,val){
				if(i !='type' && i !='path'){
					attrs[i] = val;
				}
			});
			set.push(paper.path(this.path).attr(attrs));
		});
		var mult = targetWidth/set.getBBox().width;
		set.scale(mult,mult,0,0);
		paper.setSize(set.getBBox().width, set.getBBox().height);
	});
}


function myanimation(current, previous, event, ui, percentage){
	//CUSTOM ANIMATIONS
	
	/*	layer 2 will fade in and fade out,
		with a delay before and after with
		a quick fade-in and fade out. */
	
	/* tweaked percent for fade delays */	
	delay = .8
		inPercent = Math.max(0, (percentage-delay)*(1/(1-delay)));
		outPercent = Math.max(0, (1-(percentage+delay))*(1/(1-delay)));

	//Entering Slide
	current.find('.layer-0').css({
		backgroundPosition: percentage*250-250+'px 0px'
	});
	current.find('.layer-1').css({
		left: 0,
		overflow: 'hidden'
	}).children().css({
		position: 'relative',
		left: percentage*500-500
	});
	current.find('.layer-2').css({
		backgroundPosition: -(900-(percentage*900))+'px 0px',
		left: 0
	})
	current.find('.layer-2 .png-fix').css({
		left: -(900-(percentage*900)),
		position: 'absolute'
	});
	current.find('.layer-3').css({
		opacity: inPercent,
		left: -current.position().left
	});
	
	//tweak layering on landing, so link is clickable.
	if(percentage == 1){
		current.find('.layer-1')
			.css({
				'z-index': 10,
				height: 'auto'
				});
	}else{
		previous.find('.layer-1').css('z-index', 1)
		current.find('.layer-1').css('z-index', 1)
	}
	
	// Items leaving
	previous.find('.layer-1').children().css({
		position: 'relative',
		left: -percentage*100
	});
	
	previous.find('.layer-2').css({
		backgroundPosition: '50% 0px',
		left: 0
	});
	previous.find('.layer-2 .png-fix').css({
		right: -percentage*200,
		position: 'absolute',
		left: 'auto'
	});
	previous.find('.layer-3').css({
		opacity: outPercent
	});
	
	//PREVIOUS ITEM ANIMATION OVERRIDE
	

	// previous.find('.layer-2').css({
	// 	backgroundPosition: 450-percentage*600+'px 1px'
	// });	


}

// Parallax custom script

jQuery(function(){
var curr = 0,
	direction = -1,
	max,
	animator,
	autoadvance,
	initialDelay = 3500;
if(!readCookie('bubbleShown')){ 
	initialDelay = initialDelay + 500;
}
if(!jQuery('.slides').length) return;
max = jQuery('.slides').length-1;
setTimeout(function(){
	autoadvance = setInterval(function(){
		advance();
	}, 4500);
	advance();
}, initialDelay);
function advance(){
	direction  = (curr >= max || curr <= 0)? direction*-1:direction;
	curr = curr + direction;
	navigate(jQuery('.parallax'), curr);
}		
function navigate(container, ii){
		var curr, direction;
		container.find('.pager a').removeClass('active');
		container.find('.pager a').eq(curr).addClass('active');
		curr = container.find('.track').slider('value');
		direction = (50*ii - curr)/Math.abs(50*ii-curr);
		if(isNaN(direction)) direction = 0;
		clearTimeout(animator);
		go = function(){
			curr += direction*2;
			jQuery('.track').slider('value', curr);
			// console.log(direction);
			if((curr >= 50*ii && direction > 0) || (curr <= 50*ii && direction < 0)){
				clearTimeout(animator);
			}else{
				animator = setTimeout(go, 0);
			}
		};
		go();
}
	function createCookie(name,value,days) {
		if (days) {
			var date = new Date();
			date.setTime(date.getTime()+(days*24*60*60*1000));
			var expires = "; expires="+date.toGMTString();
		}
		else var expires = "";
		document.cookie = name+"="+value+expires+"; path=/";
	}

	function readCookie(name) {
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for(var i=0;i < ca.length;i++) {
			var c = ca[i];
			while (c.charAt(0)==' ') c = c.substring(1,c.length);
			if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
		}
		return null;
	}

	function eraseCookie(name) {
		createCookie(name,"",-1);
	}
	/* this is not a part of the parallax script, 
	but this makes the amm cleaner by just targeting
	slides and wrapping them */
	
	//return false;
	// jQuery('.home .slides')
	// 	.wrapAll(innerShiv('<section class="parallax"></section>'));
	jQuery(innerShiv('<section class="parallax"/>', false))
		.appendTo(jQuery('.home .slides').parent())
		.append(jQuery('.home .slides'));
	jQuery('nav.utility li.sign_in a').click(function(){
		var login = jQuery('<div/>').load(jQuery(this).attr('href')+' .personify_sso_link', function(data){
			document.location = login.find('a').attr('href');
		})
		return false;
	}) 
	jQuery('.parallax')
		.each(function(i, slideshow){
			var slides,
				container = jQuery(this),
				custom = myanimation,
				maxheight = 0,
				lastvalue = 50,
				block = true,
				animator;
			// set up markup for jquery ui slider 
			jQuery(this)
				.append('<div class="slider"><div class="pager"/><div class="track"/></div>')
				.prependTo('#main')
				.children(':not(.slider)')
				// look for all immediate children of parallax excluding jquery ui slider, one pager per slide
				.each(function(ii, slide){
					var layers = jQuery(this).find('img, .richtext').length,
						slides;
					jQuery('<a>&bull;</a>')
						.appendTo(container.find('.pager'))
						.click(function(){
							clearInterval(autoadvance);
							navigate(container, ii);
							// clearTimeout(animator);
							// container.find('.pager a').removeClass('active');
							// jQuery(this).addClass('active');
							// var curr = container.find('.track').slider('value')
							// 	direction = (50*ii - curr)/Math.abs(50*ii-curr);
							// if(isNaN(direction)) direction = 0;
							// animator = function(){
							// 	curr += direction*5;
							// 	jQuery('.track').slider('value', curr);
							// 	if((curr >= 50*ii && direction > 0) || (curr <= 50*ii && direction < 0)){
							// 		clearTimeout(animator)
							// 	}else{
							// 		setTimeout(arguments.callee, 0);
							// 	}
							// };
							// animator();
					})
					
					// all slides have the same default styling
					jQuery(this)
						.addClass('slide slide-'+ii)
						.css({
							position: 'absolute',
							overflow: 'hidden',
							height: '100%'
						});
					
					// force all items but the first off the stage to the right
					if(ii > 0) jQuery(this).css('left', container.width());
					
					// build up layers with default styling and z-index
					jQuery(this).find('img, .richtext').each(function(iii, layer){
						var temp = jQuery('<div />').addClass('layer layer-'+iii).css({
								width: '100%',
								height: '100%',
								position: 'absolute',
								top: 0,
								zIndex: iii+1,
								backgroundRepeat: 'no-repeat'
								//left: container.width()/layers*iii
							}).appendTo(slide);
							if(ii <= 0) temp.css('left', 0);
						
						if(jQuery(this).html()!='') temp.html(jQuery(this).html());
						if(jQuery(this).is('img')){
							if(!jQuery.browser.msie || jQuery(this).attr('src').match(/(jpg|gif)$/i)){
								temp.css({backgroundImage: 'url('+jQuery(this).attr('src')+')'});
							}else{
								temp
									.css({
										height: '100%',
										width: '100%',
										overflow: 'hidden'
									})
									.append('<div class="png-fix"/>')
									.find('.png-fix')
									.css({
										filter: 'progid:DXImageTransform.Microsoft.AlphaImageLoader' + '(src=\'' + jQuery(this).attr('src') + '\', sizingMethod=\'crop\');',
										height: jQuery(this).height(),
										width: jQuery(this).width()
										})
							}
							jQuery('<img/>')
								.appendTo(container)
								.css('visibility', 'hidden')
								.css('position', 'absolute')
								.load(function(){
									maxheight = Math.max(maxheight, jQuery(this).height());
									container.height(maxheight);
									container.find('.slide').height(maxheight);
									jQuery(this).remove();
								})
								.attr('src', jQuery(this).attr('src'))
						}
					});
					
					jQuery(this).children().not('div.layer').remove();
				});
				
				slides = jQuery(this).find('.slide');
			
				jQuery(this)
					.find('.track')
					.css('z-index', jQuery('.slide').length+1)
					.slider({
						min: 0,
						animate: 100,
						max: 50*(slides.length-1),
						slide: function(event, ui){
							scrub(event, ui);
						},
						change: function(event, ui){
							scrub(event, ui);	
						},
						stop: function(event, ui){							
							block = false;
							container.css('cursor', 'default');
							jQuery('.parallax .ui-slider-handle').blur();
						}
					});
				var test = setInterval(function(){
					var value = container.find('.track').slider('value'),
						next;
					if(lastvalue == value && !block){
						block = true;
						next = (value)/50;
						if(next - Math.floor(next) > .8 || next - Math.floor(next) < .2){
							container.find('.track').slider('option','animate', 300).slider('value', Math.round(next)*50);
						}
					}
					lastvalue = value;
				}, 100)
				container
					.find('.track')
					//.slider('value', 50*Math.floor(slides.length/2));
					.slider('value', 0);
				
				if(!readCookie('bubbleShown')){
					container
						.find('.ui-slider-handle')
						.append('<div class="bubble">Drag to Explore</div>')
						.find('.bubble')
						.css({
							left: '50%',
							position: 'absolute',
							display: 'block',
							marginLeft: -container.find('.bubble').outerWidth()/2,
							top: -container.find('.bubble').outerHeight()-5
						});
					container.find('.ui-slider-handle')
						.mousedown(function(){
							jQuery(this)
								.find('.bubble')
								.fadeOut(300);
							createCookie('bubbleShown', true);
						});
				}
				container.find('.pager a')
					.eq(Math.round(container.find('.track').slider('value'))*50)
					.addClass('active');
				container.css({cursor: 'default'});
				
				jQuery('.parallax').mousewheel(function(event, delta, deltaX, deltaY) {
					block = false;
					var val = jQuery('.parallax .track').slider('value')
					jQuery('.parallax .track').slider('value', val+deltaX*10)
					if(Math.abs(deltaX) > .05){
						event.preventDefault();	
						event.stopPropagation();
					} 
			   	});
				
				function scrub(event, ui){
					var increment,
						percentage = ((ui.value%50)/50 == 0)? 1:(ui.value%50)/50,
						curr = Math.ceil(ui.value/50),
						current = jQuery(slides[curr]),
						prev = ((curr-1) >= 0) ? curr-1 : slides.length-1;
						previous = ((curr-1) >= 0) ? jQuery(slides[curr-1]) :jQuery(slides[slides.length-1]),
						increment = container.width()/current.children().length*3;
					if(event.type == 'slide') container.css('cursor', 
						function(){
							if (jQuery.browser.mozilla) {
								return '-moz-grabbing';
							}else if (jQuery.browser.webkit) {
								return '-webkit-grabbing';
							}else {
								return 'move';
						}});
					
					// Reset all elements just in case
					
					current.css('overflow','visible');
					slides
						.not(':eq('+curr+')')
						.css('overflow', 'hidden')
						.not(':eq('+prev+')')
						.css({
							left  : container.width(),
							width : 0
							});
					previous.css('left', 0)
						.find('.layer')
						.css('left',0);
					current
						.width('100%')
						
					previous
						.width(container.width()-(container.width()*percentage))

					current.children().each(function(ii, el){
						var left = increment*ii - increment*ii*percentage;
						jQuery(this).css('left', left);
					});
					
					current.css('left', container.width()-(container.width()*percentage));
					
					container.find('.pager a')
						.removeClass('active')
						.eq(Math.round(ui.value/50))
						.addClass('active');
					
					custom(current, previous, event, ui, percentage);
				}		
		});
});

jQuery("[placeholder]").textPlaceholder();



if (typeof(Number.prototype.toRad) === "undefined") {
  Number.prototype.toRad = function() {
    return this * Math.PI / 180;
  }
}

init();
