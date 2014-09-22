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
define(["require", "jquery", "underscore", "backbone", "engage/engage_core"], function(require, $, _, Backbone, Engage) {
    "use strict";
    var PLUGIN_NAME = "Engage Custom Matterhorn Endpoint Connection";
    var PLUGIN_TYPE = "engage_custom";
    var PLUGIN_VERSION = "1.0";
    var PLUGIN_TEMPLATE = "none";
    var PLUGIN_TEMPLATE_MOBILE = "none";
    var PLUGIN_TEMPLATE_EMBED = "none";
    var PLUGIN_STYLES = [
        ""
    ];
    var PLUGIN_STYLES_MOBILE = [
        ""
    ];
    var PLUGIN_STYLES_EMBED = [
        ""
    ];

    var plugin;
    var events = {
        mediaPackageModelError: new Engage.Event("MhConnection:mediaPackageModelError", "A mediapackage model error occured", "trigger"),
        plugin_load_done: new Engage.Event("Core:plugin_load_done", "when the core loaded the event successfully", "handler"),
        getMediaInfo: new Engage.Event("MhConnection:getMediaInfo", "", "handler"),
        getMediaPackage: new Engage.Event("MhConnection:getMediaPackage", "", "handler")
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
            break;
            isEmbedMode = true;
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
    var SEARCH_ENDPOINT = "/%iliasbasedir%/Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/MHData/episode.json";
    var USERTRACKING_ENDPOINT = "/%iliasbasedir%/Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/templates/usertracking";
    var USERTRACKING_ENDPOINT_FOOTPRINTS = "/footprint.json";
    var USERTRACKING_ENDPOINT_STATS = "/stats.json";

    /* don"t change these variables */
    var mediaPackageID = "";
    var initCount = 1;
    var mediaPackage; // mediaPackage data
    var mediaInfo; // media info like video tracks and attachments

    var MediaPackageModel = Backbone.Model.extend({
        urlRoot: SEARCH_ENDPOINT,
        initialize: function() {
            Engage.log("MhConnection: Init MediaPackageModel");
            this.update();
        },
        update: function() {
            // request model data
            this.fetch({
                data: {
                    id: mediaPackageID
                },
                success: function(model) {
                    var mediaPackage; // Mediapackage data
                    if (model.attributes && model.attributes["search-results"] && model.attributes["search-results"].result) {
                        mediaPackage = model.attributes["search-results"].result;
                        if (mediaPackage) {
                            // format silent the model data, see dublin core for reference names
                            if (mediaPackage.mediapackage) {
                                if (mediaPackage.mediapackage.media && mediaPackage.mediapackage.media.track) {
                                    if (!mediaPackage.mediapackage.media.track.length) {
                                        model.attributes.tracks = new Array();
                                        model.attributes.tracks.push(mediaPackage.mediapackage.media.track);
                                    } else {
                                        model.attributes.tracks = mediaPackage.mediapackage.media.track;
                                    }
                                }
                                if (mediaPackage.mediapackage.attachments.attachment) {
                                    if (!mediaPackage.mediapackage.attachments.attachment.length) {
                                        model.attributes.attachments = new Array();
                                        model.attributes.attachments.push(mediaPackage.mediapackage.attachments.attachment);
                                    } else {
                                        model.attributes.attachments = mediaPackage.mediapackage.attachments.attachment;
                                    }
                                }
                                if (mediaPackage.mediapackage.seriestitle) {
                                    model.attributes.series = mediaPackage.mediapackage.seriestitle;
                                }
                            }
                            if (mediaPackage.dcTitle) {
                                model.attributes.title = mediaPackage.dcTitle;
                            }
                            if (mediaPackage.dcCreator) {
                                model.attributes.creator = mediaPackage.dcCreator;
                            }
                            if (mediaPackage.dcCreated) {
                                model.attributes.date = mediaPackage.dcCreated;
                            }
                            if (mediaPackage.dcDescription) {
                                model.attributes.description = mediaPackage.dcDescription;
                            }
                            if (mediaPackage.dcSubject) {
                                model.attributes.subject = mediaPackage.dcSubject;
                            }
                            if (mediaPackage.dcContributor) {
                                model.attributes.contributor = mediaPackage.dcContributor;
                            }
                            if (mediaPackage.segments && mediaPackage.segments.segment) {
                                model.attributes.segments = mediaPackage.segments.segment;
                            }
                        }
                        model.trigger("change");
                    } else {
                        Engage.trigger(plugin.events.mediaPackageModelError.getName(), "Media information could not be loaded successfully.");
                    }
                }
            });
        },
        defaults: {
            "title": "",
            "creator": "",
            "date": "",
            "description": "",
            "subject": "",
            "tracks": {},
            "attachments": {}
        }
    });

    var ViewsModel = Backbone.Model.extend({
        urlRoot: USERTRACKING_ENDPOINT + USERTRACKING_ENDPOINT_STATS,
        initialize: function() {
            Engage.log("MhConnection: Init ViewsModel");
            this.put();
        },
        put: function() {
            Engage.log("MhConnection: Adding user to viewers");
            var thisModel = this;
         /*   $.ajax({
                type: "PUT",
                url: USERTRACKING_ENDPOINT + USERTRACKING_ENDPOINT_STATS,
                data: {
                    id: mediaPackageID,
                    in : 0,
                    out: 0,
                    type: "VIEWS"
                },
                success: function(result) {
                    thisModel.update();
                }
            });*/
        },
        update: function() {
            // request model data
            Engage.log("MhConnection: Updating views model");
            this.fetch({
                data: {
                    id: mediaPackageID
                },
                success: function(model) {
                    model.trigger("change");
                }
            });
        },
        defaults: {
            "stats": {
                "views": 0
            }
        }
    });

    var FootprintModel = Backbone.Model.extend({
        defaults: {
            "position": 0,
            "views": 0
        }
    });

    var FootprintCollection = Backbone.Collection.extend({
        model: FootprintModel,
        url: USERTRACKING_ENDPOINT + USERTRACKING_ENDPOINT_FOOTPRINTS,
        initialize: function() {
            this.update();
        },
        put: function(from, to) {
            Engage.log("MhConnection: Setting footprint at " + from);
            var thisModel = this;
            // put to mh endpoint
            $.ajax({
                type: "PUT",
                url: USERTRACKING_ENDPOINT,
                data: {
                    id: mediaPackageID,
                    in : from,
                    out: to,
                    type: "FOOTPRINT"
                },
                success: function(result) {
                    // update current footprint model
                    thisModel.update();
                }
            });
        },
        update: function() {
            // request collection data
            this.fetch({
                data: {
                    id: mediaPackageID
                },
                success: function(collection) {
                    collection.trigger("change");
                }
            });
        },
        parse: function(response) {
            return response.footprints.footprint;
        }
    });

    /**
     * extractMediaInfo
     */
    function extractMediaInfo() {
        if (mediaPackage) {
            mediaInfo = {};
            mediaInfo.tracks = mediaPackage.mediapackage.media.track;
            mediaInfo.attachments = mediaPackage.mediapackage.attachments.attachment;
            mediaInfo.title = mediaPackage.dcTitle;
            mediaInfo.creator = mediaPackage.dcCreator;
            mediaInfo.date = mediaPackage.dcCreated;
        } else {
            Engage.trigger(plugin.events.mediaPackageModelError.getName(), "No media information are available.");
        }
    }

    /**
     * callSearchEndpoint
     *
     * @param callback
     */
    function callSearchEndpoint(callback) {
        $.ajax({
            url: SEARCH_ENDPOINT,
            data: {
                id: mediaPackageID
            },
            cache: false
        }).done(function(data) {
            // split search results
            if (data && data["search-results"] && data["search-results"].result) {
                mediaPackage = data["search-results"].result;
                extractMediaInfo();
            } else {
                Engage.trigger(plugin.events.mediaPackageModelError.getName(), "A requested search endpoint is currently not available.");
            }
            callback();
        });
    }

    /**
     * Initialize the plugin
     */
    function initPlugin() {
        Engage.model.set("mediaPackage", new MediaPackageModel());
        Engage.model.set("views", new ViewsModel());
        Engage.model.set("footprints", new FootprintCollection());
    }

    // init event
    Engage.log("MhConnection: Init");
    var relative_plugin_path = Engage.getPluginPath("EngagePluginCustomMhConnection");

    // get ID
    mediaPackageID = Engage.model.get("urlParameters").id;
    if (!mediaPackageID) {
        mediaPackageID = "";
    }

    Engage.on(plugin.events.getMediaInfo.getName(), function(callback) {
        // check if data is already loaded
        if (!mediaPackage && !mediaInfo) {
            // get info from search endpoint
            callSearchEndpoint(function() {
                // trigger callback
                callback(mediaInfo);
            });
        } else {
            // trigger callback
            callback(mediaInfo);
        }
    });

    Engage.on(plugin.events.getMediaPackage.getName(), function(callback) {
        // check if data is already loaded
        if (!mediaPackage) {
            // get info from search endpoint
            callSearchEndpoint(function() {
                // trigger callback
                callback(mediaPackage);
            });
        } else {
            // trigger callback
            callback(mediaPackage);
        }
    });

    // all plugins loaded
    Engage.on(plugin.events.plugin_load_done.getName(), function() {
        Engage.log("MhConnection: Plugin load done");
        initCount -= 1;
        if (initCount <= 0) {
            initPlugin();
        }
    });

    return plugin;
});
