(function (XHR) {
    'use strict';

    window.__ajaxRequestsInProcess = 0;

    var send = XHR.prototype.send;

    XHR.prototype.send = function () {
        var onreadystatechange = this.onreadystatechange;
        // Request started.
        window.__ajaxRequestsInProcess++;

        this.onreadystatechange = function () {
            // Request ended.
            // @link https://developer.mozilla.org/en/docs/Web/API/XMLHttpRequest/readyState
            if (4 === this.readyState) {
                window.__ajaxRequestsInProcess--;
            }

            onreadystatechange.apply(this, arguments);
        };

        send.apply(this, arguments);
    };
})(XMLHttpRequest);
