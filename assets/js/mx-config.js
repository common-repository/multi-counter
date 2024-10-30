var path = document.location.origin + "/wp-admin/admin-ajax.php";

jQuery(document).ready(function () {

    jQuery("#yandex_reset").on("click", function (event) {
        //event.preventDefault();
        var data = "action=reset_yandex_token";
        jQuery.ajax(
            {
                type: "POST",
                url: path,
                data: data,
                success: function (anData, msg) {
                    location.reload();
                },
                error: function (anData, msg) {
                    location.reload();
                }
            });
    });


});

