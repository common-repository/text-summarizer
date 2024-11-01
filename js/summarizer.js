jQuery(document).ready(($) => {
    // Cache selectors
    const $spinner = $('#sc-spinner'),
          $ajaxMsg = $('#sc-ajax-msg'),
          $summaryResult = $('#sc-summaryResult'),
          $addSummaryBtn = $('#sc-addSummaryBtn'),
          $summaryLengthValue = $('#sc-summary-length-value');

    // Tooltip hover events
    $('.sc-tooltip').hover(
        function() {
            $(this).find('.sc-tooltiptext').css({'visibility': 'visible', 'opacity': '1'});
        },
        function() {
            $(this).find('.sc-tooltiptext').css({'visibility': 'hidden', 'opacity': '0'});
        }
    );

    // Range input value change event
    $('#sc-epSumLen').on('input', function() {
        $summaryLengthValue.text(this.value);
    });

    // Summarize content edit post AJAX request
    $('#sc-epSumBtn').on('click', function() {
        const button = $(this);
        button.prop('disabled', true);
        $spinner.removeClass('hide');
        $ajaxMsg.addClass('hide');

        $.ajax({
            type: 'POST',
            url: scsummarizerVars.ajax_url,
            data: {
                action: 'TopTechNewsTextSumm_summarizePost',
                postId: $('#sc-epPostId').val(),
                summaryStyle: $('#sc-epSumStyl').val(),
                summaryLength: parseInt($summaryLengthValue.text()),
                nonce: scsummarizerVars.nonce
            },
            success: (response) => handleSuccess(response, button),
            error: () => handleError(button)
        });
    });

    // Add Summary to Content button click event
    $('#sc-addSummaryBtn').on('click', () => {
        if (window.confirm("Do you want to add this summary at the top of the content?")) {
            $.ajax({
                type: 'POST',
                url: sc_summaraizer_updt_cntnt.ajax_url,
                data: {
                    action: 'TopTechNewsTextSumm_updatePostContent',
                    postId: $('#sc-epPostId').val(),
                    summaryText: $summaryResult.val(),
                    nonce: sc_summaraizer_updt_cntnt.nonce
                },
                success: (response) => {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(`Error: ${response.data}`);
                    }
                },
                error: () => alert('An error occurred while updating the post content.')
            });
        }
    });

    function handleSuccess(response, button) {
        $spinner.addClass('hide');
        button.hide(); // Hide the button
        if (response.success) {
            const { data } = response.data.success;
            $ajaxMsg.text(response.data.success.message).removeClass('hide error').addClass('success');
            $summaryResult.val(data.summarized_text).prop('hidden', false);
            $addSummaryBtn.prop('hidden', false);
            console.log(`Tokens used: ${data.token_used}`);
            console.log(`Remaining limit: ${data.remaining_limit}`);
        } else {
            button.prop('disabled', false);
            console.error('Server error:', response.data);
            $ajaxMsg.text(response.data).removeClass('hide success').addClass('error');
        }
    }

    function handleError(button) {
        button.prop('disabled', false);
        $spinner.addClass('hide');
        $ajaxMsg.text('Summarize edit post AJAX request failed').removeClass('hide success').addClass('error');
    }
});
