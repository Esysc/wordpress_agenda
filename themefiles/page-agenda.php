<?php
/**
 * Custom agenda page template
 *
 * This template is used to display the agenda page with consistent styling
 * across different WordPress themes.
 *
 * @package ACSAgendaManager
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

get_header();
?>

<main id="primary" class="site-main content-area">
    <div class="acs-agenda-wrapper">
        <?php
        while (have_posts()) :
            the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                </header>

                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            </article>
            <?php
        endwhile;
        ?>
    </div>
</main>

<?php
get_sidebar();
get_footer();
?>
