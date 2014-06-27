/**
 * Copyright 2009-2011 The Regents of the University of California Licensed
 * under the Educational Community License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain a
 * copy of the License at
 * 
 * http://www.osedu.org/licenses/ECL-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 * 
 */
/*jslint browser: true, nomen: true*/
/*global define, CustomEvent*/
define(['require', 'jquery', 'underscore', 'backbone', 'engage/engage_core', 'engage/engage_model'], function (require, $, _, Backbone, EngageCore, EngageModel) {
  //
  'use strict'; // strict mode in all our application
  //
  /*
   * Init logic function
   */
  var initEmbedView = function(){

  }
  /*
   * Logic to insert a plugin with name and type to the player in embed mode
   */
  var insertPluginToDOM = function(plugin) {
    //DEBUG, currently no impl here, so please init no plugin
    plugin.inserted = false;
    //switch plugin type to insert the plugin to the right DOM element and execute custom view code
    switch (plugin.type) {
    case "engage_controls":       

      break;
    case "engage_video":        

      break;        
    case "engage_tab":        

      break;
    case "engage_description":

      break;    
    case "engage_timeline":

    break; 
    default:
    }
  }
  
  /*
   * This function is triggered when all plugins are loaded and inserted into the DOM
   */
  var allPluginsLoadedEvent = function(){

  }
  
  // public functions fo the module
  return {
    initView : initEmbedView,
    insertPlugin : insertPluginToDOM,
    allPluginsLoaded : allPluginsLoadedEvent
  } 
});