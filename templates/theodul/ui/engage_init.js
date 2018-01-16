/* global require.js config */
requirejs.config({
    // baseUrl: "js/lib", ILPATCH
    baseUrl: ILIAS_THEODUL_PATH + 'ui/js/lib',
    paths: {
        engage: "../engage",
        // plugins: "/engage/plugin/*/static" ILPATCH
        plugins: ILIAS_THEODUL_PATH + 'plugin/*/static'

    },
    shim: {
        "bootstrap": {
            deps: ["jquery"],
            exports: "Bootstrap"
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
// var PLUGIN_PATH = "/engage/theodul/plugin/";
var PLUGIN_PATH = ILIAS_THEODUL_PATH + 'plugin/';
// start core logic
require(["engage/core"]);
