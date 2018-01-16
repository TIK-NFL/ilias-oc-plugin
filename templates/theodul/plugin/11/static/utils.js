define([ "./lib/d3" ], function(d3) {
	"use strict";
	/**
	 * create a Time Format String
	 * 
	 * @param {Number}
	 *            d time in seconds
	 * @returns {String} HH:MM:SS
	 */
	function toHHMMSS(d) {
		var hours = Math.floor(d / 3600);
		d -= hours * 3600;
		var minutes = Math.floor(d / 60);
		d -= minutes * 60;
		var seconds = d;

		var string;
		if (hours > 0) {
			string = hours + ":" + (minutes < 10 ? "0" + minutes : minutes) + ":" + (seconds < 10 ? "0" + seconds : seconds);
		} else if (minutes > 0) {
			string = minutes + ":" + (seconds < 10 ? "0" + seconds : seconds);
		} else {
			string = "" + seconds;
		}

		return string;
	}

	var translateFunction = null;

	/**
	 * 
	 * @param {string}
	 *            str
	 * @param {string}
	 *            strIfNotFound
	 * @returns {string}
	 */
	function translate(str, strIfNotFound) {
		return translateFunction ? translateFunction(str, strIfNotFound) : (strIfNotFound ? strIfNotFound : str);
	}

	/**
	 * 
	 * @param {function}
	 *            parTranslatFunction
	 */
	function setTranslateFunction(parTranslatFunction) {
		translateFunction = parTranslatFunction;
	}

	/**
	 * 
	 * @param {String|Node}
	 *            selector
	 * @param {object}
	 *            data
	 * @param {object}
	 *            [options]
	 */
	function showData(selector, data, options) {
		options = options || {};

		var element = d3.select(selector);

		var box = element.node();

		var svgWidth = options.svgWidth || box.clientWidth;
		var svgHeight = options.svgHeight || box.clientHeight;

		var margin = {
			top : 30,
			right : 40,
			bottom : 40,
			left : 10
		};
		var width = svgWidth - margin.left - margin.right;
		var height = svgHeight - margin.top - margin.bottom;

		// An SVG element with a bottom-right origin.
		var svg = element.append("svg").attr("width", "100%").attr("height", "100%").attr("viewBox", "0 0 " + svgWidth + " " + svgHeight).append("g").attr("transform",
				"translate(" + margin.left + "," + margin.top + ")");

		var drawArea = {
			width : width,
			height : height,
			svg : svg
		};

		var mediaData = new MediaData(data);

		mediaData.data.forEach(function(availableData) {
			if (availableData instanceof MappingData) {
				availableData.drawMapping(drawArea);
			}
		});

		var caption = new Caption(drawArea);
		mediaData.data.forEach(function(availableData) {
			caption.add(availableData);
		});
		// A label
		svg.append("text").attr("class", "title").attr("x", width / 2).attr("y", -2).text(mediaData.title);

		function createTimeAxis(time, step, maxy) {
			return new TimeGraphic(drawArea, time, step, maxy);
		}

		function removeAxis(drawArea) {
			drawArea.svg.selectAll(".axis").remove();
		}

	}

	/**
	 * @class
	 * @param {objecct}
	 *            data
	 */
	function MediaData(data) {
		var SELF = this;
		this.title = data.name;
		this.episode_id = data.episode_id;
		this.series_id = data.series_id;
		this.duration = data.duration;
		var dataArray = data.data;
		this.data = dataArray.reduce(function(list, availableData) {
			var data = null;
			switch (availableData.type) {
			case "Number":
				data = new NumberData(availableData);
				break;
			case "mapping":
				data = new MappingData(availableData, SELF);
				break;
			default:

			}
			list.push(data);
			return list;
		}, []);
	}

	/**
	 * @class
	 * @param {object}
	 *            data
	 */
	function Data(data) {
		this.name = data.name;
		this.show = false;
		this.color = 0;
	}

	/**
	 * @class
	 * @extends Data
	 * @param {object}
	 *            data
	 */
	function NumberData(data) {
		Data.call(this, data);
		this.value = data.value;

		this.drawNumber = function(drawArea) {
			this.show = true;
		};

		this.toggleDraw = function(drawArea) {
			if (this.show) {

			} else {
				this.drawNumber(drawArea);
			}
		};
	}

	/**
	 * @class
	 * @extends Data
	 * @param {object}
	 *            data
	 * @param {MediaData}
	 *            mediaData
	 */
	function MappingData(data, mediaData) {
		Data.call(this, data);
		this.key = data.key;
		this.value = data.value;
		this.step = data.step;
		this.mapping = data.mapping;
		this.show = false;

		this.drawMapping = function(drawArea) {
			this.show = true;

			var time = parseInt(mediaData.duration.substring(0, mediaData.duration.length - 3));
			var maxviews = d3.max(this.mapping);
			var step = this.step;

			var axis = TimeGraphic.getTimeGraphic(drawArea, time, step, maxviews);

			// Produce a map from time to [views].
			var mappedData = this.mapping.map(function(element, index) {
				return {
					time : step * index,
					views : element
				};
			});

			mappedData = d3.nest().key(function(d) {
				return d.time;
			}).rollup(function(v) {
				return v[0].views;
			}).map(mappedData);

			axis.add(this, mappedData);

		};

		this.remove = function(drawArea) {
			var axis = TimeGraphic.getTimeGraphic(drawArea);
			axis.remove(this);
			this.show = false;
		};

		this.toggleDraw = function(drawArea) {
			this.show = !this.show;
			var axis = TimeGraphic.getTimeGraphic(drawArea);
			axis.toggle(this);
		};
	}

	/**
	 * @class
	 * @param {object}
	 *            drawArea
	 * @param {Number}
	 *            time
	 * @param {Number}
	 *            step
	 * @param {Number}
	 *            [maxy = 0]
	 */
	function TimeGraphic(drawArea, time, step, maxy) {

		var overlap = true;

		maxy = maxy || 0;

		var selfsvg = drawArea.svg.selectAll("#TimeGraphic").data([ this ]);
		selfsvg = selfsvg.enter().append("g").attr("id", "TimeGraphic").merge(selfsvg);
		selfsvg.selectAll("*").remove();

		var barWidth = Math.max((drawArea.width / time) * step, 1);

		var x = d3.scaleLinear().range([ 0, drawArea.width ]).domain([ 0, time ]);

		var y = d3.scaleLinear().range([ drawArea.height, 0 ]).domain([ 0, maxy ]);

		var yAxis = d3.axisRight().scale(y).tickSize(-drawArea.width).ticks(Math.min(10, maxy));

		var xAxis = d3.axisBottom().scale(x).ticks(Math.min(20, time / step + 1)).tickFormat(toHHMMSS);

		// A sliding container to hold the bars by time.
		var times = selfsvg.append("g").attr("class", "times");

		// Add an axis to show the views.
		drawYAxis();

		function drawYAxis() {
			selfsvg.selectAll(".y.axis").remove();
			selfsvg.append("g").attr("class", "y axis").attr("transform", "translate(" + drawArea.width + ",0)").call(yAxis).append("text").attr("class", "label").attr(
					"transform", "rotate(90)").attr("y", -15).attr("x", (drawArea.height / 2)).attr("fill", "#000").text(translate("views", "Views"));
		}

		// Add an axis to show the time values.
		selfsvg.append("g").attr("class", "x axis").attr("transform", "translate(0," + drawArea.height + ")").call(xAxis).append("text").attr("class", "label").attr("x",
				drawArea.width / 2).attr("y", 30).attr("fill", "#000").text(translate("time", "Time"));

		// Add labeled rects for each time (so that no enter or exit is
		// required).
		var time = times.selectAll(".time").data(d3.range(0, time, step)).enter().append("g").attr("class", "time").attr("transform", function(time) {
			return "translate(" + x(time) + ",0)";
		});

		var datas = [];
		this.add = function(data, mappedData) {
			if (!datas.includes(data)) {
				datas.push(data);
			}

			var newdata = time.selectAll("rect").filter("." + data.name).data(function(time) {
				var value = mappedData.get(time);
				return value ? [ value ] : [ 0 ];
			});
			newdata.exit().remove();
			var x = overlap || datas.length === 1 ? 0 : barWidth;
			var width = overlap || datas.length === 1 ? barWidth : 0;
			newdata.enter().append("rect").attr("class", data.name).attr("x", x).attr("y", drawArea.height).attr("width", width);

			this.updateAll();

			return this;
		};

		this.remove = function(data) {
			var index = datas.indexOf(data);
			if (index < 0) {
				return;
			}

			datas.splice(index, 1);
			var removedata = time.selectAll("rect").filter("." + data.name).classed("remove", true);
			if (!overlap && datas.length > 0) {
				removedata.transition().attr("x", function(d, i) {
					return (barWidth / datas.length) * index;
				}).attr("width", 0);
			}

			removedata.transition("height").attr("y", drawArea.height).attr("height", 0).remove();

			this.updateAll();
		};

		this.toggle = function(data) {
			var index = datas.indexOf(data);
			if (index < 0) {
				return;
			}
			if (!data.show) {
				time.selectAll("rect").filter("." + data.name).transition("height").attr("y", drawArea.height).attr("height", 0);
			}
			this.updateAll();
		};

		this.updateAll = function() {
			var max = datas.reduce(function(max, data) {
				if (data.show) {
					return Math.max(max, d3.max(data.mapping));
				} else {
					return max;
				}

			}, 0);
			if (max !== maxy) {
				this.updateMaxY(max);
			}

			var datacount = datas.length;
			for (var i = 0; i < datacount; i++) {
				var data = datas[i];
				if (data.show) {
					var needupdate = time.selectAll("rect:not(.remove)").filter("." + data.name);

					if (!overlap) {
						needupdate.transition().attr("x", function(d, i) {
							return barWidth / datacount * i;
						}).attr("width", barWidth / datacount);
					}

					needupdate.transition("height").attr("y", y).attr("height", function(value) {
						return drawArea.height - y(value);
					});
				}
			}
		};

		this.updateMaxY = function(newmaxy) {
			if (!newmaxy) {
				return;
			}
			maxy = newmaxy;
			y.domain([ 0, maxy ]);
			yAxis.ticks(Math.min(10, maxy));
			drawYAxis();
		};
	}
	TimeGraphic.getTimeGraphic = function(drawArea, time, step, maxy) {
		if (drawArea.svg.selectAll("#TimeGraphic").empty()) {
			return new TimeGraphic(drawArea, time, step, maxy);
		} else {
			var timeGraphic = drawArea.svg.selectAll("#TimeGraphic").data()[0];
			timeGraphic.updateMaxY(maxy);
			return timeGraphic;
		}
	};

	/**
	 * @class
	 * @param {object}
	 *            drawArea
	 */
	function Caption(drawArea) {
		var caption = drawArea.svg.append("g").attr("class", "caption").attr("transform", "translate(" + drawArea.width * 2 / 3 + ", 2)");

		this.add = function(data) {
			var datas = caption.selectAll("g").data();
			datas.push(data);
			var captions = caption.selectAll("g").data(datas);
			var newcaptions = captions.enter().append("g").attr("class", function(d) {
				return d.name;
			}).attr("transform", function(d, i) {
				return "translate(0," + (i * 20) + ")";
			}).attr("x", 0).attr("y", function(d, i) {
				return i * 20;
			});

			newcaptions.append("text").attr("x", 12).attr("y", 15).text(function(d) {
				var displayName = translate(d.name, d.name);
				if (d instanceof NumberData) {
					return d.value + " " + displayName;
				}
				return displayName;
			});
			newcaptions.append("rect").attr("class", function(d) {
				return d.name;
			}).attr("fill-opacity", function(d) {
				return d.show ? 1 : 0;
			}).attr("x", 1).attr("y", 6).attr("width", 8).attr("height", 8).on("click", mouseclick);
		};

		this.update = function() {
			caption.selectAll("g").select("rect").attr("fill-opacity", function(d) {
				return d.show ? 1 : 0;
			});
		};

		function mouseclick(d, i) {
			d.toggleDraw(drawArea);

			d3.select(this).attr("fill-opacity", d.show ? 1 : 0);

			d3.event.preventDefault();
		}
	}

	var Utils = {
		setTranslateFunction : setTranslateFunction,
		showData : showData
	};

	return Utils;
});
