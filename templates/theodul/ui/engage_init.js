/*global requirejs*/
requirejs.config({
	baseUrl: '/%iliasbasedir%/Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/templates/theodul/ui/js/lib',
	paths: {
		engage: '../engage',
		plugins: '/%iliasbasedir%/Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/templates/theodul/plugin/*/static'
	},
    shim: {
        "bootstrap": {
            //script dependencies
            deps: ["jquery"],
            //global variable
            exports: "Bootstrap"
        },
        "backbone": {
            //script dependencies
            deps: ["underscore", "jquery"],
            //global variable
            exports: "Backbone"
        },
        "underscore": {
            //global variable
            exports: "_"
        },
        "jquery.mobile": {
            //script dependencies
            deps: ["jquery"]
        },
        "mousetrap": {
            exports: "Mousetrap"
        },
        "moment": {
            exports: "Moment"
        },
        "basil": {
            exports: "Basil"
        }
    }
});

var PLUGIN_MANAGER_PATH = '/%iliasbasedir%/Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/templates/theodul/manager/list.json';
var PLUGIN_PATH = '/%iliasbasedir%/Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/templates/theodul/plugin/';
//start core logic
require(["engage/engage_core"]);
