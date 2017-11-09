define([ "backbone", "engage/core" ], function(Backbone, Engage) {
	"use strict";
	var USERTRACKING_ENDPOINT = ILIAS_THEODUL_PATH + "../../MHData/"
			+ "usertracking";
	var USERTRACKING_ENDPOINT_STATISTICS = USERTRACKING_ENDPOINT
			+ "/statistic.json";

	var mediaPackageID = Engage.model.get("urlParameters").id;
	if (!mediaPackageID) {
		mediaPackageID = "";
	}

	var StatisticsModel = Backbone.Model.extend({
		urlRoot : USERTRACKING_ENDPOINT_STATISTICS,
		initialize : function() {
			this.update();
		},
		update : function() {
			// request model data
			Engage.log("MhConnection: Updating statistic model");
			this.fetch({
				data : {
					id : mediaPackageID
				},
				success : function(model) {
					model.set("available", "available");
				},
				error: function (model, response) {
					Engage.log("MhConnection: Error statistic model: " + response);
					model.set("available", "notavailable");
				}
			});
		},
		defaults: {
			"available": "pending"
		}
	});

	return StatisticsModel;
});
