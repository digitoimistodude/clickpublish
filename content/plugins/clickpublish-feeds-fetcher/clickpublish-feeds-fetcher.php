<?php
/**
 * Plugin Name: Clickpublish Feeds Fetcher
 * Version: 1.0.0
 *
 * @Author: Timi Wahalahti
 * @Date:   2021-08-03 21:17:13
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2021-08-12 23:50:25
 */

namespace Clickpublish_Feeds_Fetcher;

if ( ! defined( 'ABSPATH' ) ) {
  exit();
}

// By default WP caches feeds 12 hours. In our case, we need shorter cache.
add_filter( 'wp_feed_cache_transient_lifetime', function() {
  return HOUR_IN_SECONDS;
} );

/**
 * Simple queue system for fetching users feeds not all at the same time, but
 * in some order to avoid somone not updating at all.
 *
 * Do simple user query, order results based on which users have the longest
 * time after latest update fetch and get user(s).
 */
add_action( 'clickpublish_feeds_fetcher', __NAMESPACE__ . '\fetch_user_feeds_queue_handler' );
function fetch_user_feeds_queue_handler() {
  $args = wp_parse_args( [
    'number'    => 1,
    'orderby'   => 'meta_value',
    'meta_key'  => 'clickpublish_feeds_fetch_update_start', // used for orderby, end instead of start in case the last fetch has failed.
  ], clickpublish_get_user_query_args() );

  $user_query = new \WP_User_Query( $args );

  if ( empty( $user_query->get_results() ) ) {
    return;
  }

  foreach ( $user_query->get_results() as $user ) {
    // Do not make a new fetch within an hour.
    $last_fetch_ended = get_user_meta( $user->ID, 'clickpublish_feeds_fetch_update_end', true );
    if ( strtotime( $last_fetch_ended ) >= strtotime( '-1 hours' ) ) {
      continue;
    }

    // Fetch feed urls user has defined.
    fetch_user_feeds( $user->ID, empty( $last_fetch_ended ) );
  }
} // end fetch_user_feeds_queue_handler

/**
 * Fetch the latest items from user's feeds.
 */
function fetch_user_feeds( $user_id, $first_fetch = false ) {
  update_user_meta( $user_id, 'clickpublish_feeds_fetch_update_start', wp_date( 'Y-m-d H:i:s' ) );

  $feeds = get_user_meta( $user_id, 'clickpublish_feed_urls', true );

  // Bail if no feed urls.
  if ( empty( $feeds ) || ! is_array( $feeds ) ) {
    return;
  }

  foreach ( $feeds as $feed ) {
    $rss = fetch_feed( $feed );
    if ( is_wp_error( $rss ) ) {
      continue;
    }

    // We want only the latest items, so speed up the handling by limiting amount of items.
    $maxitems = $rss->get_item_quantity( 5 );

    $items = $rss->get_items( 0, $maxitems );

    foreach ( $items as $item ) {
      // If user gets 30 posts duing this fetch, accomplish the challenge
      $latestpost = clickpublish_get_user_challenge_posts( $user_id );
      if ( 30 === count( $latestpost ) ) {
        do_action( 'clickpublish_user_accomplished_challenge', get_user_by( 'ID', $user_id ) );
        return;
      }

      save_item( $item, $user_id, $feed );
    }
  }

  update_user_meta( $user_id, 'clickpublish_feeds_fetch_update_end', wp_date( 'Y-m-d H:i:s' ) );
} // end fetch_user_feeds

function save_item( $item, $user_id, $feed ) {
  if ( empty( $item->get_title() ) ) {
    return;
  }

  // Skip saving if post is published before challenge started.
  $user_challenge_started = get_user_meta( $user_id, 'clickpublish_challenge_last_started', true );
  if ( strtotime( $item->get_date( 'Y-m-d 00:00:00' ) ) < strtotime( $user_challenge_started ) ) {
    return;
  }

  // Check if the spesific item has been already saved, get post ID if so.
  $already_saved = is_item_already_saved( [
    'title'   => $item->get_title(),
    'date'    => $item->get_date( 'Y-m-d H:i:s' ),
    'user_id' => $user_id,
  ] );

  $save = wp_insert_post( [
    'ID'            => $already_saved ?: 0,
    'post_type'     => 'feed-item',
    'post_status'   => 'publish',
    'post_author'   => $user_id,
    'post_title'    => $item->get_title(),
    'post_excerpt'  => wp_strip_all_tags( $item->get_description() ),
    'post_date'     => $item->get_date( 'Y-m-d H:i:s' ),
    'meta_input'    => [
      '_original_url'           => $item->get_permalink(),
      '_source_feed'            => $feed,
      '_fetch_saved'            => wp_date( 'Y-m-d H:i:s' ),
      '_clickpublish_challenge' => '1', // save in case we support having multiple challenge periods in future
    ],
  ] );
} // end save_item

function is_item_already_saved( $item ) {
  $post = get_page_by_title( $item['title'], 'OBJECT', 'feed-item' );

  if ( ! is_a( $post, 'WP_Post' ) ) {
    return false;
  }

  if ( $item['date'] !== $post->post_date ) {
    return false;
  }

  if ( absint( $item['user_id'] ) !== absint( $post->post_author ) ) {
    return false;
  }

  return $post->ID;
} // end is_item_already_saved
