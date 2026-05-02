<?php
/**
 * Admin view: create or edit an event.
 *
 * Available vars: $event_id (int), $event (array|null|WP_Error), $api
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

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
$color       = (string) ( $e['color'] ?? '#2563eb' );

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
					<td><input id="gnl-game-type" name="poker_game_type" type="text" class="regular-text" value="<?php echo esc_attr( $game_type ); ?>" /></td>
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
