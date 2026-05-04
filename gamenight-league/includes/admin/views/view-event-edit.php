<?php
/**
 * Admin view: create or edit an event.
 *
 * Available vars: $event_id (int), $event (array|null|WP_Error), $api
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-local scope.

$is_new = ( 0 === $event_id || ! is_array( $event ) );
$err    = is_wp_error( $event ) ? $event->get_error_message() : '';
$e      = is_array( $event ) ? $event : array();

$title       = (string) ( $e['title'] ?? '' );
$description = (string) ( $e['description'] ?? '' );
$start_at    = (string) ( $e['start_at'] ?? '' );
$end_at      = (string) ( $e['end_at'] ?? '' );
$is_poker    = ! empty( $e['is_poker'] );
$buyin       = (string) ( $e['poker_buyin'] ?? '' );
$tables      = (string) ( $e['poker_tables'] ?? '' );
$seats       = (string) ( $e['poker_seats'] ?? '' );
$game_type   = (string) ( $e['poker_game_type'] ?? '' );

$allowed_game_types = array(
	'tournament' => __( 'Tournament', 'gamenight-league' ),
	'cash'       => __( 'Cash', 'gamenight-league' ),
);
if ( '' !== $game_type && ! isset( $allowed_game_types[ $game_type ] ) ) {
	$game_type = '';
}
$color       = (string) ( $e['color'] ?? '#2563eb' );

$rsvp_deadline_hours = isset( $e['rsvp_deadline_hours'] ) ? (string) (int) $e['rsvp_deadline_hours'] : '';
$waitlist_enabled    = ! array_key_exists( 'waitlist_enabled', $e ) || ! empty( $e['waitlist_enabled'] );
$reminders_enabled   = ! array_key_exists( 'reminders_enabled', $e ) || ! empty( $e['reminders_enabled'] );
$reminder_offsets    = '';
if ( ! empty( $e['reminder_offsets'] ) && is_array( $e['reminder_offsets'] ) ) {
	$reminder_offsets = implode( ', ', array_map( 'intval', $e['reminder_offsets'] ) );
}

$allowed_colors = array(
	'#2563eb' => __( 'Blue', 'gamenight-league' ),
	'#16a34a' => __( 'Green', 'gamenight-league' ),
	'#dc2626' => __( 'Red', 'gamenight-league' ),
	'#d97706' => __( 'Orange', 'gamenight-league' ),
	'#7c3aed' => __( 'Purple', 'gamenight-league' ),
	'#0891b2' => __( 'Teal', 'gamenight-league' ),
	'#db2777' => __( 'Pink', 'gamenight-league' ),
);
if ( ! isset( $allowed_colors[ $color ] ) ) {
	$color = '#2563eb';
}

// Convert ISO-8601 UTC strings into the local form-friendly "YYYY-MM-DDTHH:MM" if present.
$to_local_input = static function ( $iso ) {
	if ( empty( $iso ) ) { return ''; }
	try {
		$tz  = wp_timezone();
		$dt  = ( new \DateTimeImmutable( $iso ) )->setTimezone( $tz );
		return $dt->format( 'Y-m-d\TH:i' );
	} catch ( \Exception $ex ) { return ''; }
};
?>
<div class="wrap gnl-admin" data-gnl-admin-page="event-edit" data-event-id="<?php echo esc_attr( (string) $event_id ); ?>">
	<h1>
		<?php
		echo $is_new
			? esc_html__( 'New event', 'gamenight-league' )
			: esc_html__( 'Edit event', 'gamenight-league' );
		?>
	</h1>

	<?php if ( $err ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $err ); ?></p></div>
	<?php endif; ?>

	<form id="gnl-event-form" class="gnl-form">
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="gnl-title"><?php esc_html_e( 'Title', 'gamenight-league' ); ?></label></th>
				<td><input id="gnl-title" name="title" type="text" class="regular-text" required value="<?php echo esc_attr( $title ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="gnl-start"><?php esc_html_e( 'Start', 'gamenight-league' ); ?></label></th>
				<td><input id="gnl-start" name="start_at" type="datetime-local" required value="<?php echo esc_attr( $to_local_input( $start_at ) ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="gnl-end"><?php esc_html_e( 'End (optional)', 'gamenight-league' ); ?></label></th>
				<td><input id="gnl-end" name="end_at" type="datetime-local" value="<?php echo esc_attr( $to_local_input( $end_at ) ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="gnl-description"><?php esc_html_e( 'Description', 'gamenight-league' ); ?></label></th>
				<td><textarea id="gnl-description" name="description" rows="4" class="large-text"><?php echo esc_textarea( $description ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="gnl-color"><?php esc_html_e( 'Color', 'gamenight-league' ); ?></label></th>
				<td>
					<select id="gnl-color" name="color">
						<?php foreach ( $allowed_colors as $hex => $label ) : ?>
							<option value="<?php echo esc_attr( $hex ); ?>" <?php selected( $color, $hex ); ?> style="color:<?php echo esc_attr( $hex ); ?>">
								<?php echo esc_html( $label ); ?> (<?php echo esc_html( $hex ); ?>)
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="gnl-is-poker"><?php esc_html_e( 'Poker event?', 'gamenight-league' ); ?></label></th>
				<td>
					<label><input id="gnl-is-poker" name="is_poker" type="checkbox" value="1" <?php checked( $is_poker ); ?> />
					<?php esc_html_e( 'This is a poker event', 'gamenight-league' ); ?></label>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'RSVPs &amp; reminders', 'gamenight-league' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="gnl-rsvp-deadline"><?php esc_html_e( 'RSVP deadline (hours before start)', 'gamenight-league' ); ?></label></th>
				<td>
					<input id="gnl-rsvp-deadline" name="rsvp_deadline_hours" type="number" min="0" step="1" class="small-text" value="<?php echo esc_attr( $rsvp_deadline_hours ); ?>" />
					<p class="description"><?php esc_html_e( 'Leave blank to allow RSVPs right up to start time.', 'gamenight-league' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Waitlist (poker only)', 'gamenight-league' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="waitlist_enabled" value="1" <?php checked( $waitlist_enabled ); ?> />
						<?php esc_html_e( 'Auto-waitlist invitees beyond seats × tables', 'gamenight-league' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Reminders', 'gamenight-league' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="reminders_enabled" value="1" <?php checked( $reminders_enabled ); ?> />
						<?php esc_html_e( 'Send reminder notifications to invitees', 'gamenight-league' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><label for="gnl-reminder-offsets"><?php esc_html_e( 'Reminder offsets (minutes)', 'gamenight-league' ); ?></label></th>
				<td>
					<input id="gnl-reminder-offsets" name="reminder_offsets" type="text" class="regular-text" placeholder="2880, 720" value="<?php echo esc_attr( $reminder_offsets ); ?>" />
					<p class="description"><?php esc_html_e( 'Comma-separated minutes before start_at (e.g. 2880, 720 = 48h and 12h). Leave blank to use site defaults.', 'gamenight-league' ); ?></p>
				</td>
			</tr>
		</table>

		<div class="gnl-poker-fields" <?php echo $is_poker ? '' : 'style="display:none"'; ?>>
			<h2><?php esc_html_e( 'Poker details', 'gamenight-league' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th><label for="gnl-buyin"><?php esc_html_e( 'Buy-in', 'gamenight-league' ); ?></label></th>
					<td><input id="gnl-buyin" name="poker_buyin" type="number" min="0" step="1" value="<?php echo esc_attr( $buyin ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="gnl-tables"><?php esc_html_e( 'Tables', 'gamenight-league' ); ?></label></th>
					<td><input id="gnl-tables" name="poker_tables" type="number" min="0" step="1" value="<?php echo esc_attr( $tables ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="gnl-seats"><?php esc_html_e( 'Seats per table', 'gamenight-league' ); ?></label></th>
					<td><input id="gnl-seats" name="poker_seats" type="number" min="0" step="1" value="<?php echo esc_attr( $seats ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="gnl-game-type"><?php esc_html_e( 'Game type', 'gamenight-league' ); ?></label></th>
					<td>
						<select id="gnl-game-type" name="poker_game_type">
							<option value=""><?php esc_html_e( '— not set —', 'gamenight-league' ); ?></option>
							<?php foreach ( $allowed_game_types as $val => $label ) : ?>
								<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $game_type, $val ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>
		</div>

		<p class="submit">
			<button type="submit" class="button button-primary">
				<?php echo $is_new ? esc_html__( 'Create event', 'gamenight-league' ) : esc_html__( 'Save changes', 'gamenight-league' ); ?>
			</button>
			<span class="gnl-form-status" aria-live="polite"></span>
		</p>
	</form>
</div>
