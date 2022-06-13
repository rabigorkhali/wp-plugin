<div class="wrap svi-form-wrapper">
    <h2>
        <?php echo get_the_title() ?>
    </h2>

    <?php
    if (!empty($_GET['nonce'])) {
        $post = get_transient('svi_transient_post_' . $_GET['nonce']);
        if (is_array($post) && key_exists('additional_email_id', $post) && (int) $post['additional_email_id'] > 0) {
            $additional_email_id = (int) $post['additional_email_id'];
        }
        if (is_array($post) && key_exists('email', $post) && !empty($post['email'])) {
            $email_address = $post['email'];
        }
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
        <div>
            <h4><?php echo ($action == 'edit_email' ? 'Edit' : 'Add New'); ?> Email Address</h4>
            <form name="email-address" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                <input type="hidden" name="action" value="svi_process_email" />
                <?php if (!empty($additional_email_id) && (int) $additional_email_id > 0) { ?>
                    <input type="hidden" name="additional_email_id" value="<?php echo (int) $additional_email_id; ?>" />
                <?php } ?>
                <div class="form-row">
                    <label for="email" class="left">Email address: </label>
                    <div class="input-holder raise">
                        <input type="text" name="email" class="svi-input-field" value="<?php echo $email_address; ?>" required />
                    </div>
                </div>

                <div class="form-row">
                    <label class="left"></label>
                    <div class="input-holder">
                        <button class="button">Save</button>
                        <a href="<?php echo get_page_link($this->settings['account_page']) ?>?action=emails" class="button white right">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>