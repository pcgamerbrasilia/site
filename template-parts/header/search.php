<div class="header-search">
  <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
    <input type="search" class="search-field" placeholder="O que você procura?" value="<?php echo get_search_query(); ?>" name="s" />
    <button type="submit" class="search-submit">
<span class="material-symbols-outlined">search</span>
    </button>
  </form>
</div>