// Create terms page automatically
function create_terms_page_on_activation() {
    // Check if page already exists
    $page = get_page_by_path('terms-of-service');
    
    if (!$page) {
        $page_data = array(
            'post_title'    => 'Terms of Service',
            'post_name'     => 'terms-of-service',
            'post_content'  => '<!-- This page uses the Terms template -->',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_author'   => 1,
            'page_template' => 'terms.php'
        );
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id) {
            update_post_meta($page_id, '_wp_page_template', 'terms.php');
        }
    }
}
add_action('after_setup_theme', 'create_terms_page_on_activation');

// Force template check
function force_terms_template($template) {
    if (is_page('terms-of-service')) {
        $new_template = locate_template(array('terms.php'));
        if ('' != $new_template) {
            return $new_template;
        }
    }
    return $template;
}
add_filter('template_include', 'force_terms_template', 99);