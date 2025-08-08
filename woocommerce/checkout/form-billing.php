<?php
/**
 * Checkout billing information form
 *
 * This template is a custom override to create a card-based layout.
 */

defined( 'ABSPATH' ) || exit;

$checkout = WC()->checkout();
$fields   = $checkout->get_checkout_fields( 'billing' );
?>
<div class="woocommerce-billing-fields">
    <h2 class="ccif-main-header"><?php esc_html_e( 'اطلاعات صورتحساب و حمل و نقل', 'ccif-iran-checkout' ); ?></h2>

    <?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

    <div class="ccif-checkout-form">
        <?php
        // --- Card 1: Invoice Request ---
        echo '<div class="ccif-box ccif-invoice-request-card">';
            echo '<h2>درخواست صدور فاکتور رسمی (اختیاری)</h2>';
            woocommerce_form_field('billing_invoice_request', $fields['billing_invoice_request'], $checkout->get_value('billing_invoice_request'));
            echo '<p class="ccif-hint">در صورت نیاز به فاکتور رسمی، این گزینه را انتخاب و تمام اطلاعات خریدار را به دقت وارد نمایید.</p>';
        echo '</div>';


        // --- Card 2: Buyer Information ---
        echo '<div class="ccif-box ccif-buyer-info-card">';
            echo '<h2>اطلاعات خریدار</h2>';
            woocommerce_form_field('billing_person_type', $fields['billing_person_type'], $checkout->get_value('billing_person_type'));

            // Real Person Fields Wrapper
            echo '<div class="ccif-real-person-fields-wrapper">';
                woocommerce_form_field('billing_first_name', $fields['billing_first_name'], $checkout->get_value('billing_first_name'));
                woocommerce_form_field('billing_last_name', $fields['billing_last_name'], $checkout->get_value('billing_last_name'));
                woocommerce_form_field('billing_national_code', $fields['billing_national_code'], $checkout->get_value('billing_national_code'));
            echo '</div>';

            // Legal Person Fields Wrapper
            echo '<div class="ccif-legal-person-fields-wrapper">';
                woocommerce_form_field('billing_company_name', $fields['billing_company_name'], $checkout->get_value('billing_company_name'));
                woocommerce_form_field('billing_economic_code', $fields['billing_economic_code'], $checkout->get_value('billing_economic_code'));
                woocommerce_form_field('billing_agent_first_name', $fields['billing_agent_first_name'], $checkout->get_value('billing_agent_first_name'));
                woocommerce_form_field('billing_agent_last_name', $fields['billing_agent_last_name'], $checkout->get_value('billing_agent_last_name'));
            echo '</div>';
        echo '</div>';


        // --- Card 3: Shipping Information ---
        echo '<div class="ccif-box ccif-shipping-info-card">';
            echo '<h2>اطلاعات ارسال</h2>';
            // Render the VISIBLE custom state and city fields
            woocommerce_form_field('billing_custom_state', $fields['billing_custom_state'], $checkout->get_value('billing_custom_state'));
            woocommerce_form_field('billing_custom_city', $fields['billing_custom_city'], $checkout->get_value('billing_custom_city'));

            // Render the HIDDEN original state and city fields
            woocommerce_form_field('billing_state', $fields['billing_state'], $checkout->get_value('billing_state'));
            woocommerce_form_field('billing_city', $fields['billing_city'], $checkout->get_value('billing_city'));

            // Render the rest of the address fields
            woocommerce_form_field('billing_address_1', $fields['billing_address_1'], $checkout->get_value('billing_address_1'));
            woocommerce_form_field('billing_postcode', $fields['billing_postcode'], $checkout->get_value('billing_postcode'));
            woocommerce_form_field('billing_phone', $fields['billing_phone'], $checkout->get_value('billing_phone'));
        echo '</div>';
        ?>
    </div>

    <?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
</div>
