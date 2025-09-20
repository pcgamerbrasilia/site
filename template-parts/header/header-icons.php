<div class="header-icons">
    
  <div class="header-icon header-location">
    <button id="btn-como-chegar" title="Como chegar" aria-controls="location-popup" aria-expanded="false">
      <span class="material-symbols-outlined">location_on</span>
      <span>Como<br>Chegar</span>
    </button>

    <div id="location-popup" class="location-popup" hidden>
      <button id="close-popup" class="close-popup" aria-label="Fechar">×</button>

      <p>
        <strong>Brasília Shopping</strong>
        <span class="material-symbols-outlined" style="font-size:16px; margin-left: 6px;">location_on</span>
		<br>
        Torre Norte Sala 1417
      </p>

      <p>SCN Quadra 5 Bloco A<br>Asa Norte, Brasília - DF</p>
      <p>Segunda a Sexta das 10h às 20h<br>e Sábado até às 15h</p>

      <p>
        <a href="https://maps.app.goo.gl/EoCJuVEqdumvHf5D9" target="_blank">
          Clique e acesse nossa localização
        </a>
      </p>

      <p>
        Estoques e preços da loja física igual ao site
        <span class="material-symbols-outlined" style="color: #00cc66; margin-left: 6px;font-size:16px;">check_box</span>
      </p>
    </div>
  </div>
  
  
  
  <a href="/comparar" class="header-icon" title="Comparar Produtos">
<span class="material-symbols-outlined">two_pager</span>
<span>Compare</span>
  </a>
	
<?php if ( is_user_logged_in() ) : ?>
<div class="header-icon">
  <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'favoritos' ) ); ?>" title="Meus favoritos">
    <span class="material-symbols-outlined">favorite</span>
	 <span>Favoritos</span>
    <span class="favoritos-contador"><?php echo pcgamer_get_favoritos_count(); ?></span>
  </a>
</div>
<?php endif; ?>


	
	
<div class="header-cart">
  <div class="widget_shopping_cart_content">
    <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="header-icon" title="<?php esc_attr_e('Carrinho','pcgamer'); ?>">
      <span class="material-symbols-outlined">shopping_cart</span>
      <span class="cart-count">
        <?php echo ( function_exists('WC') && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0; ?>
      </span>

      <span class="cart-total">
        <?php
          // usa o helper do functions.php; se não existir, faz um fallback simples
          $pix_total = 0.0;

          if ( function_exists('pcg_pix_items_subtotal_simple') ) {
            $pix_total = pcg_pix_items_subtotal_simple();
          } elseif ( function_exists('WC') && WC()->cart ) {
            // Fallback: soma por linha (unitário com 15% OFF arredondado) × qty
            $rate = defined('PCG_PIX_RATE') ? (float) PCG_PIX_RATE : 0.15;
            $dec  = function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2;

            foreach ( (array) WC()->cart->get_cart() as $ci ) {
              $qty       = max(1, (int) ($ci['quantity'] ?? 1));
              $line_sub  = (float) ($ci['line_subtotal'] ?? 0.0);
              $unit_base = $qty ? ($line_sub / $qty) : 0.0;
              $pix_total += round($unit_base * (1 - $rate), $dec) * $qty;
            }
          }

          echo wc_price( $pix_total );
        ?>
      </span>
    </a>

    <?php if ( ! is_cart() && ! is_checkout() ) : ?>
      <div class="cart-dropdown">
        <?php woocommerce_mini_cart(); ?>
      </div>
    <?php endif; ?>
  </div>
</div>



	
	
</div>
