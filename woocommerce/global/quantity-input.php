<?php
/**
 * Override do WooCommerce: global/quantity-input.php
 * Mantém os botões + e − SEMPRE visíveis (mesmo quando min == max).
 *
 * Caminho: yourtheme/woocommerce/global/quantity-input.php
 */
defined( 'ABSPATH' ) || exit;

$input_id    = $input_id    ?? uniqid( 'quantity_' );
$classes_arr = $classes     ?? array();
$classes_arr[] = 'qty';
$classes     = implode( ' ', array_map( 'sanitize_html_class', $classes_arr ) );

$min_value   = isset( $min_value ) ? $min_value : 1; // <<< padrão 1 (não permite 0)
$max_value   = isset( $max_value ) ? $max_value : 0; // 0/'' = sem limite
$step        = isset( $step )      ? $step      : 1;
$input_name  = isset( $input_name )? $input_name: 'quantity';
$input_value = isset( $input_value ) ? $input_value : $min_value;
$inputmode   = isset( $inputmode ) ? $inputmode : 'numeric';
$pattern     = isset( $pattern )   ? $pattern   : '';
$product_name= isset( $product_name ) ? $product_name : __( 'Qty', 'woocommerce' );

// SEM ramo "vendido individualmente": sempre renderiza o input + botões
?>
<div class="pcg-qty" data-pcg-qty
     data-min="<?php echo esc_attr( $min_value ); ?>"
     data-max="<?php echo esc_attr( $max_value ?: '' ); ?>">
	<button type="button" class="pcg-qty__btn minus" aria-label="<?php esc_attr_e( 'Diminuir quantidade', 'pcgamer' ); ?>">−</button>

	<input
		type="number"
		id="<?php echo esc_attr( $input_id ); ?>"
		class="<?php echo esc_attr( $classes ); ?>"
		name="<?php echo esc_attr( $input_name ); ?>"
		value="<?php echo esc_attr( $input_value ); ?>"
		step="<?php echo esc_attr( $step ); ?>"
		min="<?php echo esc_attr( $min_value ); ?>"
		<?php if ( 0 < $max_value ) : ?>
			max="<?php echo esc_attr( $max_value ); ?>"
		<?php endif; ?>
		title="<?php echo esc_attr_x( 'Qtd', 'Product quantity input tooltip', 'pcgamer' ); ?>"
		size="4"
		inputmode="<?php echo esc_attr( $inputmode ); ?>"
		<?php if ( $pattern ) : ?>pattern="<?php echo esc_attr( $pattern ); ?>"<?php endif; ?>
		autocomplete="off"
	/>

	<button type="button" class="pcg-qty__btn plus" aria-label="<?php esc_attr_e( 'Aumentar quantidade', 'pcgamer' ); ?>">+</button>
</div>
