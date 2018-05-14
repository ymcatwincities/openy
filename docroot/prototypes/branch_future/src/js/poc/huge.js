(function(){

    function updateJson() {
        var $active = $(".page.active");
        var $next = $active.next();
        if ($next.length === 0) {
            $next = $(".page").first();
        }
        $active.fadeOut('slow', function() {
            $(this).removeClass('active');
            $next.fadeIn('slow').addClass('active');
        });
        $('.title').text($('html').attr('class')).css({fontSize: 13});
    };

    function timedUpdate () {
        updateJson();
        setTimeout(timedUpdate, 5000);
    };

    timedUpdate();

})();
