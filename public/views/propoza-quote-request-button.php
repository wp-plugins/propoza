<?php if ( ! Propoza_Quote_Request::get_instance()->has_propoza_coupon() ): ?>
	<a class="button alt quote-button " name="request"
	   onclick="request_quote();"><?php echo __( 'Request quote', 'propoza' ); ?></a>
<?php endif; ?>