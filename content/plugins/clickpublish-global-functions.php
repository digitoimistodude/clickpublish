<?php
/**
 * Plugin Name: Clickpublish Global Functions
 * Version: 1.0.0
 *
 * @Author: Timi Wahalahti
 * @Date:   2021-08-05 20:40:21
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2021-08-05 22:51:32
 * @package clickpublish
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit();
}

function clickpublish_user_has_ongoing_challenge( $user_id ) {
  return get_user_meta( $user_id, 'clickpublish_challenge_last_started', true );
} // end clickpublish_user_has_ongoing_challenge

function clickpublish_user_has_accomplished_last_challenge( $user_id ) {
  return get_user_meta( $user_id, 'clickpublish_challenge_last_accomplished', true );
} // end clickpublish_user_has_accomplished_last_challenge

function clickpublish_user_has_not_started_challenge( $user_id ) {
  $ongoing = clickpublish_user_has_ongoing_challenge( $user_id );
  $accomplished = clickpublish_user_has_accomplished_last_challenge( $user_id );

  if ( $ongoing || $accomplished ) {
    return false;
  }

  return true;
} // end clickpublish_user_has_not_started_challenge

/**
 * Centralized function to get basic user query args.
 */
function clickpublish_get_user_query_args( $query_type = 'attendees' ) {
  $args = [];

  switch ( $query_type ) {
    default: // attendees
      $args = [
        'meta_query'  => [
          [
            'key'     => 'clickpublish_feed_urls',
            'compare' => 'EXISTS',
          ],
          [
            'key'     => 'clickpublish_challenge_last_started',
            'compare' => 'EXISTS',
          ],
          [
            'key'     => 'clickpublish_challenge_last_accomplished',
            'compare' => 'NOT EXISTS',
          ],
        ],
        'number'      => 250,
        'count_total' => false,
      ];
      break;
  }

  return $args;
} // end clickpublish_get_user_query_args

/**
 * Get posts that have been published after the user started last challenge.
 */
function clickpublish_get_user_challenge_posts( $user_id, $posts_per_page = 60, $args = [] ) {
  $posts = wp_get_recent_posts( wp_parse_args( $args, [
    'author'      => $user_id,
    'post_type'   => 'feed-item',
    'numberposts' => $posts_per_page,
    'date_query'  => [
      [
        'after'   => get_user_meta( $user_id, 'clickpublish_challenge_last_started', true ),
      ],
    ],
  ] ) );

  return $posts;
} // end clickpublish_get_user_challenge_post_count

add_action( 'clickpublish_user_accomplished_challenge', 'clickpublich_accomplish_user_challenge' );
function clickpublich_accomplish_user_challenge( $user ) {
  update_user_meta( $user->ID, 'clickpublish_challenge_last_accomplished', wp_date( 'Y-m-d H:i:s' ) );
  do_action( 'clickpublish_send_email', 'accomplished', $user );
} // end clickpublich_accomplish_user_challenge

/**
 * Make user ready for next challenge.
 */
function clickpublish_reset_user_challenge( $user_id ) {
  // If user has previous challenge accomplished, move information about it to array before emptying current challenge pointers
  $challenge_started = get_user_meta( $user_id, 'clickpublish_challenge_last_started', true );
  $challenge_accomplished = get_user_meta( $user_id, 'clickpublish_challenge_last_accomplished', true );
  if ( ! empty( $challenge_started ) && ! empty( $challenge_accomplished ) ) {
    $user_challenges = get_user_meta( $user_id, 'clickpublish_challenges', true );
    if ( ! is_array( $user_challenges ) ) {
      $user_challenges = [];
    }

    $user_challenges[] = [
      'started'       => $challenge_started,
      'accomplished'  => $challenge_accomplished,
    ];

    update_user_meta( $user_id, 'clickpublish_challenges', $user_challenges );
  }

  // Delete or empty our markers used during challenge
  delete_user_meta( $user_id, 'clickpublish_challenge_last_started' );
  delete_user_meta( $user_id, 'clickpublish_challenge_last_accomplished' );
  update_user_meta( $user_id, 'clickpublish_feeds_fetch_update_start', '' );
  update_user_meta( $user_id, 'clickpublish_feeds_fetch_update_end', '' );
  update_user_meta( $user_id, 'clickpublish_emails_last_sent', '' );
} // end clickpublish_reset_user_challenge
