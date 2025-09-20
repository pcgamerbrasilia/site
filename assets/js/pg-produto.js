// ==================================
// FILE: pg-produto.js (PDP específica)
// ==================================
(function ($) {
  'use strict';
  if (!$('body').hasClass('single-product')) return;

  // TTL fallback (caso não exista no base.js)
  const PCG_FAV_TTL_MS = (window.PCG_FAV_TTL_MS) || (7 * 24 * 60 * 60 * 1000);

  // ===== Hidrata botão favorito (snapshot local) e reconcilia com servidor =====
  (function () {
    if (!pcgamerAjax || !pcgamerAjax.logged_in) return;
    const pid = parseInt($('input[name="add-to-cart"]').val(), 10);
    if (!pid) return;

    // 1) Instantâneo do localStorage para UI imediata
    try {
      const snap = JSON.parse(localStorage.getItem('pcg:favs:v1:' + pcgamerAjax.user_id) || 'null');
      if (snap && Array.isArray(snap.set) && (Date.now() - (snap.ts || 0) <= PCG_FAV_TTL_MS)) {
        const isFavLocal = snap.set.indexOf(pid) !== -1;
        const $btn = $('.favoritar-produto-btn');
        if ($btn.length) {
          $btn.toggleClass('favoritado', isFavLocal)
              .attr('aria-pressed', isFavLocal ? 'true' : 'false')
              .find('.material-symbols-outlined').text(isFavLocal ? 'favorite' : 'favorite_border');
        }
      }
    } catch (_) {}

    // 2) Reconciliar com servidor
    $.get(pcgamerAjax.ajax_url, { action: 'pcg_fav_snapshot', product_id: pid }, function (resp) {
      if (!resp || !resp.success || !resp.data) return;
      const isFavSrv = !!resp.data.isFavorited;
      const countSrv = (typeof resp.data.count === 'number') ? resp.data.count : undefined;

      const $btn = $('.favoritar-produto-btn');
      if ($btn.length) {
        $btn.toggleClass('favoritado', isFavSrv)
            .attr('aria-pressed', isFavSrv ? 'true' : 'false')
            .find('.material-symbols-outlined').text(isFavSrv ? 'favorite' : 'favorite_border');
      }

      // Atualiza snapshot + header
      let set = []; let ts = Date.now();
      try {
        const raw = localStorage.getItem('pcg:favs:v1:' + pcgamerAjax.user_id);
        const p = raw ? JSON.parse(raw) : null;
        set = (p && Array.isArray(p.set)) ? p.set : [];
      } catch (_) {}
      const i = set.indexOf(pid);
      if (isFavSrv && i === -1) set.push(pid);
      if (!isFavSrv && i !== -1) set.splice(i, 1);
      const count = (typeof countSrv === 'number') ? countSrv : set.length;
      localStorage.setItem('pcg:favs:v1:' + pcgamerAjax.user_id, JSON.stringify({ ts, count, set }));

      const contador = document.querySelector('.favoritos-contador');
      if (contador) contador.textContent = count;
    });
  })();

  // ---- Adicionar ao carrinho (AJAX) — PDP ----
  $('body').on('submit', 'form.cart', function (e) {
    e.preventDefault();
    const $form = $(this);
    const productID = parseInt($form.find('input[name="add-to-cart"]').val(), 10) || 0;
    const quantity = parseInt($form.find('input.qty').val(), 10) || 1;
    const variationId = parseInt($form.find('input[name="variation_id"]').val(), 10) || 0;
    if (!productID) return;

    const url = (window.pcgWcAjax && pcgWcAjax('add_to_cart')) || '/?wc-ajax=add_to_cart';
    $.post(url, { product_id: productID, quantity, variation_id: variationId }, function (response) {
      $('.custom-cart-message').remove();
      if (response && response.error) {
        pcgToast('error', 'A quantidade máxima desse produto foi atingida no carrinho.', $form.find('button[type="submit"]'));
        return;
      }

      let hadExplicit = false;
      if (response && response.messages) {
        const $wrap = $('<div>').html(response.messages);
        $wrap.find('.woocommerce-error li').each(function () {
          const t = $(this).text().trim(); if (!t) return;
          pcgToast('error', t);
          pcgToast('error', t, $form.find('button[type="submit"]'));
          hadExplicit = true;
        });
        $wrap.find('.woocommerce-message li').each(function () {
          const t = $(this).text().trim(); if (!t) return;
          pcgToast('success', t);
        });
      }
      if (response && response.fragments) {
        $.each(response.fragments, (sel, html) => $(sel).replaceWith(html));
      }
      if (!hadExplicit && response && response.fragments && !response.messages) {
        pcgToast('success', 'Produto adicionado com sucesso no carrinho');
      }
      $(document.body).trigger('wc_fragment_refresh');
    });
  });

  if (typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.ajax_url) {
    $.post(wc_add_to_cart_params.ajax_url, { action: 'clear_wc_notices' });
  }

  // ---- Favoritos (PDP) — botão de favoritar do produto ----
  $(document).on('click', '.favoritar-produto-btn', function (e) {
    e.preventDefault();
    const $btn = $(this);
    const productId = parseInt($btn.data('product-id'), 10);
    if (!productId) return;

    // trava durante a requisição (evita clique duplo)
    if ($btn.data('busy')) return;
    $btn.data('busy', true);

    $.post((window.pcgAdminAjax && pcgAdminAjax()) || '/wp-admin/admin-ajax.php', {
      action: 'pcgamer_toggle_favorito',
      product_id: productId,
      nonce: (window.pcgamerAjax && pcgamerAjax.nonce) || ''
    }, function (response) {
      if (!response || !response.success) {
        alert((response && response.data) || 'Erro ao favoritar.');
        return;
      }

      // 1) Estado vindo do servidor (preferido), senão fallback no toggle local
      const serverHasState = response.data && typeof response.data.favoritado !== 'undefined';
      const newState = serverHasState ? !!response.data.favoritado : !$btn.hasClass('favoritado');

      // 2) Aplica visual: classe, aria e ícone
      $btn.toggleClass('favoritado', newState)
          .attr('aria-pressed', newState ? 'true' : 'false');
      const $icon = $btn.find('.material-symbols-outlined');
      if ($icon.length) $icon.text(newState ? 'favorite' : 'favorite_border');

      // 3) Mensagem
      pcgToast(newState ? 'success' : 'error',
        newState ? 'Produto adicionado aos favoritos!' : 'Produto removido dos favoritos!'
      );

      // 4) Contador do header
      const contador = document.querySelector('.favoritos-contador');
      if (contador) {
        if (response.data && typeof response.data.count === 'number') {
          contador.textContent = response.data.count;
        } else {
          let atual = parseInt(contador.textContent, 10) || 0;
          contador.textContent = newState ? (atual + 1) : Math.max(0, atual - 1);
        }
      }

      // 5) SNAPSHOT LOCAL (count + set) — dentro do success (tem productId/newState/response)
      if (pcgamerAjax && pcgamerAjax.logged_in) {
        const key = 'pcg:favs:v1:' + pcgamerAjax.user_id;
        let snap = null;
        try { snap = JSON.parse(localStorage.getItem(key) || 'null'); } catch (_) {}
        if (!snap) snap = { ts: 0, count: 0, set: [] };

        const set = Array.isArray(snap.set) ? snap.set.slice() : [];
        const idx = set.indexOf(productId);
        if (newState && idx === -1) set.push(productId);
        if (!newState && idx !== -1) set.splice(idx, 1);

        const count = (response.data && typeof response.data.count === 'number')
          ? response.data.count
          : set.length;

        localStorage.setItem(key, JSON.stringify({ ts: Date.now(), count, set }));
      }
    })
    .always(function () {
      $btn.data('busy', false);
    });
  });

  // Recarrega a PDP quando voltar do histórico (bfcache), se preferir correção via reload
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) location.reload();
  });

 

  // ---- Benefícios (Popover) ----
  (function () {
    const CONTENT = {
      pagamentos: `<div class="benefit-section"><h4 class="benefit-title">Pagamentos e Segurança</h4><p class="benefit-text">Todos os pagamentos em nosso site são processados pelo intermediador <strong>Mercado Pago</strong> plataforma líder de mercado.</p></div><div class="benefit-section"><p class="benefit-text"><strong>Principais vantagens para você:</strong></p><div class="benefit-list"><li>Compra protegida pelo programa do intermediador (reembolso em caso de problema).</li><li>Dados de cartão de crédito mantidos em sigilo, com autenticação antifraude.</li><li>Opção de pagar por <strong>Pix</strong> com confirmação imediata.</li><li>Pagamento no <strong>cartão de crédito</strong> em até 12x sem juros.</li></div></div>`,
      envio: `<div class="benefit-section"><h4 class="benefit-title">Envio com Seguro</h4><p class="benefit-text">Envio realizado via Correio, com rastreio, embalagem reforçada e seguro contra roubo/dano no transporte.</p><p class="benefit-small">Prazos e valores na simulação de frete e no checkout.</p></div><div class="benefit-section"><p class="benefit-text"><strong>Retirada na loja</strong> Pedidos mais urgentes, retire na loja.</p><p class="benefit-text"><strong>Não enviamos por outras transportadoras!!!</strong></p></div>`,
      devolucao: `<div class="benefit-section"><h4 class="benefit-title">Política de Devolução</h4><p class="benefit-text">Prazo legal de 7 dias para compras online e suporte a trocas por defeito.</p><p class="benefit-small">Confira as regras em \"Trocas e Devoluções\".</p></div>`,
      oficial: `<div class="benefit-section"><h4 class="benefit-title">Revenda Oficial</h4><p class="benefit-text">Loja física em Brasília parceira dos maiores e melhores fabricantes de hardware do mercado global. Todos os nossos produtos são novos, lacrados, com <strong>suporte e garantia integral</strong> dos fábricantes.</p><p class="benefit-small">Confira a lista em \"Revenda Oficial\".</p></div>`
    };

    function openPopover(btn) {
      const pop = document.getElementById('benefit-popover');
      if (!pop) return;
      const body = pop.querySelector('.benefit-popover-body');
      const key = btn.getAttribute('data-benefit');
      const content = pop.querySelector('.benefit-popover-content');
      body.innerHTML = CONTENT[key] || '<p class="benefit-text">Conteúdo indisponível.</p>';
      pop.hidden = false;
      document.querySelectorAll('.benefit-btn[aria-expanded="true"]').forEach(b => b.setAttribute('aria-expanded', 'false'));
      btn.setAttribute('aria-expanded', 'true');

      const wrap = btn.closest('.product-benefits-wrap') || document.body;
      const wRect = wrap.getBoundingClientRect();
      const bRect = btn.getBoundingClientRect();
      const maxW = Math.min(560, wRect.width - 24, window.innerWidth - 24);
      content.style.maxWidth = maxW + 'px';
      const pw = Math.min(content.offsetWidth || maxW, maxW);
      const leftBound = Math.max(12, wRect.left + 12);
      const rightBound = Math.min(window.innerWidth - pw - 12, wRect.right - pw - 12);
      const btnCenter = bRect.left + (bRect.width / 2);
      let left = Math.round(btnCenter - (pw / 2));
      if (left < leftBound) left = leftBound;
      if (left > rightBound) left = rightBound;
      const top = Math.round(bRect.top - content.offsetHeight - 8);
      pop.style.left = left + 'px'; pop.style.top = top + 'px';
      const caret = Math.max(12, Math.min(pw - 20, btnCenter - left - 8));
      pop.style.setProperty('--_caret-left', caret + 'px');
      pop.classList.add('is-above');
    }

    function closePopover() {
      const pop = document.getElementById('benefit-popover');
      if (!pop || pop.hidden) return;
      pop.hidden = true;
      document.querySelectorAll('.benefit-btn[aria-expanded="true"]').forEach(b => b.setAttribute('aria-expanded', 'false'));
    }

    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.benefit-btn');
      if (btn) {
        e.preventDefault();
        btn.getAttribute('aria-expanded') === 'true' ? closePopover() : openPopover(btn);
        return;
      }
      const inside = !!e.target.closest('.benefit-popover-content');
      const isClose = !!e.target.closest('.benefit-popover-close');
      if (!inside || isClose) closePopover();
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePopover(); });
    window.addEventListener('resize', closePopover);
    window.addEventListener('scroll', closePopover, { passive: true });
  })();
	
	// ==== PDP: frete ====
PCGFrete.bindPopupTriggers();
PCGFrete.maskCEP('#frete-cep');

$(document).on('submit', '#pcgamer-frete-form', function(e){
  e.preventDefault();
  const cep = ($('#frete-cep').val()||'').replace(/\D/g,'');
  const pid = $('input[name="add-to-cart"]').val();
  const qty = parseInt($('.qty').val(),10) || 1;
  if (!pid || cep.length!==8){ alert('Digite um CEP válido com 8 números.'); return; }

  const $btn = $(this).find('button[type="submit"]').prop('disabled', true).text('Calculando...');
  PCGFrete.resolveUF(cep).done(function(uf){
    $.ajax({
      url: (window.pcgAdminAjax && pcgAdminAjax()) || '/wp-admin/admin-ajax.php',
      type: 'POST', dataType:'json',
      data: { action:'pcgamer_calcular_frete', cep, state: uf, product_id: pid, qty }
    })
    .done(function(resp){
      const data = resp && (resp.data||resp);
      const fretes  = data && (data.fretes || data.data) || [];
      const imposto = data && data.imposto || null;
      const ufResp  = data && data.uf || uf;
      if (fretes.length){
        PCGFrete.renderTablePDP({ fretes, imposto, uf: ufResp });
        return;
      }
      $('#frete-tabela-body').empty(); $('.frete-imposto').empty().hide();
      alert('Não foi possível calcular o frete para o CEP informado.');
    })
    .fail(function(){
      $('#frete-tabela-body').empty(); $('.frete-imposto').empty().hide();
      alert('Erro ao calcular o frete. Tente novamente.');
    })
    .always(function(){ $btn.prop('disabled', false).text('OK'); });
  });
});


})(jQuery);
