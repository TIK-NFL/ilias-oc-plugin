function initEdit(iliasopencast) {
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
        const template = '<div data-uniqueid="{{uniqueIdentifier}}"><div class="left fileName">{{fileName}} ({{file.type}})<span class="deleteFile">X</span></div><div class="progress"><div class="progress-bar progress-bar-success" role="progressbar" style="width:0%;">0%</span></div></div>';
        upload.r.on('fileAdded', function(file, event) {
            var rendered = Mustache.render(template, file);
            upload.results.append(rendered);
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
            const progress = Math.floor(file.progress() * 1000) / 10;
            $('[data-uniqueId=' + file.uniqueIdentifier + ']').find('.progress-bar').css('width', progress + '%').html('&nbsp;' + progress + '%').addClass("active progress-bar-striped");
        });

        upload.r.on('fileSuccess', function(file, message) {
            $('[data-uniqueId=' + file.uniqueIdentifier + ']').find('.progress').addClass('success');
            $('[data-uniqueId=' + file.uniqueIdentifier + ']').find('.progress-bar').removeClass("active progress-bar-striped");
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
                $('#iliasopencast_trackdate').datepicker('update', "today");
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
            $('[data-uniqueId=' + file.uniqueIdentifier + ']').find('.progress-bar').removeClass("active progress-bar-striped").addClass("progress-bar-warning");
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
            todayHighlight : true,
            todayBtn : "linked",
            immediateUpdates : true,
        }).on('hide', function(e) {
            upload.checkfieldscomplete();
        });
        $('#iliasopencast_trackdate').datepicker('update', "today");
        $('#iliasopencast_tracktime').timepicker({
            showMeridian : false,
            minuteStep : 1
        });

    }
}
iliasopencast.init = function() {
    iliasopencast.upload.init();

    iliasopencast.templates = $.get(iliasopencast.settings.uploadtarget + "/../templates/edit/edit.html");

    iliasopencast.lastupdate = -1;
    iliasopencast.updateprocessing();
    window.setInterval(iliasopencast.updateprocessing, 5000);
}

iliasopencast.updateprocessing = function() {
    const txt = iliasopencast.translation;
    const ils = iliasopencast.settings;
    const response = $.get(ils.processingcmd);
    $.when(response, iliasopencast.templates).done(function(dataResponse, templatesResponse) {
        const data = dataResponse[0];
        const templates = templatesResponse[0];
        if (data.lastupdate > -1) {
            if (iliasopencast.lastupdate < data.lastupdate) {
                updateTable(data['finished'], "finished", templates);
                showNumberIndicatorOnSubtab("finishedepisodes", data.finished.length);
                iliasopencast.lastupdate = data.lastupdate;
                console.log("updated finished");
            }
        }
        updateTable(data['processing'], "processing", templates);
        updateTable(data['onhold'], "onhold", templates);
        updateTable(data['scheduled'], "scheduled", templates);

        showNumberIndicatorOnSubtab("processtrim", data.onhold.length + data.processing.length);
        showNumberIndicatorOnSubtab("schedule", data.scheduled.length);
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

/**
 * @param string
 *            id id of sub tab
 * @param number
 *            number number to be shown
 */
function showNumberIndicatorOnSubtab(id, number) {
    const tabContent = $("#ilSubTab #subtab_" + id + " a").html(function(index, html) {
        const indexOfNumberIdicator = html.indexOf(" (<b>");
        const text = indexOfNumberIdicator == -1 ? html : html.substring(0, indexOfNumberIdicator);
        return text + (number == 0 ? "" : " (<b>" + number + "</b>)");
    });
}

$(document).ready(function() {
    iliasopencast.init();
});
}
