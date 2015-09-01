
$(document).ready(function () {
  var myPlayer = videojs('mhpreviewvideo');
  $('#settrimin').click(function(){
      $('#trimin').val(convertTime(myPlayer.currentTime().toFixed(0)));
  });
  $('#settrimout').click(function(){
      $('#trimout').val(convertTime(myPlayer.currentTime().toFixed(0)));
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
  
});

function convertTime(totalSec){
  var hours = parseInt( totalSec / 3600 ) % 24;
  var minutes = parseInt( totalSec / 60 ) % 60;
  var seconds = totalSec % 60;
  var result = hours + ":" + (minutes < 10 ? "0" + minutes : minutes) + ":" + (seconds  < 10 ? "0" + seconds : seconds);
  return result;
}