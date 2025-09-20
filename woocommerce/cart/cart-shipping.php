<?php
/**
 * Shipping Methods Display (organizado e comentado)
 *
 * Este template exibe os métodos de envio por “pacote”.
 * Copie para: yourtheme/woocommerce/cart/cart-shipping.php
 *
 * Mantém a lógica original do Woo (v8.8.0), apenas com
 * organização e comentários para facilitar manutenção.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.8.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Variáveis passadas pelo Woo:
 * - $package_name (ex.: “Entrega”)
 * - $index (índice do pacote)
 * - $available_methods (array de WC_Shipping_Rate)
 * - $chosen_method (rate id selecionado)
 * - $package (dados do pacote, incl. destino)
 * - $has_calculated_shipping (bool)
 * - $show_package_details (bool)
 * - $show_shipping_calculator (bool)
 * - $package_details (string)
 */

// Endereço de destino formatado (se não vier pronto).
$formatted_destination    = isset( $formatted_destination )
	? $formatted_destination
	: WC()->countries->get_formatted_address( $package['destination'], ', ' );

// Normaliza flags em boolean.
$has_calculated_shipping  = ! empty( $has_calculated_shipping );
$show_shipping_calculator = ! empty( $show_shipping_calculator );

// Texto padrão para o link do calculador quando exibido.
$calculator_text = '';
?>

<tr class="woocommerce-shipping-totals shipping">
	<!-- Cabeçalho da linha: nome do “pacote” (Ex.: Entrega) -->
	<th><strong>Entrega/Retirada</strong></th>

	<!-- Conteúdo da linha (métodos / destino / calculadora) -->
	<td data-title="<?php echo esc_attr( $package_name ); ?>">

		<?php if ( ! empty( $available_methods ) && is_array( $available_methods ) ) : ?>
			<!-- ATENÇÃO: trocamos a <ul> por <div>.
			     Mantemos o id="shipping_method" e a classe "woocommerce-shipping-methods"
			     para compatibilidade com CSS/JS de terceiros. -->
			<div id="shipping_method"
			     class="woocommerce-shipping-methods pcg-frete-list"
			     role="group"
			     aria-label="<?php echo esc_attr( $package_name ); ?>">

				<?php foreach ( $available_methods as $method ) : ?>
					<?php
					// Monta IDs/values conforme o padrão do Woo.
					$rate_id  = isset( $method->id ) ? $method->id : ( method_exists( $method, 'get_id' ) ? $method->get_id() : '' );
					$radio_id = sprintf( 'shipping_method_%1$d_%2$s', $index, esc_attr( sanitize_title( $rate_id ) ) );
					?>
					<div class="pcg-frete-item">
						<?php
						// Vários métodos -> mostra RADIO; 1 método -> input HIDDEN (comportamento do Woo).
						if ( count( $available_methods ) > 1 ) {
							printf(
								'<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="%2$s" value="%3$s" class="shipping_method" %4$s />',
								$index,
								esc_attr( $radio_id ),
								esc_attr( $rate_id ),
								checked( $rate_id, $chosen_method, false )
							); // XSS ok (valores escapados)
						} else {
							printf(
								'<input type="hidden" name="shipping_method[%1$d]" data-index="%1$d" id="%2$s" value="%3$s" class="shipping_method" />',
								$index,
								esc_attr( $radio_id ),
								esc_attr( $rate_id )
							); // XSS ok
						}

						// Label padrão do Woo (já inclui nome + preço do método).
						printf(
							'<label for="%1$s">%2$s</label>',
							esc_attr( $radio_id ),
							wc_cart_totals_shipping_method_label( $method )
						); // XSS ok

						// Hook para gateways adicionarem infos extras (ex.: Correios).
						do_action( 'woocommerce_after_shipping_rate', $method, $index );
						?>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( is_cart() ) : ?>
				<!-- Texto de destino / link para alterar endereço (somente no carrinho) -->
				<p class="woocommerce-shipping-destination">
					<?php
					if ( $formatted_destination ) {
						// Tradutores: %s = destino de envio.
						printf(
							esc_html__( 'Shipping to %s.', 'woocommerce' ) . ' ',
							'<strong>' . esc_html( $formatted_destination ) . '</strong>'
						);
						$calculator_text = esc_html__( 'Change address', 'woocommerce' );
					} else {
						// Sem destino definido ainda: mensagem padrão de estimativa.
						echo wp_kses_post(
							apply_filters(
								'woocommerce_shipping_estimate_html',
								__( 'Shipping options will be updated during checkout.', 'woocommerce' )
							)
						);
					}
					?>
				</p>
			<?php endif; ?>

		<?php
		/**
		 * ESTADOS SEM MÉTODOS CARREGADOS
		 * - Sem cálculo de frete OU sem destino formatado.
		 */
		elseif ( ! $has_calculated_shipping || ! $formatted_destination ) :

			if ( is_cart() && 'no' === get_option( 'woocommerce_enable_shipping_calc' ) ) {
				// Calculadora desativada no carrinho: avisa que será calculado no checkout.
				echo wp_kses_post(
					apply_filters(
						'woocommerce_shipping_not_enabled_on_cart_html',
						__( 'Shipping costs are calculated during checkout.', 'woocommerce' )
					)
				);
			} else {
				// Calculadora ativa: instrui a inserir endereço para ver opções.
				echo wp_kses_post(
					apply_filters(
						'woocommerce_shipping_may_be_available_html',
						__( 'Enter your address to view shipping options.', 'woocommerce' )
					)
				);
			}

		/**
		 * PÁGINAS QUE NÃO SÃO O CARRINHO
		 * (fallback padrão do Woo sem opções disponíveis)
		 */
		elseif ( ! is_cart() ) :
			echo wp_kses_post(
				apply_filters(
					'woocommerce_no_shipping_available_html',
					__( 'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.', 'woocommerce' )
				)
			);

		/**
		 * SEM OPÇÕES PARA O DESTINO ATUAL (no carrinho)
		 */
		else :
			echo wp_kses_post(
				/**
				 * Filtro para sobrescrever o HTML “sem frete disponível”.
				 *
				 * @since 3.0.0
				 * @param string $html                  Mensagem HTML.
				 * @param string $formatted_destination Destino formatado.
				 */
				apply_filters(
					'woocommerce_cart_no_shipping_available_html',
					// Tradutores: %s = destino de envio.
					sprintf(
						esc_html__( 'No shipping options were found for %s.', 'woocommerce' ) . ' ',
						'<strong>' . esc_html( $formatted_destination ) . '</strong>'
					),
					$formatted_destination
				)
			);

			// Mensagem do botão da calculadora quando estamos neste estado.
			$calculator_text = esc_html__( 'Enter a different address', 'woocommerce' );

		endif; // fim dos cenários de métodos/destino
		?>

		<?php if ( $show_package_details ) : ?>
			<!-- Mostra conteúdo do pacote (lista resumida de itens) -->
			<p class="woocommerce-shipping-contents">
				<small><?php echo esc_html( $package_details ); ?></small>
			</p>
		<?php endif; ?>

		<?php if ( $show_shipping_calculator ) : ?>
			<!-- Calculadora de frete (WooCommerce) -->
			<?php woocommerce_shipping_calculator( $calculator_text ); ?>
		<?php endif; ?>

	</td>
</tr>
