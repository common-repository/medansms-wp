jQuery(document).ready(function ($) {
    $("#medansmswp-subscribe #medansmswp-submit").click(function () {
        $("#medansmswp-result").hide();

        subscriber = new Array();
        subscriber['name'] = $("#medansmswp-name").val();
        subscriber['mobile'] = $("#medansmswp-mobile").val();
        subscriber['groups'] = $("#medansmswp-groups").val();
        subscriber['type'] = $('input[name=subscribe_type]:checked').val();

        $("#medansmswp-subscribe").ajaxStart(function () {
            $("#medansmswp-submit").attr('disabled', 'disabled');
            $("#medansmswp-submit").text("Loading...");
        });

        $("#medansmswp-subscribe").ajaxComplete(function () {
            $("#medansmswp-submit").removeAttr('disabled');
            $("#medansmswp-submit").text("Subscribe");
        });

        $.post(ajax_object.ajaxurl, {
            widget_id: $('#medansmswp-widget-id').attr('value'),
            action: 'subscribe_ajax_action',
            name: subscriber['name'],
            mobile: subscriber['mobile'],
            group: subscriber['groups'],
            type: subscriber['type'],
            nonce: ajax_object.nonce
        }, function (data, status) {

            var response = $.parseJSON(data);

            if (response.status == 'error') {
                $("#medansmswp-result").fadeIn();
                $("#medansmswp-result").html('<span class="medansmswp-message-error">' + response.response + '</div>');
            }

            if (response.status == 'success') {
                $("#medansmswp-result").fadeIn();
                $("#medansmswp-step-1").hide();
                $("#medansmswp-result").html('<span class="medansmswp-message-success">' + response.response + '</div>');
            }

            if (response.action == 'activation') {
                $("#medansmswp-step-2").show();
            }

        });

    });

    $("#medansmswp-subscribe #activation").on('click', function () {
        $("#medansmswp-result").hide();
        subscriber['activation'] = $("#medansmswp-ativation-code").val();

        $("#medansmswp-subscribe").ajaxStart(function () {
            $("#activation").attr('disabled', 'disabled');
            $("#activation").text('Loading...');
        });

        $("#medansmswp-subscribe").ajaxComplete(function () {
            $("#activation").removeAttr('disabled');
            $("#activation").text('Activation');
        });

        $.post(ajax_object.ajaxurl, {
            widget_id: $('#medansmswp-widget-id').attr('value'),
            action: 'activation_ajax_action',
            mobile: subscriber['mobile'],
            activation: subscriber['activation'],
            nonce: ajax_object.nonce
        }, function (data, status) {
            var response = $.parseJSON(data);

            if (response.status == 'error') {
                $("#medansmswp-result").fadeIn();
                $("#medansmswp-result").html('<span class="medansmswp-message-error">' + response.response + '</div>');
            }

            if (response.status == 'success') {
                $("#medansmswp-result").fadeIn();
                $("#medansmswp-step-2").hide();
                $("#medansmswp-result").html('<span class="medansmswp-message-success">' + response.response + '</div>');
            }
        });
    });
});