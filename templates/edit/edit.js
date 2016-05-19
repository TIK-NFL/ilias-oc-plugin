$(document)
    .ready(
        function() {
            $('#trackdate').datepicker({
                format : "yyyy-mm-dd",
                autoclose : true,
                setDate : new Date(),
                todayHighlight : true,
                todayBtn : "linked",
            });
            var chunksize = 1 * 1024 * 1024;
            var r = new Resumable({
                target : uploadtarget + "/upload",
                query : {},
                generateUniqueIdentifier : function() {
                    return seriesid;
                },
                maxChunkRetries : 2,
                maxFiles : 3,
                prioritizeFirstAndLastChunk : false,
                simultaneousUploads : 1,
                chunkSize : chunksize,
                forceChunkSize : true,
                testChunks : false
            });
            var results = $('#results'), draggable = $('#dragHere'), uploadFile = $('#uploadFiles'), browseButton = $('#browseButton'), nothingToUpload = $('[data-nothingToUpload]'), mediapackage = "", uniqueid = "", fileurl = "";

            // if resumable is not supported aka IE
            if (!r.support)
                location.href = 'http://browsehappy.com/';

            r.assignBrowse(browseButton);

            r.on('fileAdded', function(file, event) {
                var template = '<div data-uniqueid="' + file.uniqueIdentifier
                    + '">' + '<div class="left fileName">' + file.fileName + ' ('
                    + file.file.type + ')'
                    + '<span class="deleteFile">X</span></div>'
                    + '<div class="progress">'
                    + '<div class="progress-bar progress-bar-success" role="progressbar" style="width:0%;">0%</span>'
                    + '</div>' + '</div>';

                results.append(template);
                uploadFile.toggleClass("disabled");
            });

            uploadFile.on('click', function() {
                if (results.children().length > 0) {
                    $.post(uploadtarget + "/createEpisode", {
                        seriesid : seriesid,
                        episodename : $('#tracktitle').val(),
                        presenter : $('#trackpresenter').val(),
                        episodedate : $('#trackdate').val(),
                        episodetime : $('#tracktime').val()
                    }).done(
                        function(data) {
                            // alert( "Data
                            // Loaded: " +
                            // data );
                            mediapackage = (new XMLSerializer())
                                .serializeToString(data);
                            $.post(uploadtarget + "/newJob", {
                                seriesid : seriesid,
                                mediaPackage : escape(mediapackage),
                                filename : r.files[0].fileName,
                                filesize : r.files[0].size,
                                chunksize : chunksize
                            }).done(function(data) {
                                r.opts["query"] = {
                                    jobid : data,
                                    seriesid : seriesid
                                };
                                r.upload();
                            });
                        });
                } else {
                    nothingToUpload.fadeIn();
                    setTimeout(function() {
                        nothingToUpload.fadeOut();
                    }, 3000);
                }
            });

            $(document)
                .on(
                    'click',
                    '.deleteFile',
                    function() {
                        var self = $(this), parent = self.parent(), identifier = parent
                            .data('uniqueid'), file = r
                            .getFromUniqueIdentifier(identifier);

                        r.removeFile(file);
                        parent.remove();
                    });

            r.on('fileProgress', function(file) {
                var progress = Math.floor(file.progress() * 100);
                $('[data-uniqueId=' + file.uniqueIdentifier + ']').find(
                    '.progress-bar').css('width', progress + '%');
                $('[data-uniqueId=' + file.uniqueIdentifier + ']').find(
                    '.progress-bar').html('&nbsp;' + progress + '%');
            });

            r
                .on(
                    'fileSuccess',
                    function(file, message) {
                        $('[data-uniqueId=' + file.uniqueIdentifier + ']')
                            .find('.progress').addClass('success');
                        fileurl = parseXml(message).getElementsByTagName("url")[0].childNodes[0].nodeValue;
                        $.post(uploadtarget + "/finishUpload", {
                            seriesid : seriesid,
                            mediaPackage : escape(mediapackage),
                            fileurl : fileurl
                        }).done(function() {
                            for (var i = 0; i < r.files.length; i++) {
                                r.removeFile(r.files[i])
                            }
                            $('#results').empty();
                            $('#tracktitle').val("");
                            $('#trackpresenter').val("");
                            $('#trackdate').val("");
                            $('#tracktime').val("");
                        });
                    });

            r.on('uploadStart', function() {
                $('.alert-box').text('Uploading...');
            });

            r.on('complete', function() {
                $('.alert-box').text('Done uploading');
            });

            updateprocessing();
            window.setInterval(updateprocessing, 2000);

        });
var parseXml = function(xmlStr) {
    return (new window.DOMParser()).parseFromString(xmlStr, "text/xml");
};

var updateprocessing = function() {
    
    var templates = {
        "noprocessing" : '<tr class="tblrow1"><td class="std" style="text-align:center;" colspan="4">'
            + txt_none_processing + '</td></tr>',
        "processing" : '<tr><td class="std">{{title}}</td> <td class="std">{{date}}</td>'
            + '<td><div class="progress"><div class="progress-bar progress-bar-success" role="progressbar" style="width:{{processdone}}%">{{processcount}}</div></td><td>{{running}}</td>'
            + '</tr>',
        "nofinished" : '<tr class="tblrow1"><td class="std" style="text-align:center;" colspan="'
            + cols_finished + '">' + txt_none_finished + '</td></tr>',
        "finished" : '<tr>'
            + '<td class="std"><a href="{{#convertAmpersand}}{{viewurl}}{{/convertAmpersand}}">{{title}}</a></td>'
            + '<td class="std"><a href="{{#convertAmpersand}}{{viewurl}}{{/convertAmpersand}}"><img src="{{previewurl}}" /></a></td>'        
            + '<td class="std">{{date}}</td>' 
            + ((cols_finished == 4)  ? '<td><a href="{{#convertAmpersand}}{{publishurl}}{{/convertAmpersand}}">{{ txt_publish }}</a></td>' : '') 
            + '</tr>',
        'noonhold' : '<tr class="tblrow1"><td class="std" style="text-align:center;" colspan="4">'
            + txt_none_onhold + '</td></tr>',
        'onhold' : '<tr><td class="std"><a href="{{#convertAmpersand}}{{trimurl}}{{/convertAmpersand}}">{{title}}</a></td> <td class="std">{{date}}</td></tr>',
        'noscheduled' : '<tr class="tblrow1"><td class="std" style="text-align:center;" colspan="4">'
            + txt_none_scheduled + '</td></tr>',
        'scheduled' : '<tr>'
            + '<td class="std">{{title}}</td> <td class="std">{{startdate}} </td> <td class="std"> {{stopdate}} </td> <td class="std">{{location}} </td>'
            + '</tr>'

    };
    var tabledata = "";
    $
        .get(processingcmd)
        .done(
            function(data) {
                if (data['lastupdate'] > -1) {
                    if ($("#finishedtable").data('lastupdate') < data['lastupdate']) {
                        updateTable(data['finished'],"finished");
                        $("#finishedtable").data('lastupdate',
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
        $("#"+dataname +"table").html(tabledata);
    }
}

