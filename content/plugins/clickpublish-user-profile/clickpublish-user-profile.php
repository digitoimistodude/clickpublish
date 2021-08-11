<?php
/**
 * Plugin Name: Clickpublish User Profile
 * Version: 1.0.0
 *
 * @Author: Timi Wahalahti
 * @Date:   2021-08-03 22:34:05
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2021-08-11 19:06:49
 *
 * @package clickpublish
 */

namespace Clickpublish_User_Profile;

if ( ! defined( 'ABSPATH' ) ) {
  exit();
}

// Disable some things on dashboard and user profiles
add_filter( 'admin_footer_text', '__return_empty_string', 11 );
add_filter( 'update_footer', '__return_empty_string', 11 );
add_filter( 'wp_is_application_passwords_available', '__return_false' );
remove_action( 'admin_color_scheme_picker', 'admin_color_scheme_picker' );

// Force subscribers to profile edit view
add_action( 'current_screen', __NAMESPACE__ . '\maybe_redirect_dashboard_to_profile_edit' );
function maybe_redirect_dashboard_to_profile_edit( $current_screen ) {
  if ( ! current_user_can( 'publish_posts' ) && 'profile' !== $current_screen->base ) {
    wp_safe_redirect( admin_url( 'profile.php' ) );
    exit();
  }
} // end maybe_redirect_dashboard_to_profile_edit

// Buffer the profile edit view
add_action( 'admin_head-profile.php', function() {
  ob_start( __NAMESPACE__ . '\modify_profile_edit_view' );
} );

add_action( 'admin_footer-profile.php', function() {
  ob_end_flush();
} );

// Modify the profile edit view
function modify_profile_edit_view( $subject ) {
  $subject = preg_replace( '#<h[0-9]>' . __( 'Name' ) . '</h[0-9]>#s', '', $subject, 1 );
  $subject = preg_replace( '#<h[0-9]>' . __( 'Personal Options' ) . '</h[0-9]>.+?/table>#s', '', $subject, 1 );
  $subject = preg_replace( '#<tr class="user-user-login-wrap">.+?/tr>#s', '', $subject, 1 );
  $subject = preg_replace( '#<h[0-9]>' . __( 'Contact Info' ) . '</h[0-9]>#s', '', $subject, 1 );
  $subject = preg_replace( '#<tr class="user-url-wrap">.+?/tr>#s', '', $subject, 1 );
  $subject = preg_replace( '#<h[0-9]>' . __( 'About Yourself') . '</h[0-9]>#s', '', $subject, 1 );
  $subject = preg_replace( '#<h[0-9]>Authorial Info</h[0-9]>.+?/table>#s', '', $subject, 1 );
  $subject = preg_replace( '#<textarea name="description" id="description" rows="5" cols="30">#s', '<textarea name="description" id="description" rows="5" cols="30" maxlength="280" onfocus="updateCount(this)" oninput="updateCount(this)">', $subject, 1 );

  if ( ! current_user_can( 'publish_posts' ) ) {
    $subject = preg_replace( '#<h[0-9]>' . __( 'Account Management' ) . '</h[0-9]>.+?/table>#s', '', $subject, 1 );
  }

  return $subject;
} // end modify_profile_edit_view

// Add meta fields we need
add_action( 'cmb2_admin_init', __NAMESPACE__ . '\user_profile_edit_add_fields' );
function user_profile_edit_add_fields() {
  $cmb = new_cmb2_box( [
    'id'            => 'feeds_metabox',
    'title'         => __( 'Feeds', 'clickpublish' ),
    'object_types'  => [ 'user' ],
  ] );

  $cadence_readonly = false;
  if ( clickpublish_user_has_ongoing_challenge( get_current_user_id() ) ) {
    $cadence_readonly = true;
  }

  $cmb->add_field( [
    'name'        => 'Publishing cadence',
    'id'          => 'clickpublish_cadence',
    'type'        => 'radio_inline',
    'save_field'  => $cadence_readonly ? false : true,
    'default'     => 'daily',
    'options'     => [
      'daily'   => __( 'Daily', 'clickpublish' ),
      'weekly'  => __( 'Weekly', 'clickpublish' ),
    ],
    'attributes'  => [
      'readonly' => $cadence_readonly,
      'disabled' => $cadence_readonly,
    ],
  ] );

  $cmb->add_field( [
    'name'        => 'Blog RSS feeds',
    'desc'        => 'Not sure what your blog RSS feed is? <a href="' . esc_url( get_the_permalink( '66' ) ) . '">Check our guide</a>.',
    'id'          => 'clickpublish_feed_urls',
    'type'        => 'text_url',
    'repeatable'  => true,
    'text'        => [
      'add_row_text' => 'Add feed',
    ],
  ] );
} // end user_profile_edit_add_fields

add_action( 'cmb2_save_field_clickpublish_feed_urls', __NAMESPACE__ . '\maybe_start_stop_user_challenge', 10, 3 );
function maybe_start_stop_user_challenge( $updated, $action, $field ) {
  $user_id = get_current_user_id();

  $not_started = clickpublish_user_has_not_started_challenge( $user_id );
  if ( $not_started ) {
    update_user_meta( $user_id, 'clickpublish_challenge_last_started', wp_date( 'Y-m-d H:i:s' ) );
  }

  if ( 'removed' === $action ) {
    clickpublish_reset_user_challenge( $user_id );
  }
} // end maybe_start_user_challenge

// Add some JS and CSS to slightly modify the dashboard view
add_action( 'admin_head', __NAMESPACE__ . '\admin_head' );
function admin_head() { ?>
  <script type="text/javascript">
    function updateCount( input ) {
      document.querySelector('.user-description-wrap').querySelector('.description').innerHTML = input.value.length + ' / 280';
    }
  </script>

  <?php if ( current_user_can( 'publish_posts' ) ) {
    return;
  } ?>

  <style type="text/css">
    #adminmenuback,
    #adminmenuwrap {
      display: none;
    }

    #wpcontent,
    #wpfooter {
      margin-left: 0;
    }

    .wp-admin #wpadminbar #wp-admin-bar-site-name > .ab-item::before {
      content: "\f464";
    }
  </style>
<?php } // end admin_head

// Remove items from admin bar
add_action( 'wp_before_admin_bar_render', __NAMESPACE__ . '\remove_admin_bar_links' );
function remove_admin_bar_links() {
  if ( current_user_can( 'publish_posts' ) ) {
    return;
  }

  global $wp_admin_bar;

  $remove_items = [
    'wp-logo',
    'about',
    'wporg',
    'documentation',
    'support-forums',
    'feedback',
    'updates',
    'comments',
    'customize',
    'imagify',
  ];

  foreach ( $remove_items as $item ) {
    $wp_admin_bar->remove_menu( $item );
  }
} // end air_helper_helper_remove_admin_bar_links

// Show message in dashboard depending the user challenge status.
add_action( 'admin_notices', __NAMESPACE__ . '\maybe_show_admin_notices' );
function maybe_show_admin_notices() {
  $message = null;
  $notice_type = 'info';
  $user_id = get_current_user_id();
  $feed_urls = get_user_meta( $user_id, 'clickpublish_feed_urls', true );

  if ( clickpublish_user_has_accomplished_last_challenge( $user_id ) ) {
    $notice_type = 'success';
    $url = wp_nonce_url( add_query_arg( 'clickpublish', 'reset', get_edit_profile_url() ), 'clickpublish-reset-' . get_current_user_id(), '_clickpublish_nonce' );
    $message = '<p><b>Well done!</b></p><p>Your last Click Publish challenge was a success.</p><p>Would you like to <a href="' . $url . '">start a new one</a>?</p>';
  } else if ( clickpublish_user_has_ongoing_challenge( $user_id ) ) {
    $url = wp_nonce_url( add_query_arg( 'clickpublish', 'stop', get_edit_profile_url() ), 'clickpublish-reset-' . get_current_user_id(), '_clickpublish_nonce' );
    $message = '<p><b>Keep on clicking publish!</b></p><p>Your challenge is ongoing.</p><p><a href="' . esc_url( $url ) . '">Stop the challenge</a>.</p>';
  } else {
    if ( ! empty( $feed_urls ) ) {
      $message = '<p><b>Welcome!</b></p><p>Start your Click Publish challenge by confirming the feed urls and updating your profile.</p>';
    } else {
      $message = '<p><b>Welcome!</b></p><p>Start your Click Publish challenge by adding your first feed url.</p>';
    }
  }

  if ( empty( $message ) ) {
    return;
  } ?>
  <div class="notice notice-<?php echo esc_attr( $notice_type ); ?>">
    <?php echo wp_kses_post( $message ); ?>
  </div>
<?php } // end maybe_show_admin_notices

// Reset the user challenge if requested.
add_action( 'admin_init', __NAMESPACE__ . '\maybe_reset_user_challenge' );
function maybe_reset_user_challenge() {
  if ( ! isset( $_GET['clickpublish'] ) ) {
    return;
  }

  if ( 'reset' !== $_GET['clickpublish'] ) {
    return;
  }

  if ( ! wp_verify_nonce( $_GET['_clickpublish_nonce'], 'clickpublish-reset-' . get_current_user_id() ) ) {
    return;
  }

  clickpublish_reset_user_challenge( get_current_user_id() );
} // end maybe_stop_user_challenge

// Modify few text strings to be more nicer
add_filter( 'gettext', __NAMESPACE__ . '\modify_profile_edit_texts', 10, 3 );
function modify_profile_edit_texts( $translation, $string, $domain ) {
  if ( 'default' !== $domain ) {
    return $translation;
  }

  switch ( $string ) {
    case 'Display name publicly as':
      return __( 'Display name as', 'clickpublish' );
      break;
    case 'Biographical Info':
      return __( 'Share a little about yourself to fill out your public profile', 'clickpublish' );
      break;

    case 'Share a little biographical information to fill out your profile. This may be shown publicly.':
      return null;
      break;

    case 'Register For This Site':
      return __( '<b>Welcome to start your Click Publish journey!</b></br></br>Registration confirmation will be emailed to you. After confirmation, you will be able to set up blog feeds and your public profile.', 'clickpublish' );
      break;

    case 'Registration confirmation will be emailed to you.':
      return null;
      break;
  }

  return $translation;
} // end modify_profile_edit_texts
