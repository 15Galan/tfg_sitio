jQuery(document).ready(function ($) {
    $("#startlab-button").on("click", function(e) {
        e.preventDefault()
        jQuery.ajax({
            type: "post",
            url: ajax_var.url,
            data: "action=" + ajax_var.action + "&nonce=" + ajax_var.nonce,
            success: function(result){
                $('#startlab-output').html(result);
            }
        });
    });
});