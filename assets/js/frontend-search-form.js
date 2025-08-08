jQuery(document).ready(function($) {
    // Advanced options toggle
    $('#advanced-options-toggle').on('click', function() {
        const $timeFields = $('.crcm-time-fields');
        const $button = $(this);
        
        if ($timeFields.is(':visible')) {
            $timeFields.slideUp(300);
            $button.removeClass('active');
        } else {
            $timeFields.slideDown(300);
            $button.addClass('active');
        }
    });
    
    // Auto-sync return location with pickup location
    $('#pickup_location').on('change', function() {
        const selectedValue = $(this).val();
        const selectedText = $(this).find('option:selected').text();
        
        if (selectedValue) {
            $('#return_location').val(selectedValue);
        }
    });
    
    // Set minimum dates
    const today = new Date().toISOString().split('T')[0];
    $('#pickup_date, #return_date').attr('min', today);
    
    // Auto-set return date when pickup date changes
    $('#pickup_date').on('change', function() {
        const pickupDate = new Date($(this).val());
        const returnDate = new Date(pickupDate);
        returnDate.setDate(returnDate.getDate() + 1);
        
        const returnDateString = returnDate.toISOString().split('T')[0];
        $('#return_date').attr('min', returnDateString);
        
        if (!$('#return_date').val()) {
            $('#return_date').val(returnDateString);
        }
    });
});
