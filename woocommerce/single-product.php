<?php
defined( 'ABSPATH' ) || exit;
get_header(); ?>

<?php $img = get_stylesheet_directory_uri() . '/assets/img/'; ?>

<main id="primary" class="site-main">
  <section class="page-container product-layout">


<div class="woocommerce-breadcrumb">
	<?php do_action( 'woocommerce_before_main_content' ); ?>

	<!-- Compartilhar produto no whatsapp-->
  	<?php
    $url_produto = get_permalink();
    $mensagem = 'Encontrei este produto na PC Gamer Brasília: ' . $url_produto;
    $link_whatsapp = 'https://wa.me/?text=' . urlencode( $mensagem );
  ?>
  <a href="<?php echo esc_url( $link_whatsapp ); ?>" target="_blank" title="Compartilhar no WhatsApp">
    <span class="material-symbols-outlined">share</span> Compartilhe
  </a>
	<!-- Compartilhar produto no whatsapp-->
	
</div>
	  
    <?php while ( have_posts() ) : the_post(); global $product;

      if ( ! $product instanceof WC_Product ) {
        $product = wc_get_product( get_the_ID() );
      }
    ?>
      <div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'single-product-grid', $product ); ?>>

        <header class="product-header">
			


			
          <h1 class="product-title"><?php the_title(); ?></h1>
			

        </header>

        <div class="product-grid">
			
			
<!-- box galeria -->
          <div class="product-gallery">
<!-- box marca estrelas favoritos -->
<div class="produto-descricao-box">	  
<?php
$brands = get_the_terms( $product->get_id(), 'product_brand' );

if ( $brands && ! is_wp_error( $brands ) ) {
    $brand = $brands[0];
    $thumbnail_id = get_term_meta( $brand->term_id, 'thumbnail_id', true );
    $image_url = wp_get_attachment_url( $thumbnail_id );
    $brand_link = get_term_link( $brand );

    if ( $image_url && ! is_wp_error( $brand_link ) ) {
        echo '<div class="marca-produto">';
        echo '<a href="' . esc_url( $brand_link ) . '" title="' . esc_attr( $brand->name ) . '">';
        echo '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $brand->name ) . '" class="logo-marca">';
        echo '</a>';
        echo '</div>';
    }
}
?>
			  
<div class="store-review-badge" id="open-review-popup">
<div class="avaliacoes-resumo" tabindex="0" aria-label="Average rating is 3" role="img"> <span class="jdgm-star jdgm--on"></span><span class="jdgm-star jdgm--on"></span><span class="jdgm-star jdgm--on"></span><span class="jdgm-star jdgm--on"></span><span class="jdgm-star jdgm--half"></span></div>
</div>
	  

<div class="Botoes-produto">

<!-- Favoritar -->
<?php
$product_id  = (int) get_the_ID();
$is_logged   = is_user_logged_in();
$favoritado  = false;

if ( $is_logged ) {
    $user_id   = get_current_user_id();
    $favoritos = get_user_meta( $user_id, 'produtos_favoritos', true );
    $favoritos = is_array( $favoritos ) ? array_map( 'absint', $favoritos ) : []; // << normalize
$favoritado = in_array((int)$product_id, $favoritos, true);
}
?>
	
	
<?php if ( $is_logged ) : ?>
  <button
    class="botao-icone favoritar-produto-btn <?php echo $favoritado ? 'favoritado' : ''; ?>"
    data-product-id="<?php echo esc_attr( $product_id ); ?>"
    aria-pressed="<?php echo $favoritado ? 'true' : 'false'; ?>"
    title="<?php echo $favoritado ? esc_attr__( 'Remover dos favoritos', 'pcgamer' ) : esc_attr__( 'Adicionar aos favoritos', 'pcgamer' ); ?>"
    type="button"
  >
    <span class="material-symbols-outlined">
      <?php echo $favoritado ? 'favorite' : 'favorite_border'; ?>
    </span>
  </button>
<?php else : ?>
  <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"
     class="botao-icone btn-favorite-login"
     title="<?php esc_attr_e( 'Faça login para favoritar', 'pcgamer' ); ?>">
    <span class="material-symbols-outlined">favorite_border</span>
  </a>
<?php endif; ?>
<!-- Favoritar -->
	
</div>
			  
<!-- Popup oculto -->
<div id="custom-review-popup" class="jgm-popup-overlay" style="display: none;">
  <div class="jgm-popup-content">
    <button class="jgm-popup-close">&times;</button>
    <?php echo do_shortcode('[jgm-all-reviews]'); ?>
  </div>
</div>	 

            <?php woocommerce_template_single_rating(); ?>
</div>
<!-- box marca estrelas favoritos -->
			  
<?php woocommerce_show_product_images(); ?>
			  

			  
          </div>
<!-- box galeria -->
			

          <div class="produto-descricao">

	  
<div class="produto-descricao-box">

    <?php
    global $product;

    $em_estoque = $product->is_in_stock();
    $texto_estoque = $em_estoque ? 'Produto disponível' : 'Produto indisponível';
    $icone_estoque = $em_estoque ? 'check_circle' : 'cancel';
    $classe_estoque = $em_estoque ? 'disponivel' : 'indisponivel';
	$tooltip = $em_estoque ? 'Temos esse produto em estoque na loja. Pode comprar pelo site ou retirar pessoalmente.' : '';

    ?>

    <span class="info-bloco <?php echo $classe_estoque; ?>" <?php echo $tooltip ? 'title="' . esc_attr($tooltip) . '"' : ''; ?>>
      <span class="material-symbols-outlined"><?php echo $icone_estoque; ?></span>
      <?php echo esc_html($texto_estoque); ?>
    </span>

    <span class="info-bloco novo" title="Esse produto é novo, lacrado, possui nota fiscal e garantia.">
      <span class="material-symbols-outlined">new_releases</span>
      Produto novo
    </span>

    <span class="info-bloco garantia" title="Esse produto possui garantia legal e contratual junto ao fabricante.">
      <span class="material-symbols-outlined">verified_user</span>
      Garantia oficial
    </span>

</div>

<!-- box localizacao -->
<div class="produto-localizacao">
<div class="produto-localizacao-imagem">
  <a href="https://maps.app.goo.gl/EoCJuVEqdumvHf5D9" target="_blank" rel="noopener noreferrer" class="mapa-link">
    <img src="/wp-content/uploads/2025/07/maps-resumo-zoom-txt-maior.png" alt="Fachada da loja">
    <span class="mapa-overlay">Ver localização</span>
  </a>
</div>

  <div class="produto-localizacao-texto">

       <strong><span class="material-symbols-outlined">storefront</span>
      Brasília Shopping </strong>

      Torre Norte Sala 1417<br>
      SCN Quadra 5 Bloco A<br>
      Asa Norte, Brasília – DF
	  
	  <p>
	  <strong style="
    font-size: 14px;
">
<span class="material-symbols-outlined">deployed_code_update</span> Em estoque na loja<br>
<span class="material-symbols-outlined">sprint</span> Retirada imediata<br>
<span class="material-symbols-outlined">money_range</span> Pague na retirada </strong>	  
	  </p>

</div>

<div class="produto-estoque-reserva">
	
<div class="info-estoque">
	<span class="material-symbols-outlined">deployed_code_update</span><strong>Disponível</strong>
	<div class="info-estoque-numero">
<?php
global $product;
$estoque = $product->get_stock_quantity();
$estoque_exibido = ($estoque > 8) ? 8 : $estoque;

if ( $product->is_in_stock() ) {
  echo ' ' . $estoque_exibido . ' unidade' . ($estoque_exibido > 1 ? 's' : '');
} else {
  echo 'Indisponível';
}
	?>
</div>
</div>
	
<div class="botao-reserva">
<button class="rainbow-button" onclick="abrirPopupRetira()"><b> Reservar para retirar</b></button></div>
</div>
</div>
	


<!-- Popup retira rapido -->
<div id="popup-retira" class="popup-overlay" style="display:none;">
  <div class="popup-conteudo popup-retira" role="dialog" aria-modal="true" aria-labelledby="retiraTitulo">
    <button class="popup-fechar" type="button" aria-label="Fechar" onclick="fecharPopupRetira()">×</button>

    <div class="retira-grid">
      <!-- COLUNA ESQUERDA: CARD -->
      <aside class="retira-card">

        <!-- loja -->
        <header class="retira-loja">
  <img class="retira-logo"
       src="<?php echo pcgamer_get_img_url('logo-pcgamerbrasilia-quadrada.png'); ?>"
       alt="Logo PC Gamer Brasília"
       loading="lazy">
          <div>
            <h3 id="retiraTitulo" class="retira-nome">Loja Física PC Gamer Brasília</h3>
			  
			  <div class="retira-sub">
							  <span class="material-symbols-outlined">location_on</span>
								Brasília Shopping - Torre Norte Sala 1417</div>
          		</div>
        </header>
		  
		 <!-- endereço -->
        <address class="retira-end">
          SCN Q 5 Bloco A - Asa Norte, Brasília - DF, 70715-900
        </address>
        <!-- horários -->
        <div class="retira-horarios">
          <strong>Segunda a Sexta</strong> 10:00 às 20:00 <span class="sep">•</span>
          <strong>Sábado</strong> 10:00 às 15:00
        </div>
		  
		  
        <!-- faixa superior -->
        <div class="retira-badge">
<span class="material-symbols-outlined" style="font-size:16px; margin-left: 6px;">deployed_code_update</span>
          <span>Produto com estoque imediato</span>
        </div>
        <!-- ação principal + distância -->
          <div class="retira-meta">
            <small>após a reserva no site</small>
            <span class="retira-distancia">retire em 2 horas</span>
          </div>



		  
        <!-- dicas (accordion nativo) -->
        <details class="retira-dicas" open>
			<summary>Instruções para retirada</summary>
          <ul>
            <li>Adicione os itens ao carrinho, escolha forma de pagamento e retirada em loja física e finalize a reserva</li>
			<li>Confira em seu e-mail o número do pedido, chame no Whatsapp informe o número do pedido para confirmar sua reserva.</li>
			<li>Quando o pedido estiver pronto, avisamos pelo WhatsApp.</li>
            <li>Confira o horário de funcionamento da loja antes de ir.</li>
            <li>Se o pedido for pesado/volumoso, planeje o transporte adequado. O estacionamento <strong>G3 do Shopping</strong> tem acesso direto ao elevador da Torre Norte.</li>
          </ul>
        </details>

        <!-- links de apoio -->
        <nav class="retira-links">
			
<!-- botão "retirar nessa loja" -->
<div class="retira-cta">
	<a href="https://maps.app.goo.gl/EoCJuVEqdumvHf5D9" target="_blank" rel="noopener" class="btn btn-azul-outline">Abrir mapa</a>
    <a href="/carrinho" target="_blank" class="btn btn-azul-outline">Adicionar ao carrinho</a>
	<a href="/central-de-atendimento" target="_blank" class="btn btn-azul-outline">Fale conosco</a>
</div>
        </nav>
      </aside>

      <!-- COLUNA DIREITA: MAPA -->
      <div class="retira-mapa">
        <!-- Opção 1: iframe Google Maps (leve e sem JS externo) -->
        <iframe
          title="Mapa – PC Gamer Brasília"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
          allowfullscreen
  src="https://maps.google.com/maps?output=embed&z=13&q=PC%20Gamer%20Bras%C3%ADlia%20-%20Bras%C3%ADlia%20Shopping&ll=-15.7896,-47.8925">
        </iframe>

        <!-- Opção 2 (fallback): imagem clicável
        <a class="mapa-link" href="https://maps.app.goo.gl/EoCJuVEqdumvHf5D9" target="_blank" rel="noopener">
          <img src="/wp-content/uploads/2025/07/maps-resumo-zoom-txt-maior.png" alt="Ver no mapa">
          <span class="mapa-badge">Você está aqui</span>
        </a> -->
      </div>
    </div>
  </div>
</div>
<!-- Popup Retira Rápido -->



<!-- box preco -->


			  
			  
<!-- area de botoes e precos produto -->
<div class="product-price">
	
	<!-- Container fixo para mensagens do carrinho -->
<div id="mensagem-carrinho" class="custom-cart-message-wrapper"></div>


	
<div class="cartao" style="font-size: 18px;padding-top: 15px;">
<?php
global $VALOR_PIX, $VALOR_TOTAL, $VALOR_DA_PARCELA;
?>	
    <strong><?php echo wc_price( $VALOR_TOTAL ); ?></strong> em até 12x de <strong><?php echo wc_price( $VALOR_DA_PARCELA ); ?></strong> sem juros no cartão
	</div>
	
	
<!-- area de botoes e precos produto -->
<div class="product-preco-botoes">
<div class="preco-pix">
  <h2 style="margin-bottom: -5px;
    font-size: 36px;
    margin-top: 15px;">
	  
    <?php echo wc_price( $VALOR_PIX ); ?>
  </h2>
	
	<div class="texto-pix" style="font-size: 14px;">
		<img src="<?php echo pcgamer_get_img_url('ico-pix.png'); ?>" alt="Pix" style="height: 15px; vertical-align: middle;">
		No PIX com <strong>15% de desconto</strong></div></div>
	
<!-- area de botoes -->
<div class="botoes-comprar">
	

	
<form class="cart" method="post" enctype="multipart/form-data">
	<!-- botao quantidade -->
  <div class="quantidade-wrapper">
    <label for="quantity_<?php echo esc_attr( $product->get_id() ); ?>" class="screen-reader-text">Quantidade</label>
    <input
      type="number"
      id="quantity_<?php echo esc_attr( $product->get_id() ); ?>"
      class="qty"
      name="quantity"
      value="1"
		     max="<?php echo esc_attr( $product->get_stock_quantity() ); ?>"
		   	 min="<?php echo esc_attr( max( 1, (int) $product->get_min_purchase_quantity() ) ); ?>"

      inputmode="numeric"
		       pattern="[0-9]*"
    autocomplete="off"
    aria-label="Quantidade"
    />
  </div>
	
  <input type="hidden" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" />

	
<!-- botao adicionar carrinho -->
<button type="submit" class="ajax_add_to_cart">
    <span class="material-symbols-outlined">shopping_cart</span> Adicionar ao carrinho
  </button>
</form>

	
<!-- botao retira rapido -->
  <button onclick="abrirPopupRetira()">
    <span class="material-symbols-outlined">storefront</span> Retira rápido
  </button>
</div>


              <?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

</div>
</div>
	  
<!-- FORMAS DE PAGAMENTO -->
<?php
if ( ! function_exists('pcg_formata_brl') ) {
  function pcg_formata_brl($v){ return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
}

$preco_base = isset($VALOR_TOTAL) ? (float) $VALOR_TOTAL : 0.0;
if ($preco_base <= 0) return; // sem preço, não renderiza

// ===== Descontos =====
$desc_pix_dinheiro   = 0.15; // presencial e online (Pix/Dinheiro/Boleto)
$desc_avista_cartao  = 0.10; // SOMENTE retirada (cartão/débito à vista)
$desc_4x_cartao      = 0.05; // SOMENTE retirada (até 3x sem juros)

// ===== Totais com descontos =====
$total_pix_geral      = $preco_base * (1 - $desc_pix_dinheiro);
$total_cartao_avista  = $preco_base * (1 - $desc_avista_cartao);
$total_cartao_4x      = $preco_base * (1 - $desc_4x_cartao);

// ===== Parcelas sem desconto (até 12x) =====
$max_parcelas = 12;
$parcelas = [];
for ($n = 1; $n <= $max_parcelas; $n++) {
  $parcelas[$n] = $preco_base / $n; // valor por parcela para cada opção 1..12
}

// ===== Parcelas COM desconto 3x (usa total com 5% OFF) =====
$max_parcelas_4x = 4;
$parcelas_4x = [];
for ($n = 1; $n <= $max_parcelas_4x; $n++) {
  $parcelas_4x[$n] = $total_cartao_4x / $n; // valor por parcela para cada opção 1..3
}
?>


<div class="caixa-pagamento" id="formas-de-pagamento">
  <div class="cabecalho-caixa">Formas de pagamento e Descontos</div>
  <div class="lista-pagamento">

    <!-- Cartão parcelado -->
    <details class="linha-pagamento">
      <summary>
        <span class="icone">💳</span>
        <span class="titulo"><strong>Cartão de Crédito parcelado</strong> (até <?php echo $max_parcelas; ?>x sem juros)</span>
        <span class="preco"><?php echo pcg_formata_brl($preco_base); ?></span>
      </summary>
      <div class="corpo-pagamento">

          <div class="titulo-parcelas">Parceladomento exclusivamente no Cartão de Crédito — em até <?php echo $max_parcelas; ?>x sem juros</div>
          <div class="grid-parcelas">
            <?php foreach ($parcelas as $n=>$parc): ?>
              <div class="item-parcela">
                <span><strong><?php echo $n; ?>x</strong> de <?php echo pcg_formata_brl($parc); ?></span>
                <span>sem juros</span>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="texto-regra">*Online via Mercado Pago ou na Loja física</div>

      </div>
    </details>
	      <!-- Cartão parcelado em até 3x (retirada, 5% OFF) -->
    <details class="linha-pagamento">
      <summary>
        <span class="icone">💳</span>
        <span class="titulo"><strong>Cartão de Crédito em até 4x</strong> (Exclusivo Loja Física)</span>
        <span class="etiqueta">5% OFF</span>
        <span class="preco"><?php echo pcg_formata_brl($total_cartao_4x); ?></span>
      </summary>
      <div class="corpo-pagamento">
        <div class="titulo-parcelas">
          Parcelamento exclusivamente no Cartão de Crédito — em até <?php echo (int) $max_parcelas_4x; ?>x sem juros (com desconto aplicado)
        </div>
        <div class="grid-parcelas">
          <?php foreach ($parcelas_4x as $n => $parc): ?>
            <div class="item-parcela">
              <span><strong><?php echo (int) $n; ?>x</strong> de <?php echo pcg_formata_brl($parc); ?></span>
              <span>sem juros</span>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="texto-regra">*Desconto de 5% válido apenas para retirada na loja física.</div>
		<div class="nota-rodape">*Preços e estoques do site iguais da Loja Física!</div>
      </div>
    </details>
    <!-- Cartão à vista (retirada) -->
    <details class="linha-pagamento">
      <summary>
        <span class="icone">💳</span>
        <span class="titulo"><strong>Cartão à vista</strong> (Exclusivo Loja Física)</span>
        <span class="etiqueta">10% OFF</span>
        <span class="preco"><?php echo pcg_formata_brl($total_cartao_avista); ?></span>
      </summary>
      <div class="corpo-pagamento">
        <p class="texto-regra">Desconto exclusivo para pagamento na retirada em loja física, à vista no crédito ou débito.</p>
		  <div class="nota-rodape">*Preços e estoques do site iguais da Loja Física!</div>
      </div>
    </details>

    <!-- Pix (presencial ou online) -->
    <details class="linha-pagamento">
      <summary>
    <span class="icone">💵</span>
        <span class="titulo"><strong>Pix/Dinheiro</strong></span>
        <span class="etiqueta">15% OFF</span>
        <span class="preco"><?php echo pcg_formata_brl($total_pix_geral); ?></span>
      </summary>
      <div class="corpo-pagamento">
        <p class="texto-regra">Valor com desconto exclusivo para pagamento à vista já aplicado. Sendo váido para PIX no site e PIX/Dinheiro na loja física. 
		  <div class="nota-rodape">*Preços e estoques do site iguais da Loja Física!</div>
      </div>
    </details>

  </div>
</div>

			  
			  
<!-- Benefícios da loja -->
<div class="product-benefits-wrap">
  <ul class="product-benefits" role="list">
    <li>
      <button class="benefit-btn" data-benefit="pagamentos" aria-haspopup="dialog" aria-expanded="false">
        <img src="<?php echo pcgamer_get_img_url('logo-mercadopago.png'); ?>" alt="" loading="lazy">
        <span>Transação Segura</span>
      </button>
    </li>
    <li>
      <button class="benefit-btn" data-benefit="envio" aria-haspopup="dialog" aria-expanded="false">
        <img src="<?php echo esc_url($img . 'logo-correio.png'); ?>" alt="" loading="lazy">
        <span>Envio com Seguro</span>
      </button>
    </li>
    <li>
      <button class="benefit-btn" data-benefit="devolucao" aria-haspopup="dialog" aria-expanded="false">
        <img src="<?php echo esc_url($img . 'devolucao-de-produto.png'); ?>" alt="" loading="lazy">
        <span>Devolução Gratuita</span>
      </button>
    </li>
    <li>
      <button class="benefit-btn" data-benefit="oficial" aria-haspopup="dialog" aria-expanded="false">
        <img src="<?php echo esc_url($img . 'produto-original.png'); ?>" alt="" loading="lazy">
        <span>Revenda Oficial</span>
      </button>
    </li>
  </ul>

  <!-- Popover único, reaproveitado para todos os itens -->
  <div class="benefit-popover" id="benefit-popover" role="dialog" aria-modal="false" hidden>
    <div class="benefit-popover-content">
      <button class="benefit-popover-close" aria-label="Fechar">×</button>
      <div class="benefit-popover-body"><!-- conteúdo entra via JS --></div>
    </div>
  </div>
</div>
<!-- Benefícios da loja -->
			  
			  
<!-- box frete -->
<div class="box-frete">
			  
<form id="pcgamer-frete-form">
  <label class="frete-label" for="frete-cep"><strong><span class="material-symbols-outlined">local_shipping</span>Calcular entrega </strong></label>
  <input class="input" type="text" id="frete-cep" placeholder="Digite seu CEP" maxlength="9" />
  <input type="hidden" id="frete-uf" value="" />
 <!-- Ajustável via JS futuramente -->
  <button type="submit">OK</button>
  <div class="frete-resposta"></div>
</form>

<div id="popup-frete" class="frete-popup" style="display:none;">
  <div class="frete-popup-overlay"></div>
  <div class="frete-popup-conteudo">
    <button class="frete-popup-close">×</button>
    <h3>Frete e prazo de entrega</h3>

    <table class="frete-tabela">
      <thead>
        <tr>
          <th>Modalidade</th>
          <th>Taxa</th>
          <th>Prazo</th>
        </tr>
      </thead>
      <tbody id="frete-tabela-body">
        <!-- JS preenche aqui -->
      </tbody>
    </table>


	  <div class="frete-imposto" style="margin-top: 10px; font-weight: bold;"></div>
	  

	  
    <div class="frete-infos">
<p><strong>O valor do frete já contempla o seguro do envio, nos garantimos a entrega do produto em perfeitas condições!</strong></p>
<p>Os envios são realizados em até 2 dias úteis após o pagamento. E após a postagem, o rastreio será enviado por e-mail e tem a primeira atualização em até 24h.</p>
<strong>Não negociamos os valores e prazos aqui informados!</strong> Para pedidos mais urgentes, somente retirando na loja física, agradecemos a compreensão! </div>
  </div>
</div>
	</div>
<!-- box frete -->

	  
			  
			  

          
        </div>
      </div>
    <?php endwhile; ?>

    <?php do_action( 'woocommerce_after_main_content' ); ?>
  </section>
</main>

<?php get_footer(); ?>

<script>
  function copiarLinkProduto() {
    const link = "<?php echo esc_url( get_permalink() ); ?>";
    navigator.clipboard.writeText(link).then(() => {
      alert("Link copiado!");
    }).catch(err => {
      console.error("Erro ao copiar link: ", err);
    });
  }
  // Abrir popup
  function abrirPopupRetira() {
    document.getElementById('popup-retira').style.display = 'flex';
    document.body.classList.add('no-scroll');
  }

  function fecharPopupRetira() {
    document.getElementById('popup-retira').style.display = 'none';
    document.body.classList.remove('no-scroll');
  }

  // Fechar ao clicar fora
  document.addEventListener('click', function(e) {
    const overlay = document.getElementById('popup-retira');
    if (!overlay) return;

    if (overlay.style.display === 'flex' && e.target === overlay) {
      fecharPopupRetira();
    }
  });
</script>
	