<?php
/**
 * Plugin Name: Clickpublish Newsletter
 * Version: 1.0.0
 *
 * @Author: Timi Wahalahti
 * @Date:   2021-08-05 21:44:40
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2021-08-11 18:52:44
 * @package clickpublish
 */

namespace Clickpublish_Newsletter;

if ( ! defined( 'ABSPATH' ) ) {
  exit();
}

require_once plugin_dir_path( __FILE__ ) . '/send-newsletter.php';
