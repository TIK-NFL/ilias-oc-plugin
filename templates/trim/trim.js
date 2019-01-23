"use strict";
(function() {
	$(document).ready(function() {
		const myPlayer = videojs('mhpreviewvideo');
		$('#trimin').keypress(function(event) {
			const keyCode = (event.keyCode ? event.keyCode : event.which);
	        if (keyCode == 13) {
	            event.preventDefault();
	            setIn(convertTimeToSec($('#trimin').val()));
	        }
		});
		$('#trimout').keypress(function(event) {
			const keyCode = (event.keyCode ? event.keyCode : event.which);
	        if (keyCode == 13) {
	            event.preventDefault();
	            setOut(convertTimeToSec($('#trimout').val()));
	        }
		});
		$('#settrimin').click(function() {
			setIn(myPlayer.currentTime().toFixed(0));
		});
		$('#settrimout').click(function() {
			setOut(myPlayer.currentTime().toFixed(0));
		});
		$('#playtrimin').click(function() {
			const time = convertTimeToSec($('#trimin').val());
			myPlayer.currentTime(time);
			myPlayer.play();
		});
		$('#playtrimout').click(function() {
			let time = convertTimeToSec($('#trimout').val()) - 10;
			let pausetime = 11000;
			if (time < 0) {
				pausetime = (11 + time) * 1000;
				time = 0;
			}
			myPlayer.currentTime(time);
			myPlayer.play();
			myPlayer.setTimeout(myPlayer.pause, pausetime)

		});

		$("#lefttrackflavor option").filter(function() {
			return $(this).val() == $('#lefttrackflavor').data('preset');
		}).prop('selected', true);
		$("#righttrackflavor option").filter(function() {
			return $(this).val() == $('#righttrackflavor').data('preset');
		}).prop('selected', true);
		$("#lefttrack").change(function() {
			const el = $("#lefttrackflavor");
			if (el) {
				if (!this.checked) {
					el.hide();
				} else {
					el.show();
				}
			}
		});
		$("#righttrack").change(function() {
			const el = $("#righttrackflavor");
			if (el) {
				if (!this.checked) {
					el.hide();
				} else {
					el.show();
				}
			}
		});
		$("#octrimbutton").click(function(e) {
			if ($("#octrimbutton").hasClass("disabled")) {
				e.preventDefault();
			}
		});
		initSlider();
	});

	function initSlider() {
		// Single Source of Truth for the trim values
		// If you want to change the trim values set the slider values
		$("#slider-range").slider({
			range : true,
			min : 0,
			max : $("#slider-range").data('time'),
			values : [ 0, $("#slider-range").data('time') ],
			slide : handleSlide,
			change : handleChange
		});
	}
	
	function handleChange() {
		const values = $("#slider-range").slider("values");
		refreshTimesFromSliderEvent(values);
	}
	
	function handleSlide(event, {values}) {
		refreshTimesFromSliderEvent(values);
	}
	
	function setIn(time) {
		$("#slider-range").slider("values", 0, time);
	}
	
	function setOut(time) {
		$("#slider-range").slider("values", 1, time);
	}

	function refreshTimesFromSliderEvent(values) {
		$('#trimin').val(convertSecToTime(values[0]));
		$('#trimout').val(convertSecToTime(values[1]));
		if (values[0] >= values[1]) {
			$('#octrimbutton').addClass("disabled")
		} else {
			$('#octrimbutton').removeClass("disabled")
		}
	}

	function convertSecToTime(totalSec) {
		const hours = parseInt(totalSec / 3600) % 24;
		const minutes = parseInt(totalSec / 60) % 60;
		const seconds = totalSec % 60;
		const result = hours + ":" + (minutes < 10 ? "0" + minutes : minutes) + ":" + (seconds < 10 ? "0" + seconds : seconds);
		return result;
	}

	function convertTimeToSec(time) {
		const ts = time.split(':'); // split it at the colons
		const seconds = (+ts[0]) * 60 * 60 + (+ts[1]) * 60 + (+ts[2]);
		return isNaN(seconds) ? 0 : seconds;
	}
})();
