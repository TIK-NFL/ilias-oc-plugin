"use strict";
(function () {
	$(document).ready(function () {
		const trimForm = new TrimForm(document.getElementById("trim-form"));
		const videoCurrentTime = $("#videocurrenttime");
		const videoTotalTime = $("#videototaltime");
		const playButton = $("#videoplaybutton span");
		const myPlayer = videojs('ocpreviewvideo',{
		  inactivityTimeout: 0,
		  fluid: false,
		  controls: true,
		  preload:"auto",
	      controlBar: {
	          playToggle: false,
	          volumePanel: false,
	          durationDisplay: false,
	          timeDivider: false,
	          currentTimeDisplay: false,
	          remainingTimeDisplay: false,
	          pictureInPictureToggle: false,
	          fullscreenToggle: false
	      }
		});
		myPlayer.ready(function(){
			videoCurrentTime.text(TrimForm.convertSecToTime(Math.round(myPlayer.currentTime())));
		});

		myPlayer.on('durationchange', function(event){
			videoTotalTime.text(TrimForm.convertSecToTime(Math.round(myPlayer.duration())));
		});

		myPlayer.on('timeupdate', function(event){
			videoCurrentTime.text(TrimForm.convertSecToTime(Math.round(myPlayer.currentTime())));
		});

		myPlayer.on('play', function(event){
			playButton.removeClass("glyphicon-play");
			playButton.addClass("glyphicon-pause");
		});

		myPlayer.on('pause', function(event){
			playButton.removeClass("glyphicon-pause");
			playButton.addClass("glyphicon-play");
		});

		trimForm.elementTrimIn.keypress(function (event) {
			const keyCode = (event.keyCode ? event.keyCode : event.which);
			if (keyCode == 13) {
				event.preventDefault();
				trimForm.setIn(TrimForm.convertTimeToSec(trimForm.elementTrimIn.val()));
			}
		});
		trimForm.elementTrimIn.blur(function (event) {
			if(!isNaN(TrimForm.convertTimeToSec(trimForm.elementTrimIn.val()))){
				trimForm.setIn(TrimForm.convertTimeToSec(trimForm.elementTrimIn.val()));
			} else {
				trimForm.elementTrimIn.val(TrimForm.convertSecToTime(trimForm.getIn()));
			}
		});
		trimForm.elementTrimOut.keypress(function (event) {
			const keyCode = (event.keyCode ? event.keyCode : event.which);
			if (keyCode == 13) {
				event.preventDefault();
				trimForm.setOut(TrimForm.convertTimeToSec(trimForm.elementTrimOut.val()));
			}
		});
		trimForm.elementTrimOut.blur(function (event) {
			trimForm.setOut(TrimForm.convertTimeToSec(trimForm.elementTrimOut.val()));
		});

		playButton.parent().click(function() {
			if(myPlayer.paused()){
				myPlayer.play();
			} else {
				myPlayer.pause();
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
			let time = trimForm.getOut() - 10;
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
				change: this.handleChange.bind(this),
				classes: {
				  "ui-slider": "ui-corner-all",
				  "ui-slider-handle": "ui-corner-all",
				  "ui-slider-range": "ui-corner-all ui-widget-header trimslider"
				}
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
			if (values[1] - values[0] == 0) {
			    this.elementSubmitButton.disabled = true
			} else {
			    this.elementSubmitButton.disabled = false
			}
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
			return seconds;
		}
	}
})();
