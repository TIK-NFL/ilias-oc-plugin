
$(document).ready(function () {
  var myPlayer = videojs('mhpreviewvideo');
  $('#settrimin').click(function(){
      $('#trimin').val(myPlayer.currentTime().toFixed(2));
  });
  $('#settrimout').click(function(){
      $('#trimout').val(myPlayer.currentTime().toFixed(2));
  });
  $("#lefttrackflavor option").filter(function() {
    return $(this).text() == $('#lefttrackflavor').data('preset'); 
  }).prop('selected', true);
  $("#righttrackflavor option").filter(function() {
    return $(this).text() == $('#righttrackflavor').data('preset'); 
  }).prop('selected', true);
});