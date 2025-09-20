<?php
/**
 * WooCommerce Breadcrumb customizado para tema pcgamer (robusto)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// $breadcrumb/$delimiter podem vir do WooCommerce; garanta defaults
$delimiter = isset($delimiter) && $delimiter !== '' ? $delimiter : '›';

if ( ! empty($breadcrumb) && is_array($breadcrumb) ) {

    // Em página de produto, remove o último item (o próprio produto)
    if ( function_exists('is_product') && is_product() ) {
        array_pop($breadcrumb);
    }

    echo '<nav class="woocommerce-breadcrumb" aria-label="breadcrumb">';

    $lastIndex = count($breadcrumb) ? (count($breadcrumb) - 1) : null;

    foreach ( $breadcrumb as $key => $crumb ) {
        $is_last = ($lastIndex !== null && $key === $lastIndex);

        $label = isset($crumb[0]) ? $crumb[0] : '';
        $url   = isset($crumb[1]) ? $crumb[1] : '';

        if ( $url && ! $is_last ) {
            echo '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        } else {
            // Em produto, manter link na categoria final se houver URL
            if ( function_exists('is_product') && is_product() && $url ) {
                echo '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
            } else {
                echo '<span>' . esc_html($label) . '</span>';
            }
        }

        if ( ! $is_last ) {
            echo ' <span class="delimiter">' . esc_html($delimiter) . '</span> ';
        }
    }

    // === Código (SKU) no final — SEM usar $product global ===
    if ( function_exists('is_product') && is_product() ) {
        $product_obj = wc_get_product( get_the_ID() );
        if ( $product_obj && is_a($product_obj, 'WC_Product') ) {
            $sku = $product_obj->get_sku();
            if ( $sku ) {
                echo ' <span class="delimiter">' . esc_html($delimiter) . '</span> ';
                echo '<span class="delimiter"><strong>Código: ' . esc_html($sku) . '</strong></span>';
            }
        }
    }

    echo '</nav>';
}
