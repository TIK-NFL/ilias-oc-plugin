/**
 *  Copyright 2009-2011 The Regents of the University of California
 *  Licensed under the Educational Community License, Version 2.0
 *  (the "License"); you may not use this file except in compliance
 *  with the License. You may obtain a copy of the License at
 *
 *  http://www.osedu.org/licenses/ECL-2.0
 *
 *  Unless required by applicable law or agreed to in writing,
 *  software distributed under the License is distributed on an "AS IS"
 *  BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express
 *  or implied. See the License for the specific language governing
 *  permissions and limitations under the License.
 *
 */
/*jslint browser: true, nomen: true*/
/*global define*/
define(['require', 'jquery', 'underscore', 'backbone', 'engage/engage_core'], function (require, $, _, Backbone, Engage) {
	var PLUGIN_NAME = "Description";
	var PLUGIN_TYPE = "engage_tab";
	var PLUGIN_VERSION = "0.1";
	var PLUGIN_TEMPLATE = "template.html";
	var PLUGIN_STYLES = ["style.css"];
  var plugin = {
      name: PLUGIN_NAME,
      type: PLUGIN_TYPE,
      version: PLUGIN_VERSION,
      styles: PLUGIN_STYLES,
      template: PLUGIN_TEMPLATE
  };
  
  var initCount = 2; //wait for two inits, plugin load done and mediapackage
  
  var DescriptionTabView = Backbone.View.extend({
    initialize: function(mediaPackageModel, template) {
        this.setElement($(plugin.container)); // Every plugin view has it's own container associated with it
        this.model = mediaPackageModel;
        this.template = template;
        //bound the render function always to the view
        _.bindAll(this, "render");
        //listen for changes of the model and bind the render function to this
        this.model.bind("change", this.render);
    },
    render: function() {
        //format values
        var tempVars = {
            description: this.model.get("description"),
            creator: this.model.get("creator"),
            title: this.model.get("title"),
            series: this.model.get("series"),
            contributor: this.model.get("contributor"),
            date: this.model.get("date")
        };
        if(!tempVars.creator){
          tempVars.creator = "";
        }
        if(!tempVars.description){
          tempVars.description = "";
        }
        if(!tempVars.title){
          tempVars.title = "";
        }
        if(!tempVars.series){
          tempVars.series = "";
        }
        if(!tempVars.contributor){
          tempVars.contributor = "";
        }
        if(!tempVars.date){
          tempVars.date = "";
        }
        // compile template and load into the html
        this.$el.html(_.template(this.template, tempVars));
    }
  });

  function initPlugin() {
    //only init if plugin template was inserted into the DOM
    if(plugin.inserted === true){
      //create a new view with the media package model and the template
      new DescriptionTabView(Engage.model.get("mediaPackage"), plugin.template);
    }
  }
  
	//Init Event
  Engage.log("Tab:Description: init");
  var relative_plugin_path = Engage.getPluginPath('EngagePluginTabDescription');
  Engage.log('Tab:Description: relative plugin path ' + relative_plugin_path); 
  
  Engage.model.on("change:mediaPackage", function() { // listen on a change/set of the mediaPackage model
    initCount -= 1;
    if (initCount === 0) {
        initPlugin();
    }
  });
  
  //All plugins loaded lets do some stuff
  Engage.on("Core:plugin_load_done", function() {   	
    initCount -= 1;
    if (initCount === 0) {
        initPlugin();
    }
  });
   
  return plugin;
});