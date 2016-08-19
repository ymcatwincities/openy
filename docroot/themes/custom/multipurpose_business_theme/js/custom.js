/* --------------------------------------------- 
* Filename:     custom.js
* Version:      1.0.0 (2016-01-30)
* Website:      http://www.zymphonies.com
* Description:  Global Script
* Author:       Zymphonies Team
                info@zymphonies.com
-----------------------------------------------*/

jQuery(document).ready(function($){

	// Flex slider
	$('.flexslider').flexslider({
	    animation: "fade",
	    before: function(slider){
		$(slider).find(".flex-active-slide").find('.caption-inner').each(function(){
			$(this).removeClass("animated fadeInUp");
			});
	    },
	    after: function(slider){
			$(slider).find(".flex-active-slide").find('.caption-inner').addClass("animated fadeInUp");
	    },
	});
	
	// Testimonials
	$(".region-testimonials .view-wrap").owlCarousel({
	    items : 1,
	    itemsCustom : false,
	    itemsDesktop : [1199,1],
	    itemsDesktopSmall : [980,1],
	    itemsTablet: [768,1],
	    itemsTabletSmall: false,
	    itemsMobile : [479,1],
	    singleItem : false,
	    itemsScaleUp : false,
	});

	//Main menu
	$('#main-menu').smartmenus();
	
	//Mobile menu toggle
	$('.navbar-toggle').click(function(){
		$('.region-primary-menu').slideToggle();
	});

	//Mobile dropdown menu
	if ( $(window).width() < 767) {
		$(".region-primary-menu li a:not(.has-submenu)").click(function () {
			$('.region-primary-menu').hide();
	    });
	}

	//Mobile menu
	$('.navbar-toggle').click(function(){
	  $(this).toggleClass('openMenu');
	});

	//Gallery image
	$(".gallery-image a").colorbox({rel:'group1'});

  	// get the action filter option item on page load
 	var $filterType = $('#filterOptions li.active a').attr('class');
	
  	// get and assign the ourHolder element to the
	// $holder varible for use later
	var $holder = $('ul.ourHolder');

	// clone all items within the pre-assigned $holder element
	var $data = $holder.clone();

  	// attempt to call Quicksand when a filter option
	// item is clicked
	$('.filterOptions li a').click(function(e) {
		// reset the active class on all the buttons
		$('.filterOptions li').removeClass('active');
		
		// assign the class of the clicked filter option
		// element to our $filterType variable
		var $filterType = $(this).attr('class');
		$(this).parent().addClass('active');
		
		if ($filterType == 'all') {
			// assign all li items to the $filteredData var when
			// the 'All' filter option is clicked
			var $filteredData = $data.find('li');
		} 
		else {
			// find all li elements that have our required $filterType
			// values for the data-type element
			var $filteredData = $data.find('li[data-type=' + $filterType + ']');
		}
		
		// call quicksand and assign transition parameters
		$holder.quicksand($filteredData, {
			duration: 800,
			easing: 'easeInOutQuad'
		});
		return false;
	});
});

(function ($) {
  Drupal.behaviors.openy_theme = {
    attach: function (context, settings) {
      var showChar = 100;
      var ellipsestext = "...";
      var moretext = Drupal.t('read more');
      var lesstext = "less";
      $('.bottom-widgets .row .region p:not(:first-child)').each(function () {
        if ($(this).find('.morelink').length === 0) {
          var content = $(this).html();
          if (content.length > showChar) {
            var c = content.substr(0, showChar);
            var h = content.substr(showChar - 1, content.length - showChar);
            var html = c + '<span class="moreellipses">' + ellipsestext + '&nbsp;</span><span class="morecontent"><span>' + h + '</span>&nbsp;&nbsp;<a href="" class="morelink">' + moretext + '</a></span>';
            $(this).html(html);
          }
        }
      });

      $(".morelink").click(function () {
        if ($(this).hasClass("less")) {
          $(this).removeClass("less");
          $(this).html(moretext);
        } else {
          $(this).addClass("less");
          $(this).html(lesstext);
        }
        $(this).parent().prev().toggle();
        $(this).prev().toggle();
        return false;
      });
    }
  };

})(jQuery);
