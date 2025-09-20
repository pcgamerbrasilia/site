<?php
/**
 * Cabeçalho do tema PC Gamer
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
	
	<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

  <?php wp_head(); ?>

</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>


<?php get_template_part('template-parts/header/topbar'); ?>


<header class="site-header">
  <div class="header-container">
    <?php

      get_template_part('template-parts/header/logo');
      get_template_part('template-parts/header/search');
      get_template_part('template-parts/header/header-icons');
    ?>
  </div>
</header>
