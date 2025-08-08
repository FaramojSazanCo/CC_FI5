jQuery(function($) {
    'use strict';

    // Ensure ccifData and cities exist to prevent errors
    if (typeof ccifData === 'undefined' || !ccifData.cities) {
        console.error('CCIF Iran Checkout: City data is not available.');
        return;
    }
    var cities = ccifData.cities;

    /**
     * Toggles the visibility of fields for Real vs. Legal persons.
     * The wrappers are hidden by default via CSS.
     */
    function togglePersonFields() {
        var personType = $('#billing_person_type').val();
        var $realPersonWrapper = $('.ccif-real-person-fields-wrapper');
        var $legalPersonWrapper = $('.ccif-legal-person-fields-wrapper');

        // Use a more efficient slide toggle
        if (personType === 'real') {
            $legalPersonWrapper.slideUp(250);
            $realPersonWrapper.slideDown(350);
        } else if (personType === 'legal') {
            $realPersonWrapper.slideUp(250);
            $legalPersonWrapper.slideDown(350);
        } else {
            $realPersonWrapper.slideUp(250);
            $legalPersonWrapper.slideUp(250);
        }
    }

    /**
     * Updates the 'required' status of fields based on the invoice checkbox.
     */
    function updateRequiredStatus() {
        var isInvoiceRequested = $('#billing_invoice_request').is(':checked');

        // Target all fields within the new buyer info card.
        $('.ccif-buyer-info-card .form-row').each(function() {
            var $wrapper = $(this);
            var $input = $wrapper.find('input, select');

            // Set the required property on the input/select element
            $input.prop('required', isInvoiceRequested);

            // Toggle a class on the wrapper for CSS styling of the indicator
            $wrapper.toggleClass('ccif-is-required', isInvoiceRequested);
        });

        // Trigger the WooCommerce event to update its validation state
        $(document.body).trigger('update_checkout');
    }

    /**
     * Populates the city dropdown based on the selected state.
     */
    function populateCities() {
        var state = $('#billing_state').val();
        var $cityField = $('#billing_city');
        var cities = (ccifData && ccifData.cities) ? ccifData.cities : {};
        var currentCity = $cityField.val();

        // Avoid repopulating if the city is already correctly set, unless the state is empty.
        if ( $cityField.children().length > 1 && currentCity && state ) {
            // If a valid city is selected and the state hasn't changed to the default, do nothing.
            // This prevents the city from being reset when other parts of the checkout update.
            var cityExists = false;
            if(cities[state]) {
                $.each(cities[state], function(index, cityName) {
                    if(cityName === currentCity) {
                        cityExists = true;
                        return false; // break the loop
                    }
                });
            }
            if(cityExists) return;
        }

        $cityField.empty().append('<option value="">' + 'ابتدا استان را انتخاب کنید' + '</option>');

        if (state && cities[state]) {
            $.each(cities[state], function(index, cityName) {
                $cityField.append($('<option>', {
                    value: cityName,
                    text: cityName,
                    selected: cityName === currentCity
                }));
            });
        }
    }

    // --- Event Handlers ---
    $('body').on('change', '#billing_person_type', togglePersonFields);
    $('body').on('change', '#billing_invoice_request', updateRequiredStatus);

    // We no longer populate cities on direct change to avoid race conditions.
    // Instead, we rely *only* on the 'updated_checkout' event.
    // $('body').on('change', '#billing_state', populateCities);

    // --- Initial Execution on Page Load ---
    togglePersonFields();
    updateRequiredStatus();

    // The 'updated_checkout' event is the most reliable hook. It fires after WC has
    // finished its AJAX updates. We will re-populate the cities every time to ensure
    // they are correct, as this event fires after a state change.
    $(document.body).on('updated_checkout', function() {
        populateCities();
    });

    // Initial population for page loads where state is already set.
    // We trigger 'updated_checkout' manually to use the same reliable logic.
    $(document.body).trigger('updated_checkout');
});
