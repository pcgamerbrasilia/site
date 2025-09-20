<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package pcgamer
 */

?>

	<footer id="colophon" class="site-footer">
		<div class="site-info">
			<a href="<?php echo esc_url( __( 'https://wordpress.org/', 'pcgamer' ) ); ?>">
				<?php
				/* translators: %s: CMS name, i.e. WordPress. */
				printf( esc_html__( 'Proudly powered by %s', 'pcgamer' ), 'WordPress' );
				?>
			</a>
			<span class="sep"> | </span>
				<?php
				/* translators: 1: Theme name, 2: Theme author. */
				printf( esc_html__( 'Theme: %1$s by %2$s.', 'pcgamer' ), 'pcgamer', '<a href="http://underscores.me/">Underscores.me</a>' );
				?>
		</div><!-- .site-info -->
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>



<script>
  document.addEventListener('DOMContentLoaded', function () {
    const phrases = [
      "AMD RYZEN 9000 SERIES DISPONÍVEL",
      "INTEL CORE ULTRA DISPONÍVEL",
      "NVIDIA RTX 5000 EM ESTOQUE",
      "AMD RADAOEN SERIES 9000 DISPONÍVEL",
      "FORNECEDOR OFICIAL LIAN LI",
    ];
let index = 0;
const phraseEl = document.getElementById("topbar-phrase");

function updatePhrase() {
  phraseEl.classList.remove("show");

  setTimeout(() => {
    phraseEl.textContent = phrases[index];
    phraseEl.classList.add("show");
    index = (index + 1) % phrases.length;
  }, 500); // tempo entre desaparecimento e novo texto
}

updatePhrase();
setInterval(updatePhrase, 4000); // tempo de cada frase
  });
</script>


<!-- #caixa de localizacao -->

<script>
document.addEventListener('DOMContentLoaded', function () {
  const btn = document.getElementById('btn-como-chegar');
  const popup = document.getElementById('location-popup');
  const closeBtn = document.getElementById('close-popup');
  let isTouch = false;

  // Detecta toque (mobile)
  window.addEventListener('touchstart', function () {
    isTouch = true;
  }, { once: true });

  function showPopup() {
    popup.hidden = false;
  }

  function hidePopup() {
    popup.hidden = true;
  }

  if (isTouch) {
    // Mobile: clicar para abrir / fechar com botão "X"
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      showPopup();
    });

    closeBtn.addEventListener('click', hidePopup);
  } else {
    // Desktop: hover abre e sai do hover fecha
    const container = document.querySelector('.header-location');

    container.addEventListener('mouseenter', showPopup);
    container.addEventListener('mouseleave', hidePopup);
  }
});
</script>

<!-- #JS para alternar favorito -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-favorite').forEach(function (button) {
        button.addEventListener('click', function () {
            const productId = this.dataset.productId;
            fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=toggle_favorite&product_id=' + productId
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const icon = this.querySelector('span');
                    icon.textContent = data.data.action === 'added' ? 'favorite' : 'favorite_border';
                }
            });
        });
    });
});
</script>

<?php if ( is_account_page() || is_product() || is_shop() ) : ?>
<script>
jQuery(function($) {
  $('body').on('added_to_cart', function() {
    $('.custom-cart-message').remove(); // remove anterior

    const msg = $('<div class="custom-cart-message">Produto adicionado ao carrinho!</div>');
    $('body').append(msg);

    setTimeout(() => {
      msg.fadeOut(400, () => msg.remove());
    }, 4000);
  });
});
</script>
<?php endif; ?>

<?php if ( is_product() ) : ?>
<script>
jQuery(function($) {
  $('form.cart').on('submit', function(e) {
    e.preventDefault();
    
    const form = $(this);
    const button = form.find('button[name="add-to-cart"]');
    const productID = button.val();
    const qty = form.find('input.qty').val() || 1;
    
    $.post(pcgamerFavorito.ajax_url, {
      action: 'pcgamer_add_to_cart',
      product_id: productID,
      quantity: qty
    }, function(response) {
      if (response.success) {
        $('body').trigger('added_to_cart');
        // Atualizar fragmentos do WooCommerce
$.get('?wc-ajax=get_refreshed_fragments');
      }
    });
  });
});
</script>
<?php endif; ?>


</body>
</html>
