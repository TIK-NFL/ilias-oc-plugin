/**
 * Licensed to The Apereo Foundation under one or more contributor license
 * agreements. See the NOTICE file distributed with this work for additional
 * information regarding copyright ownership.
 * 
 * 
 * The Apereo Foundation licenses this file to you under the Educational
 * Community License, Version 2.0 (the "License"); you may not use this file
 * except in compliance with the License. You may obtain a copy of the License
 * at:
 * 
 * http://opensource.org/licenses/ecl2.txt
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 * 
 */
/* jslint browser: true */
/* global define */
define([ "jquery", "underscore", "backbone", "engage/core", "./lib/d3",
		"./utils", "./models/statistics" ], function($, _, Backbone, Engage,
		d3, Utils, StatisticsModel) {
	"use strict";

	var insertIntoDOM = true;
	var PLUGIN_NAME = "Statistics";
	var PLUGIN_TYPE = "engage_tab";
	var PLUGIN_VERSION = "1.0";
	var PLUGIN_TEMPLATE_DESKTOP = "templates/desktop.html";
	var PLUGIN_TEMPLATE_MOBILE = "templates/mobile.html";
	var PLUGIN_TEMPLATE_EMBED = "templates/embed.html";
	var PLUGIN_STYLES_DESKTOP = [ "styles/desktop.css" ];
	var PLUGIN_STYLES_EMBED = [ "styles/embed.css" ];
	var PLUGIN_STYLES_MOBILE = [ "styles/mobile.css" ];

	var plugin;
	var events = {
		plugin_load_done : new Engage.Event("Core:plugin_load_done", "",
				"handler"),
		mediaPackageModelError : new Engage.Event(
				"MhConnection:mediaPackageModelError", "", "handler")
	};

	var isDesktopMode = false;
	var isEmbedMode = false;
	var isMobileMode = false;

	// desktop, embed and mobile logic
	switch (Engage.model.get("mode")) {
	case "embed":
		plugin = {
			insertIntoDOM : insertIntoDOM,
			name : PLUGIN_NAME,
			type : PLUGIN_TYPE,
			version : PLUGIN_VERSION,
			styles : PLUGIN_STYLES_EMBED,
			template : PLUGIN_TEMPLATE_EMBED,
			events : events
		};
		isEmbedMode = true;
		break;
	case "mobile":
		plugin = {
			insertIntoDOM : insertIntoDOM,
			name : PLUGIN_NAME,
			type : PLUGIN_TYPE,
			version : PLUGIN_VERSION,
			styles : PLUGIN_STYLES_MOBILE,
			template : PLUGIN_TEMPLATE_MOBILE,
			events : events
		};
		isMobileMode = true;
		break;
	case "desktop":
	default:
		plugin = {
			insertIntoDOM : insertIntoDOM,
			name : PLUGIN_NAME,
			type : PLUGIN_TYPE,
			version : PLUGIN_VERSION,
			styles : PLUGIN_STYLES_DESKTOP,
			template : PLUGIN_TEMPLATE_DESKTOP,
			events : events
		};
		isDesktopMode = true;
		break;
	}

	/* change these variables */
	var class_tabGroupItem = "tab-group-item";

	/* don't change these variables */
	var viewsModelChange = "change:views";
	var mediapackageChange = "change:mediaPackage";
	var initCount = 3;
	var mediapackageError = false;
	var translations = new Array();
	var locale = "en";

	function initTranslate(language) {
		var path = Engage.getPluginPath("EngagePluginTabStatistics").replace(
				/(\.\.\/)/g, "");
		// var jsonstr = window.location.origin + "/engage/theodul/" + path; //
		// this solution is really bad, fix it... ILPATCH
		var jsonstr = ILIAS_THEODUL_PATH + path;

		Engage.log("Controls: selecting language " + language);
		jsonstr += "language/" + language + ".json";
		$.ajax({
			url : jsonstr,
			dataType : "json",
			success : function(data) {
				if (data) {
					data.value_locale = language;
					translations = data;
					locale = translate("value_locale", locale);
					Utils.setTranslateFunction(translate);
				}
			}
		});
	}

	function translate(str, strIfNotFound) {
		return (translations[str] != undefined) ? translations[str]
				: strIfNotFound;
	}

	var StatisticsTabView = Backbone.View.extend({
		initialize : function(statisticsModel, template) {
			this.setElement($(plugin.container)); // every plugin view has
			// it's own container
			// associated with it
			this.model = statisticsModel;
			this.template = template;
			// bind the render function always to the view
			_.bindAll(this, "render");
			// listen for changes of the model and bind the render function to
			// this
			this.model.bind("change", this.render);
		},
		render : function() {
			if (!mediapackageError
					&& this.model.get("available") === "available") {
				var tempVars = {};

				var options = {
					svgWidth : $(window).width(),
					svgHeight : $(window).width() * 9 / 16
				};

				// compile template and load into the html
				var template = _.template(this.template);
				this.$el.html(template(tempVars));

				$("#engage_tab_" + plugin.name.replace(/\s/g, "_")).text(
						translate("statistics", "Statistics"));

				Utils.showData("#engage_statistics_content",
						this.model.attributes, options);
			} else if (this.model.get("available") === "notavailable") {
				$("#engage_tab_" + plugin.name.replace(/\s/g, "_")).parent()
						.parent("li").detach();
				$("#engage_statistics_content").detach();
			}
		}
	});

	function initPlugin() {
		// only init if plugin template was inserted into the DOM
		if (isDesktopMode && plugin.inserted) {
			initTranslate(Engage.model.get("language"));
			// create a new view with the media package model and the template
			var statisticsTabView = new StatisticsTabView(
					new StatisticsModel(), plugin.template);
			Engage.on(plugin.events.mediaPackageModelError.getName(), function(
					msg) {
				mediapackageError = true;
			});
			statisticsTabView.render();
		}
	}

	if (isDesktopMode) {
		// init event
		Engage.log("Tab:Statistics: Init");

		Engage.model.on(viewsModelChange, function() {
			initCount -= 1;
			if (initCount <= 0) {
				initPlugin();
			}
		});

		// listen on a change/set of the mediaPackage model
		Engage.model.on(mediapackageChange, function() {
			initCount -= 1;
			if (initCount <= 0) {
				initPlugin();
			}
		});

		// all plugins loaded
		Engage.on(plugin.events.plugin_load_done.getName(), function() {
			Engage.log("Tab:Description: Plugin load done");
			initCount -= 1;
			if (initCount <= 0) {
				initPlugin();
			}
		});
	}

	return plugin;
});
