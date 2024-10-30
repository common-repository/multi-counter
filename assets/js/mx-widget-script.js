var path = document.location.origin + "/wp-admin/admin-ajax.php";

jQuery(document).ready(function () {
    runAnalytics();
});

function runAnalytics() {
    getAnalyticsData("google_analytics", "mx-google");
    getAnalyticsData("yandex_metrica", "mx-yandex");
    getAnalyticsData("statcounter", "mx-statcounter");
    getAnalyticsData("openstat", "mx-openstat");
}

function getAnalyticsData(action, id) {
    var data = "action=" + action;

    jQuery.ajax(
        {
            type: "POST",
            url: path,
            data: data,
            success: function (anData, msg) {
                processData(anData, id);
            },
            error: function (anData, msg) {
                setError(id);
            }
        });
}

function processData(json, id) {
    var data = jQuery.parseJSON(json);
    if (!data.status) {
        setError(id);
        return;
    }

    var info = "";
    var property;

    for (property in data.result) {
        info += getBlock(property, data.result[property]);
    }

    var mx = jQuery("#" + id);
    mx.empty();
    mx.append(info);

}

function getBlock(system_id, value) {
    var title;
    var block;

    if (value === null) {
        value = "—";
    }

    switch (system_id) {
        case "bounceCount":
            title = "Bounce Rate";
            break;
        case "organicSearchCount":
            title = "Organic Search";
            break;
        case "pagesCount":
            title = "Page Views";
            break;
        case "pagesPerSessionCount":
            title = "Pages/Session";
            break;
        case "sessionsCount":
            title = "Sessions";
            break;
        case "usersCount":
            title = "Users";
            break;
        default:
            title = "n/a";
    }

    block = "<div class='mx-block'><div class='title'>" + title + "</div><div class='data'>" + value + "</div></div>";
    return block;
}

function setError(id) {
    var block;
    block = "<div class='mx-error'>Please check settings</div>";

    var mx = jQuery("#" + id);
    mx.empty();
    mx.append(block);
}