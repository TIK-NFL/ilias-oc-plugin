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
 *   http://opensource.org/licenses/ecl2.txt
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.  See the
 * License for the specific language governing permissions and limitations under
 * the License.
 *
 */
/*jslint browser: true, nomen: true*/
/*global define*/
define(["jquery", "backbone", "engage/core"], function($, Backbone, Engage) {
    "use strict";

    var insertIntoDOM = false;
    var PLUGIN_NAME = "Engage Plugin Custom Usertracking";
    var PLUGIN_TYPE = "engage_custom";
    var PLUGIN_VERSION = "1.0";
    var PLUGIN_TEMPLATE_DESKTOP = "none";
    var PLUGIN_TEMPLATE_MOBILE = "none";
    var PLUGIN_TEMPLATE_EMBED = "none";
    var PLUGIN_STYLES_DESKTOP = [
        ""
    ];
    var PLUGIN_STYLES_EMBED = [
        ""
    ];
    var PLUGIN_STYLES_MOBILE = [
        ""
    ];

    var plugin;
    var events = {
        plugin_load_done: new Engage.Event("Core:plugin_load_done", "", "handler"),
        timeupdate: new Engage.Event("Video:timeupdate", "notices a timeupdate", "handler"),
        play: new Engage.Event("Video:play","notices a play event", "handler"),
        pause: new Engage.Event("Video:pause","notices a pause event", "handler"),
        ended: new Engage.Event("Video:ended","notices a ended event", "handler"),
        slider: new Engage.Event("Slider:stop","notices a slider stop event", "handler"),
        seek: new Engage.Event('Video:seek', 'seek video to a given position in seconds', "handler"),
        mediaPackageModelError: new Engage.Event("MhConnection:mediaPackageModelError", "", "handler")
    };

    var isDesktopMode = false;
    var isEmbedMode = false;
    var isMobileMode = false;

    // desktop, embed and mobile logic
    switch (Engage.model.get("mode")) {
        case "embed":
            plugin = {
                insertIntoDOM: insertIntoDOM,
                name: PLUGIN_NAME,
                type: PLUGIN_TYPE,
                version: PLUGIN_VERSION,
                styles: PLUGIN_STYLES_EMBED,
                template: PLUGIN_TEMPLATE_EMBED,
                events: events
            };
            isEmbedMode = true;
            break;
        case "mobile":
            plugin = {
                insertIntoDOM: insertIntoDOM,
                name: PLUGIN_NAME,
                type: PLUGIN_TYPE,
                version: PLUGIN_VERSION,
                styles: PLUGIN_STYLES_MOBILE,
                template: PLUGIN_TEMPLATE_MOBILE,
                events: events
            };
            isMobileMode = true;
            break;
        case "desktop":
        default:
            plugin = {
                insertIntoDOM: insertIntoDOM,
                name: PLUGIN_NAME,
                type: PLUGIN_TYPE,
                version: PLUGIN_VERSION,
                styles: PLUGIN_STYLES_DESKTOP,
                template: PLUGIN_TEMPLATE_DESKTOP,
                events: events
            };
            isDesktopMode = true;
            break;
    }

    /* don't change these variables */
    var mediapackageChange = "change:mediaPackage";
    var footprintsChange = "change:footprints";
    var videoDataModelChange = "change:videoDataModel";
    var initCount = 4;
    var lastFootprint = undefined;
    var cTime = 0;
    var mediapackageID;
    var mediapackageError = false;
    var footprintStep = 10;

    function initPlugin() {
        mediapackageID = Engage.model.get("urlParameters").id;
        if (!mediapackageID) {
            mediapackageID = "";
            return;
        }

//        function continueAtLastView() {
//            if (!Engage.model.has("time") && Engage.model.get("footprints").getLast() && Engage.model.get("videoDataModel").has("duration")) {
//                var duration = Math.floor(parseInt(Engage.model.get("videoDataModel").get("duration")) / 1000);
//                var second = Engage.model.get("footprints").getLast();
//                if (second < duration) {
//                    Engage.model.set("time", second);
//                }
//            }
//        };
//
//        Engage.model.get("footprints").on("change", continueAtLastView);
//        Engage.model.get("videoDataModel").on("change", continueAtLastView);

        function putFootprint() {
            if (lastFootprint < cTime) {
                Engage.model.get("footprints").put(lastFootprint, cTime);
                lastFootprint = cTime;
            }
        }

        Engage.on(plugin.events.mediaPackageModelError.getName(), function(msg) {
            mediapackageError = true;
        });

        Engage.on(plugin.events.timeupdate.getName(), function(currentTime) {
            if (!mediapackageError) {
                cTime = Math.floor(currentTime);
                if (lastFootprint != undefined) {
                    if (cTime % footprintStep == 0) {
                        putFootprint();
                    }
                } else {
                    lastFootprint = cTime;
                }
            }
        });

        Engage.on(plugin.events.play.getName(), function() {
            Engage.log("Usertracking: Play event");
            if (!mediapackageError) {
                Engage.model.get("footprints").put(-1, 0);
            }
        });

        Engage.on(plugin.events.slider.getName(), function() {
            if (!mediapackageError) {
                putFootprint();
                lastFootprint = undefined;
            }
        });

        Engage.on(plugin.events.pause.getName(), function() {
            if (!mediapackageError) {
                putFootprint();
            }
        });

        Engage.on(plugin.events.ended.getName(), function() {
            if (!mediapackageError) {
                putFootprint();
                lastFootprint = undefined;
            }
        });

        Engage.on(plugin.events.seek.getName(), function(second) {
            if (!mediapackageError) {
                putFootprint();
                lastFootprint = second;
            }
        });
    }

    // init event
    Engage.log("Usertracking: Init");
    var relative_plugin_path = Engage.getPluginPath("EngagePluginCustomUsertracking");

    // mediapackage model created
    Engage.model.on(mediapackageChange, function() {
        initCount -= 1;
        if (initCount <= 0) {
            initPlugin();
        }
    });

    // footprints model created
    Engage.model.on(footprintsChange, function() {
        initCount -= 1;
        if (initCount <= 0) {
            initPlugin();
        }
    });

    // videoDataModel model created
    Engage.model.on(videoDataModelChange, function() {
        initCount -= 1;
        if (initCount <= 0) {
            initPlugin();
        }
    });

    // all plugins loaded
    Engage.on(plugin.events.plugin_load_done.getName(), function() {
        Engage.log("Usertracking: Plugin load done");
        initCount -= 1;
        if (initCount <= 0) {
            initPlugin();
        }
    });

    /**/

    return plugin;
});
