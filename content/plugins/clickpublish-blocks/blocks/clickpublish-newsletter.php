<?php
/**
 * Functions to register client-side assets (scripts and stylesheets) for the
 * Gutenberg block.
 *
 * @package clickpublish-blocks
 */

/**
 * Registers all block assets so that they can be enqueued through Gutenberg in
 * the corresponding context.
 *
 * @see https://wordpress.org/gutenberg/handbook/designers-developers/developers/tutorials/block-tutorial/applying-styles-with-stylesheets/
 */
function clickpublish_newsletter_block_init() {
	// Skip block registration if Gutenberg is not enabled/merged.
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	$dir = dirname( __FILE__ );

	$index_js = 'clickpublish-newsletter/index.js';
	wp_register_script(
		'clickpublish-newsletter-block-editor',
		plugins_url( $index_js, __FILE__ ),
		[
			'wp-blocks',
			'wp-i18n',
			'wp-element',
      'wp-editor',
      'wp-server-side-render',
		],
		filemtime( "{$dir}/{$index_js}" )
	);

	$editor_css = 'clickpublish-newsletter/editor.css';
	wp_register_style(
		'clickpublish-newsletter-block-editor',
		plugins_url( $editor_css, __FILE__ ),
		[],
		filemtime( "{$dir}/{$editor_css}" )
	);

	$style_css = 'clickpublish-newsletter/style.css';
	wp_register_style(
		'clickpublish-newsletter-block',
		plugins_url( $style_css, __FILE__ ),
		[],
		filemtime( "{$dir}/{$style_css}" )
	);

	register_block_type( 'clickpublish-blocks/clickpublish-newsletter', [
		'editor_script'   => 'clickpublish-newsletter-block-editor',
		'editor_style'    => 'clickpublish-newsletter-block-editor',
		'style'           => 'clickpublish-newsletter-block',
    'render_callback' => 'clickpublish_newsletter_block_render',
	] );
} // end clickpublish_newsletter_block_init

add_action( 'init', 'clickpublish_newsletter_block_init' );

function clickpublish_newsletter_block_render( $block_attributes, $content ) {
  $newsletter_status_messages = [
    'error'   => 'Undefined error occured, please try again.',
    'exists'  => 'You already have user account, please <a href="' . wp_login_url() . '">log in to subscribe</a>.',
    'success' => 'Check your email to confirm the subscription.',
    'confirmed' => 'Thanks! Your subscription has been activated.',
  ];
  ob_start(); ?>

  <div id="#newsletter">
    <?php if ( isset( $_GET['newsletter'] ) && array_key_exists( $_GET['newsletter'], $newsletter_status_messages ) ) : ?>
      <div class="message">
        <?php echo wp_kses_post( wpautop( $newsletter_status_messages[ $_GET['newsletter'] ] ) ); ?>
      </div>
    <?php else : ?>
      <form action="<?php echo esc_url( home_url() ); ?>#newsletter" method="GET">
         <label for="clickpublish-email">Email address</label>
        <input id="clickpublish-email" type="email" name="email">
        <input type="submit" value="Subscribe">
        <?php wp_nonce_field( 'subscribe', 'clickpublish_newsletter' ); ?>
      </form>
    <?php endif; ?>
  </div>

  <?php return ob_get_clean();
} // end clickpublish_newsletter_block_render
