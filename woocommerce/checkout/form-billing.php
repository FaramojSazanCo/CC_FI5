<?php
/**
 * Checkout billing information form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-billing.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

// Get the checkout object and all billing fields
$checkout = WC()->checkout();
$fields   = $checkout->get_checkout_fields( 'billing' );

// Define the structure of our cards and the fields they contain
$card_structure = [
    'invoice' => [
        'title'  => 'درخواست صدور فاکتور رسمی (اختیاری)',
        'fields' => ['billing_invoice_request'],
        'hint'   => 'در صورت نیاز به فاکتور رسمی، این گزینه را انتخاب و تمام اطلاعات خریدار را به دقت وارد نمایید.',
    ],
    'buyer' => [
        'title'  => 'اطلاعات خریدار',
        'fields' => [
            'billing_person_type',
            // These will be wrapped conditionally by JS
            'billing_first_name',
            'billing_last_name',
            'billing_national_code',
            'billing_company_name',
            'billing_economic_code',
            'billing_agent_first_name',
            'billing_agent_last_name',
        ],
    ],
    'shipping' => [
        'title'  => 'اطلاعات ارسال',
        'fields' => [
            'billing_state',
            'billing_city',
            'billing_address_1',
            'billing_postcode',
            'billing_phone',
        ],
    ],
];

?>
<div class="woocommerce-billing-fields">
    <?php if ( wc_ship_to_billing_address_only() && WC()->cart->needs_shipping() ) : ?>
        <h2 class="ccif-main-header"><?php esc_html_e( 'Billing &amp; Shipping', 'woocommerce' ); ?></h2>
    <?php else : ?>
        <h2 class="ccif-main-header"><?php esc_html_e( 'Billing details', 'woocommerce' ); ?></h2>
    <?php endif; ?>

    <?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

    <div class="ccif-checkout-form">
        <?php
        // --- Card 1: Invoice Request ---
        $card = $card_structure['invoice'];
        echo '<div class="ccif-box ccif-invoice-request-card">';
        echo '<h2>' . esc_html($card['title']) . '</h2>';
        // Render the single field for this card
        woocommerce_form_field($card['fields'][0], $fields[$card['fields'][0]], $checkout->get_value($card['fields'][0]));
        // Render the hint text
        echo '<p class="ccif-hint">' . esc_html($card['hint']) . '</p>';
        echo '</div>';


        // --- Card 2: Buyer Information ---
        $card = $card_structure['buyer'];
        echo '<div class="ccif-box ccif-buyer-info-card">';
        echo '<h2>' . esc_html($card['title']) . '</h2>';
        // Render person type dropdown first
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
        $card = $card_structure['shipping'];
        echo '<div class="ccif-box ccif-shipping-info-card">';
        echo '<h2>' . esc_html($card['title']) . '</h2>';
        // Loop through and render all shipping fields
        foreach ($card['fields'] as $field_name) {
            if (isset($fields[$field_name])) {
                woocommerce_form_field($field_name, $fields[$field_name], $checkout->get_value($field_name));
            }
        }
        echo '</div>';

        ?>
    </div>

    <?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
</div>
