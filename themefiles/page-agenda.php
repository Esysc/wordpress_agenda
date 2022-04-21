<?php
/**custom agenda pageall single posts and attachments
 *
 * @package ACSagendaManager
 * @subpackage page-agenda.php
 * @since ACSagendaManager 1.0
 */

get_header(); ?>

<div id="primary" class="content-area">
		<?php
		// Start the loop.
		while ( have_posts() ) :
			the_post();
			the_content();
			// End the loop.
		endwhile;
		?>
	<?php get_sidebar( 'content-bottom' ); ?>

</div><!-- .content-area -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
