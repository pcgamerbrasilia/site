<?php
/**
 * Template padrão para páginas
 * @package pcgamer
 */

get_header();
?>

<main id="primary" class="site-main">
  <section class="page-container">
    <?php while ( have_posts() ) : the_post(); ?>

      <article id="post-<?php the_ID(); ?>" <?php post_class('page-content'); ?>>
        <header class="page-header">
          <h1 class="page-title"><?php the_title(); ?></h1>
        </header>

        <div class="page-body">
          <?php the_content(); ?>
        </div>

      </article>

      <?php
        if ( comments_open() || get_comments_number() ) :
          comments_template();
        endif;
      ?>

    <?php endwhile; ?>
  </section>
</main>

<?php get_footer(); ?>
