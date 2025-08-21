<?php
get_header();

// טען את תבנית האלמנטור
if (class_exists('\Elementor\Frontend')) {
    echo \Elementor\Plugin::$instance->frontend->get_builder_content(337);
} else {
    echo "<p>שגיאה: אלמנטור לא זמין</p>";
}

get_footer();
