(function(){

    function updateClock() {
        var now = moment();
        var second = now.seconds();
        var minute = now.minutes();
        var hour = now.hours();

        $(".header .time").html(moment().tz("America/Chicago").format("H:mma"));
    };

    function timedUpdate () {
        updateClock();
        setTimeout(timedUpdate, 1000);
    };

    timedUpdate();
})();
