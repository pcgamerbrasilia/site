// ==================================
// FILE: pg-carrinho.js (Carrinho)
// ==================================
(function($){
'use strict';
if (!$('body').hasClass('woocommerce-cart')) return;


// ---- Helpers qty ----
const clamp = (val,min,max,step)=>{ let mn=parseFloat(min); if(isNaN(mn)) mn=1; let v=parseFloat(val); if(isNaN(v)) v=mn; let mx=parseFloat(max); if(isNaN(mx)) mx=Infinity; let st=parseFloat(step); if(isNaN(st)||st<=0) st=1; v=Math.round(v/st)*st; v=Math.min(Math.max(v,mn),mx); return v; };
const refreshFragments = ()=>{ const url = (window.pcgWcAjax&&pcgWcAjax('get_refreshed_fragments')); if(!url) return; $.get(url, function(resp){ if(resp && resp.fragments){ $.each(resp.fragments, (sel,html)=> $(sel).replaceWith(html)); } }); };
const setLoading = (on)=>{ const $f=$('.woocommerce-cart-form'); if(!$f.length) return; if(on){ if(!$('.pcg-cart-loading').length) $('<div class="pcg-cart-loading"/>').css({position:'absolute', inset:0, background:'rgba(255,255,255,.6)', zIndex:5}).appendTo($f.css('position','relative')); } else { $('.pcg-cart-loading').remove(); } };


let debounceTimer=null;
const updateCartAjax = (extraButton)=>{ clearTimeout(debounceTimer); debounceTimer=setTimeout(function(){ const $form=$('.woocommerce-cart-form'); if(!$form.length) return; setLoading(true); const arr=$form.serializeArray(); arr.push({name:'update_cart', value:'1'}); if (extraButton&&extraButton.name) arr.push({name:extraButton.name, value:extraButton.value||'1'}); $.ajax({ url:$form.attr('action'), type:'POST', data: $.param(arr), dataType:'html' }).done(function(html){ $(document.body).trigger('wc_fragment_refresh'); const $resp=$('<div>').append($.parseHTML(html)); const $newForm=$resp.find('.woocommerce-cart-form'); const $newCol=$resp.find('.cart-collaterals'); if($newForm.length) $('.woocommerce-cart-form').replaceWith($newForm); if($newCol.length) $('.cart-collaterals').replaceWith($newCol); wrapQtyInputs(document); refreshFragments(); $(document.body).trigger('updated_wc_div'); }).fail(function(){ window.location.reload(); }).always(function(){ setLoading(false); }); }, 300); };

// Disponibiliza para outros módulos (ex.: base-frete aplicar frete -> recarregar totais)
window.pcgCartUpdate = updateCartAjax;

function wrapQtyInputs(ctx){ $(ctx).find('.woocommerce-cart-form .product-quantity input.qty').each(function(){ const $input=$(this); if ($input.parent('.pcg-qty').length) return; const $wrap=$('<div class="pcg-qty" data-pcg-qty/>'); const $minus=$('<button type="button" class="pcg-qty__btn minus" aria-label="Diminuir">−</button>'); const $plus=$('<button type="button" class="pcg-qty__btn plus" aria-label="Aumentar">+</button>'); $input.attr({inputmode:'numeric'}).css({border:0, outline:'none'}); $input.wrap($wrap); $input.before($minus); $input.after($plus); }); }
wrapQtyInputs(document);


// helper: mostra mensagem logo abaixo do wrapper do qty
function showQtyMsg($wrap, text){
  let $msg = $wrap.next('.pcg-qty__msg');        // logo abaixo do bloco +/−
  if(!$msg.length){
    $msg = $('<div class="pcg-qty__msg" role="alert" />');
    $wrap.after($msg);
  }
  $msg.stop(true, true).text(text).fadeIn(120);

  // some após 5s
  clearTimeout($msg.data('t'));
  const t = setTimeout(()=> $msg.fadeOut(160), 5000);
  $msg.data('t', t);
}

// +/−
$(document).on('click', '.pcg-qty__btn', function(){
  const $wrap  = $(this).closest('[data-pcg-qty]');
  const $input = $wrap.find('input.qty');
  if (!$input.length) return;

  const min  = parseFloat($input.attr('min')) || 1;
  const max  = parseFloat($input.attr('max'));
  const step = parseFloat($input.attr('step')) || 1;

  const prev = parseFloat($input.val()) || min;
  let next   = prev;

  if ($(this).hasClass('minus')) {
    // tentativa abaixo do mínimo
    if (prev - step < min) {
      next = Math.max(prev, min);
      showQtyMsg($wrap, 'Mínimo aceito!');
    } else {
      next = prev - step;
    }
  } else {
    // tentativa acima do máximo
    if (!isNaN(max)) {
      if (prev >= max) {
        // já está no teto → só avisa e NÃO atualiza carrinho
        showQtyMsg($wrap, 'Máximo disponível!');
        return; // <- importante: não dispara change/AJAX
      }
      next = Math.min(prev + step, max);
      if (next >= max) showQtyMsg($wrap, 'Máximo disponível!');
    } else {
      next = prev + step;
    }
  }

  // Só dispara change se realmente mudou
  if (next !== prev) {
    $input.val(next).trigger('change');
  }
});

// Mudança manual (digitada)
$(document).on('change', '.woocommerce-cart-form input.qty', function(){
  const $input = $(this);
  const $wrap  = $input.closest('[data-pcg-qty]');
  const min  = parseFloat($input.attr('min')) || 1;
  const max  = parseFloat($input.attr('max'));
  const step = parseFloat($input.attr('step')) || 1;

  const before = parseFloat($input.val());
  const after  = clamp(before, min, max, step);

  // Ajusta visualmente se precisou clampar e mostra aviso
  if (after !== before) {
    $input.val(after);
    if (after <= min) showQtyMsg($wrap, 'Mínimo aceito!');
    else if (!isNaN(max) && after >= max) showQtyMsg($wrap, 'Máximo disponível!');
  } else {
    // Se não clampou mas está nos limites, ainda assim avisa (opcional)
    if (!isNaN(before)) {
      if (before <= min) showQtyMsg($wrap, 'Mínimo aceito!');
      if (!isNaN(max) && before >= max) showQtyMsg($wrap, 'Máximo disponível!');
    }
  }

  // **Sempre** recalcula o carrinho quando o input dispara change
  updateCartAjax();
});




// Botão que originou o submit (cupom etc.)
let lastBtn=null; $(document).on('click', '.woocommerce-cart-form button, .woocommerce-cart-form input[type=submit]', function(){ lastBtn={ name:this.name, value:$(this).val() }; });
$(document).on('submit', '.woocommerce-cart-form', function(e){ e.preventDefault(); updateCartAjax(lastBtn); lastBtn=null; });

	// ==== Carrinho: frete ====
PCGFrete.bindPopupTriggers();
PCGFrete.maskCEP('#frete-cep');

// Abrir popup via [data-open-frete] já está no base

// Calcular opções para o carrinho (lista com rádios)
$(document).on('submit', '#pcgamer-frete-form', function(e){
  // só processa se for o popup
  if (!$(e.currentTarget).closest('#popup-frete').length) return;
  e.preventDefault();

  const raw = ($('#frete-cep').val()||'');
  const cep = raw.replace(/\D/g,'');
  if (cep.length!==8){ $('#pcg-frete-erro').text('Digite um CEP válido (8 números).').show(); return; }

  $('#pcg-frete-erro').hide();
  const $btn = $(this).find('button[type=submit]').prop('disabled', true).text('Calculando…');

  PCGFrete.resolveUF(cep).done(function(uf){
    $.post((window.pcgAdminAjax&&pcgAdminAjax())||'/wp-admin/admin-ajax.php',
      { action:'pcg_cart_rates', cep, uf }, function(resp){
        $btn.prop('disabled', false).text('OK');
        if (!resp || !resp.success || !resp.data || !resp.data.fretes){
          $('#pcg-frete-erro').text('Não foi possível calcular o frete.').show();
          return;
        }
        PCGFrete.renderTableCart({ fretes: resp.data.fretes, uf: resp.data.uf });
      }, 'json'
    ).fail(function(){
      $btn.prop('disabled', false).text('OK');
      $('#pcg-frete-erro').text('Erro ao comunicar com o servidor.').show();
    });
  });
});

// realce do radio selecionado
$(document).on('change', 'input[name="pcg_rate"]', function(){
  $('.pcg-frete-item').removeClass('is-checked');
  $(this).closest('.pcg-frete-item').addClass('is-checked');
});

// Aplicar no carrinho
$(document).on('click', '#pcg-aplicar-frete', function(){
  const $r = $('input[name="pcg_rate"]:checked');
  if (!$r.length) return;

  const rate_id = $r.val();
  const cep = ($('#frete-cep').val()||'').replace(/\D/g,'');
  const uf  = ($('#frete-uf').val()||'DF');
  const $btn = $(this).prop('disabled', true).text('Aplicando…');

  $.post((window.pcgAdminAjax&&pcgAdminAjax())||'/wp-admin/admin-ajax.php', {
    action:'pcg_cart_choose_rate',
    nonce:(window.pcgamerAjax && pcgamerAjax.nonce) || '',
    rate_id, cep, uf
  }, function(resp){
    $btn.prop('disabled', false).text('Aplicar frete selecionado');
    if (!resp || !resp.success){ alert('Não foi possível aplicar o frete.'); return; }
    PCGFrete.closePopup();
    // força recálculo/refresh da coluna de totais e tabela
    if (typeof window.pcgCartUpdate === 'function') window.pcgCartUpdate();
    else location.reload();
  }, 'json').fail(function(){
    $btn.prop('disabled', false).text('Aplicar frete selecionado');
    alert('Falha ao aplicar frete.');
  });
});

// Exponha para o módulo base chamar quando finalizar frete
window.pcgCartUpdate = function(){ updateCartAjax(); };

	
})(jQuery);
