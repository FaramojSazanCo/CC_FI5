<?php
/**
 * Plugin Name: CCIF Iran Checkout (Fresh Rebuild)
 * Description: A plugin to customize the WooCommerce checkout form for Iran.
 * Version: 6.0
 * Author: Your Name
 * Text Domain: ccif-iran-checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CCIF_Iran_Checkout_Rebuild {

    private $order_notes_field = [];
    private $custom_billing_fields = [
        'billing_person_type',
        'billing_national_code',
        'billing_company_name',
        'billing_economic_code',
        'billing_agent_first_name',
        'billing_agent_last_name',
        'billing_invoice_request'
    ];

    public function __construct() {
        // Add custom validation
        add_action( 'woocommerce_checkout_process', [ $this, 'validate_custom_fields' ] );

        // Modify checkout fields
        add_filter( 'woocommerce_checkout_fields', [ $this, 'modify_checkout_fields' ] );

        // Hook into checkout fields to manage them
        add_filter( 'woocommerce_checkout_fields', [ $this, 'move_order_notes_field' ] );

        // Modify field arguments, e.g., to remove '(optional)' text
        add_filter( 'woocommerce_form_field_args', [ $this, 'remove_optional_text' ], 10, 3 );

        // Save custom fields to order meta
        add_action( 'woocommerce_checkout_create_order', [ $this, 'save_custom_fields_to_order_meta' ], 10, 2 );

        // Save custom fields to user meta
        add_action( 'woocommerce_checkout_update_user_meta', [ $this, 'save_custom_fields_to_user_meta' ], 10, 2 );

        // Pre-populate custom fields from user meta
        add_filter( 'woocommerce_checkout_get_value', [ $this, 'get_custom_field_value_from_user_meta' ], 10, 2 );

        // Enqueue scripts and styles
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        // --- New Template Override Logic ---
        // This filter forces WooCommerce to use our custom form-billing.php template
        add_filter( 'woocommerce_locate_template', [ $this, 'override_billing_form_template' ], 10, 3 );

        // This action renders the "Additional Notes" card after the billing form
        add_action( 'woocommerce_after_checkout_billing_form', [ $this, 'render_order_notes_card' ], 15 );

        // This filter tells WooCommerce to treat the city field as a dropdown, preventing AJAX issues.
        add_filter( 'woocommerce_get_country_locale', [ $this, 'fix_iran_city_locale' ] );
    }

    public function fix_iran_city_locale( $locale ) {
        $locale['IR']['city']['type']     = 'select';
        $locale['IR']['city']['required'] = true;
        // Providing default options is crucial to prevent WC's JS from replacing the field.
        $locale['IR']['city']['options']  = [ '' => __( 'ابتدا استان را انتخاب کنید', 'woocommerce' ) ];
        return $locale;
    }

    public function override_billing_form_template( $template, $template_name, $template_path ) {
        // We are only interested in overriding the checkout billing form.
        if ( 'checkout/form-billing.php' === $template_name ) {
            // Check if our custom template file exists in the plugin directory.
            $plugin_template_path = plugin_dir_path( __FILE__ ) . 'woocommerce/' . $template_name;
            if ( file_exists( $plugin_template_path ) ) {
                // If it exists, return its path so WooCommerce uses it.
                return $plugin_template_path;
            }
        }
        // Otherwise, return the default template path.
        return $template;
    }

    public function render_order_notes_card( $checkout ) {
        // Prevent the default order notes field from rendering in its original location
        add_filter('woocommerce_enable_order_notes_field', '__return_false');

        // Manually render the field inside our custom card structure
        if ( ! empty( $this->order_notes_field ) ) {
            echo '<div class="ccif-box ccif-order-notes-card">';
            echo '<h2>توضیحات تکمیلی</h2>';
            woocommerce_form_field( 'order_comments', $this->order_notes_field, $checkout->get_value( 'order_comments' ) );
            echo '</div>';
        }
    }

    public function validate_custom_fields() {
        $is_invoice_requested = isset( $_POST['billing_invoice_request'] ) && $_POST['billing_invoice_request'] == 1;
        $person_type = isset( $_POST['billing_person_type'] ) ? $_POST['billing_person_type'] : '';

        // --- Postcode Validation (only if the field is not empty) ---
        if ( ! empty( $_POST['billing_postcode'] ) && ( ! is_numeric( $_POST['billing_postcode'] ) || strlen( $_POST['billing_postcode'] ) !== 10 ) ) {
            wc_add_notice( __( 'لطفاً یک <strong>کد پستی</strong> معتبر ۱۰ رقمی و عددی وارد کنید.' ), 'error' );
        }

        // --- Phone Validation (only if the field is not empty) ---
        if ( ! empty( $_POST['billing_phone'] ) && ! is_numeric( $_POST['billing_phone'] ) ) {
            wc_add_notice( __( 'فیلد <strong>شماره تماس</strong> باید فقط شامل اعداد باشد.' ), 'error' );
        }

        // --- Conditional Validation based on Invoice Request ---
        if ( $is_invoice_requested ) {
            if ( $person_type === 'real' && ! empty( $_POST['billing_national_code'] ) ) {
                if ( ! is_numeric( $_POST['billing_national_code'] ) || strlen( $_POST['billing_national_code'] ) !== 10 ) {
                    wc_add_notice( __( 'لطفاً یک <strong>کد ملی</strong> معتبر ۱۰ رقمی و عددی وارد کنید.' ), 'error' );
                }
            }
            if ( $person_type === 'legal' && ! empty( $_POST['billing_economic_code'] ) ) {
                if ( ! is_numeric( $_POST['billing_economic_code'] ) ) {
                    wc_add_notice( __( 'فیلد <strong>شناسه ملی/اقتصادی</strong> باید فقط شامل اعداد باشد.' ), 'error' );
                }
            }
        }
    }

    public function save_custom_fields_to_order_meta( $order, $data ) {
        $this->log_message( 'Attempting to save custom fields for order ID: ' . $order->get_id() );
        foreach ( $this->custom_billing_fields as $field_key ) {
            if ( isset( $data[ $field_key ] ) && ! empty( $data[ $field_key ] ) ) {
                $value = sanitize_text_field( $data[ $field_key ] );
                $order->update_meta_data( '_' . $field_key, $value );
                $this->log_message( "Saved meta for order {$order->get_id()}: { '{$field_key}' : '{$value}' }" );
            }
        }
    }

    public function save_custom_fields_to_user_meta( $customer_id, $posted_data ) {
        if ( ! $customer_id ) {
            return; // Only for logged-in users
        }

        foreach ( $this->custom_billing_fields as $field_key ) {
            if ( isset( $posted_data[ $field_key ] ) ) {
                $value = sanitize_text_field( $posted_data[ $field_key ] );
                update_user_meta( $customer_id, $field_key, $value );
            }
        }
    }

    public function get_custom_field_value_from_user_meta( $value, $key ) {
        // If there's already a value (e.g., from a failed submission), don't override it.
        if ( ! empty( $value ) ) {
            return $value;
        }

        // Check if the user is logged in and the key is one of our custom fields.
        if ( is_user_logged_in() && in_array( $key, $this->custom_billing_fields ) ) {
            $saved_value = get_user_meta( get_current_user_id(), $key, true );
            if ( $saved_value ) {
                return $saved_value;
            }
        }

        return $value;
    }

    public function move_order_notes_field( $fields ) {
        if ( isset( $fields['order'] ) && isset( $fields['order']['order_comments'] ) ) {
            // We capture the field definition but no longer unset it from the main array.
            // WooCommerce will render it in its default location unless we render it manually elsewhere.
            $this->order_notes_field = $fields['order']['order_comments'];
        }
        return $fields;
    }

    public function remove_optional_text( $args, $key, $value ) {
        // This function removes the "(optional)" text from the labels of non-required fields.
        if ( ! $args['required'] && isset($args['label']) ) {
            // This regex is more flexible. It looks for an optional whitespace or &nbsp;
            // followed by the <span class="optional">...</span> tag and removes it.
            $args['label'] = preg_replace( '/(\s|&nbsp;)?<span class="optional">.*?<\/span>/i', '', $args['label'] );
        }
        return $args;
    }

    private function normalize_persian_string($string) {
        // Replace common Arabic characters with Persian equivalents for better matching.
        $string = str_replace(['ي', 'ك', 'آ'], ['ی', 'ک', 'ا'], $string);
        // Remove non-breaking spaces and trim whitespace from the beginning and end.
        $string = trim(str_replace('&nbsp;', ' ', $string));
        return $string;
    }

    private function load_iran_data() {
        // Ensure WooCommerce is active to avoid fatal errors.
        if ( ! class_exists('WooCommerce') ) {
            return ['states' => [], 'cities' => []];
        }

        // Get the official list of states from WooCommerce for Iran.
        // This returns an array like: [ 'TEH' => 'تهران', 'KHZ' => 'خوزستان', ... ]
        $wc_states = WC()->countries->get_states('IR');

        if (empty($wc_states)) {
            return ['states' => [], 'cities' => []];
        }

        // Load the city data from our custom JSON file.
        $json_file = plugin_dir_path(__FILE__) . 'assets/data/iran_data-2.json';
        if (!file_exists($json_file)) {
            // If the city file is missing, still return the official states for the dropdown.
            return ['states' => $wc_states, 'cities' => []];
        }
        $custom_data = json_decode(file_get_contents($json_file), true);

        // Create a reverse map of normalized state names to state codes for robust lookup.
        // e.g., [ 'تهران' => 'TEH', 'خوزستان' => 'KHZ', ... ]
        $normalized_name_to_code_map = [];
        foreach ($wc_states as $code => $name) {
            $normalized_name_to_code_map[$this->normalize_persian_string($name)] = $code;
        }

        $cities = [];
        if (is_array($custom_data)) {
            foreach ($custom_data as $province) {
                if (isset($province['name']) && isset($province['cities'])) {
                    $normalized_province_name = $this->normalize_persian_string($province['name']);
                    // Find the official WooCommerce code for the current province name.
                    if (isset($normalized_name_to_code_map[$normalized_province_name])) {
                        $state_code = $normalized_name_to_code_map[$normalized_province_name];
                        // Use the official state code as the key for the cities array.
                        $cities[$state_code] = $province['cities'];
                    }
                }
            }
        }

        // Return the official WC states for the dropdown and the cities keyed by official codes.
        return ['states' => $wc_states, 'cities' => $cities];
    }

    public function modify_checkout_fields( $fields ) {
        $this->log_message( 'Modifying checkout fields.' );
        $iran_data = $this->load_iran_data();

        // --- Custom Fields Definition ---
        $custom_fields = [
            'billing_invoice_request' => ['type' => 'checkbox', 'label' => 'درخواست صدور فاکتور رسمی', 'class' => ['form-row-wide'], 'priority' => 1],
            'billing_person_type'     => ['type' => 'select', 'label' => 'نوع شخص', 'class' => ['form-row-wide'], 'options' => ['' => 'انتخاب کنید', 'real' => 'حقیقی', 'legal' => 'حقوقی'], 'priority' => 10],
            'billing_national_code'   => ['label' => 'کد ملی', 'class' => ['form-row-wide'], 'placeholder' => '۱۰ رقم بدون خط تیره', 'priority' => 23],
            'billing_company_name'    => ['label' => 'نام شرکت', 'class' => ['form-row-first'], 'priority' => 31],
            'billing_economic_code'   => ['label' => 'شناسه ملی/اقتصادی', 'class' => ['form-row-last'], 'priority' => 32],
            'billing_agent_first_name' => ['label' => 'نام نماینده', 'class' => ['form-row-first'], 'priority' => 33],
            'billing_agent_last_name' => ['label' => 'نام خانوادگی نماینده', 'class' => ['form-row-last'], 'priority' => 34],
        ];

        $fields['billing'] = array_merge($fields['billing'], $custom_fields);

        // --- Modify Standard Fields (Labels, Placeholders, Priorities) ---
        // IMPORTANT: We are no longer setting the 'class' property here for default
        // fields. This allows WooCommerce to apply its own crucial classes
        // like 'address-field' and 'state_select', which are required for AJAX updates.
        $fields['billing']['billing_first_name']['priority'] = 21;
        $fields['billing']['billing_last_name']['priority'] = 22;

        $fields['billing']['billing_state']['type'] = 'select';
        $fields['billing']['billing_state']['options'] = [ '' => 'انتخاب کنید' ] + $iran_data['states'];
        $fields['billing']['billing_state']['priority'] = 41;

        $fields['billing']['billing_city']['type'] = 'select';
        $fields['billing']['billing_city']['options'] = [ '' => 'ابتدا استان را انتخاب کنید' ];
        $fields['billing']['billing_city']['priority'] = 42;

        $fields['billing']['billing_address_1']['label'] = 'آدرس خیابان';
        $fields['billing']['billing_address_1']['placeholder'] = 'آدرس کامل خیابان، کوچه، پلاک، واحد';
        $fields['billing']['billing_address_1']['priority'] = 51;

        $fields['billing']['billing_postcode']['label'] = 'کدپستی';
        $fields['billing']['billing_postcode']['placeholder'] = 'بدون فاصله و با اعداد انگلیسی';
        $fields['billing']['billing_postcode']['priority'] = 61;

        $fields['billing']['billing_phone']['priority'] = 62;

        // --- Unset Unwanted Fields ---
        unset($fields['billing']['billing_company']);
        unset($fields['billing']['billing_address_2']);

        // --- Add a label to Order Notes and change placeholder ---
        if (isset($fields['order']['order_comments'])) {
            $fields['order']['order_comments']['label'] = 'توضیحات سفارش (اختیاری)';
            $fields['order']['order_comments']['placeholder'] = 'یادداشت‌ها درباره سفارش شما، برای مثال نکات مهم درباره نحوه تحویل سفارش.';
        }

        // --- Reorder All Billing Fields ---
        // This is crucial for the template to loop through them in the correct order if needed,
        // although our template places them manually. It's good practice to keep it.
        uasort($fields['billing'], 'wc_checkout_fields_uasort_comparison');

        return $fields;
    }

    public function enqueue_assets() {
        if ( ! is_checkout() ) return;
        wp_enqueue_script( 'ccif-checkout-js', plugin_dir_url( __FILE__ ) . 'assets/js/ccif-checkout.js', ['jquery'], '6.0', true );
        wp_localize_script( 'ccif-checkout-js', 'ccifData', [ 'cities' => $this->load_iran_data()['cities'] ] );
        wp_enqueue_style( 'ccif-checkout-css', plugin_dir_url( __FILE__ ) . 'assets/css/ccif-checkout.css', [], '6.0' );
    }

    // --- Custom Layout Functions ---
    // All old layout functions have been removed in favor of the template override.


    private function log_message( $message ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
            if ( function_exists( 'wc_get_logger' ) ) {
                $logger = wc_get_logger();
                $logger->debug( $message, [ 'source' => 'ccif-iran-checkout' ] );
            }
        }
    }
}

new CCIF_Iran_Checkout_Rebuild();
