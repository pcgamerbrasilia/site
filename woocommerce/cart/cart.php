<?php
/**
 * Cart Page (override PC Gamer Brasília)
 * @version 10.0.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' );
?>

<form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
	<?php do_action( 'woocommerce_before_cart_table' ); ?>

	<table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">
		<thead>
			<tr>
				<th class="product-thumbnail"><span class="screen-reader-text"><?php esc_html_e( 'Thumbnail image', 'woocommerce' ); ?></span></th>
				<th scope="col" class="product-name"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
				<!-- SEM a coluna Preço -->
				<th scope="col" class="product-quantity"><?php esc_html_e( 'Quantity', 'woocommerce' ); ?></th>
				<th scope="col" class="product-subtotal"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
			</tr>
		</thead>

		<tbody>
		<?php
		do_action( 'woocommerce_before_cart_contents' );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

			$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
			$product_name = apply_filters( 'woocommerce_cart_item_name', $_product ? $_product->get_name() : '', $cart_item, $cart_item_key );

			if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {

				$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );

				/* ========= CÁLCULOS COMERCIAIS ========= */
				$qty = max( 1, (int) $cart_item['quantity'] );

				// Preço base por item (segue configuração de impostos do Woo)
				$unit_price = function_exists('wc_get_price_to_display')
					? (float) wc_get_price_to_display( $_product, array( 'qty' => 1 ) )
					: (float) $_product->get_price();

				$PIX_RATE  = defined('PCG_PIX_RATE') ? (float) PCG_PIX_RATE : 0.15; // 15% OFF
				$PARCELAS  = 12;
				$dec       = function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2;

				// PIX unitário = preço base * (1 - 15%), arredondado
				$pix_unit        = round( $unit_price * ( 1 - $PIX_RATE ), $dec );

				// Totais do item
				$pix_total_item  = round( $pix_unit   * $qty, $dec );
				$card_total_item = round( $unit_price * $qty, $dec );

				// Parcela (por item) para o texto
				$parcela_unit    = round( $unit_price / $PARCELAS, $dec );
				/* ======================================= */
				?>
				<tr class="woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">



					<!-- Miniatura -->
					<td class="product-thumbnail">
						<?php
						$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
						echo $product_permalink ? sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail ) : $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</td>

					<!-- Nome + linhas comerciais -->
					<th scope="row" class="product-name" data-title="<?php esc_attr_e( 'Product', 'woocommerce' ); ?>">
						<?php
						if ( $product_permalink ) {
							echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a class="pcg-item-title" href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ), $cart_item, $cart_item_key ) );
						} else {
							echo wp_kses_post( $product_name . '&nbsp;' );
						}

						do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );
						echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>

						<!-- Linhas comerciais -->
						<div class="pcg-item-lines">
							<span class="pcg-item-line pcg-pix">
								<?php echo esc_html__('Preço unitário com desconto no PIX', 'pcgamer'); ?>
								<strong><?php echo wc_price( $pix_unit ); ?></strong>
							</span>
							<span class="pcg-item-line pcg-card">
								<?php echo esc_html__('Preço unitário parcelado', 'pcgamer'); ?>
								<strong><?php echo wc_price( $unit_price ); ?></strong>
								<?php
									printf(
										/* translators: 1: número de parcelas, 2: valor da parcela */
										esc_html__(' em até %1$dx sem juros de %2$s', 'pcgamer'),
										$PARCELAS,
										wc_price( $parcela_unit )
									);
								?>
							</span>
						</div>

						<?php
						if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
							echo wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'woocommerce' ) . '</p>', $product_id ) );
						}
						?>
					</th>

					<!-- Quantidade -->
<td class="product-quantity" data-title="<?php esc_attr_e( 'Quantity', 'woocommerce' ); ?>">
	<?php
	// Sempre renderizar o input (mesmo com estoque 1)
	$min_quantity = 1; // não permite 0
	$max_quantity = max( 1, $_product->get_max_purchase_quantity() ); // respeita estoque/limites

	$product_quantity = woocommerce_quantity_input(
		array(
			'input_name'   => "cart[{$cart_item_key}][qty]",
			'input_value'  => $cart_item['quantity'],
			'max_value'    => $max_quantity,
			'min_value'    => $min_quantity,
			'product_name' => $product_name,
			// opcional: deixa claro o step
			'step'         => 1,
		),
		$_product,
		false
	);

	echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // PHPCS: XSS ok.
	?>
	
						<!-- Remover -->
					<div class="product-remove">
						<?php
						echo apply_filters(
							'woocommerce_cart_item_remove_link',
							sprintf(
								'<a role="button" href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s"><span class="material-symbols-outlined">delete</span>Remover</a>',
								esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
								esc_attr( sprintf( __( 'Remove %s from cart', 'woocommerce' ), wp_strip_all_tags( $product_name ) ) ),
								esc_attr( $product_id ),
								esc_attr( $_product->get_sku() )
							),
							$cart_item_key
						);
						?>
					</div>
</td>


					<!-- Subtotal do item (stack: PIX total / total parcelado) -->
					<td class="product-subtotal" data-title="<?php esc_attr_e( 'Subtotal', 'woocommerce' ); ?>">
						<div class="pcg-price-stack">
							<div class="pcg-price pcg-price--pix">
								<strong><?php echo wc_price( $pix_total_item ); ?></strong>
								<small><?php echo esc_html__('À vista no PIX', 'pcgamer'); ?></small>
							</div>
							<div class="pcg-price pcg-price--card">
								<strong><?php echo wc_price( $card_total_item ); ?></strong>
								<small><?php printf( esc_html__('Parcelado em %sx', 'pcgamer'), $PARCELAS ); ?></small>
							</div>
						</div>
					</td>

				</tr>
				<?php
			}
		}

		do_action( 'woocommerce_after_cart_contents' );
		?>
			<tr>
				<!-- ATENÇÃO: agora são 5 colunas -->
				<td colspan="5" class="actions">
					<?php if ( wc_coupons_enabled() ) : ?>
						<div class="coupon">
							<label for="coupon_code" class="screen-reader-text"><?php esc_html_e( 'Coupon:', 'woocommerce' ); ?></label>
							<input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" />
							<button type="submit" class="button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>"><?php esc_html_e( 'Apply coupon', 'woocommerce' ); ?></button>
							<?php do_action( 'woocommerce_cart_coupon' ); ?>
						</div>
					<?php endif; ?>

					<button type="submit" class="button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'woocommerce' ); ?>"><?php esc_html_e( 'Update cart', 'woocommerce' ); ?></button>

					<?php
					do_action( 'woocommerce_cart_actions' );
					wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' );
					?>
				</td>
			</tr>
		</tbody>
	</table>

	<?php do_action( 'woocommerce_after_cart_table' ); ?>
</form>

<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

<div class="cart-collaterals">
	<?php do_action( 'woocommerce_cart_collaterals' ); ?>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
