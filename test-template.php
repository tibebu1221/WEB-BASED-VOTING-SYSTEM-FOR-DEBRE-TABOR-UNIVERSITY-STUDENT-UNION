<?php
// Test if template system works
echo '<pre>';
echo 'Testing WordPress Environment...<br>';

// Check WordPress functions
if (function_exists('get_bloginfo')) {
    echo '✅ WordPress functions available<br>';
} else {
    echo '❌ WordPress not loaded<br>';
    // Try to load WordPress
    $wp_load = dirname(__FILE__) . '/wp-load.php';
    if (file_exists($wp_load)) {
        require_once($wp_load);
        echo '✅ WordPress loaded manually<br>';
    } else {
        echo '❌ Cannot find wp-load.php<br>';
    }
}

// Check theme directory
$theme_dir = get_stylesheet_directory();
echo 'Theme Directory: ' . $theme_dir . '<br>';

// Check if terms.php exists
if (file_exists($theme_dir . '/terms.php')) {
    echo '✅ terms.php exists in theme folder<br>';
} else {
    echo '❌ terms.php NOT found in theme folder<br>';
}

echo '</pre>';
?>