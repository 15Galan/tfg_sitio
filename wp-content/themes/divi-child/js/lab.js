jQuery(document).ready(function ($) {
    $("#startlab-button").on("click", function(e) {
        e.preventDefault()
        let $el = $(e.currentTarget);
        $el.attr('disabled', true);
        jQuery.ajax({
            type: "post",
            url: ajax_var.url,
            data: "action=" + ajax_var.start_action + "&nonce=" + ajax_var.nonce,
            success: function(result){
                if (result.success) {
                    $('#lab-row').addClass('running');
                    $('#actionlab-output').html(result.data);
                } else {
                    $('#actionlab-output').html(result.data);
                }
            },
            finally: () => {
                $el.attr('disabled', false);
            }
        });
    });

    $("#stoplab-button").on("click", function(e) {
        e.preventDefault()
        let $el = $(e.currentTarget);
        $el.attr('disabled', true);
        jQuery.ajax({
            type: "post",
            url: ajax_var.url,
            data: "action=" + ajax_var.stop_action + "&nonce=" + ajax_var.nonce,
            success: function(result){
                if (result.success) {
                    $('#lab-row').removeClass('running');
                    $('#actionlab-output').html(result.data);
                } else {
                    $('#actionlab-output').html(result.data);
                }
            },
            finally: () => {
                $el.attr('disabled', false);
            }
        });
    });

    if (current_lab && typeof current_lab.id != 'undefined') {
        $('#lab-row').addClass('running');
    }
});