define(['jquery'], function($) {
    'use strict';

    var actualVersion = $.fn.jquery;

    // Bootstrap 3.3.6 rejects jQuery 3 solely through its startup version
    // guard. The player uses only APIs that remain compatible.
    $.fn.jquery = '2.2.0';

    return {
        restore: function() {
            $.fn.jquery = actualVersion;
        }
    };
});
