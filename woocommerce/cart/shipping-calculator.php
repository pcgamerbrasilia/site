<?php
defined( 'ABSPATH' ) || exit;
if ( ! wc_shipping_enabled() ) return;

$customer = WC()->customer;
$postcode = $customer ? $customer->get_shipping_postcode() : '';
$country  = $customer ? $customer->get_shipping_country()  : WC()->countries->get_base_country();
$state    = $customer ? $customer->get_shipping_state()    : '';
$city     = $customer ? $customer->get_shipping_city()     : '';
?>
<div class="pcg-calc-cep pcg-calc-cep--open">
  <p class="pcg-dest-line">
    <?php
    $cep_txt = $postcode ? esc_html( $postcode ) : '______-___';
    echo 'Cálculo de entrega para <strong>' . $cep_txt . '</strong>';
    ?>
  </p>

  <form class="pcg-calc-cep__form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
    <label for="calc_shipping_postcode" class="screen-reader-text"><?php esc_html_e('CEP','pcgamer'); ?></label>
    <input type="text" id="calc_shipping_postcode" name="calc_shipping_postcode"
           value="<?php echo esc_attr($postcode); ?>" placeholder="70683-550"
           inputmode="numeric" autocomplete="postal-code" />
    <input type="hidden" name="calc_shipping_country" value="<?php echo esc_attr($country); ?>" />
    <input type="hidden" name="calc_shipping_state"   value="<?php echo esc_attr($state);   ?>" />
    <input type="hidden" name="calc_shipping_city"    value="<?php echo esc_attr($city);    ?>" />
    <button type="submit" name="calc_shipping" value="1" class="button">
      <?php esc_html_e('Alterar CEP','pcgamer'); ?>
    </button>
    <?php wp_nonce_field('woocommerce-cart'); ?>
  </form>
</div>
