function initEdit(iliasopencast) {

    iliasopencast.init = function() {
        iliasopencast.templates = $.get(iliasopencast.settings.pluginbasedir + "/templates/edit/edit.html");

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

            updateTable(data['finished'], "finished", templates);
            updateTable(data['processing'], "processing", templates);
            updateTable(data['onhold'], "onhold", templates);
            updateTable(data['scheduled'], "scheduled", templates);

            showNumberIndicatorOnSubtab("finishedepisodes", data.finished.length);
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

    iliasopencast.init();
}
