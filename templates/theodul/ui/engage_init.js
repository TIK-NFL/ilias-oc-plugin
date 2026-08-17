/* global require.js config */
requirejs.config({
    // baseUrl: "js/lib", ILPATCH
    baseUrl: ILIAS_THEODUL_PATH + 'ui/js/lib',
    urlArgs: 'v=6',
    paths: {
        engage: "../engage",
        // plugins: "/engage/plugin/*/static" ILPATCH
        plugins: ILIAS_THEODUL_PATH + 'plugin/*/static'

    },
    shim: {
        "bootstrap": {
            deps: ["jquery", "engage/bootstrap_jquery_compat"],
            init: function($, compatibility) {
                compatibility.restore();
            }
        },
        "backbone": {
            deps: ["underscore", "jquery"],
            exports: "Backbone"
        },
        "underscore": {
            exports: "_"
        },
        "mousetrap": {
            exports: "Mousetrap"
        },
        "moment": {
            exports: "Moment"
        },
        "basil": {
            exports: "Basil"
        },
        "bowser": {
            exports: "Bowser"
        },
        "bootbox": {
            deps: ["bootstrap"],
            exports: "Bootbox"
        }
    }
});

// Keep ILIAS' jQuery instance. Loading Theodul's bundled jQuery globally
// replaces it and loses ILIAS plugins such as jsTree.
if (!requirejs.defined('jquery')) {
    define('jquery', [], function() {
        return window.jQuery;
    });
}
// var PLUGIN_PATH = "/engage/theodul/plugin/";
var PLUGIN_PATH = ILIAS_THEODUL_PATH + 'plugin/';
// start core logic
require(["engage/core"]);
