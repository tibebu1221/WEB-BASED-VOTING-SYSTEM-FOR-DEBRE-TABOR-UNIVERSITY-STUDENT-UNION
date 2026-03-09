<?php
// Enable all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>WordPress Template Diagnosis</h1>";

// Try to load WordPress
$wp_load = dirname(__FILE__) . '/../../wp-load.php';

if (file_exists($wp_load)) {
    require_once($wp_load);
    echo "<p style='color:green;'>✅ WordPress loaded successfully</p>";
    
    // Check current theme
    $theme = wp_get_theme();
    echo "<p>Current Theme: " . $theme->get('Name') . "</p>";
    echo "<p>Theme Folder: " . get_stylesheet_directory() . "</p>";
    
    // List all templates
    echo "<h3>Available Page Templates:</h3>";
    $templates = get_page_templates();
    foreach ($templates as $template_name => $template_file) {
        echo "<p>" . $template_name . " → " . $template_file . "</p>";
    }
    
    // Check for terms.php
    $terms_path = get_stylesheet_directory() . '/terms.php';
    if (file_exists($terms_path)) {
        echo "<p style='color:green;'>✅ terms.php exists at: " . $terms_path . "</p>";
        
        // Check file contents
        $content = file_get_contents($terms_path);
        if (strpos($content, 'Template Name:') !== false) {
            echo "<p style='color:green;'>✅ Template header found</p>";
        } else {
            echo "<p style='color:red;'>❌ Template header NOT found</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ terms.php NOT found</p>";
    }
    
} else {
    echo "<p style='color:red;'>❌ Cannot find wp-load.php at: " . $wp_load . "</p>";
}
?>