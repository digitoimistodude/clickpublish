<?php
/**
 * Plugin Name: Clickpublish Small Things
 * Version: 1.0.0
 *
 * @Author: Timi Wahalahti
 * @Date:   2021-08-03 21:17:13
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2021-08-05 21:11:38
 *
 * @package clickpublish
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit();
}

/**
 * Modify the author archivces to use feed-items CPT to show
 * correct posts for participants.
 */
add_action( 'pre_get_posts', function( $query ) {
  if ( is_admin() ) {
    return;
  }

  if ( ! is_author() ) {
    return;
  }

  if ( ! $query->is_main_query() ) {
    return;
  }

  $author = get_user_by( 'slug', get_query_var( 'author_name' ) );

  $query->set( 'post_type', 'feed-item' );
  $query->set( 'date_query', [
    [
      'after' => get_user_meta( $author->ID, 'clickpublish_challenge_last_started', true ),
    ],
  ] );
} );

/**
 * Modify the feed item permalinks to have original url instead
 * og local one.
 */
add_filter( 'post_type_link', function( $permalink, $post ) {
  if ( 'feed-item' !== $post->post_type ) {
    return $permalink;
  }

  return get_post_meta( $post->ID, '_original_url', true );
}, 10, 2 );

/**
 * Remove help tabs from dashboard.
 */
add_action( 'admin_head', function() {
  $screen = get_current_screen();
  $screen->remove_help_tabs();
} );

/**
 * When user registers, start their challenge at the same time.
 */
add_action( 'user_register', function( $user_id ) {
  clickpublish_reset_user_challenge( $user_id );
} );

/**
 * Simplify register and login styles.
 */
add_action( 'login_head', function() { ?>
  <style type="text/css">
    #login {
      padding-top: 20%;
    }

    .login h1 a {
      display: none;
    }

    .login p#nav {
      display: none;
    }
  </style>
<?php } );
