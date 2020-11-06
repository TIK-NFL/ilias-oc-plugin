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
            finishedepisodes = data['finished'].map(x => x.episode_id).sort()
            onholdepisodes = data['onhold'].map(x => x.episode_id).sort()
            scheduledepisodes = data['scheduled'].map(x => x.episode_id).sort()
            if (!arrayEquals(finishedepisodes, iliasopencast.currentrenderings.finished)) {
                iliasopencast.currentrenderings.finished = finishedepisodes;
                updateTable(data['finished'], "finished", templates);
            }
            if (data['processing'] != []) {
                updateTable(data['processing'], "processing", templates);
            }
            if (!arrayEquals(onholdepisodes, iliasopencast.currentrenderings.onhold)) {
                iliasopencast.currentrenderings.onhold = onholdepisodes;
                updateTable(data['onhold'], "onhold", templates);
            }
            if (!arrayEquals(scheduledepisodes, iliasopencast.currentrenderings.scheduled)) {
                iliasopencast.currentrenderings.scheduled = scheduledepisodes;
                updateTable(data['scheduled'], "scheduled", templates);
            }
            showNumberIndicatorOnSubtab("finishedepisodes", data.finished.length);
            showNumberIndicatorOnSubtab("processtrim", data.onhold.length + data.processing.length);
            showNumberIndicatorOnSubtab("schedule", data.scheduled.length);
        });

        var arrayEquals = function(a, b) {
          return Array.isArray(a) &&
            Array.isArray(b) &&
            a.length === b.length &&
            a.every((val, index) => val === b[index]);
        }

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
