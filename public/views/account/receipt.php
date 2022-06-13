<?php
if ($_GET['from'] == 'checkout_success') {
    if ($checkout_success_message) {
?>
        <div class="checkout-success-message">
            <?php echo $checkout_success_message; ?>
        </div>
<?php
    }
}
?>
<div class="wrap svi-form-wrapper">
    <div class="receipt-top">
        <div class="x-column x-sm x-1-2">
            <?php echo (!empty($this->settings['receipt_img']) && $order->info->from_this_remote_site ? wp_get_attachment_image($this->settings['receipt_img'], 'post-thumbnail', false, array('id' => 'receipt_image')) : '<img src="' . $receipt->logo . '" />'); ?>
        </div>
        <div class="x-column x-sm x-1-2 last" style="text-align: right;">
            <?php echo nl2br(!empty($this->settings['receipt_address']) && $order->info->from_this_remote_site ? $this->settings['receipt_address'] : $receipt->address); ?>
        </div>
    </div>
    <div class="receipt-title-row">
        <div class="back-link">
            <a href="<?= $this->page_url() ?>">&lt; Back</a>
        </div>
        <h2>Order Receipt</h2>
        <?php
        if ($_GET['from'] == 'checkout_success') {
            $message = get_transient('svi_checkout_message_' . $order->oID);
            if ($message) {
                echo '<div class="validation_message success">' . $message . '</div>';
            }
        }
        ?>
    </div>
    <div class="x-column x-sm x-1-3">
        <strong>Order no.:</strong> <?php echo $order->oID; ?><br />
        <strong>Date:</strong> <?php echo date('n/j/Y', strtotime($order->info->date_purchased)); ?><br />
        <strong>Payment method:</strong> <?php echo $order->info->payment_method; ?><br />
        <br />
        <strong>Status:</strong> <?php echo $order->info->orders_status; ?>
    </div>
    <div class="x-column x-sm x-1-3">
        <strong>Bill To</strong><br />
        <?php echo $order->billing->formatted; ?>
    </div>
    <div class="x-column x-sm x-1-3 last">
        <strong>Ship To</strong><br />
        <?php echo $order->delivery->formatted; ?>
    </div>

    <?php if ($order->event) { ?>
        <hr class="x-clear" />
        <hr>
        <div class="align-left">
            <strong>Event:</strong> <?php echo '<a href="' . $this->svi->dashboard->page_url() . '?action=event&eID=' . $order->event->eID . '">' . $order->event->event_description . '</a>'; ?>
        </div>
    <?php } ?>
    <div style="padding-top: 1.5em; clear: both;">
        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="svi-table">
            <thead>
                <tr class="cell-header">
                    <td>Item</td>
                    <td class="align-center">Taxable</td>
                    <td class="align-right">Qty</td>
                    <td class="align-right">Price</td>
                    <td class="align-right">Total</td>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order->products as $product) {
                    echo '<tr>';
                    echo '<td>' . $product->name . '</td>';
                    echo '<td class="align-center">' . ($product->taxable && $product->tax > 0 ? 'x' : '') . '</td>';
                    echo '<td class="align-right">' . number_format($product->qty) . '</td>';
                    echo '<td class="align-right">' . '$' . (round($product->final_price, 2) == $product->final_price ? number_format($product->final_price, 2) : $product->final_price + 0) . '</td>';
                    echo '<td class="align-right">' . '$' . number_format($product->final_price * $product->qty, 2) . '</td>';
                    echo '</tr>';
                    if (is_array($product->attributes)) {
                        foreach ($product->attributes as $attribute) {
                            if (!empty($attribute->value)) {
                                echo '<tr class="attribute">';
                                echo '<td style="border: none; font-size: 0.8em; padding: 0 0 3px 3em;"><em>' . $attribute->option . (substr($attribute->option, -1) != ':' ? ':' : '') . ' ' . nl2br($attribute->value) . '</em></td>';
                                echo '</tr>';
                            }
                        }
                    }
                    if ($product->event_id) {
                        echo '<tr class="attribute">';
                        echo '<td style="border: none; font-size: 0.8em; padding: 0 0 3px 3em;"><em>' . 'Event: ' . $product->event_description . '</em></td>';
                        echo '</tr>';
                    }
                }
                $first_total = true;
                foreach ($order->totals as $total) {
                    if (($total->class != 'ot_customer_discount' && $total->class != 'ot_tax') || $total->value != 0) {
                        echo '<tr>';
                        echo '<td colspan="4" class="align-right"' . (!$first_total ? ' style="border-top: none;"' : '') . '>' . ($total->class == 'ot_total' ? '<strong>' : '') . $total->title . ($total->class == 'ot_total' ? '</strong>' : '') . '</td>';
                        echo '<td class="align-right"' . (!$first_total ? ' style="border-top: none;"' : '') . '>' . ($total->class == 'ot_total' ? '<strong>' : '') . $total->text . ($total->class == 'ot_total' ? '</strong>' : '') . '</td>';
                        echo '<tr>';
                        $first_total = false;
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

    <div style="padding-top: 1.5em; clear: both;">
        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="svi-table">
            <thead>
                <tr>
                    <td colspan="4" class="x-highlight">
                        <div class="mas header">Shipments</div>
                    </td>
                </tr>
                <tr class="cell-header">
                    <td>Date</td>
                    <td>Method</td>
                    <td>Tracking</td>
                    <td>Status</td>
                </tr>
            </thead>
            <tbody>
                <!-- <?php echo '<tr><td colspan="4"><pre>' . print_r($order->shipments, 1) . '</pre></td></tr>'; ?> -->
                <?php if (is_array($order->shipments) && sizeof($order->shipments) > 0) {
                    foreach ($order->shipments as $shipment) {
                        echo '<tr>';
                        echo '<td>' . date('n/j/Y', strtotime($shipment->ship_date)) . '</td>';
                        echo '<td>' . $shipment->method . '</td>';
                        echo '<td>' . (!empty($shipment->tracking_link) ? $shipment->tracking_link : $shipment->tracking_number) . '</td>';
                        echo '<td>' . $shipment->last_status . '</td>';
                        echo '</tr>';
                        // products in shipment
                        echo '<tr class="shipment-contents">';
                        echo '<td colspan="4">' . nl2br(trim($shipment->products_formatted)) . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">There are no shipments associated with this order.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <div style="padding-top: 1.5em; clear: both;">
        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="svi-table">
            <thead>
                <tr>
                    <td colspan="4" class="x-highlight">
                        <div class="mas header">Payment History</div>
                    </td>
                </tr>
                <tr class="cell-header">
                    <td>Date</td>
                    <td>Method</td>
                    <td>Amount</td>
                    <td>Transaction ID</td>
                </tr>
            </thead>
            <tbody>
                <!-- <?php echo '<tr><td colspan="4"><pre>' . print_r($order->shipments, 1) . '</pre></td></tr>'; ?> -->
                <?php if (is_array($order->payments->history) && sizeof($order->payments->history) > 0) {
                    foreach ($order->payments->history as $payment) {
                        echo '<tr>';
                        echo '<td>' . date('n/j/Y', strtotime($payment->date)) . '</td>';
                        echo '<td>' . $payment->payment_method . '</td>';
                        echo '<td>' . '$' . number_format($payment->amount, 2) . '</td>';
                        echo '<td>' . $payment->transaction_id . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">There are no payments associated with this order.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($this->settings['receipt_img'])) {
        echo '<em>Please note: any credit card charges will appear on your statement as "SermonView"</em>';
    } ?>

</div>

<style>
    .shipment-contents {
        background-color: #f3f3f3;
        font-size: 0.8em;
        font-style: italic;
    }

    .shipment-contents td {
        padding-left: 2em;
    }

    @media screen {
        .receipt-top {
            display: <?php echo ((!empty($this->settings['receipt_img']) || !empty($this->settings['receipt_address'])) && $order->info->from_this_remote_site ? 'block' : 'none'); ?>;
            height: auto;
        }

        .receipt-title-row {
            display: block;
            position: relative;
            clear: both;
        }
    }

    @media print {
        @page {
            margin: 0;
        }

        header,
        footer,
        .zsiq_floatmain,
        .x-scroll-top .back-link,
        .back-link,
        .validation_message,
        .checkout-success-message {
            display: none !important;
        }

        /* body {font-size: 0.7em !important; padding: 20px;} */
        .entry-content {
            font-size: 14px !important;
        }

        .entry-wrap {
            margin: 0;
            padding: 0;
        }

        a[href]:after {
            content: none !important;
        }

        a {
            text-decoration: none;
        }

        .receipt-top {
            display: block;
        }
        
        /* Tighten up leading on printed receipt */
        .svi-form-wrapper {
            line-height: 1.2em;
        }
        .svi-form-wrapper .receipt-title-row {
            clear: both;
        }

        /* force columns when printing from mobile device */
        .x-column.x-1-3 {
            float: left;
            width: 30.6667%;
        }

        .x-column.x-1-2 {
            float: left;
            width: 48%;
        }
    }
</style>