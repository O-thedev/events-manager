jQuery(document).ready(function($) {
    // Handle RSVP button click
    $('.em-rsvp-button').on('click', function() {
        var button = $(this);
        var eventId = button.data('event-id');
        var messageDiv = button.siblings('.em-rsvp-message');
        
        button.prop('disabled', true);
        messageDiv.removeClass('success error').empty();
        
        $.ajax({
            url: em_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'handle_rsvp',
                event_id: eventId,
                nonce: em_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    messageDiv.addClass('success').text(response.data.message);
                    button.text('RSVP Confirmed');
                } else {
                    messageDiv.addClass('error').text(response.data.message);
                    button.prop('disabled', false);
                }
            },
            error: function() {
                messageDiv.addClass('error').text('An error occurred. Please try again.');
                button.prop('disabled', false);
            }
        });
    });
    
    // Live search/filter on archive page
    var filterTimer;
    $('.em-filter-form input, .em-filter-form select').on('change keyup', function() {
        clearTimeout(filterTimer);
        filterTimer = setTimeout(function() {
            $('.em-filter-form').submit();
        }, 500);
    });
    
    // Date picker enhancement
    if ($('.em-filter-form input[type="date"]').length) {
        $('.em-filter-form input[type="date"]').on('focus', function() {
            $(this).attr('type', 'date');
        });
    }
});