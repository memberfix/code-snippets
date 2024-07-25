/**
 * Removes 'add-to-cart' parameters from the checkout page URL.
 * 
 * This function addresses the issue where products added to the cart via URL parameters 
 * can result in additional items being added to the cart if the user refreshes the checkout page.
 * By removing these parameters, it prevents duplicate items from being added during a page refresh.
 */



function remove_add_to_cart_parameter() {
    // Check if it's the checkout page and the add-to-cart parameter is present
    if (is_checkout() && isset($_GET['add-to-cart'])) {
        // Get the current URL
        $current_url = add_query_arg(array(), $_SERVER['REQUEST_URI']);

        // Remove the add-to-cart parameter
        $current_url = remove_query_arg('add-to-cart', $current_url);

        // Redirect to the modified URL
        wp_redirect(home_url($current_url));
        exit;
    }
}

add_action('template_redirect', 'remove_add_to_cart_parameter');
