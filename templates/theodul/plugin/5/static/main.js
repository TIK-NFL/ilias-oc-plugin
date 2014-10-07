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
 */
/*jslint browser: true, nomen: true*/
/*global define*/
define(["require", "jquery", "underscore", "backbone", "engage/engage_core", "moment"], function(require, $, _, Backbone, Engage, Moment) {
    "use strict";
    var PLUGIN_NAME = "Basic Engage Description";
    var PLUGIN_TYPE = "engage_description";
    var PLUGIN_VERSION = "1.0";
    var PLUGIN_TEMPLATE = "template.html";
    var PLUGIN_TEMPLATE_MOBILE = "template_mobile.html";
    var PLUGIN_TEMPLATE_EMBED = "template_embed.html";
    var PLUGIN_STYLES = [
        "style.css"
    ];
    var PLUGIN_STYLES_MOBILE = [
        "style_mobile.css"
    ];
    var PLUGIN_STYLES_EMBED = [
        "style_embed.css"
    ];

    var plugin;
    var events = {
        plugin_load_done: new Engage.Event("Core:plugin_load_done", "", "handler"),
        mediaPackageModelError: new Engage.Event("MhConnection:mediaPackageModelError", "", "handler")
    };

    var isDesktopMode = false;
    var isEmbedMode = false;
    var isMobileMode = false;

    // desktop, embed and mobile logic
    switch (Engage.model.get("mode")) {
        case "mobile":
            plugin = {
                name: PLUGIN_NAME,
                type: PLUGIN_TYPE,
                version: PLUGIN_VERSION,
                styles: PLUGIN_STYLES_MOBILE,
                template: PLUGIN_TEMPLATE_MOBILE,
                events: events
            };
            isMobileMode = true;
            break;
        case "embed":
            plugin = {
                name: PLUGIN_NAME,
                type: PLUGIN_TYPE,
                version: PLUGIN_VERSION,
                styles: PLUGIN_STYLES_EMBED,
                template: PLUGIN_TEMPLATE_EMBED,
                events: events
            };
            isEmbedMode = true;
            break;
        case "desktop":
        default:
            plugin = {
                name: PLUGIN_NAME,
                type: PLUGIN_TYPE,
                version: PLUGIN_VERSION,
                styles: PLUGIN_STYLES,
                template: PLUGIN_TEMPLATE,
                events: events
            };
            isDesktopMode = true;
            break;
    }

    /* change these variables */
    // nothing to see here...

    /* don"t change these variables */
    var initCount = 2;
    var id_engage_description = "engage_description";
    var mediapackageChange = "change:mediaPackage";
    var mediapackageError = false;

    // view //

    var DescriptionView = Backbone.View.extend({
        el: $("#" + id_engage_description), // every view has a element associated with it
        initialize: function(mediaPackageModel, template) {
            this.model = mediaPackageModel;
            this.template = template;
            // bind the render function always to the view
            _.bindAll(this, "render");
            // listen for changes of the model and bind the render function to this
            this.model.bind("change", this.render);
        },
        render: function() {
            if (!mediapackageError) {
                var tempVars = {
                    title: this.model.get("title"),
                    creator: this.model.get("creator"),
                    date: this.model.get("date")
                };
                // try to format the date
                if (Moment(tempVars.date) != null) {
                    tempVars.date = Moment(tempVars.date).format("MMMM Do YYYY");
                }
                // compile template and load into the html
                this.$el.html(_.template(this.template, tempVars));
            }
        }
    });

    function initPlugin() {
        // only init if plugin template was inserted into the DOM
        if (isDesktopMode && plugin.inserted) {
            Moment.locale("en", {
                // customizations
            });
            // create a new view with the media package model and the template
            new DescriptionView(Engage.model.get("mediaPackage"), plugin.template);
            Engage.on(plugin.events.mediaPackageModelError.getName(), function(msg) {
                mediapackageError = true;
            });
        }
    }

    if (isDesktopMode) {
        // init event
        Engage.log("Description: Init");
        var relative_plugin_path = Engage.getPluginPath("EngagePluginDescription");

        // listen on a change/set of the mediaPackage model
        Engage.model.on(mediapackageChange, function() {
            initCount -= 1;
            if (initCount <= 0) {
                initPlugin();
            }
        });

        // all plugins loaded
        Engage.on(plugin.events.plugin_load_done.getName(), function() {
            initCount -= 1;
            if (initCount <= 0) {
                initPlugin();
            }
        });
    }

    return plugin;
});
