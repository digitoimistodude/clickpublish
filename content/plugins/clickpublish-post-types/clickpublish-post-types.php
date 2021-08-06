<?php
/**
 * Plugin Name: Clickpublish Post Types
 * Version: 1.0.0
 *
 * @Author: Timi Wahalahti
 * @Date:   2021-08-03 21:42:56
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2021-08-04 10:51:52
 */

namespace Clickpublish_Post_types;

if ( ! defined( 'ABSPATH' ) ) {
  exit();
}

add_action( 'init', __NAMESPACE__ . '\register_cpt_feed_item' );
function register_cpt_feed_item() {
  $labels = [
    'name'               => __( 'Feed Items', 'textdomain' ),
    'singular_name'      => __( 'Feed Item', 'textdomain' ),
    'add_new'            => __( 'Add New Feed Item', 'textdomain' ),
    'add_new_item'       => __( 'Add New Feed Item', 'textdomain' ),
    'edit_item'          => __( 'Edit Feed Item', 'textdomain' ),
    'new_item'           => __( 'New Feed Item', 'textdomain' ),
    'view_item'          => __( 'View Feed Item', 'textdomain' ),
    'search_items'       => __( 'Search Feed Items', 'textdomain' ),
    'not_found'          => __( 'No feed items found.', 'textdomain' ),
    'not_found_in_trash' => __( 'No feed items found in trash', 'textdomain' ),
    'menu_name'          => __( 'Feed Items', 'textdomain' ),
  ];

  $args = [
    'labels'              => $labels,
    'hierarchical'        => false,
    'public'              => false,
    'show_ui'             => true,
    'show_in_admin_bar'   => false,
    'show_in_nav_menus'   => false,
    'menu_icon'           => 'dashicons-rss',
    'capability_type'     => 'post',
    'supports'            => [
      'title',
      'author',
    ],
  ];

  register_post_type( 'feed-item', $args );
} // end register_cpt_feed_item
