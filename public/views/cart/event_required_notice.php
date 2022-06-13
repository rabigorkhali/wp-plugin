<?php
if ($notice) {
    echo '<div class="event-info-bar ' . $notice['type'] . '">' . $notice['message'] . '</div>';
    switch ($notice['popup']) {
        case 'event-required':
            if(!$_COOKIE['sermonview_eventinfo_notice']) {
                // set a cookie so the dialog doesn't come back for a while
                setcookie('sermonview_eventinfo_notice', 'true', strtotime('+4 hours'));
            ?>
            <div id="event-info-popup" style="display: none;"><?= $notice['message'] ?>
                <hr />
                <div style="float: right;" class="buttons"><a href="<?php echo $category->event_info_link . $get_vars; ?>" target="_blank" class="x-btn white">Provide Event Info</a><?php echo (!is_user_logged_in() ? ' <a href="' . $this->svi->login->login_url() . '" class="x-btn">Log In</a>' : ''); ?></div>
                <div style="margin-top: 30px;"><a href="#" id="close-event-info-popup" class="smallText blue">Just let me browse...</a></div>
            </div>
            <script>
                jQuery(document).ready(function($) {
                    $('#event-info-popup').dialog({
                        title: 'Event Information Required',
                        resizable: false,
                        height: 'auto',
                        width: 600,
                        modal: true
                    });
                    $('#close-event-info-popup').click(function(e) {
                        e.preventDefault();
                        $('#event-info-popup').dialog("close");
                    });
                });
            </script>
        <?php
            }
            break;
        case 'choose-event':
            ?>
        <div id="select-event-popup" style="display: none;"><?= ($notice['popup_msg'] ? $notice['popup_msg'] : $notice['message']) ?><br /><br />
            <?php
                    echo '<form name="select_event_form" action="' . get_permalink() . '" method="GET">';
                    echo (!empty($_GET['pID']) ? '<input type="hidden" name="pID" value="' . $_GET['pID'] . '" />' : '');
                    echo (!empty($_GET['return_id']) ? '<input type="hidden" name="return_id" value="' . $_GET['return_id'] . '" />' : '');
                    echo (!empty($_GET['catID']) ? '<input type="hidden" name="catID" value="' . $_GET['catID'] . '" />' : '');
                    echo '<table width="100%" border="0" cellpadding="0" cellspacing="0">';
                    foreach ($this->events as $single_event) {
                        echo '<tr>';
                        echo '<td style="border-top: 1px #cccccc solid; padding: 8px; height: 100%;">' . '<label style="height: 100%; width: 100%; display: block;"><input type="radio" name="eID" value="' . $single_event->event_id . '" id="eID_' . $single_event->event_id . '"' . ($_GET['eID'] == $single_event->event_id ? ' checked="checked"' : '') . ' /></label>' . '</td>';
                        echo '<td style="border-top: 1px #cccccc solid; padding: 8px"><label for="eID_' . $single_event->event_id . '">' . $single_event->event_description . '</label></td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                    ?>
            <hr style="width: 100%; margin-top: 0;" />
            <div style="float: right;" class="buttons"><button class="x-btn">Select Event</button></div>
            <div style="margin-top: 30px;"><a href="#" id="close-select-event-popup" class="smallText blue">Just let me browse...</a></div>
            </form>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#select-event-popup').dialog({
                    title: 'Select Event for Order',
                    resizable: false,
                    height: 'auto',
                    width: 600,
                    modal: true,
                    autoOpen: false
                });
                $('.select-event-btn').click(function(e) {
                    e.preventDefault();
                    $('#select-event-popup').dialog('open');
                });
                $('#close-select-event-popup').click(function(e) {
                    e.preventDefault();
                    $('#select-event-popup').dialog("close");
                });
                <?php
                        if (!$_COOKIE['sermonview_selectevent_notice'] && $notice['auto_open']) {
                            // set a cookie so the dialog doesn't come back for a while
                            setcookie('sermonview_selectevent_notice', 'true', strtotime('+4 hours'));
                            ?>
                    $('#select-event-popup').dialog('open');
                <?php
                        }
                        ?>
            });
        </script>
<?php
        break;
    }
}
?>