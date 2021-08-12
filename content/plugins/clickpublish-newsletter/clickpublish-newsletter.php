<?php
/**
 * Plugin Name: Clickpublish Newsletter
 * Version: 1.0.0
 *
 * @Author: Timi Wahalahti
 * @Date:   2021-08-05 21:44:40
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2021-08-12 22:13:24
 * @package clickpublish
 */

namespace Clickpublish_Newsletter;

if ( ! defined( 'ABSPATH' ) ) {
  exit();
}

require_once plugin_dir_path( __FILE__ ) . '/send-newsletter.php';

add_action( 'init', __NAMESPACE__ . '\maybe_handle_newsletter_form' );
function maybe_handle_newsletter_form() {
  if ( ! isset( $_GET['email'] ) ) {
    return;
  }

  if ( ! isset( $_GET['clickpublish_newsletter'] ) ) {
    return;
  }

  if ( ! wp_verify_nonce( $_GET['clickpublish_newsletter'], 'subscribe' ) ) {
    return;
  }

  $subscribed = subscribe( sanitize_email( $_GET['email'] ) );

  if ( 'error' === $subscribed['status'] ) {
    wp_safe_redirect( add_query_arg( 'newsletter', 'error', home_url() . '#newsletter' ) );
    return;
  }

  if ( 'user-already' === $subscribed['status'] ) {
    wp_safe_redirect( add_query_arg( 'newsletter', 'exists', home_url() . '#newsletter' ) );
    return;
  }

  wp_safe_redirect( add_query_arg( 'newsletter', 'success', home_url() . '#newsletter' ) );
  return;
} // end maybe_handle_newsletter_actions

add_action( 'init', __NAMESPACE__ . '\maybe_handle_newsletter_confirm' );
function maybe_handle_newsletter_confirm() {
  if ( ! isset( $_GET['newsletter_confirmation'] ) ) {
    return false;
  }

  if ( ! isset( $_GET['newsletter_email'] ) ) {
    return false;
  }

  $email = str_replace( '%2B', '+', sanitize_email( $_GET['newsletter_email'] ) );

  $user = get_user_by( 'email', $email );
  if ( ! is_a( $user, 'WP_User' ) ) {
    return false;
  }

  if ( get_subscriber_hash( $email ) === sanitize_text_field( $_GET['newsletter_confirmation'] ) ) {
    update_user_meta( $user->ID, 'clickpublish_newsletter_confimed', wp_date( 'Y-m-d H:i:s' ) );
    wp_safe_redirect( add_query_arg( 'newsletter', 'confirmed', home_url() . '#newsletter' ) );
    return;
  }

  return false;
} // end maybe_handle_newsletter_confirm

function subscribe( $email ) {
  if ( ! is_email( $email ) ) {
    return [ 'status' => 'error' ];
  }

  if ( is_a( get_user_by( 'email', $email ), 'WP_User' ) ) {
    return [ 'status' => 'user-already' ];
  }

  $user_id = wp_insert_user( [
    'user_login'  => sanitize_title( $email ),
    'user_email'  => $email,
  ] );

  if ( ! $user_id ) {
    return [ 'status' => 'error' ];
  }

  update_user_meta( $user_id, 'clickpublish_newsletter', 'on' );

  $headers = [
    'Content-Type: text/html; charset=UTF-8',
  ];

  $hash = get_subscriber_hash( $email );
  $link = add_query_arg( [
    'newsletter_email'        => urlencode( $email ),
    'newsletter_confirmation' => $hash,
  ], home_url() );
  $message = 'Confirm your Click Publish weekly newsletter subscription by clicking <a href="' . $link . '">this link</a>.';

  wp_mail( $email, 'Confirm your newsletter subscription', $message, $headers );

  return [ 'status' => 'subscribed' ];
} // end subscribe

function get_subscriber_hash( $email ) {
  return md5( NONCE_SALT . $email );
} // end get_subscriber_hash

function get_newsletter_subscribers() {
  $subscribers = [];

  $args = wp_parse_args( [
    'meta_key'    => 'clickpublish_newsletter',
    'meta_query'  => null,
    'number'      => 500,
  ], clickpublish_get_user_query_args() );

  $user_query = new \WP_User_Query( $args );

  if ( empty( $user_query->get_results() ) ) {
    return;
  }

  foreach ( $user_query->get_results() as $user ) {
    $subscribers[] = $user->user_email;
  }

  return $subscribers;
} // end get_newsletter_subscribers
