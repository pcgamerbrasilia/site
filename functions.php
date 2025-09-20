<?php
/**
 * pcgamer functions and definitions
 * @package pcgamer
 */

// Versão do tema
if ( ! defined( '_S_VERSION' ) ) {
    define( '_S_VERSION', '1.0.0' );
}

// -------------------------------------------------------
// Módulos do tema
// -------------------------------------------------------
$payments_file = get_theme_file_path('/inc/payments-discounts.php');
if ( file_exists($payments_file) ) {
  require_once $payments_file;
}

$frete_popup_file = get_theme_file_path('/inc/frete-popup.php');
if ( file_exists($frete_popup_file) ) {
  require_once $frete_popup_file;
}


// Garanta os notices no topo do Carrinho e da Minha Conta
add_action('init', function () {
    add_action('woocommerce_before_cart', 'woocommerce_output_all_notices', 1);
    add_action('woocommerce_before_account_navigation', 'woocommerce_output_all_notices', 1);
});

// Retorna a URL de uma imagem dentro da pasta /assets/img/
function pcgamer_get_img_url( $filename ) {
    return esc_url( get_stylesheet_directory_uri() . '/assets/img/' . ltrim( $filename, '/' ) );
}

// Soma dos ITENS com desconto aplicado por UNIDADE (modo "display": c/ ou s/ imposto conforme a loja)
if ( ! function_exists('pcg_discount_items_subtotal_display') ) {
  function pcg_discount_items_subtotal_display(float $rate): float {
    if ( ! function_exists('WC') || ! WC()->cart ) return 0.0;
    $dec = function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2;

    $sum = 0.0;
    foreach ( (array) WC()->cart->get_cart() as $ci ) {
      $qty  = max(1, (int)($ci['quantity'] ?? 1));

      // base no "display": se a loja mostra preço com imposto, some o tax do line_subtotal
      $line = (float)($ci['line_subtotal'] ?? 0.0);
      if ( WC()->cart->display_prices_including_tax() ) {
        $line += (float)($ci['line_subtotal_tax'] ?? 0.0);
      }

      $unit_base  = $qty ? ($line / $qty) : 0.0;
      $unit_disc  = round($unit_base * (1 - $rate), $dec); // ← arredonda o UNITÁRIO
      $sum       += $unit_disc * $qty;                     // e só depois multiplica
    }
    return $sum;
  }
}


// Versões específicas (fica mais legível no template)
if ( ! function_exists('pcg_pix_items_subtotal_display') ) {
  function pcg_pix_items_subtotal_display(): float {
    $rate = defined('PCG_PIX_RATE') ? (float)PCG_PIX_RATE : 0.15;
    return pcg_discount_items_subtotal_display($rate);
  }
}


// AJAX: alterna favorito (devolve estado atual e contagem)
// Requer login (intencional). Usa nonce unificado `pcgamer_nonce` com compat do antigo.
add_action('wp_ajax_pcgamer_toggle_favorito', 'pcgamer_toggle_favorito');
function pcgamer_toggle_favorito() {
  // 1) Segurança (aceita nonce novo e antigo para compatibilidade)
  $n  = isset($_POST['nonce']) ? $_POST['nonce'] : '';
  $ok = ( wp_verify_nonce($n, 'pcgamer_nonce') || wp_verify_nonce($n, 'pcgamer_favorito_nonce') );
  if ( ! $ok ) {
    wp_send_json_error('Falha de segurança (nonce).', 403);
  }

  // 2) Requer login
  if ( ! is_user_logged_in() ) {
    wp_send_json_error('Login obrigatório.', 401);
  }

  // 3) Dados de entrada
  $user_id    = get_current_user_id();
  $product_id = absint( $_POST['product_id'] ?? 0 );
  if ( ! $product_id ) {
    wp_send_json_error('Produto inválido.', 400);
  }

  // 4) Lê e normaliza a lista atual
  $favoritos = get_user_meta( $user_id, 'produtos_favoritos', true );
  if ( ! is_array( $favoritos ) ) {
    // compat: caso algum ambiente tenha salvo como string
    $favoritos = $favoritos ? array_map('absint', (array) $favoritos) : [];
  }

  // 5) Alterna presença do produto
  $was_fav = in_array( $product_id, $favoritos, true );
  if ( $was_fav ) {
    $favoritos = array_values( array_diff( $favoritos, [ $product_id ] ) );
  } else {
    $favoritos[] = $product_id;
  }

  // 6) Persiste
  update_user_meta( $user_id, 'produtos_favoritos', $favoritos );

  // 7) Retorno determinístico para o JS (estado e contagem)
  wp_send_json_success( [
    'favoritado' => ! $was_fav,           // true = está favoritado agora
    'count'      => count( $favoritos ),  // total atual
  ] );
}



/**Adiciona mensagens de notices à resposta AJAX*/
add_filter( 'woocommerce_ajax_add_to_cart_response', 'pcgamer_ajax_add_to_cart_response', 20 );
function pcgamer_ajax_add_to_cart_response( $response ) {
    if ( function_exists( 'wc_get_notices' ) && wc_notice_count() ) {
        ob_start();
        wc_print_notices();
        $msgs = ob_get_clean();
        if ( $msgs ) {
            $response['messages'] = $msgs;
        }
    }
    return $response;
}

/*Contagem de favoritos (header)*/
function pcgamer_get_favoritos_count() {
    if ( ! is_user_logged_in() ) {
        return 0;
    }

    $favorites = get_user_meta( get_current_user_id(), 'produtos_favoritos', true );

    // Garante array e normaliza para int (corrige legados salvos como string)
    if ( ! is_array( $favorites ) ) {
        if ( empty( $favorites ) ) {
            return 0;
        }
        // Se veio string ou qualquer outro formato, força array e normaliza
        $favorites = array_map( 'absint', (array) $favorites );
    } else {
        $favorites = array_map( 'absint', $favorites );
    }

    // (Opcional) remove duplicados, por via das dúvidas
    $favorites = array_values( array_unique( $favorites ) );

    return count( $favorites );
}


/* Aba Favoritos em Minha Conta*/
add_filter( 'woocommerce_account_menu_items', 'pcgamer_add_favorites_tab' );
function pcgamer_add_favorites_tab( $items ) {
    $items['favoritos'] = 'Favoritos';
    return $items;
}
add_action( 'init', function() {
    add_rewrite_endpoint( 'favoritos', EP_PAGES );
} );
add_action( 'woocommerce_account_favoritos_endpoint', 'pcgamer_favorites_content' );
function pcgamer_favorites_content() {
    $user_id   = get_current_user_id();
    $favorites = get_user_meta( $user_id, 'produtos_favoritos', true ) ?: [];

    echo '<div class="titulo-pagina-favoritos"><span class="material-symbols-outlined">favorite</span><h3> FAVORITOS</h3></div>';
    if ( empty( $favorites ) ) {
        echo '<p>Você ainda não favoritou nenhum produto.</p>';
        return;
    }
    // Recupera produtos de uma vez
    $products = wc_get_products([ 'include' => $favorites, 'limit' => -1 ]);
    echo '<ul class="favoritos-lista">';
    foreach ( $products as $product ) {
        $pid   = $product->get_id();
        $link  = get_permalink( $pid );
        $image = $product->get_image( 'thumbnail' );
        $name  = $product->get_name();
        $price = $product->get_price_html();
        $stock = $product->is_in_stock() ? '<span class="em-estoque">Em estoque</span>' : '<span class="fora-estoque">Fora de estoque</span>';
        $brands = wp_get_post_terms( $pid, 'product_brand', ['fields' => 'names'] );
        $brand_html = $brands && ! is_wp_error( $brands ) ? '<strong>' . esc_html( $brands[0] ) . '</strong><br>' : '';
        echo '<li class="favorito-item">';
            echo '<div class="favorito-col foto"><a href="' . esc_url( $link ) . '">' . $image . '</a></div>';
            echo '<div class="favorito-col titulo">' . $brand_html . '<a href="' . esc_url( $link ) . '"><strong>' . esc_html( $name ) . '</strong></a></div>';
            echo '<div class="favorito-col preco">' . $price . '<br>' . $stock . '</div>';
            echo '<div class="favorito-col acao">';
                echo '<button class="remover-favorito" data-product-id="' . esc_attr( $pid ) . '" title="Remover dos favoritos">';
                echo '<span class="material-symbols-outlined">delete</span></button>';
                if ( $product->is_in_stock() && $product->is_purchasable() ) {
                    echo '<a href="' . esc_url( $product->add_to_cart_url() ) . '" data-quantity="1" data-product_id="' . esc_attr( $pid ) . '" data-product_sku="' . esc_attr( $product->get_sku() ) . '" class="button add_to_cart_button ajax_add_to_cart favorito-add-cart" aria-label="' . esc_attr( $product->add_to_cart_description() ) . '" rel="nofollow">';
                    echo '<span class="material-symbols-outlined">shopping_cart</span></a>';
                }
            echo '</div>';
        echo '</li>';
    }
    echo '</ul>';
}

/**
 * Atualiza cabeçalho via AJAX ao adicionar ao carrinho
 */
add_action( 'wp_ajax_pcgamer_add_to_cart', 'pcgamer_add_to_cart' );
function pcgamer_add_to_cart() {
    if ( ! isset( $_POST['product_id'] ) ) {
        wp_send_json_error();
    }
    $product_id = intval( $_POST['product_id'] );
    if ( WC()->cart ) {
        WC()->cart->add_to_cart( $product_id );
        wp_send_json_success();
    }
    wp_send_json_error();
}




/** Checkout: bloco de resumo que atualiza ao trocar o método de pagamento */
add_filter('woocommerce_update_order_review_fragments', function ($fragments) {
    $chosen = WC()->session ? WC()->session->get('chosen_payment_method') : '';
    $is_pix = in_array($chosen, PCG_GATEWAYS_PIX, true) || stripos((string)$chosen, 'pix') !== false
           || in_array($chosen, PCG_GATEWAYS_RET, true);

     // antes: $pix_total = pcg_pix_preview_total();
  $pix_items = pcg_pix_items_subtotal_display(); // ← por unidade
  $pix_total = $pix_items
             + (float) WC()->cart->get_shipping_total()
             + (float) WC()->cart->get_fee_total()
             + (float) WC()->cart->get_taxes_total();
	
    ob_start(); ?>
    <div class="pcg-checkout-resumo">
      <div class="linha">
        <span><?php echo esc_html__('Total parcelado', 'pcgamer'); ?>:</span>
        <strong><?php echo wp_kses_post(WC()->cart->get_total()); ?></strong>
      </div>
      <div class="linha">
        <span><?php echo esc_html__('Total no Pix (15% OFF)', 'pcgamer'); ?><?php echo $is_pix ? ' <em>(' . esc_html__('selecionado', 'pcgamer') . ')</em>' : ''; ?>:</span>
        <strong><?php echo wc_price($pix_total); ?></strong>
      </div>
    </div>
    <?php
    $fragments['div.pcg-checkout-resumo'] = ob_get_clean();
    return $fragments;
}, 20);


/*** Exibe marca do produto */
function pcgamer_exibir_marca_produto() {
    global $product;
    $brands = get_the_terms( $product->get_id(), 'product_brand' );
    if ( $brands && ! is_wp_error( $brands ) ) {
        $brand = $brands[0];
        $thumb_id = get_term_meta( $brand->term_id, 'thumbnail_id', true );
        $thumb_url = wp_get_attachment_url( $thumb_id );
        echo '<div class="product-brand">';
        if ( $thumb_url ) {
            echo '<img src="' . esc_url( $thumb_url ) . '" alt="' . esc_attr( $brand->name ) . '" class="logo-marca">';
        }
        echo '<span class="brand-name">' . esc_html( $brand->name ) . '</span>';
        echo '</div>';
    }
}


/** === CONFIG PAGAMENTO PIX=== */
// === DESCONTOS POR FORMA DE PAGAMENTO ===
if ( ! defined('PCG_PIX_RATE') )        define('PCG_PIX_RATE',        0.15); // 15%
if ( ! defined('PCG_CARD_AVISTA_RATE') ) define('PCG_CARD_AVISTA_RATE', 0.10); // 10% (exclusivo loja física)
if ( ! defined('PCG_CARD_4X_RATE') )     define('PCG_CARD_4X_RATE',     0.05); // 5%   (exclusivo loja física)

// Rótulos (como aparecem na linha de "taxas/fees")
if ( ! defined('PCG_PIX_LABEL') )             define('PCG_PIX_LABEL',             'Desconto no PIX (15%)');
if ( ! defined('PCG_CARD_AVISTA_LABEL') )     define('PCG_CARD_AVISTA_LABEL',     'Desconto Cartão à vista (10%)');
if ( ! defined('PCG_CARD_4X_LABEL') )         define('PCG_CARD_4X_LABEL',         'Desconto Cartão até 4x (5%)');

if ( ! defined('PCG_GATEWAYS_PIX') ) define('PCG_GATEWAYS_PIX', ['woo-mercado-pago-custom-pix','woo-mercado-pago-pix','pix']);
if ( ! defined('PCG_GATEWAYS_RET') ) define('PCG_GATEWAYS_RET', ['Pagamento na retirada']);


// functions.php
add_action( 'template_redirect', function () {
    if ( ! is_product() ) return;

    global $VALOR_TOTAL, $VALOR_PIX, $VALOR_DA_PARCELA;

    $product_id = get_queried_object_id();
    if ( ! $product_id ) return;

    $p = wc_get_product( $product_id );
    if ( ! $p instanceof WC_Product ) return;

    // Se quiser considerar impostos/exibição, use wc_get_price_to_display($p)
    $preco = (float) $p->get_price();
    if ( $preco <= 0 ) return; // produto sem preço definido

    $VALOR_TOTAL      = $preco;
    $VALOR_PIX        = $preco * 0.85;  // 15% OFF
    $VALOR_DA_PARCELA = $preco / 12;    // 12x sem juros
});



/**
 * Aplica desconto via Pix ou Retirada (15%) quando o método está selecionado.
 * Compatível com versões que não possuem get_cart_contents_subtotal().
 */
add_action('woocommerce_cart_calculate_fees', function (WC_Cart $cart) {
    if ( is_admin() && ! defined('DOING_AJAX') ) return;
    if ( ! $cart || $cart->is_empty() )          return;
    if ( ! empty( $cart->get_applied_coupons() ) ) return;

    // 1) Checkout: se o gateway já está escolhido, ele tem prioridade
    $chosen_gateway = WC()->session ? WC()->session->get('chosen_payment_method') : '';
    $label = '';
    $rate  = 0.0;

    if ( $chosen_gateway ) {
        $is_pix = in_array($chosen_gateway, PCG_GATEWAYS_PIX, true) || stripos((string)$chosen_gateway, 'pix') !== false;
        if ( $is_pix ) {
            $label = PCG_PIX_LABEL; $rate = PCG_PIX_RATE;
        }
        // (Se quiser aplicar 10%/5% no checkout quando um gateway “de loja física” existir,
        // basta mapear aqui. Normalmente só aplicamos no carrinho como prévia.)
    }

    // 2) Carrinho: se não há gateway, usamos a escolha salva na sessão
    if ( ! $rate ) {
        $opt = WC()->session ? WC()->session->get('pcg_pay_opt') : '';
        switch ( $opt ) {
            case 'pix':        $label = PCG_PIX_LABEL;             $rate = PCG_PIX_RATE;        break;
            case 'card_avista':$label = PCG_CARD_AVISTA_LABEL;     $rate = PCG_CARD_AVISTA_RATE;break;
            case 'card_4x':    $label = PCG_CARD_4X_LABEL;         $rate = PCG_CARD_4X_RATE;    break;
            default:           $label = '';                        $rate = 0.0;
        }
    }

    if ( $rate <= 0 ) return;

// Base do desconto = subtotal de itens (sem frete/taxas) com fallback
$base = 0.0;
if ( method_exists( $cart, 'get_cart_contents_subtotal' ) ) {
    $base = (float) $cart->get_cart_contents_subtotal();
} elseif ( method_exists( $cart, 'get_subtotal' ) ) {
    $base = (float) $cart->get_subtotal();
} else {
    $base = (float) $cart->get_cart_contents_total();
}

	
    if ( $base <= 0 ) return;

    $cart->add_fee( $label, -1 * $base * $rate, false ); // false => não é taxável
}, 20, 1);


add_action('wp_ajax_pcg_set_payopt', 'pcg_set_payopt');
add_action('wp_ajax_nopriv_pcg_set_payopt', 'pcg_set_payopt');
function pcg_set_payopt() {
    check_ajax_referer( 'pcgamer_nonce', 'nonce' );
    $opt = isset($_POST['opt']) ? sanitize_key($_POST['opt']) : '';
    if ( function_exists('WC') && WC()->session ) {
        WC()->session->set('pcg_pay_opt', $opt); // salva 'credit','card_4x','card_avista','pix'
        wp_send_json_success();
    }
    wp_send_json_error();
}


/**
 * Calcula um preview do total no Pix (sem aplicar de fato).
 * Usa subtotal dos ITENS e adiciona frete/fees/impostos atuais APENAS para exibição.
 * Compatível com versões sem get_cart_contents_subtotal().
 */
function pcg_pix_preview_total(): float {
    $cart = WC()->cart;
    if ( ! $cart ) return 0.0;

    // Subtotal dos produtos (compatível)
    $items_subtotal = (float) $cart->get_subtotal();
    if ( $items_subtotal <= 0 ) {
        $items_subtotal = (float) $cart->get_cart_contents_total();
    }

    $rate = defined('PCG_PIX_RATE') ? (float) PCG_PIX_RATE : 0.15;
    $pix_itens = round( $items_subtotal * (1 - $rate), 2 );

    // Para efeito visual no preview, somamos os componentes atuais do carrinho
    $shipping = (float) $cart->get_shipping_total();
    $fees     = (float) $cart->get_fee_total();
    $taxes    = (float) $cart->get_taxes_total();

    return $pix_itens + $shipping + $fees + $taxes;
}





/** Carrinho: linha de resumo "Total no Pix (15% OFF)" */
add_action('woocommerce_cart_totals_after_order_total', function () {
    if (WC()->cart->is_empty()) return;
    $pix_total = pcg_pix_preview_total();
    echo '<tr class="order-total-pix-preview">
            <th>' . esc_html__('Total no Pix (15% OFF)', 'pcgamer') . '</th>
            <td data-title="Total no Pix"><strong>' . wc_price($pix_total) . '</strong></td>
          </tr>';
});


// Preço unitário "no Pix" (15% OFF) com arredondamento simples
if ( ! function_exists('pcg_pix_unit_price') ) {
    function pcg_pix_unit_price( float $unit_base ): float {
        $rate = defined('PCG_PIX_RATE') ? (float) PCG_PIX_RATE : 0.15;
        $dec  = function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2;
        // arredonda o UNITÁRIO já com desconto
        return round( $unit_base * (1 - $rate), $dec );
    }
}

// Subtotal "no Pix" SOMENTE ITENS: soma (unitário arredondado × qty)
if ( ! function_exists('pcg_pix_items_subtotal_simple') ) {
    function pcg_pix_items_subtotal_simple(): float {
        if ( ! function_exists('WC') || ! WC()->cart ) return 0.0;
        $sum = 0.0;
        foreach ( (array) WC()->cart->get_cart() as $ci ) {
            $qty       = max(1, (int) ($ci['quantity'] ?? 1));
            $line_sub  = (float) ($ci['line_subtotal'] ?? 0.0);
            $unit_base = $qty ? ($line_sub / $qty) : 0.0;
            $sum      += pcg_pix_unit_price($unit_base) * $qty;
        }
        return $sum;
    }
}


/*** Fragmentos de carrinho no header (robusto p/ add_to_cart) ***/
function pcgamer_cart_fragments_update( $fragments ) {
    $count     = 0;
    $total_pix = 0.0;

    try {
        // Pode acontecer de WC() ou WC()->cart ainda não estarem prontos no início do AJAX
        if ( function_exists('WC') ) {
            $wc = WC();
            if ( $wc && isset($wc->cart) && $wc->cart ) {
                // contador seguro
                $count = (int) $wc->cart->get_cart_contents_count();

                // total no Pix (somando unitário arredondado × qtd)
                if ( function_exists('pcg_pix_items_subtotal_simple') ) {
                    $total_pix = (float) pcg_pix_items_subtotal_simple();
                } else {
                    // fallback super simples se o helper ainda não foi carregado
                    $rate = defined('PCG_PIX_RATE') ? (float) PCG_PIX_RATE : 0.15;
                    $dec  = function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2;
                    foreach ( (array) $wc->cart->get_cart() as $ci ) {
                        $qty       = max(1, (int) ($ci['quantity'] ?? 1));
                        $line_sub  = (float) ($ci['line_subtotal'] ?? 0.0);
                        $unit_base = $qty ? ($line_sub / $qty) : 0.0;
                        $total_pix += round($unit_base * (1 - $rate), $dec) * $qty;
                    }
                }
            }
        }
    } catch (Throwable $e) {
        // último recurso: deixa tudo zerado para não quebrar o AJAX
        $count     = 0;
        $total_pix = 0.0;
        // Se quiser logar: error_log('pcg fragments error: ' . $e->getMessage());
    }

    // Render seguro do fragment
    ob_start(); ?>
    <div class="widget_shopping_cart_content">
      <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="header-icon" title="<?php esc_attr_e('Carrinho','pcgamer'); ?>">
        <span class="material-symbols-outlined">shopping_cart</span>
        <span class="cart-count"><?php echo $count; ?></span>
        <span class="cart-total"><?php echo wc_price( $total_pix ); ?></span>
      </a>
      <div class="cart-dropdown">
        <?php
        // Render do mini-cart protegido
        try {
            if ( function_exists('woocommerce_mini_cart') ) {
                woocommerce_mini_cart();
            }
        } catch (Throwable $e) {
            // evita 500 se o template do mini-cart falhar por algum motivo
        }
        ?>
      </div>
    </div>
    <?php
    $fragments['div.widget_shopping_cart_content'] = ob_get_clean();
    return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'pcgamer_cart_fragments_update', 20 );

/* Cart totals: mostrar SUBTOTAL = soma no PIX (sem frete/fees/impostos) */
add_filter( 'woocommerce_cart_totals_subtotal_html', function( $subtotal_html, $cart ){
	if ( function_exists('pcg_pix_items_subtotal_simple') ) {
		$pix = (float) pcg_pix_items_subtotal_simple();
		return wc_price( $pix ); // sem “incl. imposto”
	}
	return $subtotal_html;
}, 10, 2 );


/**
 * Simula frete via AJAX e inclui imposto do produto no retorno
 * Espera POST: product_id, cep (8 dígitos), state (UF opcional), qty
 * Retorna: { success:true, data:{ fretes:[...], imposto:{valor, html}, uf } }
 */
add_action( 'wp_ajax_pcgamer_calcular_frete', 'pcgamer_calcular_frete' );
add_action( 'wp_ajax_nopriv_pcgamer_calcular_frete', 'pcgamer_calcular_frete' );
function pcgamer_calcular_frete() {
    // ===== 1) Sanitização e validações básicas =====
    $product_id = intval( $_POST['product_id'] ?? 0 );
    $cep        = preg_replace( '/\D+/', '', (string) ( $_POST['cep'] ?? '' ) );
    $state      = strtoupper( substr( sanitize_text_field( (string) ( $_POST['state'] ?? '' ) ), 0, 2 ) );
    $qty        = max( 1, intval( $_POST['qty'] ?? 1 ) );

    if ( ! $product_id || strlen( $cep ) !== 8 ) {
        wp_send_json_error( 'CEP ou produto inválido' );
    }

    if ( empty( $state ) ) {
        // fallback defensivo: DF por padrão (você já envia do front)
        $state = 'DF';
    }

    // ===== 2) Cache por transient (chave inclui CEP, produto, qty e UF) =====
    $transient_key = "pcgamer_frete_{$product_id}_{$cep}_{$qty}_{$state}";
    if ( false !== ( $cached = get_transient( $transient_key ) ) ) {
        wp_send_json_success( $cached );
    }

    // ===== 3) Produto válido e que precisa de envio =====
    $product = wc_get_product( $product_id );
    if ( ! $product || ! $product->needs_shipping() ) {
        wp_send_json_error( 'Produto inválido ou não requer envio.' );
    }

    // ===== 4) Monta pacote de envio isolado =====
    $package = [
        'contents' => [[
            'data'     => $product,
            'quantity' => $qty,
            // dimensões/peso do produto — Woo usa quando o método requer
            'length'   => $product->get_length(),
            'width'    => $product->get_width(),
            'height'   => $product->get_height(),
            'weight'   => $product->get_weight(),
        ]],
        'destination' => [
            'country'  => 'BR',
            'postcode' => $cep,
            'state'    => $state,
        ],
        'contents_cost'   => (float) $product->get_price() * $qty,
        'applied_coupons' => [],
        'user'            => [ 'ID' => get_current_user_id() ],
        'default_package' => true,
    ];

    // ===== 5) Calcula métodos de envio disponíveis =====
    WC_Shipping::instance()->reset_shipping();
    $available = WC_Shipping::instance()->calculate_shipping_for_package( $package );

    if ( empty( $available['rates'] ) ) {
        wp_send_json_error( 'Nenhum método de envio disponível.' );
    }

    // ===== 6) Normaliza fretes e captura prazo (_delivery_forecast) =====
    $raw_rates = [];
    foreach ( $available['rates'] as $rate_id => $rate ) {
        $meta  = method_exists( $rate, 'get_meta_data' ) ? $rate->get_meta_data() : [];
        $prazo = isset( $meta['_delivery_forecast'] )
            ? intval( preg_replace( '/[^0-9]/', '', (string) $meta['_delivery_forecast'] ) )
            : 999; // fallback "indisponível"

        $cost  = (float) $rate->get_cost();

        $raw_rates[] = [
            'id'         => $rate_id,
            'label'      => $rate->get_label(),
            'cost'       => $cost,
            'days'       => $prazo,
            'price_html' => wc_price( $cost ),
        ];
    }

    // ===== 7) Filtra "dominação": remove métodos piores (mais caros e mais lentos) =====
    $filtered = array_filter( $raw_rates, function( $r ) use ( $raw_rates ) {
        foreach ( $raw_rates as $c ) {
            if ( $c['id'] !== $r['id'] && $c['cost'] < $r['cost'] && $c['days'] <= $r['days'] ) {
                return false;
            }
        }
        return true;
    });

    // Ordena retiradas primeiro (qualidade de UX)
    usort( $filtered, function( $a, $b ) {
        $ar = stripos( $a['label'], 'retirada' ) !== false ? 0 : 1;
        $br = stripos( $b['label'], 'retirada' ) !== false ? 0 : 1;
        if ( $ar !== $br ) return $ar - $br;
        // depois por custo crescente, depois por prazo
        if ( $a['cost'] != $b['cost'] ) return $a['cost'] <=> $b['cost'];
        return $a['days'] <=> $b['days'];
    });

    // Monta saída de fretes enxuta para o front
    $fretes_out = [];
    foreach ( $filtered as $r ) {
        $fretes_out[] = [
            'id'    => $r['id'],
            'label' => $r['label'],
            'cost'  => $r['price_html'], // já formatado
            'days'  => $r['days'],
        ];
    }

    if ( empty( $fretes_out ) ) {
        wp_send_json_error( 'Nenhum frete disponível.' );
    }

       // ===== 8) Calcula IMPOSTO do produto para o destino (usa as regras do Woo) =====
    // Se o produto não é tributável, imposto = 0
    $tax_total = 0.0;
    if ( 'taxable' === $product->get_tax_status() ) {

        // Base de cálculo SEM imposto (independe se loja usa preços com/sem imposto)
        // Considera a quantidade informada
        $price_ex_tax_total = (float) wc_get_price_excluding_tax( $product, [ 'qty' => $qty ] );

        if ( $price_ex_tax_total > 0 ) {
            // Localiza taxas aplicáveis para a UF/CEP informados
            // Observação: $product->get_tax_class() retorna '' para classe "Padrão"
            $tax_rates = WC_Tax::find_rates( [
                'country'   => 'BR',
                'state'     => $state,
                'postcode'  => $cep,
                'city'      => '',
                'tax_class' => $product->get_tax_class(),
            ] );

            if ( ! empty( $tax_rates ) ) {
                // Como a base é "preço sem imposto", o terceiro argumento é FALSE
                $taxes     = WC_Tax::calc_tax( $price_ex_tax_total, $tax_rates, false );
                $tax_total = (float) wc_round_tax_total( array_sum( (array) $taxes ) );
            }
        }
    }


    // ===== 9) Resposta final + cache =====
    $result = [
        'fretes'  => $fretes_out,
        'imposto' => [
            'valor' => (float) $tax_total,
            'html'  => wc_price( $tax_total ),
        ],
        'uf' => $state,
    ];

    // Cache curto (1 hora). Ajuste se quiser mais/menos.
    set_transient( $transient_key, $result, HOUR_IN_SECONDS );

    wp_send_json_success( $result );
}


/**
 * Ajusta fretes: zera "Retirada na loja" e arredonda os demais para a dezena -0,01
 */
add_filter( 'woocommerce_package_rates', 'pcgamer_ajusta_fretes', 20, 2 );
function pcgamer_ajusta_fretes( $rates, $package ) {
	foreach ( $rates as $id => $rate ) {
		// Identificação robusta do método e label
		$method_id = method_exists( $rate, 'get_method_id' ) ? $rate->get_method_id() : ( $rate->method_id ?? '' );
		$label     = method_exists( $rate, 'get_label' )     ? $rate->get_label()     : ( $rate->label ?? '' );
		$label_s   = sanitize_title( wc_strtolower( $label ) );

		// === Regra: Retirada na loja (local_pickup) não cobra frete ===
		if ( $method_id === 'local_pickup'
			|| strpos( $label_s, 'retirada' ) !== false
			|| strpos( $label_s, 'retira' ) !== false
			|| strpos( $label_s, 'store-pickup' ) !== false
		) {
			$rates[ $id ]->cost = 0;
			if ( ! empty( $rates[ $id ]->taxes ) ) {
				foreach ( $rates[ $id ]->taxes as $tkey => $tval ) {
					$rates[ $id ]->taxes[ $tkey ] = 0;
				}
			}
			continue; // pula arredondamento
		}

		// === Demais métodos: arredondar para a dezena -0,01 ===
		$orig = (float) $rate->cost;
		if ( $orig > 0 ) {
			if ( $orig < 10 ) {
				$new = 9.99;
			} else {
				$dec = ceil( $orig / 10 ) * 10;
				$new = $dec - 0.01;
			}
			$rates[ $id ]->cost = $new;
		}

		// Zera impostos do frete (como no seu código original)
		if ( ! empty( $rates[ $id ]->taxes ) ) {
			foreach ( $rates[ $id ]->taxes as $tkey => $tval ) {
				$rates[ $id ]->taxes[ $tkey ] = 0;
			}
		}
	}
	return $rates;
}


// Descarrega scripts do Mercado Pago fora do checkout
add_action('wp_enqueue_scripts', function () {
    if ( ! function_exists('is_checkout') || ! is_checkout() || is_order_received_page() ) {
        $handles = array(
            'mp-custom-checkout',
            'mp-checkout-card',
            'mp-checkout-pix',
            'woocommerce-mercado-pago-custom',
            'woocommerce-mercado-pago',
        );
        foreach ($handles as $h) {
            if ( wp_script_is($h, 'enqueued') )   wp_dequeue_script($h);
            if ( wp_script_is($h, 'registered') ) wp_deregister_script($h);
        }

        // Fallback: varre por src contendo "woocommerce-mercado-pago" ou "mp-custom-checkout"
        global $wp_scripts;
        if ( isset($wp_scripts) && is_object($wp_scripts) ) {
            foreach ((array)$wp_scripts->queue as $h) {
                $src = isset($wp_scripts->registered[$h]) ? $wp_scripts->registered[$h]->src : '';
                if ( strpos($src, 'woocommerce-mercado-pago') !== false || strpos($src, 'mp-custom-checkout') !== false ) {
                    wp_dequeue_script($h);
                }
            }
        }
    }
}, 100);

/*Setup básico do tema*/
function pcgamer_setup() {
    load_theme_textdomain( 'pcgamer', get_template_directory() . '/languages' );
    add_theme_support( 'automatic-feed-links' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    register_nav_menus([ 'menu-1' => esc_html__( 'Primary', 'pcgamer' ) ]);
    add_theme_support( 'html5', ['search-form','comment-form','comment-list','gallery','caption','style','script'] );
    add_theme_support( 'custom-background', apply_filters('pcgamer_custom_background_args',['default-color'=>'ffffff','default-image'=>'']) );
    add_theme_support( 'customize-selective-refresh-widgets' );
    add_theme_support( 'custom-logo', ['height'=>250,'width'=>250,'flex-width'=>true,'flex-height'=>true] );
    // Recursos da galeria WooCommerce:
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );

}
add_action( 'after_setup_theme', 'pcgamer_setup' );

/**
 * Largura do conteúdo
 */
function pcgamer_content_width() {
    $GLOBALS['content_width'] = apply_filters( 'pcgamer_content_width', 640 );
}
add_action( 'after_setup_theme', 'pcgamer_content_width', 0 );

/**
 * Widgets
 */
function pcgamer_widgets_init() {
    register_sidebar([ 'name'=>esc_html__( 'Sidebar', 'pcgamer' ), 'id'=>'sidebar-1', 'description'=>esc_html__( 'Adicione widgets aqui.', 'pcgamer' ), 'before_widget'=>'<section id="%1$s" class="widget %2$s">', 'after_widget'=>'</section>', 'before_title'=>'<h2 class="widget-title">', 'after_title'=>'</h2>' ]);
}
add_action( 'widgets_init', 'pcgamer_widgets_init' );

/**
 * Força uso do single-product.php do tema
 */
add_filter( 'template_include', 'pcgamer_force_single_product_template', 99 );
function pcgamer_force_single_product_template( $template ) {
    if ( is_singular('product') ) {
        $custom = get_stylesheet_directory() . '/woocommerce/single-product.php';
        if ( file_exists( $custom ) ) {
            return $custom;
        }
    }
    return $template;
}

/**
 * Arquivos extras
 */
require get_template_directory() . '/inc/custom-header.php';
require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/template-functions.php';
require get_template_directory() . '/inc/customizer.php';
if ( defined( 'JETPACK__VERSION' ) ) {
    require get_template_directory() . '/inc/jetpack.php';
}

/**
 * Pop-up de avaliações
 */
add_action('wp_footer', function() {
    if ( is_product() ) : ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const trigger = document.getElementById('open-review-popup');
        const popup   = document.getElementById('custom-review-popup');
        const closeBtn= document.querySelector('.jgm-popup-close');
        if(trigger && popup && closeBtn) {
            trigger.addEventListener('click', function(e) { e.preventDefault(); popup.style.display = 'flex'; });
            closeBtn.addEventListener('click', function() { popup.style.display = 'none'; });
            window.addEventListener('click', function(e) { if (e.target === popup) popup.style.display = 'none'; });
        }
    });
    </script>
    <?php endif;
});
// Desativa os estilos padrão do WooCommerce
add_filter('woocommerce_enqueue_styles', function($styles) {
    return []; // Retorna array vazio para não carregar nenhum CSS do WooCommerce
});

/**
 * Se existir "Retirada na loja" (local_pickup), deixa como método padrão
 * sem atrapalhar a escolha do usuário depois.
 */
add_filter( 'woocommerce_shipping_chosen_method', function( $chosen, $rates ) {
    // Se já existe um escolhido (sessão do usuário), respeita.
    if ( ! empty( $chosen ) ) {
        return $chosen;
    }
    // Procura local_pickup entre as opções disponíveis.
    foreach ( (array) $rates as $rate_id => $rate_obj ) {
        $method_id = method_exists( $rate_obj, 'get_method_id' ) ? $rate_obj->get_method_id() : ( $rate_obj->method_id ?? '' );
        if ( $method_id === 'local_pickup' ) {
            return $rate_id; // seleciona retirada
        }
    }
    // Caso não exista retirada, mantém o padrão do Woo (primeiro da lista).
    return $chosen;
}, 10, 2 );

// 1) Desativa cart fragments no carrinho/checkout (e impede re-enfileirar)
add_action('wp_enqueue_scripts', function () {
  if (function_exists('is_cart') && (is_cart() || is_checkout())) {
    wp_dequeue_script('wc-cart-fragments');
    wp_deregister_script('wc-cart-fragments');
    add_filter('script_loader_tag', function ($tag, $handle) {
      return ($handle === 'wc-cart-fragments') ? '' : $tag;
    }, 10, 2);
  }
}, 100);

// 2) Registro de TODOS os assets (facilita reuse/versão)
add_action('wp_enqueue_scripts', function () {
  $theme_uri  = get_stylesheet_directory_uri();
  $theme_path = get_stylesheet_directory();

  // ---- CSS (registros) ----
  wp_register_style('pcg-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap', [], null);
  wp_register_style('pcg-material', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:FILL@0;1&display=swap', [], null);
  wp_register_style('pcg-style', get_stylesheet_uri(), [], defined('_S_VERSION') ? _S_VERSION : '1.0');

  $deps_css = [];
  if (wp_style_is('woocommerce-general', 'registered')) $deps_css[] = 'woocommerce-general';
  if (wp_style_is('woocommerce-layout',  'registered')) $deps_css[] = 'woocommerce-layout';
  wp_register_style('pcg-header', $theme_uri.'/assets/css/header.css', $deps_css, file_exists($theme_path.'/assets/css/header.css') ? filemtime($theme_path.'/assets/css/header.css') : '1.0');
  wp_register_style('pcg-topbar', $theme_uri.'/assets/css/topbar.css',  $deps_css, file_exists($theme_path.'/assets/css/topbar.css')  ? filemtime($theme_path.'/assets/css/topbar.css')  : '1.0');

  wp_register_style('pcg-product',  $theme_uri.'/assets/css/product.css',   [], file_exists($theme_path.'/assets/css/product.css')   ? filemtime($theme_path.'/assets/css/product.css')   : '1.0');
  wp_register_style('pcg-beneficios',$theme_uri.'/assets/css/beneficios.css',[], file_exists($theme_path.'/assets/css/beneficios.css')? filemtime($theme_path.'/assets/css/beneficios.css'): '1.0');
  wp_register_style('pcg-cart',     $theme_uri.'/assets/css/cart.css',      [], file_exists($theme_path.'/assets/css/cart.css')      ? filemtime($theme_path.'/assets/css/cart.css')      : '1.0');

  // ---- JS (registros) ----
  // Base global
  $ver_base = file_exists($theme_path.'/assets/js/base.js') ? filemtime($theme_path.'/assets/js/base.js') : '1.0';
  wp_register_script('pcg-base', $theme_uri.'/assets/js/base.js', ['jquery'], $ver_base, true);
  wp_script_add_data('pcg-base', 'defer', true);

  // Base de frete (compartilhado por PDP + Carrinho)
  $ver_bfrete = file_exists($theme_path.'/assets/js/base-frete.js') ? filemtime($theme_path.'/assets/js/base-frete.js') : '1.0';
  wp_register_script('pcg-base-frete', $theme_uri.'/assets/js/base-frete.js', ['jquery','pcg-base'], $ver_bfrete, true);
  wp_script_add_data('pcg-base-frete', 'defer', true);

  // PDP
  $ver_pdp = file_exists($theme_path.'/assets/js/pg-produto.js') ? filemtime($theme_path.'/assets/js/pg-produto.js') : '1.0';
  $deps_pdp = ['jquery','pcg-base','pcg-base-frete'];
  if (wp_script_is('wc-add-to-cart', 'registered')) $deps_pdp[]='wc-add-to-cart';
  wp_register_script('pcg-pg-produto', $theme_uri.'/assets/js/pg-produto.js', $deps_pdp, $ver_pdp, true);
  wp_script_add_data('pcg-pg-produto', 'defer', true);

  // Carrinho
  $ver_cart = file_exists($theme_path.'/assets/js/pg-carrinho.js') ? filemtime($theme_path.'/assets/js/pg-carrinho.js') : '1.0';
  wp_register_script('pcg-pg-carrinho', $theme_uri.'/assets/js/pg-carrinho.js', ['jquery','pcg-base','pcg-base-frete'], $ver_cart, true);
  wp_script_add_data('pcg-pg-carrinho', 'defer', true);
}, 5);

// 3) Enfileira por página + localize
add_action('wp_enqueue_scripts', function () {
  // CSS globais
  wp_enqueue_style('pcg-fonts');
  wp_enqueue_style('pcg-material');
  wp_enqueue_style('pcg-style');
  wp_enqueue_style('pcg-header');
  wp_enqueue_style('pcg-topbar');

  // JS base sempre
  wp_enqueue_script('pcg-base');

  // Localize uma vez no base (já é dependência dos demais)
  wp_localize_script('pcg-base', 'pcgamerAjax', [
    'ajax_url'  => admin_url('admin-ajax.php'),
    'nonce'     => wp_create_nonce('pcgamer_nonce'),
    'user_id'   => is_user_logged_in() ? get_current_user_id() : 0,
    'logged_in' => is_user_logged_in(),
  ]);

  // PDP
  if (function_exists('is_product') && is_product()) {
    wp_enqueue_style('pcg-product');
    wp_enqueue_style('pcg-beneficios');
    if (wp_script_is('wc-add-to-cart', 'registered')) wp_enqueue_script('wc-add-to-cart');
    wp_enqueue_script('pcg-base-frete');
    wp_enqueue_script('pcg-pg-produto');
  }

  // Carrinho
  if (function_exists('is_cart') && is_cart()) {
    wp_enqueue_style('pcg-cart');
    wp_enqueue_script('pcg-base-frete');
    wp_enqueue_script('pcg-pg-carrinho');
  }

  // Fragments apenas fora de carrinho/checkout
  if (!(function_exists('is_cart') && (is_cart() || is_checkout()))) {
    if (wp_script_is('wc-cart-fragments', 'registered')) {
      wp_enqueue_script('wc-cart-fragments');
    }
  }
}, 20);

// 4) Mercado Pago — desregistrar fora do checkout (mantido do seu código)
add_action('wp_enqueue_scripts', function () {
  if (!function_exists('is_checkout') || !is_checkout() || is_order_received_page()) {
    $handles = [
      'mp-custom-checkout','mp-checkout-card','mp-checkout-pix',
      'woocommerce-mercado-pago-custom','woocommerce-mercado-pago',
    ];
    foreach ($handles as $h) {
      if (wp_script_is($h, 'enqueued'))   wp_dequeue_script($h);
      if (wp_script_is($h, 'registered')) wp_deregister_script($h);
    }
    global $wp_scripts;
    if (isset($wp_scripts) && is_object($wp_scripts)) {
      foreach ((array)$wp_scripts->queue as $h) {
        $src = isset($wp_scripts->registered[$h]) ? $wp_scripts->registered[$h]->src : '';
        if (strpos($src, 'woocommerce-mercado-pago') !== false || strpos($src, 'mp-custom-checkout') !== false) {
          wp_dequeue_script($h);
        }
      }
    }
  }
}, 100);

// Processos local favoritos
add_action('wp_ajax_pcg_fav_snapshot', function () {
  if ( ! is_user_logged_in() ) {
    wp_send_json_error('Login obrigatório.', 401);
  }
  $user_id = get_current_user_id();
  $pid     = isset($_GET['product_id']) ? absint($_GET['product_id']) : 0;

  $fav = get_user_meta($user_id, 'produtos_favoritos', true);
  $fav = is_array($fav) ? array_map('absint', $fav) : [];
  $fav = array_values(array_unique($fav));

  $resp = [
    'count'       => count($fav),
    'isFavorited' => $pid ? in_array($pid, $fav, true) : null,
  ];

  wp_send_json_success($resp);
});
