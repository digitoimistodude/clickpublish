<?php
/**
 * @Author: Timi Wahalahti
 * @Date:   2021-08-05 00:32:38
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2021-08-05 23:23:10
 *
 * @package clickpublish
 */

namespace Clickpublish_Emails;

if ( ! defined( 'ABSPATH' ) ) {
  exit();
}

add_filter( 'wp_mail_from_name', function( $name ) {
  return 'Click Publish';
} );

add_filter( 'wp_mail_from', function( $email ) {
  return str_replace( 'wordpress@', 'noreply@', $email );
} );

/**
 * Modify Magic Login plugin login email contents.
 */
add_filter( 'option_magic_login_settings', __NAMESPACE__ . '\modify_magic_login_email' );

function modify_magic_login_email( $value ) {
  $value['login_email'] = 'You requested a magic link to log in to {{SITENAME}}, and here it is!

<a href="{{MAGIC_LINK}}" target="_blank" rel="noreferrer noopener">Use this link to log in</a>.

Note that this link expires in {{EXPIRES}} minutes and can only be used once. You can safely ignore and delete this email if you do not want to log in.

Need the link? {{MAGIC_LINK}}';

  return $value;
} // end modify_magic_login_email

/**
 * Modify new user notification email contents.
 */
add_filter( 'wp_new_user_notification_email', __NAMESPACE__ . '\modify_new_user_notification_email', 10, 2 );
function modify_new_user_notification_email( $email, $user ) {
  $email['subject'] = 'Welcome to Click Publish!';
  $email['message'] = "Welcome to start your Click Publish challenge!\r\n\r\nStart your journey by verifying your account and getting a magic link to log in " . \MagicLogin\Utils\create_login_link( $user );
  return $email;
} // end modify_new_user_notification_email
