<?php
// /inc/frete-popup.php
defined('ABSPATH') || exit;

/**
 * Injeta o HTML do popup de frete (reuso entre PDP e Carrinho).
 * IDs e classes compatíveis com seu JS: #popup-frete, #pcgamer-frete-form,
 * #frete-cep, #frete-uf, #frete-tabela-body, .frete-imposto, #pcg-aplicar-frete, #pcg-frete-erro
 */
add_action('wp_footer', function () {
  if ( (function_exists('is_product') && is_product()) || (function_exists('is_cart') && is_cart()) ) : ?>
    <div id="popup-frete" class="frete-popup" style="display:none">
      <div class="frete-popup-overlay"></div>

      <div class="frete-popup-content" role="dialog" aria-modal="true" aria-labelledby="frete-popup-title">
        <button type="button" class="frete-popup-close" aria-label="Fechar">×</button>

        <h3 id="frete-popup-title">Calcular frete</h3>

        <form id="pcgamer-frete-form" class="frete-form" autocomplete="off">
          <div class="frete-form-row">
            <label for="frete-cep">CEP</label>
            <input type="text" id="frete-cep" inputmode="numeric" placeholder="00000-000" maxlength="9" />
            <input type="hidden" id="frete-uf" value="DF" />
            <button type="submit" class="button">OK</button>
          </div>

          <div id="pcg-frete-erro" class="frete-erro" style="display:none"></div>

          <div class="frete-table-wrap">
            <table class="frete-table">
              <thead>
                <tr>
                  <th>Modalidade</th>
                  <th>Custo</th>
                  <th>Prazo</th>
                </tr>
              </thead>
              <tbody id="frete-tabela-body"></tbody>
            </table>
          </div>

          <div class="frete-imposto" style="display:none"></div>

          <div class="frete-actions">
            <button type="button" id="pcg-aplicar-frete" class="button button-primary" disabled>Aplicar frete selecionado</button>
          </div>
        </form>
      </div>
    </div>
  <?php
  endif;
}, 99);
