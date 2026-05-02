<?php
/**
 * Template: RSVP form.
 *
 * Available vars: $event_id (int)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<form class="gnl gnl-rsvp" data-gnl-rsvp data-event-id="<?php echo esc_attr( (string) $event_id ); ?>">
	<?php wp_nonce_field( 'wp_rest', '_wpnonce', false ); ?>
	<input type="hidden" name="event_id" value="<?php echo esc_attr( (string) $event_id ); ?>" />

	<p class="gnl-rsvp__field">
		<label for="gnl-rsvp-name-<?php echo esc_attr( (string) $event_id ); ?>">
			<?php esc_html_e( 'Your name', 'gamenight-league' ); ?>
		</label>
		<input
			type="text"
			id="gnl-rsvp-name-<?php echo esc_attr( (string) $event_id ); ?>"
			name="display_name"
			required
			autocomplete="name"
		/>
	</p>

	<p class="gnl-rsvp__field">
		<label for="gnl-rsvp-email-<?php echo esc_attr( (string) $event_id ); ?>">
			<?php esc_html_e( 'Email', 'gamenight-league' ); ?>
		</label>
		<input
			type="email"
			id="gnl-rsvp-email-<?php echo esc_attr( (string) $event_id ); ?>"
			name="email"
			autocomplete="email"
		/>
	</p>

	<p class="gnl-rsvp__field">
		<label for="gnl-rsvp-phone-<?php echo esc_attr( (string) $event_id ); ?>">
			<?php esc_html_e( 'Phone (optional if email provided)', 'gamenight-league' ); ?>
		</label>
		<input
			type="tel"
			id="gnl-rsvp-phone-<?php echo esc_attr( (string) $event_id ); ?>"
			name="phone"
			autocomplete="tel"
		/>
	</p>

	<fieldset class="gnl-rsvp__choice">
		<legend><?php esc_html_e( 'Will you attend?', 'gamenight-league' ); ?></legend>
		<label><input type="radio" name="rsvp" value="yes" required /> <?php esc_html_e( 'Yes', 'gamenight-league' ); ?></label>
		<label><input type="radio" name="rsvp" value="maybe" /> <?php esc_html_e( 'Maybe', 'gamenight-league' ); ?></label>
		<label><input type="radio" name="rsvp" value="no" /> <?php esc_html_e( 'No', 'gamenight-league' ); ?></label>
	</fieldset>

	<p class="gnl-rsvp__submit">
		<button type="submit" class="gnl-rsvp__button">
			<?php esc_html_e( 'Send RSVP', 'gamenight-league' ); ?>
		</button>
	</p>

	<div class="gnl-rsvp__status" role="status" aria-live="polite"></div>
</form>
