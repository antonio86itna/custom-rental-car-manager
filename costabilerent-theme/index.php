<?php
/**
 * Main index template.
 *
 * @package Costabilerent
 */

get_header();
?>

<main id="primary" class="site-main">
<?php if ( have_posts() ) : ?>
	<?php while ( have_posts() ) : the_post(); ?>
		<?php the_content(); ?>
	<?php endwhile; ?>
<?php else : ?>
	<p><?php esc_html_e( 'No posts found.', 'costabilerent' ); ?></p>
<?php endif; ?>
</main>

<?php
get_footer();
