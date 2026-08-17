function initEdit(iliasopencast) {

    iliasopencast.init = function() {
        iliasopencast.requestInFlight = false;
        iliasopencast.updateprocessing();
        window.setInterval(iliasopencast.updateprocessing, 5000);
    }

    iliasopencast.updateprocessing = function() {
        if (iliasopencast.requestInFlight) {
            return;
        }

        iliasopencast.requestInFlight = true;
        $.getJSON(iliasopencast.settings.processingcmd).done(function(data) {
            ["finished", "processing", "onhold", "scheduled"].forEach(function(type) {
                const signature = JSON.stringify(data[type]);
                if (iliasopencast.currentrenderings[type] !== signature) {
                    const mount = document.getElementById("iliasopencast_" + type + "table");
                    if (mount && data.html && typeof data.html[type] === "string") {
                        replaceTable(mount, data.html[type]);
                        iliasopencast.currentrenderings[type] = signature;
                    }
                }
            });

            showNumberIndicatorOnSubtab("finishedepisodes", data.finished.length);
            showNumberIndicatorOnSubtab("processtrim", data.onhold.length + data.processing.length);
            showNumberIndicatorOnSubtab("schedule", data.scheduled.length);
        }).always(function() {
            iliasopencast.requestInFlight = false;
        });
    }

    function replaceTable(mount, html) {
        const fragment = document.createRange().createContextualFragment(html);
        const scripts = Array.from(fragment.querySelectorAll("script"));

        scripts.forEach(function(script) {
            script.remove();
        });
        mount.replaceChildren(fragment);

        scripts.forEach(function(script) {
            const executable = document.createElement("script");
            Array.from(script.attributes).forEach(function(attribute) {
                executable.setAttribute(attribute.name, attribute.value);
            });
            executable.textContent = script.textContent;
            mount.appendChild(executable);
        });
    }

    /**
     * @param id string id of sub tab
     * @param number number to be shown
     */
    function showNumberIndicatorOnSubtab(id, number) {
        const tabContent = $("#ilSubTab #subtab_" + id + " a").html(function(index, html) {
            const indexOfNumberIdicator = html.indexOf(" (<b>");
            const text = indexOfNumberIdicator === -1 ? html : html.substring(0, indexOfNumberIdicator);
            return text + (number === 0 ? "" : " (<b>" + number + "</b>)");
        });
    }

    iliasopencast.init();
}
