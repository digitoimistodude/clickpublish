<?php
/**
 * @Author: Timi Wahalahti
 * @Date:   2021-08-05 22:54:12
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2021-08-12 21:29:29
 * @package clickpublish
 */

namespace Clickpublish_Newsletter;

if ( ! defined( 'ABSPATH' ) ) {
  exit();
}

add_action( 'clickpublish_send_newsletter', __NAMESPACE__ . '\send_newsletter' );
function send_newsletter() {
  $subs = get_newsletter_subscribers();
  if ( empty( $subs ) ) {
    return;
  }

  $message = compose_newsletter();
  if ( empty( $message ) ) {
    return;
  }

  $newsletter_edition = wp_date( 'W' ) . '/' . wp_date( 'Y' );
  $headers = [
    'Content-Type: text/html; charset=UTF-8',
  ];

  foreach ( $subs as $sub ) {
    wp_mail( $sub, "Weekly newsletter {$newsletter_edition}", $message, $headers );
  }
} // end send_newsletter

function compose_newsletter() {
  $posts = get_posts_for_newsletter();
  if ( empty( $posts ) ) {
    return;
  }

  $message = '<b style="color:#111;font-size:15px;">Click Publish weekly newsletter</b></br></br>';
  $message .= 'Here\'s last weeks\'s posts from current Click Publish participants. Enjoy!</br></br>';

  foreach ( $posts as $post ) {
    $author_url = get_author_posts_url( $post['author_user']->ID );

    $message .= '<a href="' . $post['permalink'] . '"><b>' . $post['title'] . '</b></a></br>';
    $message .= '<span style="color:#767676;">// ' . $post['author_user']->display_name . '</span></br></br>';
  }

  $message .= 'You have subscribed for weekly newsletter from <a href="' . get_home_url() . '">Click Publish</a>. <a href="#">Unsubscribe</a>.';

  return $message;
} // end compose_newsletter

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
