var iliasopencast = iliasopencast || {}

Date.prototype.yyyymmdd = function() {
    var yyyy = this.getFullYear().toString();
    var mm = (this.getMonth() + 1).toString(); // getMonth() is zero-based
    var dd = this.getDate().toString();
    return yyyy + '-' + (mm[1] ? mm : "0" + mm[0]) + '-' + (dd[1] ? dd : "0" + dd[0]); // padding
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
    cancelButton : $('#iliasopencast_cancelUpload'),
    nothingToUpload : $('[data-iliasopencast_nothingToUpload]'),
    mediapackage : "",
    uniqueid : "",
    fileurl : "",
    checkfieldscomplete : function() {
        var upload = iliasopencast.upload;
        var fieldscomplete = upload.results.children().length > 0;
        fieldscomplete = fieldscomplete && ($('#iliasopencast_tracktitle').val() != '');
        fieldscomplete = fieldscomplete && ($('#iliasopencast_trackdate').val() != '');
        fieldscomplete = fieldscomplete && ($('#iliasopencast_tracktime').val() != '');
        if (fieldscomplete) {
            upload.uploadFile.removeClass("disabled");
        } else {
            upload.uploadFile.addClass("disabled");
        }
        return fieldscomplete;
    },
    init : function() {
        var upload = iliasopencast.upload;
        var ils = iliasopencast.settings;
        // if resumable is not supported aka IE
        if (!upload.r.support)
            location.href = 'http://browsehappy.com/';

        upload.r.assignBrowse(upload.browseButton);

        upload.r.on('fileAdded', function(file, event) {
            var template = '<div data-uniqueid="' + file.uniqueIdentifier + '">' + '<div class="left fileName">' + file.fileName + ' (' + file.file.type + ')'
                    + '<span class="deleteFile">X</span></div>' + '<div class="progress">'
                    + '<div class="progress-bar progress-bar-success" role="progressbar" style="width:0%;">0%</span>' + '</div>' + '</div>';
            upload.results.append(template);
            upload.browseButton.addClass("disabled");
            $('#iliasopencast_results .deleteFile').on('click', function() {
                var self = $(this), parent = self.parent().parent(), identifier = parent.data('uniqueid'), file = upload.r.getFromUniqueIdentifier(identifier);

                if (upload.r.isUploading()) {
                    upload.r.cancel();
                }
                upload.r.removeFile(file);
                parent.remove();
                upload.checkfieldscomplete();
                upload.nothingToUpload.show();
                upload.browseButton.removeClass("disabled");
            });

            upload.nothingToUpload.hide();
            upload.checkfieldscomplete();
        });

        upload.cancelButton.on('click', function() {
            $('#iliasopencast_results .deleteFile').click();
        });

        upload.uploadFile.on('click', function() {
            if (upload.checkfieldscomplete) {
                $.post(ils.uploadtarget + "/createEpisode", {
                    seriesid : ils.seriesid,
                    episodename : $('#iliasopencast_tracktitle').val(),
                    presenter : $('#iliasopencast_trackpresenter').val(),
                    episodedate : $('#iliasopencast_trackdate').val(),
                    episodetime : $('#iliasopencast_tracktime').val()
                }).done(function(data) {
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
            var progress = Math.floor(file.progress() * 1000) / 10;
            $('[data-uniqueId=' + file.uniqueIdentifier + ']').find('.progress-bar').css('width', progress + '%');
            $('[data-uniqueId=' + file.uniqueIdentifier + ']').find('.progress-bar').html('&nbsp;' + progress + '%');
        });

        upload.r.on('fileSuccess', function(file, message) {
            $('[data-uniqueId=' + file.uniqueIdentifier + ']').find('.progress').addClass('success');
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
                    showMeridian : false,
                    minuteStep : 1
                });
                upload.checkfieldscomplete();
                upload.nothingToUpload.show();
                $('.alert-box').text(iliasopencast.translation.txt_done_uploading);
            });
        });

        upload.r.on('uploadStart', function() {
            $('.alert-box').text(iliasopencast.translation.txt_uploading);
            upload.uploadFile.addClass("disabled");
            upload.browseButton.addClass("disabled");
            upload.cancelButton.removeClass("disabled");
        });

        upload.r.on('fileError', function(file, message) {
            console.log(message);

        });
        upload.r.on('cancel', function() {
            upload.nothingToUpload.show();
            $('.alert-box').text(iliasopencast.translation.txt_upload_canceled);
            upload.cancelButton.addClass("disabled");
            upload.browseButton.removeClass("disabled");
            console.log("Upload cancel");
            upload.checkfieldscomplete();

        });
        upload.r.on('complete', function(file, message) {
            // console.log("Completed");
            upload.cancelButton.addClass("disabled");
            upload.browseButton.removeClass("disabled");
            upload.checkfieldscomplete();
        });

        $('#iliasopencast_tracktitle').on('blur', function() {
            upload.checkfieldscomplete();
        });
        $('#iliasopencast_tracktitle').on('input', function() {
            upload.checkfieldscomplete();
        });

        $('#iliasopencast_trackdate').on('blur', function() {
            upload.checkfieldscomplete();
        });
        $('#iliasopencast_trackdate').on('input', function() {
            upload.checkfieldscomplete();
        });
        $('#iliasopencast_tracktime').on('blur', function() {
            upload.checkfieldscomplete();
        });
        $('#iliasopencast_tracktime').on('input', function() {
            upload.checkfieldscomplete();
        });

        $('#iliasopencast_trackdate').datepicker({
            format : "yyyy-mm-dd",
            autoclose : true,
            setDate : new Date(),
            todayHighlight : true,
            todayBtn : "linked",
            immediateUpdates : true,
        }).on('hide', function(e) {
            upload.checkfieldscomplete();
        });
        var today = new Date();
        $('#iliasopencast_trackdate').val(today.yyyymmdd());
        $('#iliasopencast_trackdate').datepicker('update', today);
        $('#iliasopencast_tracktime').timepicker({
            showMeridian : false,
            minuteStep : 1
        });

    }
}
iliasopencast.init = function() {
    iliasopencast.upload.init();

    iliasopencast.templates = $.get(iliasopencast.settings.uploadtarget + "/../templates/edit/edit.html");

    iliasopencast.updateprocessing();
    window.setInterval(iliasopencast.updateprocessing, 5000);
}

iliasopencast.updateprocessing = function() {
    var txt = iliasopencast.translation;
    var ils = iliasopencast.settings;
    var response = $.get(ils.processingcmd);
    $.when(response, iliasopencast.templates).done(function(dataResponse, templatesResponse) {
        var data = dataResponse[0];
        var templates = templatesResponse[0];
        if (data['lastupdate'] > -1) {
            if ($("#iliasopencast_finishedtable").data('lastupdate') < data['lastupdate']) {
                updateTable(data['finished'], "finished", templates);
                $("#iliasopencast_finishedtable").data('lastupdate', data['lastupdate']);
                console.log("updated finished");
            }
        }
        updateTable(data['processing'], "processing", templates);
        updateTable(data['onhold'], "onhold", templates);
        updateTable(data['scheduled'], "scheduled", templates);
    });

    var updateTable = function(data, dataname, templates) {
        var sections = {
            txt : txt,
            manualRelease : ils.manualRelease
        };
        sections[dataname] = [ {
            episodes : data
        } ];
        var tabledata = Mustache.render(templates, sections);

        $("#iliasopencast_" + dataname + "table").html(tabledata);
    }
}

$(document).ready(function() {
    iliasopencast.init();
});
