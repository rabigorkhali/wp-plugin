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
    <form name="sermonview-cart" action="<?php echo $this->page_url('checkout', 'action=process_order'); ?>" method="post">
        <div class="x-column x-sm x-1-2">
            <h4>
                Billing address
                <div class="control-links checkout">(<a href="<?php echo $this->page_url('checkout', 'action=change_address&type=billing'); ?>">change</a>)</div>
            </h4>
            <div class="checkout-address">
                <?php echo $cart->billing->formatted; ?>
            </div>
            <?php
            if (!empty($cart->billing->address_id) && ($cart->billing->address_id != $addresses->default_billing_id)) {
                ?>
                <div class="input-holder custom-checkbox raised">
                    <input type="checkbox" id="set_default" name="set_default" value="true" class="svi-input-field"><label for="set_default">Set as default billing address</label>
                </div>
            <?php } ?>
            <?php if ($cart->info->total_value > 0) { ?>
                <h4 class="second-header">Payment method</h4>
                <div id="payment-methods">
                <?php if (is_array($billing_methods)) {
                        if (count($billing_methods) > 0) {
                            // first determine if there are actually more than one available, checking for disabled
                            $count = 0;
                            foreach ($billing_methods as $method) {
                                if(!$method->disabled) {
                                    $count++;
                                }
                            }
                            // multiple payment options, loop through them
                            echo '<div class="svi-accordion" id="svi-payment-methods">';
                            foreach ($billing_methods as $method) { ?>
                            <div class="svi-panel">
                                <h4 class="header<?php echo ($method->disabled ? ' disabled' : ''); ?>">

                                    <div class="custom-checkbox"><label><input type="radio" name="payment_method" value="<?php echo $method->id; ?>" id="<?php echo $method->id; ?>" <?php echo ($method->disabled ? ' disabled="disabled" class="disabled"' : ($count == 1 || $post['payment_method'] == $method->id ? ' checked="checked"' : '')); ?>><span></span><?php echo strip_tags($method->module); ?></label></div>
                                </h4>
                                <div class="body <?php echo ($method->disabled ? 'keep-open' : 'hidden'); ?>">
                                    <div>
                                        <?php if($method->id == 'authorizenet' && key_exists('enable_authnet_seal', $this->settings) && $this->settings['enable_authnet_seal'] == 'true') { ?>
                                            <div class="security-seal">
                                                <div class="AuthorizeNetSeal">
                                                    <script type="text/javascript" language="javascript">var ANS_customer_id="3a93122c-99f9-456e-a7f4-bac5afbc1d31";</script>
                                                    <script type="text/javascript" language="javascript" src="//VERIFY.AUTHORIZE.NET/anetseal/seal.js" ></script>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <?php if (is_array($method->fields)) {
                                                            foreach ($method->fields as $field) {
                                                                // skip some fields
                                                                if ($field->field) {
                                                                    $output = $this->setFieldValue($field->field,$post,array('authorizenet_cc_owner'=>$cart->billing->name),array('credit_card_type', 'authorizenet_cc_owner'));
                                                                    if($output) {
                                                                        echo '	<div class="form-row"><label>' . $field->title . '</label><div class="input-holder">' . $output . '</div></div>';
                                                                    }
                                                                } else {
                                                                    echo $field->title;
                                                                }
                                                            }
                                                        } ?>
                                    </div>
                                </div>
                            </div>
                <?php }
                            echo '</div>';
                        } else {
                            // just one payment option
                        }
                    } else {
                        echo 'We apologize, online payment options are not currently available. Please contact customer service to complete your order.';
                    } ?>
            <?php } ?>
            </div>
            <div class="align-right">
                <button class="x-btn">Place Order <i class="fa fa-chevron-right icon-right"></i></button>
            </div>
        </div>
        <div class="x-column x-sm x-1-2 last">
            <?php include('checkout_side_cart.php'); ?>
        </div>
    </form>
    <?php //echo '<hr class="clear" /><pre>' . print_r($response->shipping, 1) . '</pre>'; 
    ?>
</div>