/**
 * Display custom Terms of Service (ToS) for each product in the order.
 * 
 * This function outputs a list of products in the order along with a link to their 
 * respective Terms of Service that the customer has agreed to. The agreed date is also displayed.
 * 
 */


function display_custom_tos_order_field($order){
	echo '<h3> Agreed Terms of Service: </h3>';
	
	$mfx_order_date = $order->get_date_created();	
    // Loop through order items
    foreach ($order->get_items() as $item_id => $item) {
        $product_id = $item->get_product_id();

        // Get the product
        $product = $item->get_product();

        // Get the product name
        $product_name = $product->get_name();

        // Get custom product terms URL
        $product_terms_url = get_post_meta($product_id, '_custom_product_terms_url', true);

        // Output Product's ToS

        echo '<li><a href="' . esc_html($product_terms_url) . '">' . esc_html($product_name) . ' Terms of Service</a> agreed on ' . esc_html($mfx_order_date) . '</li>';
    }
}

add_action('woocommerce_admin_order_data_after_billing_address', 'display_custom_tos_order_field', 10, 1);
