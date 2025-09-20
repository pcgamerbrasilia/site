// ==================================
// FILE: base-frete.js (Compartilhado PDP + Carrinho)
// Exponde window.PCGFrete com utilitários
// ==================================
(function($){
  'use strict';

  // --- utils privados ---
  function sortFretes(fretes){
    // retirada primeiro, depois menor custo, depois menor prazo
    const arr = (fretes || []).slice();
    arr.sort((a,b)=>{
      const ar = String(a.label||'').toLowerCase().includes('retirada') ? 0 : 1;
      const br = String(b.label||'').toLowerCase().includes('retirada') ? 0 : 1;
      if (ar !== br) return ar - br;
      const ac = parseFloat(a.cost_num || a.cost || 0);
      const bc = parseFloat(b.cost_num || b.cost || 0);
      if (ac !== bc) return ac - bc;
      const ad = parseInt(a.days, 10) || 999;
      const bd = parseInt(b.days, 10) || 999;
      return ad - bd;
    });
    return arr;
  }
  function prazoFmt(days){
    if (days === 'Imediato') return 'Imediato';
    const d = parseInt(days,10);
    if (!d || d === 999) return '—';
    return `em até ${d} dias úteis`;
  }

  // --- API pública ---
  const PCGFrete = {
    /** Aplica máscara no CEP (idempotente) */
    maskCEP(selector = '#frete-cep'){
      $(document)
        .off('input.pcgMaskCep', selector)
        .on('input.pcgMaskCep', selector, function(){
          let v = this.value.replace(/\D/g,'').slice(0,8);
          this.value = (v.length>=6) ? v.replace(/(\d{5})(\d{3})/, '$1-$2') : v;
        });
    },

    /** Resolve UF via ViaCEP. Retorna jQuery.Deferred (suporta .done/.then) */
    resolveUF(cepNum){
      const dfd = $.Deferred();
      $.getJSON(`https://viacep.com.br/ws/${cepNum}/json/`)
        .done(function(data){
          const uf = (data && !data.erro && data.uf) ? String(data.uf).toUpperCase() : 'DF';
          $('#frete-uf').val(uf);
          dfd.resolve(uf);
        })
        .fail(function(){ $('#frete-uf').val('DF'); dfd.resolve('DF'); });
      return dfd;
    },

    /** Abre/fecha popup e binds de abertura/fechamento */
    openPopup(){
      $('#pcg-frete-erro').hide().text('');
      $('#frete-tabela-body').empty();
      $('.frete-imposto').hide().empty();
      $('#pcg-aplicar-frete').prop('disabled', true);
      $('#popup-frete').fadeIn(150);
      $('#frete-cep').trigger('focus');
    },
    closePopup(){ $('#popup-frete').fadeOut(120); },
    bindPopupTriggers(){
      $(document)
        .off('click.pcgOpenFrete','[data-open-frete]')
        .on('click.pcgOpenFrete','[data-open-frete]', function(e){ e.preventDefault(); PCGFrete.openPopup(); });
      $(document)
        .off('click.pcgCloseFrete','.frete-popup-close, .frete-popup-overlay')
        .on('click.pcgCloseFrete','.frete-popup-close, .frete-popup-overlay', function(){ PCGFrete.closePopup(); });
    },

    /** Render PDP: apenas tabela + imposto + mostra popup */
    renderTablePDP({ fretes, imposto, uf }){
      const $tbody = $('#frete-tabela-body').empty();
      sortFretes(fretes).forEach(f=>{
        const isPick = String(f.label).toLowerCase().includes('retirada');
        const modalidade = isPick ? 'Retirada na loja física' : (f.label || '');
        const taxa = isPick ? 'Sem Taxa' : (f.cost_html || f.cost || '');
        $tbody.append(`<tr><td>${modalidade}</td><td>${taxa}</td><td>${prazoFmt(f.days)}</td></tr>`);
      });
      if (uf) $('#frete-uf').val(uf);

      let htmlImp = `<span><strong>O valor do produto já inclui impostos para o Distrito Federal (UF).
Para os demais estados será adicionado ao final da compra.</strong></span>`;
      if (imposto && Number(imposto.valor) > 0) {
        const html = (typeof imposto.html === 'string') ? imposto.html : `R$ ${Number(imposto.valor||0).toFixed(2).replace('.',',')}`;
        htmlImp += `<p><span>Imposto Adicional sobre o produto: <strong>${html}</strong></span>`;
        if (uf) htmlImp += `<br><span>Estado destino (UF): <strong>${uf}</strong></p>
<strong>IMPORTANTE!</strong> Essa taxa é aplicada diretamente no site; retirando na loja com seus próprios meios, ela não é cobrada!
<br><br><strong>NÃO VENDEMOS POR OUTRAS PLATAFORMAS, NÃO ENVIAMOS POR OUTROS MEIOS!</strong>
</span>`;
      }
      if (htmlImp) $('.frete-imposto').html(htmlImp).show(); else $('.frete-imposto').empty().hide();
      $('#pcg-aplicar-frete').prop('disabled', true); // PDP não “aplica”
      PCGFrete.openPopup();
    },

    /** Render Carrinho: tabela com rádios e habilita botão Aplicar */
    renderTableCart({ fretes, uf }){
      const $tb = $('#frete-tabela-body').empty();
      sortFretes(fretes).forEach(f=>{
        const linha = `
          <tr class="pcg-frete-item">
            <td>
              <label>
                <input type="radio" name="pcg_rate" value="${f.id}"
                  data-label="${$('<div>').text(f.label||'').html()}"
                  data-days="${f.days}">
                ${f.label || ''}
              </label>
            </td>
            <td>${f.cost_html || f.cost || ''}</td>
            <td>${prazoFmt(f.days)}</td>
          </tr>`;
        $tb.append(linha);
      });

      // Seleciona retirada, senão o primeiro
      const $pickup = $tb.find('input[name="pcg_rate"]').filter(function(){
        return $(this).closest('tr').text().toLowerCase().includes('retirada');
      });
      if ($pickup.length) $pickup.first().prop('checked', true).trigger('change');
      else $tb.find('input[name="pcg_rate"]').first().prop('checked', true).trigger('change');

      if (uf) $('#frete-uf').val(uf);
      $('.frete-imposto').hide().empty(); // imposto no carrinho entra pelos totais
      $('#pcg-aplicar-frete').prop('disabled', false);
      PCGFrete.openPopup();
    }
  };

  // expõe global
  window.PCGFrete = PCGFrete;

})(jQuery);
