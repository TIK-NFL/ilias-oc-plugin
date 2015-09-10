
$(document).ready(function () {
  var myPlayer = videojs('mhpreviewvideo');
  $('#settrimin').click(function(){
      $('#trimin').val(convertTime(myPlayer.currentTime().toFixed(0)));
      $( "#slider-range" ).slider("values",0,myPlayer.currentTime().toFixed(0));
  });
  $('#settrimout').click(function(){
      $('#trimout').val(convertTime(myPlayer.currentTime().toFixed(0)));
      $( "#slider-range" ).slider("values",1,myPlayer.currentTime().toFixed(0));
  });  
  $("#lefttrackflavor option").filter(function() {
    return $(this).val() == $('#lefttrackflavor').data('preset'); 
  }).prop('selected', true);
  $("#righttrackflavor option").filter(function() {
    return $(this).val() == $('#righttrackflavor').data('preset'); 
  }).prop('selected', true);
  $("#lefttrack").change(function(){
    var el = $("#lefttrackflavor");
    if (el){
        if (!this.checked){
          el.hide();     
        } else {
          el.show();     
        }
    }
  });  
  $("#righttrack").change(function(){
    var el = $("#righttrackflavor");
    if (el){
        if (!this.checked){
          el.hide();     
        } else {
          el.show();     
        }
    }
  });  
  $( "#slider-range" ).slider({
      range: true,
      min: 0,
      max: $( "#slider-range" ).data('time'),
      values: [0 , $( "#slider-range" ).data('time') ],
      slide: refreshTimes,
      change: refreshTimes
    });
  $( "#slider-range" ).css('background', '#A0A0A0');
  $( "#slider-range .ui-slider-range" ).css('background', '#86ED2C');
  $( "#slider-range .ui-slider-handle" ).css('background', 'rgb(0,0,0)');
});

function refreshTimes() {
    var values = $( "#slider-range" ).slider( "values" );   
    $('#trimin').val(convertTime(values[0]));    
    $('#trimout').val(convertTime(values[1]));
  }

function convertTime(totalSec){
  var hours = parseInt( totalSec / 3600 ) % 24;
  var minutes = parseInt( totalSec / 60 ) % 60;
  var seconds = totalSec % 60;
  var result = hours + ":" + (minutes < 10 ? "0" + minutes : minutes) + ":" + (seconds  < 10 ? "0" + seconds : seconds);
  return result;
}