(function () {
    'use strict';

    window.errors = [];

    var catchError = function (error) {
        if (typeof(error) !== 'object') {
            return;
        }

        window.errors.push(error);
    };

    window.onerror = function (message, url, line, column) {
        catchError({
            message: message,
            location: url + ':' + line + ':' + column
        });
    };
})();
