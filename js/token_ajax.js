(function($) {
    $(document).ready(function() {
        var $form = $('#ttnTStokenForm');
        var $submit = $('#submit');
        var $message = $('#message');
        var $apiToken = $('#api_token_ttn');
        
        $form.on('submit', function(e) {
            e.preventDefault();
            var apiToken = $apiToken.val();
            $submit.hide();
            $.ajax({
                type: 'POST',
                url: summarize_content_ajax_object.ajax_url,
                dataType: 'json', // Ensure the response is treated as JSON
                data: {
                    action: 'TopTechNewsTextSumm_validate_and_update_api_token',
                    api_token: apiToken,
                    nonce: summarize_content_ajax_object.nonce
                }
            }).done(function(response) {
                var message = response.data && response.data.message ? response.data.message : 'Success, but no message returned.';
                if (response.success) {
                    $message.text(message).removeClass('error').addClass('success');
                } else {
                    $message.text(message).removeClass('success').addClass('error');
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                var message = textStatus + ': ' + errorThrown;
                $message.text(message).removeClass('success').addClass('error');
            }).always(function() {
                $submit.show();
            });
        });
    });
})(jQuery);
