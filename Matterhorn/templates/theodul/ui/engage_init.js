/*global requirejs*/
requirejs.config({
  baseUrl: '/ilias44/Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/templates/theodul/ui/js/lib',
  paths: {
    engage: '../engage',
    plugins: '/ilias44/Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/templates/theodul/plugin/*/static'
  },
  shim: {
    'bootstrap': {
      //script dependencies
      deps: ['jquery'],
      //global variable
      exports: 'Bootstrap'
    },
    'backbone': {
      //script dependencies
      deps: ['underscore', 'jquery'],
      //global variable
      exports: 'Backbone'
    },
    'underscore': {
      //global variable
      exports: '_'
    },
    'jquery.mobile': {
      //script dependencies
      deps: ['jquery'],
    },
    'mousetrap': {
      exports: 'Mousetrap'
    }
  }
});
var PLUGIN_MANAGER_PATH = '/ilias44/Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/templates/theodul/manager/list.json';
var PLUGIN_PATH = '/ilias44/Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/templates/theodul/plugin/';
//start core logic
require(["engage/engage_core"]);