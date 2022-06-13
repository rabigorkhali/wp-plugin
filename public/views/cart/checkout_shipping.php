<div class="wrap svi-form-wrapper svi-checkout">
    <h2>
        <?php echo get_the_title() ?>
    </h2>
    <?php
    if (!empty($_GET['nonce'])) {
        $message = get_transient('svi_transient_message_' . $_GET['nonce']);
        if (!is_array($message)) {
            echo '<div class="validation_message">' . $message . '</div>';
        } elseif (key_exists('top_message', $message)) {
            echo '<div class="validation_message">' . $message['top_message'] . '</div>';
        }
    }
    ?>
    <form name="sermonview-cart" action="<?php echo $this->page_url('checkout', 'action=process_shipping'); ?>" method="post">
        <div class="x-column x-sm x-1-2">
            <h4>
                Shipping address
                <div class="control-links checkout">(<a href="<?php echo $this->page_url('checkout', 'action=change_address&type=shipping'); ?>">change</a>)</div>
            </h4>
            <div class="checkout-address">
                <?php echo $cart->delivery->formatted; ?>
            </div>
            <?php
            if (!empty($cart->delivery->address_id) && ($cart->delivery->address_id != $addresses->default_shipping_id)) {
                ?>
                <div class="input-holder custom-checkbox raised">
                    <input type="checkbox" id="set_default" name="set_default" value="true" class="svi-input-field"><label for="set_default">Set as default shipping address</label>
                </div>
            <?php } ?>
            <?php if ($response->shipping->total_weight > 0) { ?>
                <h4 class="second-header">Shipping method</h4>
                <table class="checkout-shipping-table">
                    <?php if (is_array($response->shipping->quote)) {
                            foreach ($response->shipping->quote as $service) {
                                if (is_array($service->methods)) {
                                    foreach ($service->methods as $method) {
                                        $shipping_form_id = $service->id . '_' . $method->id;
                                        ?>
                                    <tr>
                                        <td>
                                            <div class="custom-checkbox"><label><input type="radio" name="shipping_method" value="<?php echo $shipping_form_id; ?>" id="<?php echo $shipping_form_id; ?>"<?php echo (!empty($cart->info->shipping_method_id) ? ($cart->info->shipping_method_id == $shipping_form_id ? ' checked="checked"' : '') : ($response->shipping->cheapest->service == $service->module && $response->shipping->cheapest->id == $method->id ? ' checked="checked"' : '')); ?>><span></span></label></div>
                                        </td>
                                        <td><label for="<?php echo $shipping_form_id; ?>"><?php echo $method->title . (!empty($method->transit_time) ? ' (' . $method->transit_time . ')' : ''); ?></label></td>
                                        <td class="align-right">
                                            <label for="<?php echo $shipping_form_id; ?>">
                                                <?php echo ($method->cost == 0 ? '<strong class="highlight-color">FREE!</strong>' : '$' . number_format($method->cost, 2)); ?>
                                                <?php echo ($mixed_shipping && $method->discounted == 'true' ? '<sup class="highlight-color" style="margin: 0 -4px;">*</sup>' : ''); ?>
                                            </label>
                                        </td>
                                    </tr>
                                    <?php }
                                    }
                                }
                                // echo '<tr><td colspan="3"></td></tr>';
                            } else { ?>
                        <tr>
                            <td colspan="3">No shipping method is available. Please contact customer service to complete your order.</td>
                        </tr>
                    <?php } ?>
                </table>
            <?php
                if(is_array($response->shipping->quote)) {
                    echo '<input type="hidden" name="shipping_quote_json" value="' . rawurlencode(json_encode($response->shipping->quote)) . '" />';
                }
            }
            ?>
            <div class="align-right">
                <button class="x-btn">Continue <i class="fa fa-chevron-right icon-right"></i></button>
            </div>
        </div>
        <div class="x-column x-sm x-1-2 last">
            <?php include('checkout_side_cart.php'); ?>
        </div>
    </form>
    <?php //echo '<hr class="clear" /><pre>' . print_r($response->shipping, 1) . '</pre>'; ?>
</div>