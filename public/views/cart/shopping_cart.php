<div class="wrap svi-form-wrapper sermonview-cart">
    <form name="sermonview-cart" action="<?php echo $this->page_url('cart'); ?>?action=update" method="post" id="svi-shopping-cart">
        <div class="float-left x-column x-1-4"><a class="x-btn white" href="<?php echo ((int) $_GET['return_id'] > 0 ? get_permalink((int) $_GET['return_id']) . '?' . ((int) $_GET['eID'] ? '&eID=' . (int) $_GET['eID'] : '') : $this->page_url('catalog', ((int) $_GET['eID'] ? 'eID=' . (int) $_GET['eID'] : ''))); ?>"><i class="fa fa-chevron-left"></i> Keep Shopping</a></div>
        <div class="x-column x-1-2">
            <h2 class="align-center">
                <?php echo get_the_title() ?>
            </h2>
        </div>
        <?php
        if ($this->count_cart() > 0) {
            ?>
            <div class="x-column x-1-4 last align-right">
                <!-- <button class="x-btn white">Update</button> --><a class="x-btn" href="<?php echo $this->page_url('checkout'); ?>">Checkout <i class="fa fa-chevron-right icon-right"></i></a>
            </div>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="svi-table clear">
                <thead>
                    <tr class="">
                        <td class="align-center"></td>
                        <td></td>
                        <td>Product</td>
                        <td class="align-right">Price</td>
                        <td class="align-center">Quantity</td>
                        <td class="align-right">Total</td>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        foreach ($cart->products as $product) {
                            if (!$product->automagic) {
                                $product_link = $this->page_url('product') . '?pID=' . $product->id . ($product->event_id ? '&eID=' . $product->event_id : '') . ($product->category_id ? '&catID=' . $product->category_id : '') . ($product->return_id ? '&return_id=' . $product->return_id : '');
                                ?>
                            <tr>
                                <td class="align-center"><a href="<?php echo $this->page_url('cart'); ?>?action=remove&opID=<?php echo $product->orders_products_id; ?>" class="remove-from-cart-link"><i class="fa fa-times-circle"></i></a></td>
                                <td><?php echo (!empty($product->image) ? '<a href="' . $product_link . '"><img src="' . $response->img_src . $product->image . '" /></a>' : ''); ?></td>
                                <td>
                                    <?php echo '<a href="' . $product_link . '">' . $product->name . '</a>' ?>
                                    <div class="attributes">
                                        <?php if ($product->event_id) {
                                                        echo '- Event: ' . $product->event_description;
                                                    } ?>
                                    </div>
                                    <?php echo (!empty($product->free_shipping_notice) ? '<div class="special-notice highlight-color">' . $product->free_shipping_notice . '</div>' : ''); ?>
                                </td>
                                <td class="align-right"><?php echo '$' . number_format($product->final_price, 2); ?></td>
                                <td class="align-center"><input name="qty[<?php echo $product->orders_products_id; ?>]" value="<?php echo $product->qty; ?>" size="2" /></td>
                                <td class="align-right"><?php echo '$' . number_format($product->final_price * $product->qty, 2); ?></td>
                            </tr>
                    <?php }
                        }
                        foreach ($cart->totals as $total) {
                            if ($total->class == 'ot_subtotal') {
                                echo '<tr>';
                                echo '<td colspan="2" class="smallText light-gray"><em>Cart ID: ' . $cart->orders_id . '</em></td>';
                                echo '<td class="align-right" colspan="3"><strong>' . $total->title . '</strong></td>';
                                echo '<td class="align-right"><strong>' . $total->text . '</strong></td>';
                                echo '</tr>';
                            }
                        }
                        ?>
                </tbody>
            </table>
            <div class="align-right">
                <div class="float-left x-column x-1-4"><a class="x-btn white" href="<?php echo ((int) $_GET['return_id'] > 0 ? get_permalink((int) $_GET['return_id']) . '?' . ((int) $_GET['eID'] ? '&eID=' . (int) $_GET['eID'] : '') : $this->page_url('catalog', ((int) $_GET['eID'] ? 'eID=' . (int) $_GET['eID'] : ''))); ?>"><i class="fa fa-chevron-left"></i> Keep Shopping</a></div>
                <button class="x-btn white">Update</button> <a class="x-btn" href="<?php echo $this->page_url('checkout'); ?>">Checkout <i class="fa fa-chevron-right icon-right"></i></a>
            </div>
            <hr class="clear" />
        <?php
        } else {
            echo '<div class="clear"></div>';
            echo 'There are no products in your shopping cart.';
        }
        ?>
    </form>
</div>