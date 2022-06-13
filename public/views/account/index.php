<div class="wrap svi-form-wrapper">
    <h2>
        <?php echo get_the_title() ?>
        <div style="font-size: 14px; font-weight: 300; letter-spacing: 0;"><a href="<?php echo wp_logout_url(); ?>">Logout</a></div>
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
    $account_message = get_transient('svi_account_message');
    if ($account_message) {
        echo '<div class="validation_message">' . $account_message . '</div>';
    }
    ?>
    <div>
        <div class="x-column x-sm x-1-3">
            <div><strong>Name:</strong> <?php echo $user->display_name ?></div>
            <div><strong>Email:</strong> <?php echo $sv_customer->email ?></div>
            <div><strong>Phone:</strong> <?php echo $sv_customer->telephone ?></div>
            <div><strong>Alternate Phone:</strong> <?php echo $sv_customer->telephone_alt ?></div>
            <div class="svi-small-link" style="margin-bottom: 1em;">
                <a href="<?php echo get_page_link($this->settings['account_page']) ?>?action=edit">Edit account</a> |
                <a href="<?php echo get_page_link($this->svi->login->settings['login_page']) ?>?action=change-password">Change password</a>
            </div>
            <?php if (is_array($sv_customer->other_emails) && sizeof($sv_customer->other_emails) > 0) { ?>
                <div><strong>Other Emails</strong>
                    <div class="other-emails-block">
                        <?php foreach ($sv_customer->other_emails as $email) { ?>
                            <div><?php echo $email->email; ?></div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
            <div class="svi-small-link">
                <a href="<?php echo get_page_link($this->settings['account_page']) ?>?action=emails">Manage other emails</a>
            </div>
        </div>
        <div class="x-column x-sm x-1-3">
            <div><strong>Billing Address</strong></div>
            <div><?php echo $this->outputAddress($sv_addresses->addresses->{$sv_addresses->default_billing_id}, true); ?></div>
            <div class="svi-small-link"><a href="<?php echo get_page_link($this->settings['account_page']) . '?action=edit_address&aID=' . (int) $sv_addresses->addresses->{$sv_addresses->default_billing_id}->address_id; ?>">Edit</a> | <a href="<?php echo get_page_link($this->settings['account_page']) ?>?action=addresses">View all addresses</a></div>
        </div>
        <div class="x-column x-sm x-1-3">
            <div><strong>Shipping Address</strong></div>
            <div><?php echo $this->outputAddress($sv_addresses->addresses->{$sv_addresses->default_shipping_id}, true); ?></div>
            <div class="svi-small-link"><a href="<?php echo get_page_link($this->settings['account_page']) . '?action=edit_address&aID=' . (int) $sv_addresses->addresses->{$sv_addresses->default_shipping_id}->address_id; ?>">Edit</a> | <a href="<?php echo get_page_link($this->settings['account_page']) ?>?action=addresses">View all addresses</a></div>
        </div>
    </div>
    <div style="padding-top: 1.5em; clear: both;">
        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="svi-table">
            <thead>
                <tr>
                    <td colspan="7" class="x-highlight">
                        <div class="mas header">Order History</div>
                    </td>
                </tr>
                <tr class="cell-header">
                    <td class="align-center">Order #</td>
                    <td>Date</td>
                    <td>Campaign</td>
                    <td>Status</td>
                    <td class="align-center">Products</td>
                    <td class="align-right">Total</td>
                    <td class="align-right">&nbsp;</td>
                </tr>
            </thead>
            <tbody>
                <?php if (is_object($sv_orders) && is_object($sv_orders->orders)) {
                    foreach ($sv_orders->orders as $order) {
                        echo '<tr>';
                        echo '<td class="align-center">' . '<a href="' . $this->page_url() . '?action=receipt&oID=' . $order->order_id . '">' . $order->order_id . '</a>' . '</td>';
                        echo '<td>' . date('n/j/Y', strtotime($order->info->date_purchased)) . '</td>';
                        echo '<td>' . ($order->event ? '<a href="' . $this->svi->dashboard->page_url() . '?action=event&eID=' . $order->event->eID . '">' . $order->event->event_description . '</a>' : '') . '</td>';
                        echo '<td>' . $order->info->orders_status . '</td>';
                        echo '<td class="align-center">' . count($order->products) . '</td>';
                        echo '<td class="align-right">' . $order->info->total . '</td>';
                        echo '<td>' . '<a href="' . $this->page_url() . '?action=receipt&oID=' . $order->order_id . '">' . '<i class="fa fa-file-text"></i>' . '</a>' . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5">You do not have any orders in your account.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>