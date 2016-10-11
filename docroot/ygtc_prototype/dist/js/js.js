(function(){

    function updateClock() {
        var now = moment();
        var second = now.seconds();
        var minute = now.minutes();
        var hour = now.hours();

        $(".header .time").html(moment().tz("America/Belize").format("H:m a"));
    };

    function timedUpdate () {
        updateClock();
        setTimeout(timedUpdate, 1000);
    };

    timedUpdate();
})();
