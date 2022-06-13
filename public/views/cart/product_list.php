<div class="wrap svi-form-wrapper svi-page">
    <?php
    $col_no = 0;
    if (count(get_object_vars($products->products)) > 0) {
        ?>
        <div class="product-count">
            Displaying <?php echo count(get_object_vars($products->products)); ?> products
        </div>
        <?php
            foreach ($products->products as $product) {
                if (!key_exists($product->id, $hide_products_arr)) {
                    $col_no++;
                    if ($columns == 1) {
                        echo '<div class="sv-product-list-block">';
                    } else {
                        echo '<div class="sv-product-list-block x-column x-1-' . $columns . ($col_no % $columns == 0 ? ' last' : '') . '">';
                    }
                    $link = $this->page_url('product') .
                        '?pID=' . $product->pID . '&return_id=' . $GLOBALS['post']->ID . ($products->category->categories_id ? '&catID=' . $products->category->categories_id : '') . ($eID ? '&eID=' . $eID : '');
                    ?>
                <div class="product-image">
                    <a href="<?php echo $link; ?>"><img src="<?php echo $products->img_src . $product->image_lrg; ?>" /></a>
                </div>
                <div class="product-name">
                    <a href="<?php echo $link; ?>"><?php echo $product->name; ?></a>
                </div>
                <div class="product-price">
                    <?php echo '$' . number_format($product->price, 2); ?>
                </div>
                <?php echo (!empty($product->free_shipping_notice) ? '<div class="special-notice"><strong class="highlight-color">FREE Shipping!</strong></div>' : ''); ?>
                
                <div class="product-button">
                    <a href="<?php echo $link; ?>" class="x-btn white x-btn-mini button">View Details</a>
                </div>
    <?php
                echo '</div>';
                echo ($columns > 1 && $col_no % $columns == 0 ? '<div class="clear"></div>' : '');
            }
        }
    } else {
        echo 'There are no products to display.';
    }
    ?>
</div>