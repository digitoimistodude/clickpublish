<?php
/**
 * The template for displaying archive pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package WordPress
 * @subpackage Twenty_Nineteen
 * @since Twenty Nineteen 1.0
 */

$feed_posts = [];
while ( have_posts() ) {
  the_post();

  $feed_posts[] = [
    'id'        => get_the_id(),
    'title'     => get_the_title(),
    'permalink' => get_the_permalink(),
    'published' => get_the_date( 'Y-m-d' ),
  ];
}

$user_id = get_the_author_meta( 'ID' );
$posts_count = count( clickpublish_get_user_challenge_posts( $user_id ) );

// Do not show users who have not posted, this is to avoid abuses.
if ( 0 === $posts_count ) {
  global $wp_query;
  $wp_query->set_404();
  status_header( 404 );

  // Yuuup, nasty nasty. But wanted to keep the logic here instead of new hook.
  include( get_theme_file_path( '404.php' ) );
  exit;
}

$description = get_user_meta( $user_id, 'description', true );
$cadence = get_user_meta( $user_id, 'clickpublish_cadence', true );

get_header();
?>

<div id="primary" class="content-area">
  <main id="main" class="site-main">

    <header class="page-header">
      <?php echo get_avatar( $user_id, 300, 'mystery' ); ?>

      <h2><?php echo esc_html( get_the_author() ); ?></h2>

      <?php if ( ! empty( $description ) ) : ?>
        <p><?php echo esc_html( $description ); ?></p>
      <?php endif; ?>
    </header><!-- .page-header -->

    <article <?php post_class(); ?>>
      <div class="entry-content">
        <h3 class="progress"><?php echo absint( $posts_count ) ?> of 30 <?php echo esc_html( $cadence ); ?> posts</h3>

        <?php if ( ! empty( $feed_posts ) ) : ?>
          <ul>
            <?php foreach ( $feed_posts as $feed_post ) : ?>
              <li>
                <a href="<?php echo esc_url( $feed_post['permalink'] ); ?>">
                <?php echo esc_html( $feed_post['title'] ); ?></a>

                <span class="published"><?php echo esc_html( $feed_post['published'] ); ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

      </div>
    </article>

  </main><!-- #main -->
</div><!-- #primary -->

<?php
get_footer();
