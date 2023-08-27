jQuery(document).ready(function ($) {
    // Al pulsar el botón de inicio del laboratorio, se ejecuta:
    $("#startlab-button").on("click", function(e) {
        // Evitar acción por defecto
        e.preventDefault()

        let $el = $(e.currentTarget);

        $el.attr('disabled', true);

        // Llamada AJAX
        jQuery.ajax({
            type: "post",
            url: ajax_var.url,
            data: "action=" + ajax_var.start_action + "&nonce=" + ajax_var.nonce,
            success: function(result) {
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

    // Al pulsar el botón de detención del laboratorio, se ejecuta:
    $("#stoplab-button").on("click", function(e) {
        // Evitar acción por defecto
        e.preventDefault()

        let $el = $(e.currentTarget);

        $el.attr('disabled', true);

        // Llamada AJAX
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

    // Comprobar si el laboratorio está en ejecución
    if (current_lab && typeof current_lab.id != 'undefined') {
        $('#lab-row').addClass('running');
    }
});
