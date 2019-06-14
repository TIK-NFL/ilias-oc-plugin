"use strict";
(function () {
	$(document).ready(function () {
		const trimForm = new TrimForm(document.getElementById("trim-form"));
		const myPlayer = videojs('ocpreviewvideo');
		trimForm.elementTrimIn.keypress(function (event) {
			const keyCode = (event.keyCode ? event.keyCode : event.which);
			if (keyCode == 13) {
				event.preventDefault();
				trimForm.setIn(TrimForm.convertTimeToSec(trimForm.elementTrimIn.val()));
			}
		});
		trimForm.elementTrimOut.keypress(function (event) {
			const keyCode = (event.keyCode ? event.keyCode : event.which);
			if (keyCode == 13) {
				event.preventDefault();
				trimForm.setOut(TrimForm.convertTimeToSec(trimForm.elementTrimOut.val()));
			}
		});
		$('#settrimin').click(() => trimForm.setIn(myPlayer.currentTime().toFixed(0)));
		$('#settrimout').click(() => trimForm.setOut(myPlayer.currentTime().toFixed(0)));

		$('#playtrimin').click(function () {
			const time = trimForm.getIn();
			myPlayer.currentTime(time);
			myPlayer.play();
		});
		$('#playtrimout').click(function () {
			let time = trimForm.getOut(); - 10;
			let pausetime = 11000;
			if (time < 0) {
				pausetime = (11 + time) * 1000;
				time = 0;
			}
			myPlayer.currentTime(time);
			myPlayer.play();
			myPlayer.setTimeout(myPlayer.pause, pausetime);
		});
	});

	class TrimForm {
		constructor(form) {
			this.elementForm = form;
			this.inputStreamType = $("#inputStreamType").val();
			this.elementTrimIn = $('#trimin');
			this.elementTrimOut = $('#trimout');
			this.elementKeepLeftTrack = $('#lefttrack');
			this.elementKeepRightTrack = $('#righttrack');
			this.elementSubmitButton = document.getElementById('octrimbutton');
			this.slider = $("#slider-range");
			this.initSlider();
			this.initTrimInOut();
			this.elementForm.addEventListener('submit', this.onsubmitForm.bind(this));
		}

		setIn(time) {
			this.slider.slider("values", 0, time);
		}

		setOut(time) {
			this.slider.slider("values", 1, time);
		}

		getIn() {
			return this.slider.slider("values")[0];
		}

		getOut() {
			return this.slider.slider("values")[1];
		}

		initSlider() {
			// Single Source of Truth for the trim values
			// If you want to change the trim values set the slider values
			this.slider.slider({
				range: true,
				min: 0,
				max: this.slider.data('time'),
				values: [0, this.slider.data('time')],
				slide: this.handleSlide.bind(this),
				change: this.handleChange.bind(this)
			});
		}

		initTrimInOut() {
			this.elementTrimIn.val(TrimForm.convertSecToTime(this.getIn()));
			this.elementTrimOut.val(TrimForm.convertSecToTime(this.getOut()));
		}

		handleChange() {
			const values = this.slider.slider("values");
			this.refreshTimesFromSliderEvent(values);
		}

		handleSlide(event, { values }) {
			this.refreshTimesFromSliderEvent(values);
		}

		onsubmitForm(event) {
			event.preventDefault();
			if (this.validateForm()) {
				this.sendTrimData();
			}
		}

		validateForm() {
			if (this.getIn() >= this.getOut()) {
				return false;
			}

			if (this.inputStreamType == "dual") {
				if (!this.elementKeepLeftTrack.prop('checked') && !this.elementKeepRightTrack.prop('checked')) {
					return false;
				}
			}

			if (!$("#episodetitle").val()) {
				return false;
			}

			return true;
		}

		sendTrimData() {
			const url = this.elementForm.getAttribute("action");
			const headers = new Headers();
			headers.set('Accept', 'application/json');

			const formData = new FormData();
			formData.append("episode_id", $("#episode_id").val());
			formData.append("episodetitle", $("#episodetitle").val());
			let outputStreamType;
			if (this.inputStreamType == "dual") {
				const lefttrack = this.elementKeepLeftTrack.prop('checked');
				const righttrack = this.elementKeepRightTrack.prop('checked');
				if (lefttrack && righttrack) {
					outputStreamType = "dual";
				} else if (lefttrack) {
					outputStreamType = "presenter";
				} else if (righttrack) {
					outputStreamType = "presentation";
				} else {
					throw new Error("Broken validation");
				}
			} else {
				outputStreamType = this.inputStreamType;
			}
			formData.append("outputStreamType", outputStreamType);

			formData.append("trimin", this.getIn());
			formData.append("trimout", this.getOut());

			const fetchOptions = {
				method: 'POST',
				headers,
				body: formData
			};

			this.elementSubmitButton.disabled = true;
			fetch(url, fetchOptions).then(async response => {
				const json = await response.json();
				if (json.redirect_url) {
					window.location.replace(json.redirect_url);
				}
			}).catch(() => this.elementSubmitButton.disabled = false);
		}

		refreshTimesFromSliderEvent(values) {
			this.elementTrimIn.val(TrimForm.convertSecToTime(values[0]));
			this.elementTrimOut.val(TrimForm.convertSecToTime(values[1]));
		}

		static convertSecToTime(totalSec) {
			const hours = parseInt(totalSec / 3600) % 24;
			const minutes = parseInt(totalSec / 60) % 60;
			const seconds = totalSec % 60;
			const result = hours + ":" + (minutes < 10 ? "0" + minutes : minutes) + ":" + (seconds < 10 ? "0" + seconds : seconds);
			return result;
		}

		static convertTimeToSec(time) {
			const ts = time.split(':'); // split it at the colons
			const seconds = (+ts[0]) * 60 * 60 + (+ts[1]) * 60 + (+ts[2]);
			return isNaN(seconds) ? 0 : seconds;
		}
	}
})();
