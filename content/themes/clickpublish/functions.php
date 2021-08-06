<?php
/**
 * @Author: Timi Wahalahti
 * @Date:   2021-08-04 22:18:46
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2021-08-04 22:21:25
 *
 * @package clickpublish
 */

namespace Clickpublish_Theme;

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_styles' );
function enqueue_styles() {
  $theme = wp_get_theme();

  wp_enqueue_style( 'twentynineteen', get_template_directory_uri() . '/style.css', [], $theme->parent()->get( 'Version' ) );
  wp_enqueue_style( 'clickpublish', get_stylesheet_uri(), 'twentynineteen', $theme->get( 'Version' ) );
} // end enqueue_styles
