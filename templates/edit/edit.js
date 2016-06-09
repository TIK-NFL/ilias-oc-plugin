var iliasopencast = iliasopencast ||  {}

Date.prototype.yyyymmdd = function() {
    var yyyy = this.getFullYear().toString();
    var mm = (this.getMonth()+1).toString(); // getMonth() is zero-based
    var dd  = this.getDate().toString();
    return yyyy + '-' +(mm[1]?mm:"0"+mm[0]) + '-' +(dd[1]?dd:"0"+dd[0]); // padding
};

iliasopencast.upload = {
    chunksize : 1 * 1024 * 1024,
    r : new Resumable({
        target : iliasopencast.settings.uploadtarget + "/upload",
        query : {},
        generateUniqueIdentifier : function() {
            return iliasopencast.settings.seriesid;
        },
        maxChunkRetries : 2,
        maxFiles : 3,
        prioritizeFirstAndLastChunk : false,
        simultaneousUploads : 1,
        chunkSize : this.chunksize,
        forceChunkSize : true,
        testChunks : false
    }),
    results : $('#iliasopencast_results'),
    draggable : $('#iliasopencast_dragHere'),
    uploadFile : $('#iliasopencast_uploadFiles'),
    browseButton : $('#iliasopencast_browseButton'),
    nothingToUpload : $('[data-iliasopencast_nothingToUpload]'),
    mediapackage : "",
    uniqueid : "",
    fileurl : "",
    checkfieldscomplete : function(){
        var upload = iliasopencast.upload;
        var fieldscomplete = upload.results.children().length > 0;
        fieldscomplete = fieldscomplete && ($('#iliasopencast_tracktitle').val() != '');
        fieldscomplete = fieldscomplete && ($('#iliasopencast_trackdate').val() != '');
        fieldscomplete = fieldscomplete && ($('#iliasopencast_tracktime').val() != '');
        if(fieldscomplete){
            upload.uploadFile.removeClass("disabled");
        } else {
            upload.uploadFile.addClass("disabled");
        }
        return fieldscomplete;
    },
    init: function(){
            var upload = iliasopencast.upload;
            var ils = iliasopencast.settings;
            // if resumable is not supported aka IE
            if (!upload.r.support)
                location.href = 'http://browsehappy.com/';

            upload.r.assignBrowse(upload.browseButton);

            upload.r.on('fileAdded', function(file, event) {
                var template = '<div data-uniqueid="' + file.uniqueIdentifier
                    + '">' + '<div class="left fileName">' + file.fileName + ' ('
                    + file.file.type + ')'
                    + '<span class="deleteFile">X</span></div>'
                    + '<div class="progress">'
                    + '<div class="progress-bar progress-bar-success" role="progressbar" style="width:0%;">0%</span>'
                    + '</div>' + '</div>';
                upload.results.append(template);
                $('#iliasopencast_results .deleteFile').on(
                    'click',
                    function() {
                        var self = $(this),
                          parent = self.parent().parent(),
                            identifier = parent
                            .data('uniqueid'), file = upload.r
                            .getFromUniqueIdentifier(identifier);

                        upload.r.removeFile(file);
                        parent.remove();
                        upload.checkfieldscomplete();
                        upload.nothingToUpload.show();
                    });

                upload.nothingToUpload.hide();
                upload.checkfieldscomplete();
            });

            upload.uploadFile.on('click', function() {
                if (upload.checkfieldscomplete) {
                    $.post(ils.uploadtarget + "/createEpisode", {
                        seriesid : ils.seriesid,
                        episodename : $('#iliasopencast_tracktitle').val(),
                        presenter : $('#iliasopencast_trackpresenter').val(),
                        episodedate : $('#iliasopencast_trackdate').val(),
                        episodetime : $('#iliasopencast_tracktime').val()
                    }).done(
                        function(data) {
                            upload.r.files[0].mpid = data;
                            $.post(ils.uploadtarget + "/newJob", {
                                seriesid : ils.seriesid,
                                mpid : data,
                                filename : upload.r.files[0].fileName,
                                filesize : upload.r.files[0].size,
                                chunksize : upload.chunksize
                            }).done(function(data) {
                                upload.r.opts["query"] = {
                                    jobid : data,
                                    seriesid : ils.seriesid
                                };
                                upload.r.upload();
                            });
                        });
                } else {
                    nothingToUpload.fadeIn();
                    setTimeout(function() {
                        nothingToUpload.fadeOut();
                    }, 3000);
                }
            });

            upload.r.on('fileProgress', function(file) {
                var progress = Math.floor(file.progress() * 1000)/10;
                $('[data-uniqueId=' + file.uniqueIdentifier + ']').find(
                    '.progress-bar').css('width', progress + '%');
                $('[data-uniqueId=' + file.uniqueIdentifier + ']').find(
                    '.progress-bar').html('&nbsp;' + progress + '%');
            });

            upload.r
                .on(
                    'fileSuccess',
                    function(file, message) {
                        $('[data-uniqueId=' + file.uniqueIdentifier + ']')
                            .find('.progress').addClass('success');
                        $.post(ils.uploadtarget + "/finishUpload", {
                            seriesid : ils.seriesid,
                            mpid : file.mpid,
                            trimeditor : $("#iliasopencast_trimeditor").is(':checked'),
                            jobid : message
                        }).done(function() {
                            for (var i = 0; i < upload.r.files.length; i++) {
                                upload.r.removeFile(upload.r.files[i])
                            }
                            $('#iliasopencast_results').empty();
                            $('#iliasopencast_tracktitle').val("");
                            $('#iliasopencast_trackpresenter').val("");
                            $('#iliasopencast_trackdate').val(today.yyyymmdd());
                            $("#iliasopencast_trimeditor").attr('checked', false);
                            $('#iliasopencast_tracktime').timepicker({
                              showMeridian: false,
                              minuteStep: 1
                            });
                            upload.checkfieldscomplete();
                        });
                    });

            upload.r.on('uploadStart', function() {
                $('.alert-box').text('Uploading...');
                upload.uploadFile.addClass("disabled");
            });

            upload.r.on('complete', function() {
                $('.alert-box').text('Done uploading');
            });
            $('#iliasopencast_tracktitle').on('blur',function () {
                upload.checkfieldscomplete();
            });
            $('#iliasopencast_tracktitle').on('input',function () {
                upload.checkfieldscomplete();
            });

            $('#iliasopencast_trackdate').on('blur',function () {
                upload.checkfieldscomplete();
            });
            $('#iliasopencast_trackdate').on('input',function () {
                upload.checkfieldscomplete();
            });
            $('#iliasopencast_tracktime').on('blur',function () {
                upload.checkfieldscomplete();
            });
            $('#iliasopencast_tracktime').on('input',function () {
                upload.checkfieldscomplete();
            });

            $('#iliasopencast_trackdate').datepicker({
                format : "yyyy-mm-dd",
                autoclose : true,
                setDate : new Date(),
                todayHighlight : true,
                todayBtn : "linked",
            });
            var today = new Date();
            $('#iliasopencast_trackdate').val(today.yyyymmdd());
            $('#iliasopencast_tracktime').timepicker({
              showMeridian: false,
              minuteStep: 1
            });

    }
}
iliasopencast.init = function() {
      iliasopencast.upload.init();
}

iliasopencast.updateprocessing = function() {
    var txt = iliasopencast.translation;
    var ils = iliasopencast.settings;
    var templates = {
        "noprocessing" : '<tr class="tblrow1"><td class="std" style="text-align:center;" colspan="4">'
            + txt.txt_none_processing + '</td></tr>',
        "processing" : '<tr><td class="std">{{title}}</td> <td class="std">{{date}}</td>'
            + '<td><div class="progress"><div class="progress-bar progress-bar-success" role="progressbar" style="width:{{processdone}}%">{{processcount}}</div></td><td>{{running}}</td>'
            + '</tr>',
        "nofinished" : '<tr class="tblrow1"><td class="std" style="text-align:center;" colspan="'
            + ils.cols_finished + '">' + txt.txt_none_finished + '</td></tr>',
        "finished" : '<tr>'
            + '<td class="std"><a href="{{#convertAmpersand}}{{viewurl}}{{/convertAmpersand}}">{{title}}</a></td>'
            + '<td class="std"><a href="{{#convertAmpersand}}{{viewurl}}{{/convertAmpersand}}"><img src="{{previewurl}}" /></a></td>'        
            + '<td class="std">{{date}}</td>' 
            + ((ils.cols_finished == 4)  ? '<td><a href="{{#convertAmpersand}}{{publishurl}}{{/convertAmpersand}}">{{ txt_publish }}</a></td>' : '') 
            + '</tr>',
        'noonhold' : '<tr class="tblrow1"><td class="std" style="text-align:center;" colspan="4">'
            + txt.txt_none_onhold + '</td></tr>',
        'onhold' : '<tr><td class="std"><a href="{{#convertAmpersand}}{{trimurl}}{{/convertAmpersand}}">{{title}}</a></td> <td class="std">{{date}}</td></tr>',
        'noscheduled' : '<tr class="tblrow1"><td class="std" style="text-align:center;" colspan="5">'
            + txt.txt_none_scheduled + '</td></tr>',
        'scheduled' : '<tr>'
            + '<td class="std">{{title}}</td> <td class="std">{{startdate}} </td> <td class="std"> {{stopdate}} </td> <td class="std">{{location}} </td><td class="std"><a href="{{#convertAmpersand}}{{deletescheduledurl}}{{/convertAmpersand}}">'+txt.txt_delete+'</a></td>'
            + '</tr>'

    };
    var tabledata = "";
    $
        .get(ils.processingcmd)
        .done(
            function(data) {
                if (data['lastupdate'] > -1) {
                    if ($("#iliasopencast_finishedtable").data('lastupdate') < data['lastupdate']) {
                        updateTable(data['finished'],"finished");
                        $("#iliasopencast_finishedtable").data('lastupdate',
                            data['lastupdate']);
                        console.log("updated finished");
                    }
                }
                updateTable(data['processing'],"processing");
                updateTable(data['onhold'],"onhold");
                updateTable(data['scheduled'],"scheduled");
            });

    var updateTable = function(data, dataname) {
        keys = $.map(data, function(v, i) {
            return i;
        });
        var tabledata = "";
        if (keys.length == 0) {
            tabledata = templates["no"+ dataname];
        } else {
            $(keys).each(
                function() {
                    var episode = data[this];
                    episode['convertAmpersand'] = function(){
                        return function(str,render){
                            return render(str).replace(/&amp;/g, "\&");
                        }
                     }
                    var html = Mustache.to_html(
                        templates[dataname], episode);
                    tabledata = tabledata + html;
                });
        }
        $("#iliasopencast_"+dataname +"table").html(tabledata);
    }
}


$(document)
    .ready(
        function() {
            iliasopencast.init();
            iliasopencast.updateprocessing();
            window.setInterval(iliasopencast.updateprocessing, 5000);
        }
    );
