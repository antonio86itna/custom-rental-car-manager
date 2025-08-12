jQuery(document).ready(function($) {
    // Vehicle type filter
    $(document).on('change', '#vehicle-type-filter', function() {
        const selectedType = $(this).val();
        const $cards = $('.crcm-vehicle-card');

        if (selectedType === '') {
            $cards.show();
        } else {
            $cards.each(function() {
                const cardType = $(this).data('vehicle-type');
                if (cardType === selectedType) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }

        updateResultsCount();
    });

    // Location filter
    $(document).on('change', '#location-filter', function() {
        const selectedLocation = $(this).val();
        const $cards = $('.crcm-vehicle-card');

        if (selectedLocation === '') {
            $cards.show();
        } else {
            $cards.each(function() {
                const cardLocation = $(this).data('location');
                if (cardLocation === selectedLocation) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }

        updateResultsCount();
    });

    // Price sorting
    $(document).on('change', '#price-sort-filter', function() {
        const sortType = $(this).val();
        const $grid = $('#vehicles-grid');
        const $cards = $('.crcm-vehicle-card').toArray();

        if (sortType === 'price-asc' || sortType === 'price-desc') {
            $cards.sort(function(a, b) {
                const priceA = parseFloat($(a).data('daily-rate'));
                const priceB = parseFloat($(b).data('daily-rate'));

                if (sortType === 'price-asc') {
                    return priceA - priceB;
                } else {
                    return priceB - priceA;
                }
            });

            $grid.empty().append($cards);
        }
    });

    // AJAX pagination
    $(document).on('click', '.crcm-pagination a', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');

        $.get(url, function(response) {
            const $html = $('<div>').html(response);
            const $content = $html.find('.crcm-vehicle-list-container').html();
            $('.crcm-vehicle-list-container').html($content);

            $('#vehicle-type-filter').trigger('change');
            $('#location-filter').trigger('change');
            updateResultsCount();
        });
    });

    // Book now button click
    $(document).on('click', '.crcm-book-now', function() {
        const vehicleId = $(this).data('vehicle-id');
        const pickupDate = $(this).data('pickup-date');
        const returnDate = $(this).data('return-date');
        const pickupTime = $(this).data('pickup-time');
        const returnTime = $(this).data('return-time');

        // Build booking URL with parameters
        const bookingUrl = new URL(window.location.origin + '/booking-form/');
        bookingUrl.searchParams.set('vehicle', vehicleId);
        bookingUrl.searchParams.set('pickup_date', pickupDate);
        bookingUrl.searchParams.set('return_date', returnDate);
        bookingUrl.searchParams.set('pickup_time', pickupTime);
        bookingUrl.searchParams.set('return_time', returnTime);

        window.location.href = bookingUrl.toString();
    });

    // Update results count
    function updateResultsCount() {
        const visibleCards = $('.crcm-vehicle-card:visible').length;
        $('.crcm-results-count').text('Trovati ' + visibleCards + ' veicoli');
    }
});
