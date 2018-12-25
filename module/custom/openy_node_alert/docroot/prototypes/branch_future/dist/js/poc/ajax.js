(function(){

    var i = 1;

    function updateJson() {
        $.get('/prototypes/branch_future/dist/js/data/data-00' + i + '.json?' + (new Date()).getTime(), function(data) {
            $(".result").html(data['data-string']);
        });

        i++;
        if (i > 2) {
            i = 1;
        }
    };

    function timedUpdate () {
        updateJson();
        setTimeout(timedUpdate, 5000);
    };

    timedUpdate();

})();
