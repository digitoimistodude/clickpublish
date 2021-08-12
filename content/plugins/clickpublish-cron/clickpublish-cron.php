<?php
/**
 * Plugin Name: Clickpublish Cron
 * Version: 1.0.0
 *
 * @Author: Timi Wahalahti
 * @Date:   2021-08-05 00:37:15
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2021-08-11 19:51:10
 *
 * @package clickpublish
 */

namespace Clickpublish_Cron;

if ( ! defined( 'ABSPATH' ) ) {
  exit();
}

// Add cron interval to run task every minute.
add_filter( 'cron_schedules', function( $schedules ) {
  $schedules['clickpublish_feeds_fecther_interval'] = [
    'interval'  => MINUTE_IN_SECONDS,
    'display'   => 'Every minute',
  ];

  return $schedules;
} );

add_action( 'init', __NAMESPACE__ . '\maybe_schedule_cron_events' );
register_activation_hook( __FILE__, __NAMESPACE__ . '\maybe_schedule_cron_events' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\remove_cron_events' );

function maybe_schedule_cron_events() {
  if ( ! wp_next_scheduled( 'clickpublish_feeds_fetcher' ) ) {
    wp_schedule_event( time(), 'clickpublish_feeds_fecther_interval', 'clickpublish_feeds_fetcher' );
  }

  if ( ! wp_next_scheduled( 'clickpublish_check_attendee_activity' ) ) {
    wp_schedule_event( time(), 'daily', 'clickpublish_check_attendee_activity' );
  }

  if ( ! wp_next_scheduled( 'clickpublish_send_newsletter' ) ) {
    wp_schedule_event( time(), 'weekly', 'clickpublish_send_newsletter' );
  }
} // end maybe_schedule_cron_events

function remove_cron_events() {
  wp_clear_scheduled_hook( 'clickpublish_feeds_fetcher' );
  wp_clear_scheduled_hook( 'clickpublish_check_attendee_activity' );
  wp_clear_scheduled_hook( 'clickpublish_send_newsletter' );
} // end remove_cron_events

add_action( 'clickpublish_check_attendee_activity', __NAMESPACE__ . '\check_active_attendees' );
function check_active_attendees() {
  $cadence_thresholds = [
    'daily'   => [
      'reminder'  => '1 week', // since last post
      'reset'     => '2 weeks', // since last post
      'boost'     => '1 week', // since last email
    ],
    'weekly'  => [
      'reminder'  => '2 weeks', // since last post
      'reset'     => '4 weeks', // since last post
      'boost'     => '5 weeks', // since last email
    ],
  ];

  $user_query = new \WP_User_Query( clickpublish_get_user_query_args() );
  if ( empty( $user_query->get_results() ) ) {
    return;
  }

  foreach ( $user_query->get_results() as $user ) {
    $cadence = get_user_meta( $user->ID, 'clickpublish_cadence', true );
    $latestpost = clickpublish_get_user_challenge_posts( $user->ID );

    if ( 30 === count( $latestpost ) ) {
      do_action( 'clickpublish_user_accomplished_challenge', $user );
      continue;
    }

    // If user has no posts, send email to encourage starting.
    if ( ! is_array( $latestpost ) || empty( $latestpost ) ) {
      do_action( 'clickpublish_send_email', 'encouragement', $user, $cadence );
      continue;
    }

    // If user has not posted for a moment, send reminder
    if ( strtotime( $latestpost[0]['post_date'] ) > strtotime( '-' . $cadence_thresholds[ $cadence ]['reminder'] ) ) {
      do_action( 'clickpublish_send_email', 'reminder', $user, $cadence );
      continue;
    }

    // If user has not been active for a while, reset their challenge
    if ( strtotime( $latestpost[0]['post_date'] ) > strtotime( '-' . $cadence_thresholds[ $cadence ]['reset'] ) ) {
      clickpublish_reset_user_challenge( $user->ID );
      continue;
    }

    // If user has done good job with publishing, send some occasional boost
    $last_email_sent = get_user_meta( $user->ID, 'clickpublish_emails_last_sent', true );
    if ( empty( $last_email_sent ) || strtotime( $last_email_sent ) > strtotime( '-' . $cadence_thresholds[ $cadence ]['boost'] ) ) {
      do_action( 'clickpublish_send_email', 'boost', $user, $cadence );
      continue;
    }
  }
} // end check_active_attendees
