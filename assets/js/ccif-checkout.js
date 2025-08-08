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
    $('body').on('change', '#billing_state', populateCities);

    // --- Initial Execution on Page Load ---
    togglePersonFields();
    updateRequiredStatus();

    // Populate cities on load if a state is already selected (e.g., on form validation error)
    // Also, trigger it on updated_checkout which is fired by WooCommerce after state field changes.
    $(document.body).on('updated_checkout', function() {
        // This is our final debug point. Let's see what the city field looks like AFTER
        // WooCommerce has finished its own AJAX updates.
        var cityFieldHTML = $('#billing_city_field').html();
        console.log('--- CCIF FINAL DEBUG ---');
        console.log('Event "updated_checkout" fired.');
        console.log('HTML content of city field wrapper (#billing_city_field):');
        console.log(cityFieldHTML);
        console.log('----------------------');

        // A small delay can help ensure our script runs after WooCommerce has finished its own updates.
        setTimeout(function() {
            if ($('#billing_state').val() && $('#billing_city').children().length <= 1) {
                populateCities();
            }
        }, 100);
    });

    // Initial population for page loads where state is already set.
    if ($('#billing_state').val()) {
        populateCities();
    }
});
