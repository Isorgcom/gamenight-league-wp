<?php
/**
 * Template: standalone "Join the league" form.
 *
 * Available vars: (none)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<form class="gnl gnl-join" data-gnl-join>
	<?php wp_nonce_field( 'wp_rest', '_wpnonce', false ); ?>

	<p class="gnl-join__field">
		<label for="gnl-join-name"><?php esc_html_e( 'Your name', 'gamenight-league' ); ?></label>
		<input type="text" id="gnl-join-name" name="display_name" required autocomplete="name" />
	</p>

	<p class="gnl-join__field">
		<label for="gnl-join-email"><?php esc_html_e( 'Email', 'gamenight-league' ); ?></label>
		<input type="email" id="gnl-join-email" name="email" autocomplete="email" />
	</p>

	<p class="gnl-join__field">
		<label for="gnl-join-phone"><?php esc_html_e( 'Phone (optional if email provided)', 'gamenight-league' ); ?></label>
		<input type="tel" id="gnl-join-phone" name="phone" autocomplete="tel" />
	</p>

	<p class="gnl-join__submit">
		<button type="submit" class="gnl-join__button"><?php esc_html_e( 'Join the league', 'gamenight-league' ); ?></button>
	</p>

	<div class="gnl-join__status" role="status" aria-live="polite"></div>
</form>
