<?php
/**
 * Plugin Name: Clickpublish Emails
 * Version: 1.0.0
 *
 * @Author: Timi Wahalahti
 * @Date:   2021-08-05 00:31:07
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2021-08-05 21:43:52
 *
 * @package clickpublish
 */

namespace Clickpublish_Emails;

if ( ! defined( 'ABSPATH' ) ) {
  exit();
}

require_once plugin_dir_path( __FILE__ ) . '/email-modifications.php';

add_action( 'clickpublish_send_email', __NAMESPACE__ . '\send_email', 10, 2 );
function send_email( $email_type, $user ) {
  // Prevent emails going out too often.
  $last_email_sent = get_user_meta( $user->ID, 'clickpublish_emails_last_sent', true );
  if ( 'accomplished' !== $email_type && strtotime( $last_email_sent ) > strtotime( '-1 week' ) ) {
    return;
  }

  $email = compose_email( $email_type, $user );
  if ( ! $email ) {
    return;
  }

  $headers = [
    'Bcc: timi+clickpublish@wahalahti.fi',
    'Content-Type: text/html; charset=UTF-8',
  ];

  wp_mail( $user->user_email, $email['subject'], $email['message'], $headers );

  update_user_meta( $user->ID, 'clickpublish_emails_last_sent', wp_date( 'Y-m-d H:i:s' ) );
} // end send_email

function compose_email( $email_type, $user ) {
  $subject = 'Hello from Click Publish';
  $message = "Hi {$user->display_name}!</br></br>";

  switch ( $email_type ) {
    case 'encouragement':
      // Email sent in case we notice that registered user hasn't published any posts after starting the challenge
      $subject = 'Don\'t forget to Click Publish';
      $message .= "We noticed that you haven't started the Click Publish challenge. Here's some tips to get you started....</br></br>";
      $message .= "If you think this email is send by mistake or you have published posts, please contact us.";
      break;

    case 'reminder':
      // Email sent if we notice that user hasn't published for a while
      $subject = 'Don\'t forget to Click Publish';
      $message .= "We noticed that you haven't clicked the publish button for a while :( The start was so promising! Here's some tips to get you started....</br></br>";
      $message .= "If you think this email is send by mistake or you have published posts, please contact us.";
      break;

    case 'boost':
      // Email sent if user is doing good job with posting
      $subject = 'Keep up with cliking publish!';
      $message .= "Great jod with clicking that publish button. Keep up the good work!</br></br>";
      break;

    case 'accomplished':
      // Email when user reaches 30 posts
      $cadence = get_user_meta( $user->ID, 'clickpublish_cadence', true );
      $profile_url = get_edit_profile_url( $user->ID );
      $subject = '🎉 Click Publish challenge accomplished!';
      $message .= "Congratulations on accomplishing the {$cadence} Click Publish challenge!</br></br>";
      $message .= 'Ready to <a href="' . $profile_url . '">start next one</a>?' . "</br></br>";
      break;

    default:
      return false;
      break;
  }

  $message .= ' You received this email because you registred for Click Publish challenge.';

  return [
    'subject' => $subject,
    'message' => $message,
  ];
} // end compose_email
