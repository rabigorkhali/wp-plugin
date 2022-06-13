<?php
if ($action == 'billing' && $response->shipping->total_weight > 0) {
    ?>
    <div class="svi-panel">
        <h4 class="header">
            Shipping address
            <div class="control-links checkout">(<a href="<?php echo $this->page_url('checkout', 'action=change_address&type=shipping'); ?>">change</a>)</div>
        </h4>
        <div class="body">
            <?php echo $cart->delivery->formatted; ?>
        </div>
    </div>
    <div class="svi-panel">
        <h4 class="header">
            Shipping method
            <div class="control-links checkout">(<a href="<?php echo $this->page_url('checkout', 'action=shipping'); ?>">change</a>)</div>
        </h4>
        <div class="body">
            <?php echo $cart->info->shipping_method; ?>
        </div>
    </div>
<?php
}
?>
<div class="svi-panel svi-side-cart">
    <h4 class="header">
        Order Details
        <div class="control-links checkout">(<a href="<?php echo $this->page_url('cart'); ?>">change</a>)</div>
    </h4>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="svi-table body">
        <!-- <thead>
            <tr class="">
                <td>Product</td>
                <td class="align-right">Total</td>
            </tr>
        </thead> -->
        <tbody>
            <?php
            foreach ($cart->products as $product) {
                if (!$product->automagic) {
                    ?>
                    <tr>
                        <td>
                            <?php echo $product->qty . ' x ' . $product->name; ?>
                            <?php echo ($mixed_shipping && !empty($product->free_shipping_notice && ($action=='shipping' || $cart->info->shipping_method_discounted == true)) ? '<sup class="highlight-color">*</sup>' : ''); ?>
                            <?php echo ($mixed_tax && $product->taxable && $product->tax > 0 ? '<sup class="highlight-color">&dagger;</sup>' : ''); ?>
                        </td>
                        <td class="align-right"><?php echo '$' . number_format($product->final_price * $product->qty, 2); ?></td>
                    </tr>
            <?php }
            }
            foreach ($cart->totals as $total) {
                if ($action == 'billing' && ($total->class == 'ot_shipping' || $total->value != 0) || $total->class == 'ot_subtotal') {
                    echo '<tr class="total-line ' . $total->class . '">';
                    echo '<td class="align-right total" colspan="1">' . $total->title . '</td>';
                    echo '<td class="align-right total">' . ($total->value == 0 ? '<strong class="highlight-color">FREE</strong>' : $total->text) . '</td>';
                    echo '</tr>';
                }
            }
            ?>
        </tbody>
    </table>
</div>
<div class="under-box">
    <div class="smallText light-gray float-right mls"><em>Cart ID: <?php echo $cart->orders_id; ?></em></div>
    <?php echo ($mixed_shipping && ($action == 'shipping' || $cart->info->shipping_method_discounted == true) ? '<div class="under-box-shipping-notice smallText"><sup class="highlight-color">*</sup>Shipping price reflects free shipping on eligible products</div>' : ''); ?>
    <?php echo ($mixed_tax ? '<div class="smallText"><sup class="highlight-color">&dagger;</sup>Taxable item</div>' : ''); ?>
</div>