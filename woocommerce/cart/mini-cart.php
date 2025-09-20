<?php
/**
 * Mini-cart
 *
 * Contains the markup for the mini-cart, used by the cart widget.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/mini-cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.0.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_mini_cart' ); ?>

<?php if ( WC()->cart && ! WC()->cart->is_empty() ) : ?>

	<ul class="woocommerce-mini-cart cart_list product_list_widget <?php echo esc_attr( $args['list_class'] ); ?>">
		<?php
		do_action( 'woocommerce_before_mini_cart_contents' );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

			if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
				/**
				 * This filter is documented in woocommerce/templates/cart/cart.php.
				 *
				 * @since 2.1.0
				 */
				$product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
				$thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
				$product_price     = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
				$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
				?>
<li class="woocommerce-mini-cart-item <?php echo esc_attr( apply_filters( 'woocommerce_mini_cart_item_class', 'mini_cart_item', $cart_item, $cart_item_key ) ); ?>">
  
  <!-- Nome do produto ocupando largura total -->
  <div class="mini-cart-produto-nome">
    <?php if ( empty( $product_permalink ) ) : ?>
      <?php echo wp_kses_post( $product_name ); ?>
    <?php else : ?>
      <a href="<?php echo esc_url( $product_permalink ); ?>">
        <?php echo wp_kses_post( $product_name ); ?>
      </a>
    <?php endif; ?>
  </div>

  <!-- Linha com foto + preço + remover -->
  <div class="mini-cart-linha">
    <div class="mini-cart-thumb">
      <?php echo $thumbnail; ?>
    </div>
	  
  <!-- Pix por unidade -->
<?php
$qty       = max(1, (int) ($cart_item['quantity'] ?? 1));
$line_sub  = (float) ($cart_item['line_subtotal'] ?? 0.0);
$unit_base = $qty ? ($line_sub / $qty) : 0.0;
$rate      = defined('PCG_PIX_RATE') ? (float) PCG_PIX_RATE : 0.15;

if ( function_exists('wc_add_number_precision') && function_exists('wc_remove_number_precision') ) {
    $unit_pix_prec = wc_add_number_precision( $unit_base * (1 - $rate) ); // arredonda o unitário em centavos
    $unit_pix      = wc_remove_number_precision( $unit_pix_prec );
} else {
    $unit_pix = round( $unit_base * (1 - $rate), function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2 );
}
?>
<?php
$qty       = max(1, (int) ($cart_item['quantity'] ?? 1));
$line_sub  = (float) ($cart_item['line_subtotal'] ?? 0.0);
$unit_base = $qty ? ($line_sub / $qty) : 0.0;
$unit_pix  = function_exists('pcg_pix_unit_price') ? pcg_pix_unit_price($unit_base) : round($unit_base * 0.85, 2);
?>
<span class="mini-cart-pix-line">
  <strong><?php echo $qty . ' × ' . wc_price( $unit_pix ); ?> no Pix</strong>
</span>


	  
	  
    <div class="mini-cart-preco-remove">


      <!-- Botão de remover com ícone de lixeira -->
      <a role="button"
         href="<?php echo esc_url( wc_get_cart_remove_url( $cart_item_key ) ); ?>"
         class="remove remove_from_cart_button"
         aria-label="<?php esc_attr_e( 'Remover produto', 'pcgamer' ); ?>"
         data-product_id="<?php echo esc_attr( $product_id ); ?>"
         data-cart_item_key="<?php echo esc_attr( $cart_item_key ); ?>"
         data-product_sku="<?php echo esc_attr( $_product->get_sku() ); ?>">
         <span class="material-symbols-outlined">delete</span>
      </a>
    </div>
  </div>



</li>

				<?php
			}
		}

		do_action( 'woocommerce_mini_cart_contents' );
		?>
	</ul>

		
<?php $pix_subtotal = function_exists('pcg_pix_items_subtotal_simple') ? pcg_pix_items_subtotal_simple() : 0.0; ?>
<div class="woocommerce-mini-cart__total total mini-cart-total-pix">
  <span><?php esc_html_e( 'Subtotal com Desconto PIX', 'pcgamer' ); ?></span>
  <strong><?php echo wc_price( $pix_subtotal ); ?></strong>
</div>



<span style="
    display: flex;
    justify-content: center;
    padding: 8px;
">(Não incluído taxas, fretes e parcelamento)</span>
<div class="botoes-mini-cart1">
  
  <?php do_action( 'woocommerce_widget_shopping_cart_before_buttons' ); ?>

  <div class="botoes-mini-cart2">
    <!-- Força classes iguais às do produto + nossa classe de controle -->
    <a href="<?php echo esc_url( wc_get_cart_url() ); ?>"
       class="button wc-forward btn-minicart"
       aria-label="<?php esc_attr_e( 'Ver carrinho', 'pcgamer' ); ?>">
       <?php esc_html_e( 'Ver carrinho', 'pcgamer' ); ?>
    </a>

    <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>"
       class="button checkout wc-forward btn-minicart"
       aria-label="<?php esc_attr_e( 'Finalização de compra', 'pcgamer' ); ?>"
       rel="nofollow">
       <?php esc_html_e( 'Finalização de compra', 'pcgamer' ); ?>
    </a>
  </div>

  <?php do_action( 'woocommerce_widget_shopping_cart_after_buttons' ); ?>
</div>



<?php else : ?>

	<p class="woocommerce-mini-cart__empty-message"><?php esc_html_e( 'No products in the cart.', 'woocommerce' ); ?></p>

<?php endif; ?>

<?php do_action( 'woocommerce_after_mini_cart' ); ?>
