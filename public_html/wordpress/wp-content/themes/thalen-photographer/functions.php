<?php

function photographer_theme_support() {
    add_theme_support('title-tag');
}

add_action('after_setup_theme', 'photographer_theme_support');

?>