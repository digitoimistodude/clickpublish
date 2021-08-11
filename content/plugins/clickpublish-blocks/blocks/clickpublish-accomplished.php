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
function clickpublish_accomplished_block_init() {
	// Skip block registration if Gutenberg is not enabled/merged.
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	$dir = dirname( __FILE__ );

	$index_js = 'clickpublish-accomplished/index.js';
	wp_register_script(
		'clickpublish-accomplished-block-editor',
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

	$editor_css = 'clickpublish-accomplished/editor.css';
	wp_register_style(
		'clickpublish-accomplished-block-editor',
		plugins_url( $editor_css, __FILE__ ),
		[],
		filemtime( "{$dir}/{$editor_css}" )
	);

	$style_css = 'clickpublish-accomplished/style.css';
	wp_register_style(
		'clickpublish-accomplished-block',
		plugins_url( $style_css, __FILE__ ),
		[],
		filemtime( "{$dir}/{$style_css}" )
	);

	register_block_type( 'clickpublish-blocks/clickpublish-accomplished', [
		'editor_script'   => 'clickpublish-accomplished-block-editor',
		'editor_style'    => 'clickpublish-accomplished-block-editor',
		'style'           => 'clickpublish-accomplished-block',
    'render_callback' => 'clickpublish_accomplished_block_render',
	] );
} // end clickpublish_accomplished_block_init

add_action( 'init', 'clickpublish_accomplished_block_init' );

function clickpublish_accomplished_block_render( $block_attributes, $content ) {
  $user_query = new WP_User_Query( clickpublish_get_user_query_args( 'accomplished' ) );
  if ( empty( $user_query->get_results() ) ) {
    return;
  }

  ob_start();

  echo '<section class="clickpublish-users-block">';
  echo '<h2>Accomplished challenges</h2>';
  echo '<div class="clickpublish-users">';

  foreach ( $user_query->get_results() as $user ) :
    // Do not show users who have not posted, this is to avoid abuses.
    $posts_count = count( clickpublish_get_user_challenge_posts( $user->ID ) );
    if ( 0 === $posts_count ) {
      continue;
    }

    $description = get_user_meta( $user->ID, 'description', true );
    $cadence = get_user_meta( $user->ID, 'clickpublish_cadence', true );
    $latestpost = clickpublish_get_user_challenge_posts( $user->ID, 1 );
    ?>
    <div class="user">

      <a href="<?php echo esc_url( get_author_posts_url( $user->ID ) ); ?>">
        <?php echo get_avatar( $user, 300, 'mystery' ); ?>
      </a>

      <h3>
        <a href="<?php echo esc_url( get_author_posts_url( $user->ID ) ); ?>">
          <?php echo esc_html( $user->display_name ); ?>
        </a>
      </h3>

      <?php if ( ! empty( $description ) ) : ?>
        <p><?php echo esc_html( $description ); ?></p>
      <?php endif; ?>

      <p>
        <span class="progress"><?php echo absint( $posts_count ) ?> of 30 <?php echo esc_html( $cadence ); ?> posts</span>.

        <?php if ( ! empty( $latestpost ) ) : ?>
          Latest is
          <a href="<?php echo esc_url( get_the_permalink( $latestpost[0]['ID'] ) ); ?>">
          <?php echo esc_html( $latestpost[0]['post_title'] ); ?></a>

          <span class="published">
            <?php echo esc_html( wp_date( 'Y-m-d', strtotime( $latestpost[0]['post_date'] ) ) ); ?>
          </span>
        <?php endif; ?>
      </p>

    </div>
  <?php endforeach;

  echo '</div></section>';

  return ob_get_clean();
} // end clickpublish_accomplished_block_render
