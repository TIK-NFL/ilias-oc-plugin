/* global require.js config */
requirejs.config({
    baseUrl: '/%iliasbasedir%/Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/templates/theodul/ui/js/lib',
    paths: {
        engage: '../engage',
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
            exports: "Bootbox"
        }
    }
});

var PLUGIN_PATH = '/%iliasbasedir%/Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/templates/theodul/plugin/';
// start core logic
require(["engage/core"]);
