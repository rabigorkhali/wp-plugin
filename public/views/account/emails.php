<div class="wrap svi-form-wrapper">
    <?php echo $this->back_link('account'); ?>
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
    $account_message = get_transient('svi_account_message');
    if ($account_message) {
        echo '<div class="validation_message">' . $account_message . '</div>';
    }
    ?>
    <div>
        <div class="x-column x-sm x-1-2">
            <h4>Primary Email Address</h4>
            <div><?php echo $sv_customer->email; ?></div>
            <div class="svi-small-link">
                <a href="<?php echo get_page_link($this->settings['account_page']) ?>?action=edit">Edit</a>
            </div>
        </div>
        <div class="x-column x-sm x-1-2">
            <h4>Other Email Addresses</h4>
            <?php if (is_array($sv_customer->other_emails) && sizeof($sv_customer->other_emails) > 0) { ?>
                <?php foreach ($sv_customer->other_emails as $email) { ?>
                    <div>
                        <?php echo $email->email; ?>
                        <div class="svi-small-link" style="margin-bottom: 1em;">
                            <a href="<?php echo get_page_link($this->settings['account_page']) ?>?action=edit_email&eID=<?php echo $email->id; ?>">Edit</a> |
                            <a href="<?php echo get_page_link($this->settings['account_page']) ?>?action=make_primary_email&eID=<?php echo $email->id; ?>">Make primary</a> |
                            <a href="<?php echo get_page_link($this->settings['account_page']) ?>?action=delete_email&eID=<?php echo $email->id; ?>">Remove</a>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div>None</div>
            <?php } ?>
            <div class="svi-small-link">
                <a href="<?php echo get_page_link($this->settings['account_page']) ?>?action=add_email"><i class="fa fa-plus"></i> Add new</a>
            </div>
        </div>
    </div>
    <div style="clear: both;"></div>
</div>