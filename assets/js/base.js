// =============================
// FILE: base.js (núcleo comum)
// =============================
/* Compat: legado que usa pcgamerFavorito → aponta para pcgamerAjax */
window.pcgamerAjax = window.pcgamerAjax || { ajax_url: '/wp-admin/admin-ajax.php', nonce: '' };
if (typeof window.pcgamerFavorito === 'undefined') {
  window.pcgamerFavorito = {
    ajax_url: window.pcgamerAjax.ajax_url,
    nonce:    window.pcgamerAjax.nonce
  };
}

// ===== Snapshot local de favoritos (por usuário) =====
const PCG_FAV_TTL_MS = 7 * 24 * 60 * 60 * 1000; // 7 dias
function favKey() {
  const uid = (window.pcgamerAjax && pcgamerAjax.user_id) || 0;
  return 'pcg:favs:v1:' + uid; // isolado por usuário
}
function loadFavSnapshot() {
  try {
    const raw = localStorage.getItem(favKey());
    if (!raw) return null;
    const data = JSON.parse(raw);
    if (!data || !data.ts) return null;
    // TTL
    if (Date.now() - data.ts > PCG_FAV_TTL_MS) return null;
    return data; // { ts, count, set: [ids] (opcional) }
  } catch (_) { return null; }
}
function saveFavSnapshot(obj) {
  try {
    const now = Date.now();
    const prev = loadFavSnapshot() || {};
    const data = { ts: now, count: obj.count ?? prev.count ?? 0, set: obj.set ?? prev.set ?? [] };
    localStorage.setItem(favKey(), JSON.stringify(data));
    return data;
  } catch (_) { /* ignore */ }
  return null;
}
function updateHeaderCountFromSnapshot() {
  const snap = loadFavSnapshot();
  if (!snap) return;
  const el = document.querySelector('.favoritos-contador');
  if (el) el.textContent = snap.count || 0;
}
updateHeaderCountFromSnapshot();


(function ($) {
  'use strict';

  // ---- Utils AJAX ----
  window.pcgAdminAjax = function () {
    return (window.pcgamerAjax && pcgamerAjax.ajax_url) ? pcgamerAjax.ajax_url : '/wp-admin/admin-ajax.php';
  };
  window.pcgWcAjax = function (endpoint) {
    const base = (window.wc_add_to_cart_params && wc_add_to_cart_params.wc_ajax_url) || '';
    return base ? base.toString().replace('%%endpoint%%', endpoint) : null;
  };

  // ---- Toast simples reutilizável (favoritos e avisos globais) ----
  window.pcgToast = function (tipo, texto, $after) {
    $('.custom-cart-message').remove();
    const $msg = $('<div class="custom-cart-message ' + (tipo || 'info') + '">' + texto + '</div>');
    if ($after && $after.length) { $after.after($msg); } else { $('body').append($msg); }
    setTimeout(() => $msg.fadeOut(400, () => $msg.remove()), 5000);
  };

  // ---- Compartilhar WhatsApp (opcional) ----
  $(document).on('click', '.compartilhar-whatsapp', function (e) {
    e.preventDefault();
    const mensagem = $(this).data('msg') || document.location.href;
    const isMobile = /Android|iPhone|iPad|iPod|Windows Phone/i.test(navigator.userAgent);
    const baseURL = isMobile ? 'https://wa.me/?text=' : 'https://web.whatsapp.com/send?text=';
    window.open(baseURL + encodeURIComponent(mensagem), '_blank', 'noopener,noreferrer');
  });

  // ---- Popup “Retira Rápido” (global) ----
  function abrirPopupRetiraInner() { $('#popup-retira').fadeIn(120); $('body').addClass('no-scroll'); }
  function fecharPopupRetiraInner() { $('#popup-retira').fadeOut(120); $('body').removeClass('no-scroll'); }
  window.abrirPopupRetira = abrirPopupRetiraInner;
  window.fecharPopupRetira = fecharPopupRetiraInner;
  $(document).on('click', '#popup-retira', function (e) {
    if ($(e.target).closest('.popup-conteudo').length === 0) fecharPopupRetiraInner();
  });
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape') { fecharPopupRetiraInner(); $('#popup-frete:visible').fadeOut(120); }
  });

  // ---- Favoritos (GERAL): remover da lista + contador no header ----
  $(document).on('click', '.remover-favorito', function (e) {
    e.preventDefault();
    const $btn = $(this);
    const productId = $btn.data('product-id');
    if (!productId) return;

    $.post(pcgAdminAjax(), {
      action: 'pcgamer_toggle_favorito',
      product_id: productId,
      nonce: (window.pcgamerAjax && pcgamerAjax.nonce) || ''
    }, function (response) {
      if (!response || !response.success) {
        alert((response && response.data) || 'Erro ao remover favorito.');
        return;
      }

      // Remove item da lista
      $btn.closest('.favorito-item').fadeOut(() => {
        const $restantes = $('.favorito-item:visible');
        if (!$restantes.length) {
          $('.favoritos-lista').html('<p>Você ainda não favoritou nenhum produto.</p>');
        }
      });

      // Atualiza contador no header: usa o valor do servidor quando disponível
      const contador = document.querySelector('.favoritos-contador');
      if (contador) {
        if (response.data && typeof response.data.count === 'number') {
          contador.textContent = response.data.count;
        } else {
          let atual = parseInt(contador.textContent) || 0;
          contador.textContent = Math.max(0, atual - 1);
        }
      }

      pcgToast('error', 'Produto removido dos favoritos.');
    });
  });

})(jQuery);

/* =========================
 * Navegação (header) — mobile e acessibilidade básica
 *  (substitui o antigo navigation.js)
 * ========================= */
(() => {
  const nav = document.getElementById('site-navigation');
  if (!nav) return;

  const button = nav.querySelector('button');
  const menu = nav.querySelector('ul');
  if (!button || !menu) return;

  menu.classList.add('nav-menu');

  // Toggle do menu
  button.addEventListener('click', (e) => {
    e.preventDefault();
    const open = nav.classList.toggle('toggled');
    button.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  // Fecha ao clicar fora
  document.addEventListener('click', (e) => {
    if (!nav.contains(e.target)) {
      nav.classList.remove('toggled');
      button.setAttribute('aria-expanded', 'false');
    }
  });

  // Fecha com ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      nav.classList.remove('toggled');
      button.setAttribute('aria-expanded', 'false');
      button.focus();
    }
  });

  // Acessibilidade: foco/blur e toque em itens com submenu
  const links = menu.querySelectorAll('a');
  const linksWithChildren = menu.querySelectorAll('.menu-item-has-children > a, .page_item_has_children > a');

  function toggleFocus(e) {
    if (e.type === 'focus' || e.type === 'blur') {
      let el = this;
      while (el && !el.classList.contains('nav-menu')) {
        if (el.tagName && el.tagName.toLowerCase() === 'li') {
          el.classList.toggle('focus', e.type === 'focus');
        }
        el = el.parentNode;
      }
    }
    if (e.type === 'touchstart') {
      const li = this.parentNode;
      e.preventDefault();
      Array.from(li.parentNode.children).forEach((sibling) => {
        if (sibling !== li) sibling.classList.remove('focus');
      });
      li.classList.toggle('focus');
    }
  }

  links.forEach((a) => {
    a.addEventListener('focus', toggleFocus, true);
    a.addEventListener('blur', toggleFocus, true);
  });
  linksWithChildren.forEach((a) => {
    a.addEventListener('touchstart', toggleFocus, false);
  });
})();
