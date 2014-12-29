<div id="dialog" style="display: none">

</div>
<div style="display: none">
    <div id="error-message">
        <ul class="woocommerce-error">
            <li><?php echo _e('An error occurred, please try again. If this error persists please contact us.', 'propoza'); ?></li>
        </ul>
    </div>
    <div id="success-message">
        <ul class="woocommerce-message">
            <li><?php echo _e('Quote is requested', 'propoza');?></li>
        </ul>
        <div class="additional-message">
            <?php do_action('get_additional_message');?>
        </div>
    </div>
</div>
<div class="clear"></div>
<input type="button" class="button alt quote-button" name="request" value="<?php _e('Request quote', 'propoza'); ?>"
       onclick="request_quote();"/>