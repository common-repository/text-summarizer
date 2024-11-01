jQuery(document).ready(($) => {
    // Cached jQuery selectors
    const errorContainer = $('.error'),
          successContainer = $('.success'),
          $coverSpin = $('#cover-spin'),
          $textarea = $("#textarea"),
          $tokenCount = $('#token-count'),
          $remainingLimitHdn = $('#remaining-limit-hdn'),
          $sumLength = $('#sum_length'),
          $sliderValue = $('#slider-value'),
          $sumQuality = $("#sum_quality"),
          $nonce = $('#summarize_content_nonce'),
          $copyText = $('#copyText'),
          $refreshLink = $('#refresh-link'),
          $sbmtText = $("#sbmtText");

    // Update slider value on input
    $sumLength.on('input', function() {
        $sliderValue.text($(this).val());
    });

    // Send data to API after validation
    $sbmtText.click((event) => {
        event.preventDefault();
        let textAreaVal = $textarea.val().trim();
        processSubmission(textAreaVal);
    });

    $('#copyText').click(function() {
        var copyText = document.getElementById("textarea"); 
        copyText.select(); 
        document.execCommand("copy"); 
        alert("Text copied to clipboard!"); 
    });

    // Refresh page functionality
    $refreshLink.click(() => {
        location.reload();
        return false;
    });

    // Debounced token counting for textarea input
    const debounce = (func, wait) => {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    };

    const countPromptTokens = debounce((text) => {
        $.ajax({
            type: 'POST',
            url: tokenCountData.ajaxurl,
            dataType: 'json',
            data: {
                'action': 'TopTechNewsTextSumm_fetchTokenCount',
                'text': text,
                'nonce': tokenCountData.nonce
            },
            success: (response) => {
                if (response.success) {
                    $tokenCount.val(response.data.num_tokens);
                } else {
                    console.error(response.data.message);
                }
            },
            error: (xhr, status, error) => {
                console.error('AJAX error:', error);
            }
        });
    }, 250);

    $textarea.on('input', function() {
        countPromptTokens($(this).val());
    });

    function processSubmission(textAreaVal) {
        if (validateInput(textAreaVal)) {
            $coverSpin.show(0);
            textAreaVal = sanitizeInput(textAreaVal);
            $textarea.val(textAreaVal);
            submitData(textAreaVal);
        }
    }

    function validateInput(textAreaVal) {
        if (textAreaVal === "") {
            errorContainer.text("Text area must be filled out");
            return false;
        }

        let wordCount = textAreaVal.split(/\s+/).length;
        if (wordCount > 2000) {
            errorContainer.text("Text area can't contain more than 2000 words");
            return false;
        }

        let completion_tokens = parseInt($sliderValue.text(), 10),
            prompt_tokens = parseInt($tokenCount.val(), 10),
            token_sum = (completion_tokens * 3) + prompt_tokens,
            remaining_limit = parseInt($remainingLimitHdn.val(), 10);

        if (token_sum >= remaining_limit) {
            errorContainer.text("Please increase your remaining limit, subscribe again or add tokens.");
            return false;
        }

        return true;
    }

    function sanitizeInput(input) {
        return input.replace(/<[^>]*>/g, '').replace(/[^\w\s]/gi, '');
    }

    function submitData(textAreaVal) {
        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: {
                'action': 'TopTechNewsTextSumm_summarize_content_action',
                'textarea': textAreaVal,
                'sum_length': parseInt($sliderValue.text(), 10),
                'sum_quality': $sumQuality.val(),
                'summarize_content_nonce': $nonce.val()
            },
            dataType: 'json',
            success: (response) => handleSuccess(response),
            error: (xhr, status, error) => handleError(xhr, status, error)
        });
    }

    function handleSuccess(response) {
        //console.log(response); return false;
        $coverSpin.hide();
        if (response.success) {
            let responseData = response.data.data;
            successContainer.text(response.data.message).fadeIn().delay(5000).fadeOut();
            $textarea.val(responseData.summarized_text);
            $('.tokens_used').text(responseData.token_used);
            $('#remaining-limit').text(responseData.remaining_limit);
            $copyText.show();
            $sbmtText.hide();
            $('.token-container').hide();
            $refreshLink.show();
        } else {
            errorContainer.text(response.data.message);
            $textarea.val('');
        }
    }

    function handleError(xhr, status, error) {
        $coverSpin.hide();
        errorContainer.text(`Error while processing request: ${xhr.status}: ${xhr.statusText}`);
    }
});
