<?php
// Enqueue the parent theme's styles
function divi_child_enqueue_styles() {
    wp_enqueue_style('divi-parent-style', get_template_directory_uri() . '/style.css');
}
add_action('wp_enqueue_scripts', 'divi_child_enqueue_styles');

function custom_register_divi_blog_module() {
    if ( class_exists('ET_Builder_Module') ) {
        // Verify this file path is correct
        include_once get_stylesheet_directory() . '/includes/builder/module/BlogTagFilter.php';
        
        if (class_exists('ET_Builder_Module_Blog_Tag_Filter')) {
            $custom_blog = new ET_Builder_Module_Blog_Tag_Filter();
            remove_shortcode('et_pb_blog'); // Uncomment to replace the default blog module
            add_shortcode('et_pb_blog_tag_filter', [$custom_blog, '_shortcode_callback']);
        } else {
            error_log('ET_Builder_Module_Blog_Tag_Filter class not found.');
        }
    }
}

// add_action('et_builder_ready', 'custom_register_divi_blog_module');