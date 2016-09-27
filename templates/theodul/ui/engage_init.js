/* global require.js config */
requirejs.config({
    // baseUrl: "js/lib", ILPATCH
    baseUrl: '/%iliasbasedir%/Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/templates/theodul/ui/js/lib',
    paths: {
        engage: "../engage",
        // plugins: "/engage/plugin/*/static" ILPATCH
        plugins: '/%iliasbasedir%/Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/templates/theodul/plugin/*/static'

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
var PLUGIN_PATH = '/%iliasbasedir%/Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/templates/theodul/plugin/';
// start core logic
require(["engage/core"]);
