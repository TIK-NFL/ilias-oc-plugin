(function() {
	$(document).ready(function() {
		var myPlayer = videojs('mhpreviewvideo');
		$('#settrimin').click(function() {
			$('#trimin').val(convertSecToTime(myPlayer.currentTime().toFixed(0)));
			$("#slider-range").slider("values", 0, myPlayer.currentTime().toFixed(0));
		});
		$('#settrimout').click(function() {
			$('#trimout').val(convertSecToTime(myPlayer.currentTime().toFixed(0)));
			$("#slider-range").slider("values", 1, myPlayer.currentTime().toFixed(0));
		});
		$('#playtrimin').click(function() {
			time = convertTimeToSec($('#trimin').val());
			myPlayer.currentTime(time);
			myPlayer.play();
		});
		$('#playtrimout').click(function() {
			time = convertTimeToSec($('#trimout').val()) - 10;
			pausetime = 11000;
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
			var el = $("#lefttrackflavor");
			if (el) {
				if (!this.checked) {
					el.hide();
				} else {
					el.show();
				}
			}
		});
		$("#righttrack").change(function() {
			var el = $("#righttrackflavor");
			if (el) {
				if (!this.checked) {
					el.hide();
				} else {
					el.show();
				}
			}
		});
		initSlider();
	});

	function initSlider() {
		$("#slider-range").slider({
			range : true,
			min : 0,
			max : $("#slider-range").data('time'),
			values : [ 0, $("#slider-range").data('time') ],
			slide : refreshTimes,
			change : refreshTimes
		});
	}

	function refreshTimes() {
		var values = $("#slider-range").slider("values");
		$('#trimin').val(convertSecToTime(values[0]));
		$('#trimout').val(convertSecToTime(values[1]));
		if (values[1] - values[0] == 0) {
			$('#octrimbutton').addClass("disabled")
		} else {
			$('#octrimbutton').removeClass("disabled")
		}
	}

	function convertSecToTime(totalSec) {
		var hours = parseInt(totalSec / 3600) % 24;
		var minutes = parseInt(totalSec / 60) % 60;
		var seconds = totalSec % 60;
		var result = hours + ":" + (minutes < 10 ? "0" + minutes : minutes) + ":" + (seconds < 10 ? "0" + seconds : seconds);
		return result;
	}

	function convertTimeToSec(time) {
		var ts = time.split(':'); // split it at the colons
		var seconds = (+ts[0]) * 60 * 60 + (+ts[1]) * 60 + (+ts[2]);
		return seconds;
	}
})();
