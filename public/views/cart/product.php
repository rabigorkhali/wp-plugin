<div class="wrap svi-form-wrapper product-details product-id-<?php echo $product->id; ?> product-category-id-<?php echo $category->id; ?>">
    <h1><?php echo $product->name ?></h1>
    <div class="x-column x-1-3">
        <?php if ($return_id && $category_id) { ?>
            <a href="<?php echo get_page_link($return_id) . ($eID ? '?eID=' . $eID : ''); ?>">&laquo; Return to <strong><?php echo $category->categories_name; ?></strong> Products</a>
        <?php } ?>
        <div class="product-image">
            <img src="<?php echo $img_src . $product->image_lrg; ?>" />
        </div>
        <div class="product-add">
            <div class="product-price"><?php echo '$' . number_format($product->price, 2); ?></div>
            <div class="product-add-box">
                <form name="add_to_cart" action="<?php echo $this->page_url('cart'); ?>?action=add" method="POST">
                    <?php if ($event) { ?>
                        <input type="hidden" name="event_id" value="<?php echo $event->event_id; ?>" />
                    <?php } ?>
                    <input type="hidden" name="product_id" value="<?php echo $product->id; ?>" />
                    <input type="hidden" name="category_id" value="<?php echo $category->id; ?>" />
                    <input type="hidden" name="return_id" value="<?php echo $return_id; ?>" />
                    Qty: <input name="quantity" size="3" value="1" /><br />
                    <?php echo (!empty($product->free_shipping_notice) ? '<div class="special-notice highlight-color">' . $product->free_shipping_notice . '</div>' : ''); ?>

                    <?php if ($notice && $notice['type'] != 'success') {
                        echo '<div class="event-info-notice-add-product">' . $notice['message'] . '</div>';
                        echo '<button class="x-btn x-btn-sm white" disabled="disabled">Add to Cart</button>';
                    } else {
                        echo '<button class="x-btn x-btn-sm">Add to Cart</button>';
                    } ?>
                </form>
            </div>
        </div>
    </div>
    <div class="x-column x-2-3 last">
        <div class="product-description">
            <?php echo $product->description; ?>
        </div>
    </div>
    <div class="x-clear align-left">
        <?php if ($return_id && $category_id) { ?>
            <a href="<?php echo get_page_link($return_id) . ($eID ? '?eID=' . $eID : ''); ?>">&laquo; Return to <strong><?php echo $category->categories_name; ?></strong> Products</a>
        <?php } ?>
    </div>
</div>