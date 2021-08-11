<?php
/**
 * @Author: Timi Wahalahti
 * @Date:   2021-08-05 22:54:12
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2021-08-11 19:02:36
 * @package clickpublish
 */

namespace Clickpublish_Newsletter;

if ( ! defined( 'ABSPATH' ) ) {
  exit();
}

add_action( 'clickpublish_send_newsletter', __NAMESPACE__ . '\send_newsletter' );
function send_newsletter() {
  $posts = get_posts_for_newsletter();
  if ( empty( $posts ) ) {
    return;
  }

  // TODO:
  // Include accomplished attendees

  $message = '<b style="color:#111;font-size:15px;">Click Publish weekly newsletter</b></br></br>';
  $message .= 'Here\'s last weeks\'s posts from current Click Publish participants. Enjoy!</br></br>';

  foreach ( $posts as $post ) {
    $author_url = get_author_posts_url( $post['author_user']->ID );

    $message .= '<a href="' . $post['permalink'] . '"><b>' . $post['title'] . '</b></a></br>';
    $message .= '<span style="color:#767676;">//</span> <a href="' . $author_url . '">' . $post['author_user']->display_name . '</a></br></br>';
  }

  $message .= 'You have subscribed for weekly newsletter from <a href="' . get_home_url() . '">Click Publish</a>. <a href="#">Unsubscribe</a>.';

  $headers = [
    'Content-Type: text/html; charset=UTF-8',
  ];

  wp_mail( 'timi@wahalahti.fi', 'Weekly newsletter', $message, $headers );
} // end send_newsletter

function get_posts_for_newsletter() {
  $posts = [];
  $user_query = new \WP_User_Query( clickpublish_get_user_query_args() );
  if ( empty( $user_query->get_results() ) ) {
    return false;
  }

  foreach ( $user_query->get_results() as $user ) {
    // Do not include users that have no published posts.
    $user_posts = clickpublish_get_user_challenge_posts( $user->ID, 10, [ 'meta_key' => '_clickpublish_newsletter_included', 'meta_compare' => 'NOT EXISTS' ] );
    if ( 0 === count( $user_posts ) ) {
      continue;
    }

    foreach ( $user_posts as $post ) {
      update_post_meta( $post['ID'], '_clickpublish_newsletter_included', wp_date( 'Y-m-d H:i:s' ) );

      $posts[] = [
        'id'          => $post['ID'],
        'title'       => $post['post_title'],
        'timestamp'   => strtotime( $post['post_date'] ),
        'published'   => wp_date( 'Y-m-d', strtotime( $post['post_date'] ) ),
        'author_user' => $user,
        'permalink'   => get_the_permalink( $post['ID'] ),
      ];
    }
  }

  usort( $posts, function( $a, $b ) {
    return $a['timestamp'] - $b['timestamp'];
  } );

  return $posts;
} // end get_posts_for_newsletter
