$(document).ready(function () {
    $('#trackdate').datepicker({
        format: "yyyy-mm-dd",
        autoclose: true
    });
    var chunksize = 1*1024*1024;
    var r = new Resumable({
        target: uploadtarget+"/upload",
        query: {},
        generateUniqueIdentifier: function(){return seriesid;},
        maxChunkRetries: 2,
        maxFiles: 3,
        prioritizeFirstAndLastChunk: false,
        simultaneousUploads: 1,
        chunkSize: chunksize,
        forceChunkSize: true,
        testChunks: false
    });
    var results = $('#results'),
        draggable = $('#dragHere'),
        uploadFile = $('#uploadFiles'),
        browseButton = $('#browseButton'),
        nothingToUpload = $('[data-nothingToUpload]'),
        mediapackage = "",
        uniqueid = "",
        fileurl = "";
   

    // if resumable is not supported aka IE
    if (!r.support) location.href = 'http://browsehappy.com/';

    r.assignBrowse(browseButton);

    r.on('fileAdded', function (file, event) {
        var template =
            '<div data-uniqueid="' + file.uniqueIdentifier + '">' +
            '<div class="fileName">' + file.fileName + ' (' + file.file.type + ')' + '</div>' +
            '<div class="large-6 right deleteFile">X</div>' +
            '<div class="progress large-6">' +
            '<span class="meter" style="width:0%;"></span>' +
            '</div>' +
            '</div>';

        results.append(template);
    });

    uploadFile.on('click', function () {        
        if (results.children().length > 0) {
            $.post( uploadtarget+"/createEpisode", { seriesid: seriesid, episodename: $('#tracktitle').val(), presenter: $('#trackpresenter').val(), episodedate: $('#trackdate').val(), episodetime: $('#tracktime').val()})
                .done(function( data ) {
                    //alert( "Data Loaded: " + data );
                    mediapackage = (new XMLSerializer()).serializeToString(data);
                    $.post( uploadtarget+"/newJob", { seriesid: seriesid, mediaPackage: escape(mediapackage), filename: r.files[0].fileName, filesize: r.files[0].size, chunksize: chunksize})
                        .done(function( data ) {
                            r.opts["query"] = { jobid : data, seriesid: seriesid  };
                            r.upload();
                        });
                });
        } else {
            nothingToUpload.fadeIn();
            setTimeout(function () {
                nothingToUpload.fadeOut();
            }, 3000);
        }
    });

    $(document).on('click', '.deleteFile', function () {
        var self = $(this),
            parent = self.parent(),
            identifier = parent.data('uniqueid'),
            file = r.getFromUniqueIdentifier(identifier);

        r.removeFile(file);
        parent.remove();
    });


    r.on('fileProgress', function (file) {
        var progress = Math.floor(file.progress() * 100);
        $('[data-uniqueId=' + file.uniqueIdentifier + ']').find('.meter').css('width', progress + '%');
        $('[data-uniqueId=' + file.uniqueIdentifier + ']').find('.meter').html('&nbsp;' + progress + '%');
    });

    r.on('fileSuccess', function (file, message) {
        $('[data-uniqueId=' + file.uniqueIdentifier + ']').find('.progress').addClass('success');
        fileurl = parseXml(message).getElementsByTagName("url")[0].childNodes[0].nodeValue;
        $.post( uploadtarget+"/finishUpload", { seriesid: seriesid, mediaPackage: escape(mediapackage), fileurl: fileurl})
            .done(function(){
                for (var i = 0; i < r.files.length; i++){
                    r.removeFile(r.files[i])
                }
                $('#results').empty();
                $('#tracktitle').val("");
                $('#trackpresenter').val("");
                $('#trackdate').val("");
                $('#tracktime').val("");
        });      
    });


    r.on('uploadStart', function () {
        $('.alert-box').text('Uploading....');
    });

    r.on('complete', function () {
        $('.alert-box').text('Done Uploading');
    });

    updateprocessing();
    window.setInterval(updateprocessing, 2000);
  
});
var parseXml = function(xmlStr) {
    return ( new window.DOMParser() ).parseFromString(xmlStr, "text/xml");
};

var updateprocessing = function() {
  var template_noprocessing = '<tr class="tblrow1"><td class="std" style="text-align:center;" colspan="4">'+txt_none_processing+'</td></tr>';
  var template_processing = '<tr><td class="std">{{title}}</td> <td class="std">{{recorddate}}</td></tr>';
  var tabledata = "";
  console.log("updateprocessing");
  $.get( processingcmd ).done(function( data ) {    
    keys = $.map(data, function(v, i){
      return i;
    });
    if(keys.length == 0){
      tabledata = template_noprocessing;
      console.log("no data");
    } else {
      $(keys).each(function() {        
          var episode = data[this];
          console.log(episode);
          var html = Mustache.to_html(template_processing, episode);
          tabledata = tabledata + html;
      });
    }
    $("#processingtable").html(tabledata);
  });

}
