<?php
/**
 * PCG - Descontos por Forma de Pagamento (hooks)
 *
 * Regras:
 * - Preço normal = parcelado (até 10x) -> nada a fazer (já é o preço do produto)
 * - Pix: 15% de desconto
 * - Retirada na loja + Pagamento na entrega (COD): 15% de desconto
 *
 * Implementação:
 * - Aplica como "taxa negativa" no carrinho/checkout (woocommerce_cart_calculate_fees)
 * - Mostra rótulos no checkout e mini-cart
 * - Mostra preços informativos por forma de pagamento via shortcode [pcg_preco_pagamento]
 *
 * Observação:
 * - Ajuste os IDs de gateway e método de envio conforme seu setup.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class PCG_Payments_Discounts {

  // Ajuste aqui os IDs usados pelo seu site
  private const GATEWAY_PIX_IDS = [
    // Exemplos mais comuns do Mercado Pago:
    // 'woo-mercado-pago-pix', 'woo-mercado-pago-custom'
    'woo-mercado-pago-pix',
  ];

  private const GATEWAY_COD_IDS = [
    // Pagamento na retirada (pagar na loja)
    'cod',
  ];

  private const SHIPPING_LOCAL_PICKUP_IDS = [
    // Método de retirada (Woo padrão)
    'local_pickup',
  ];

  private const DESCONTO_PIX = 0.15;      // 15%
  private const DESCONTO_RETIRADA = 0.15; // 15%

  public static function bootstrap() : void {
    // Aplica descontos no total do carrinho
    add_action( 'woocommerce_cart_calculate_fees', [__CLASS__, 'aplicar_descontos'], 20 );

    // Atualiza totais quando o cliente troca gateway/método (Woo já faz, mas reforçamos)
    add_action( 'wp_enqueue_scripts', [__CLASS__, 'enqueue_checkout_js'] );

    // Shortcode para exibir variação de preço por forma de pagamento
    add_shortcode( 'pcg_preco_pagamento', [__CLASS__, 'shortcode_preco_pagamento'] );


  }

  /**
   * Aplica taxas negativas (descontos) a depender do gateway escolhido e do método de envio.
   */
  public static function aplicar_descontos( WC_Cart $cart ) : void {
    if ( is_admin() && ! defined('DOING_AJAX') ) return;
    if ( ! WC()->cart || $cart->is_empty() ) return;

    $pagamento = self::get_payment_method_from_session();
    $envio     = self::get_shipping_method_from_session();

    $subtotal = (float) $cart->get_subtotal();
    if ( $subtotal <= 0 ) return;

    // Calcula Pix (não acumula com retirada)
    if ( self::is_pix($pagamento) ) {
      $desconto = $subtotal * self::DESCONTO_PIX * -1;
      $cart->add_fee( __( 'Desconto Pix (15%)', 'pcg' ), $desconto, false );
      return; // garante que não some com retirada
    }

    // Calcula Retirada + Pagar na loja (COD)
    if ( self::is_cod($pagamento) && self::is_local_pickup($envio) ) {
      $desconto = $subtotal * self::DESCONTO_RETIRADA * -1;
      $cart->add_fee( __( 'Desconto Retirada na Loja (15%)', 'pcg' ), $desconto, false );
    }
  }

  /**
   * Força o update do checkout quando trocar forma de pagamento (reforço).
   */
  public static function enqueue_checkout_js() : void {
    if ( ! is_checkout() ) return;

    $handle = 'pcg-checkout-refresh';
    $src    = false; // script inline simples (não precisa arquivo)
    wp_register_script( $handle, $src, ['jquery'], '1.0', true );
    wp_enqueue_script( $handle );

    $inline = <<<JS
      jQuery(function($){
        $(document.body).on('change', 'input[name="payment_method"]', function(){
          $(document.body).trigger('update_checkout');
        });
        $(document.body).on('change', 'select.shipping_method, input[name^="shipping_method["]', function(){
          $(document.body).trigger('update_checkout');
        });
      });
    JS;

    wp_add_inline_script( $handle, $inline );
  }

  /**
   * Shortcode: [pcg_preco_pagamento]
   * Mostra uma caixinha com:
   * - Preço cheio (parcelado até 10x)
   * - Preço no Pix (–15%)
   * - Preço na Retirada (–15%)
   *
   * Uso: inserir no single do produto onde quiser (ex.: abaixo do preço).
   */
  public static function shortcode_preco_pagamento( $atts ) : string {
    if ( ! is_product() ) return '';

    global $product;
    if ( ! $product instanceof WC_Product ) return '';

    $preco_base = (float) wc_get_price_to_display( $product );
    if ( $preco_base <= 0 ) return '';

    $pix      = $preco_base * (1 - self::DESCONTO_PIX);
    $retirada = $preco_base * (1 - self::DESCONTO_RETIRADA);

    ob_start(); ?>
    <div class="pcg-precos-pagamento" style="margin-top:8px">
      <div><strong>Parcelado até 10x:</strong> <?php echo wc_price($preco_base); ?></div>
      <div><strong>Pix (–15%):</strong> <?php echo wc_price($pix); ?></div>
      <div><strong>Retirada na loja (–15%):</strong> <?php echo wc_price($retirada); ?></div>
    </div>
    <?php
    return ob_get_clean();
  }



  // ------------------ Helpers ------------------

  private static function get_payment_method_from_session() : ?string {
    $session = WC()->session ? WC()->session->get('chosen_payment_method') : null;
    if ( $session ) return (string) $session;

    // Fallback: tenta pegar do POST (durante update_checkout)
    if ( isset($_POST['payment_method']) ) {
      return sanitize_text_field( wp_unslash($_POST['payment_method']) );
    }
    return null;
  }

  private static function get_shipping_method_from_session() : ?string {
    $chosen = WC()->session ? WC()->session->get('chosen_shipping_methods') : null;
    if ( is_array($chosen) && ! empty($chosen[0]) ) {
      return (string) $chosen[0]; // geralmente "local_pickup:ID"
    }

    // Fallback: tenta o POST
    if ( isset($_POST['shipping_method']) && is_array($_POST['shipping_method']) ) {
      $first = reset($_POST['shipping_method']);
      return sanitize_text_field( wp_unslash($first) );
    }

    return null;
  }

  private static function is_pix( ?string $gateway_id ) : bool {
    if ( ! $gateway_id ) return false;
    foreach ( self::GATEWAY_PIX_IDS as $id ) {
      if ( $gateway_id === $id ) return true;
    }
    return false;
  }

  private static function is_cod( ?string $gateway_id ) : bool {
    if ( ! $gateway_id ) return false;
    foreach ( self::GATEWAY_COD_IDS as $id ) {
      if ( $gateway_id === $id ) return true;
    }
    return false;
  }

  private static function is_local_pickup( ?string $shipping_id ) : bool {
    if ( ! $shipping_id ) return false;

    // shipping_id vem como "method_id:instance_id" (ex.: "local_pickup:3")
    $method = explode( ':', $shipping_id )[0];

    foreach ( self::SHIPPING_LOCAL_PICKUP_IDS as $id ) {
      if ( $method === $id ) return true;
    }
    return false;
  }

}

PCG_Payments_Discounts::bootstrap();
