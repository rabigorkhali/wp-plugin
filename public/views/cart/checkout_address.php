<div class="wrap svi-wrapper svi-checkout">
    <h2>
        <?php echo get_the_title() ?>
    </h2>
    <h4>Select Shipping Address:</h4>
    <a href="<?php echo get_page_link($this->svi->account->settings['account_page']); ?>?action=addresses" class="subheader-link">Manage addresses</a>
    <?php if (is_object($addresses->addresses)) { ?>
        <div class="x-container flexmethod max width svi-address-selector">
            <div class="x-column x-sm container x-1-3 svi-address-box svi-add-address-wrapper">
                <a href="<?php echo get_page_link($this->svi->account->settings['account_page']) . '?action=add_address&return=checkout&type=' . $type; ?>">
                    <div class="svi-add-address"><span>+</span>Add Address</div>
                </a>
            </div>
        <?php
            $col = 1;
            foreach ($addresses->addresses as $address) {
                echo '<div class="x-column x-sm container x-1-3 svi-address-box">';
                echo '<a href="' . $this->page_url('checkout', 'action=process_address&aID=' . $address->address_id) . ($type ? '&type=' . $type : '') . '">';
                echo '<div class="svi-address">';
                echo $this->svi->account->outputAddress($address, false, true);
                echo '</div>';
                echo '</a>';
                echo '</div>';
                $col++;
                if ($col >= 3) {
                    $col = 0;
                    echo '</div><div class="x-container flexmethod max width svi-address-selector">';
                }
            }
            echo '</div>';
        }
        ?>
        </div>