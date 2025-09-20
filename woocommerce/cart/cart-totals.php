<?php
/**
 * Cart totals – PCGamer (somente formas de pagamento / sem frete)
 * Caminho: yourtheme/woocommerce/cart/cart-totals.php
 */
defined('ABSPATH') || exit;

$cart = WC()->cart;

// Subtotal "a prazo" exibido (soma das linhas no modo display)
$items_subtotal = 0.0;
foreach ( WC()->cart->get_cart() as $ci ) {
  $line = (float)($ci['line_subtotal'] ?? 0.0);
  if ( WC()->cart->display_prices_including_tax() ) {
    $line += (float)($ci['line_subtotal_tax'] ?? 0.0);
  }
  $items_subtotal += $line;
}

// ===== Taxas de desconto (fallbacks caso constantes não existam) =====
$rate_pix     = defined('PCG_PIX_RATE')         ? (float) PCG_PIX_RATE         : 0.15; // 15%
$rate_avista  = defined('PCG_CARD_AVISTA_RATE') ? (float) PCG_CARD_AVISTA_RATE : 0.10; // 10%
$rate_4x      = defined('PCG_CARD_4X_RATE')     ? (float) PCG_CARD_4X_RATE     : 0.05; // 5%

// Totais por CONDIÇÃO usando a mesma regra do item (arredonda unitário, depois soma)
$pix_total_display     = pcg_pix_items_subtotal_display();
$card4_total_display   = pcg_discount_items_subtotal_display($rate_4x);
$avista_total_display  = pcg_discount_items_subtotal_display($rate_avista);

// ===== Cálculos =====
$parc12 = $items_subtotal / 12;                       // 12x sem juros
$parc4  = $card4_total_display / 4;                                // 4x sem juros
$economia_pix = max(0, $items_subtotal - $pix_total_display);

$total4x  = round($items_subtotal * (1 - $rate_4x), $dec);             // 5% OFF
$avista   = round($items_subtotal * (1 - $rate_avista), $dec);         // 10% OFF
$pix_tot  = round($items_subtotal * (1 - $rate_pix), $dec);            // 15% OFF

?>
<section class="cart_totals pcg-cart-totals">
  <h2 class="pcg-ct-title"><?php echo esc_html__('RESUMO', 'pcgamer'); ?></h2>

  <div class="pcg-summary">

    <!-- Valor dos produtos -->
    <div class="pcg-summary-row">
      <span class="pcg-label"><?php echo esc_html__('Valor dos Produtos', 'pcgamer'); ?></span>
      <span class="pcg-amount"><?php echo wc_price($items_subtotal); ?></span>
    </div>
	  
<!-- Linha do frete, logo após “Valor dos Produtos” -->
<div class="pcg-summary-row" id="pcg-frete-resumo-row">
  <span class="pcg-label">Frete</span>
  <span class="pcg-amount" id="pcg-frete-resumo">—</span>
  <button type="button" class="button pcg-btn-frete" data-open-frete>Calcular</button>
</div>

<!-- Modal do frete (fora do fluxo normal do card) -->
<div id="pcg-frete-modal-cart" class="pcg-modal" style="display:none">
  <div class="pcg-modal__backdrop" data-close-frete></div>
  <div class="pcg-modal__card">
    <button type="button" class="pcg-modal__close" data-close-frete aria-label="Fechar">×</button>
    <h3>Calcular frete</h3>

    <form id="pcg-frete-form-cart" class="pcg-frete-form">
      <label for="pcg-cep-cart">CEP</label>
      <input id="pcg-cep-cart" type="text" inputmode="numeric" maxlength="9" placeholder="00000-000" />
      <input type="hidden" id="pcg-uf-cart" value="">
      <button type="submit" class="button">OK</button>
    </form>

    <table id="pcg-frete-tabela-cart" class="pcg-frete-tabela">
      <thead>
        <tr><th></th><th>Método</th><th>Preço</th><th>Prazo</th></tr>
      </thead>
      <tbody></tbody>
    </table>

    <button type="button" id="pcg-aplicar-frete" class="button" disabled>Aplicar frete selecionado</button>
  </div>
</div>


	  
    <!-- Total a prazo (12x) -->
    <div class="pcg-summary-row">
  <span class="pcg-label">
    <span><?php echo esc_html__('Cartão de Crédito', 'pcgamer'); ?></span>
    <span class="pcg-label-sub"><?php printf( esc_html__('(12x de %s sem juros)', 'pcgamer'), wc_price($parc12) ); ?></span>
  </span>
     	<div class="pcg-amount"><?php echo wc_price($items_subtotal); ?>
		</div>
    </div>

    <!-- Cartão em até 4x (5% OFF) -->
    <div class="pcg-summary-row">
<span class="pcg-label">
  <span class="pcg-label-main"><?php echo esc_html__('Cartão de Crédito', 'pcgamer'); ?></span>
  <span class="pcg-badge">5% OFF</span>
  <span class="pcg-label-sub">    <?php printf( esc_html__('(4x de %s sem juros)', 'pcgamer'), wc_price($parc4) ); ?>  </span>
</span>
     <div class="pcg-amount"><?php echo wc_price($card4_total_display); ?>
      <span class="pcg-amount-sub">Exclusivo loja física</span></div>	
    </div>

    <!-- Cartão à vista (10% OFF) -->
    <div class="pcg-summary-row">
      <span class="pcg-label">
        <?php echo esc_html__('Cartão à vista', 'pcgamer'); ?>
		  <span class="pcg-badge">10% OFF</span>
<span class="pcg-label-sub">(crédito/débito)</span>
      </span>
      <div class="pcg-amount"><?php echo wc_price($avista_total_display); ?>
      <span class="pcg-amount-sub">Exclusivo loja física</span></div>
    </div>

    <!-- Pix/Dinheiro (15% OFF) -->
    <div class="pcg-summary-row">
      <span class="pcg-label">
          <?php echo esc_html__('PIX/Dinheiro', 'pcgamer'); ?>
        	<span class="pcg-badge">15% OFF</span>
      </span>
      <div class="pcg-amount"><?php echo wc_price($pix_total_display); ?>
      <span class="pcg-amount-sub"><?php printf( esc_html__('(Economize: %s)', 'pcgamer'), wc_price($economia_pix) ); ?></span></div>
    </div>

    <div class="wc-proceed-to-checkout" style="margin-top:12px">
      <?php do_action( 'woocommerce_proceed_to_checkout' ); ?>
    </div>
  </div>
	
	<!-- POPUP FRETE (igual PDP) -->
<div id="popup-frete" style="display:none">
  <div class="frete-popup-overlay"></div>
  <div class="frete-popup-card">
    <button type="button" class="frete-popup-close" aria-label="Fechar">×</button>
    <h3>Calcular frete</h3>

    <form id="pcgamer-frete-form">
      <label for="frete-cep">CEP</label>
      <input id="frete-cep" type="text" inputmode="numeric" maxlength="9" placeholder="00000-000" />
      <input type="hidden" id="frete-uf" value="DF" />
      <button type="submit" class="button">OK</button>
      <p class="pcg-erro" id="pcg-frete-erro" style="display:none;"></p>
    </form>

    <table class="frete-tabela">
      <thead>
        <tr><th>Método</th><th>Preço</th><th>Prazo</th></tr>
      </thead>
      <tbody id="frete-tabela-body"></tbody>
    </table>

    <div class="frete-imposto" style="display:none"></div>

    <div class="frete-actions">
      <button type="button" class="button button-primary" id="pcg-aplicar-frete" disabled>Aplicar frete selecionado</button>
    </div>
  </div>
</div>

</section>
