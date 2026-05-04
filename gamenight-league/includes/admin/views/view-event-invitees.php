<?php
/**
 * Admin view: invitees for one event — set RSVPs, add/remove invitees.
 *
 * Available vars: $id (int), $event (array|WP_Error), $invitees (array|WP_Error), $members (array|WP_Error), $api
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-local scope.

$back_url = admin_url( 'admin.php?page=' . \GameNight\League\Admin\Admin_Menu::SLUG_EVENTS );

$event_title  = is_array( $event ) ? (string) ( $event['title'] ?? '' ) : '';
$event_when   = is_array( $event ) ? gnl_format_datetime( (string) ( $event['start_at'] ?? '' ) ) : '';
$invitee_rows = is_array( $invitees ) && isset( $invitees['invitees'] ) ? $invitees['invitees'] : array();

// Build list of members not already invited (for the picker).
// Pending members (user_id=null) are included but disabled — operator must use
// the "Add new person" form below to materialize them as real users first.
$invited_ids = array_map( 'intval', array_filter( wp_list_pluck( $invitee_rows, 'user_id' ) ) );
$picker_pool = array();
if ( is_array( $members ) ) {
	foreach ( $members as $m ) {
		if ( ! empty( $m['user_id'] ) && in_array( (int) $m['user_id'], $invited_ids, true ) ) {
			continue;
		}
		$picker_pool[] = $m;
	}
}
?>
<div class="wrap gnl-admin" data-gnl-admin-page="event-invitees" data-event-id="<?php echo esc_attr( (string) $id ); ?>">
	<h1>
		<a href="<?php echo esc_url( $back_url ); ?>" class="button button-secondary" style="vertical-align:middle">&larr; <?php esc_html_e( 'Events', 'gamenight-league' ); ?></a>
		<?php echo esc_html( $event_title ); ?>
		<?php if ( $event_when ) : ?>
			<span class="gnl-event-when"><?php echo esc_html( $event_when ); ?></span>
		<?php endif; ?>
	</h1>

	<?php if ( is_wp_error( $event ) ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $event->get_error_message() ); ?></p></div>
	<?php endif; ?>
	<?php if ( is_wp_error( $invitees ) ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $invitees->get_error_message() ); ?></p></div>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Invitees', 'gamenight-league' ); ?></h2>
	<?php if ( empty( $invitee_rows ) ) : ?>
		<p><?php esc_html_e( 'No invitees yet.', 'gamenight-league' ); ?></p>
	<?php else : ?>
		<table class="widefat striped gnl-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'gamenight-league' ); ?></th>
					<th><?php esc_html_e( 'Role', 'gamenight-league' ); ?></th>
					<th><?php esc_html_e( 'RSVP', 'gamenight-league' ); ?></th>
					<th><?php esc_html_e( 'Approval', 'gamenight-league' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'gamenight-league' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $invitee_rows as $inv ) :
					$uid  = (int) ( $inv['user_id'] ?? 0 );
					$rsvp = (string) ( $inv['rsvp'] ?? '' );
					?>
					<tr data-user-id="<?php echo esc_attr( (string) $uid ); ?>">
						<td><?php echo esc_html( $inv['display_name'] ?? '' ); ?></td>
						<td><?php echo esc_html( $inv['event_role'] ?? '' ); ?></td>
						<td>
							<select class="gnl-rsvp-select" data-current="<?php echo esc_attr( $rsvp ); ?>">
								<option value="" <?php selected( $rsvp, '' ); ?>><?php esc_html_e( '— no response —', 'gamenight-league' ); ?></option>
								<option value="yes" <?php selected( $rsvp, 'yes' ); ?>><?php esc_html_e( 'Yes', 'gamenight-league' ); ?></option>
								<option value="maybe" <?php selected( $rsvp, 'maybe' ); ?>><?php esc_html_e( 'Maybe', 'gamenight-league' ); ?></option>
								<option value="no" <?php selected( $rsvp, 'no' ); ?>><?php esc_html_e( 'No', 'gamenight-league' ); ?></option>
							</select>
						</td>
						<td><?php echo esc_html( $inv['approval_status'] ?? '' ); ?></td>
						<td>
							<button type="button" class="button button-small button-link-delete gnl-invitee-remove"><?php esc_html_e( 'Remove', 'gamenight-league' ); ?></button>
							<span class="gnl-row-status" aria-live="polite"></span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Add existing member', 'gamenight-league' ); ?></h2>
	<?php if ( empty( $picker_pool ) ) : ?>
		<p><?php esc_html_e( 'All league members are already invited to this event.', 'gamenight-league' ); ?></p>
	<?php else : ?>
		<form id="gnl-add-invitee-form" class="gnl-form">
			<select id="gnl-add-invitee-select">
				<option value=""><?php esc_html_e( '— Choose a member —', 'gamenight-league' ); ?></option>
				<?php foreach ( $picker_pool as $m ) :
					$is_pending = empty( $m['user_id'] );
					$name       = (string) ( $m['display_name'] ?? '' );
					if ( '' === $name ) {
						$name = $is_pending ? __( '(unnamed pending)', 'gamenight-league' ) : ( '#' . (int) $m['user_id'] );
					}
					?>
					<option
						value="<?php echo esc_attr( $is_pending ? '' : (string) (int) $m['user_id'] ); ?>"
						<?php disabled( $is_pending ); ?>
					>
						<?php
						if ( $is_pending ) {
							/* translators: %s: pending member display name */
							echo esc_html( sprintf( __( '%s (pending — use "Add new person" below)', 'gamenight-league' ), $name ) );
						} else {
							echo esc_html( $name );
						}
						?>
					</option>
				<?php endforeach; ?>
			</select>
			<label class="gnl-inline-check">
				<input type="checkbox" name="manager" value="1" />
				<?php esc_html_e( 'as event manager', 'gamenight-league' ); ?>
			</label>
			<button type="submit" class="button"><?php esc_html_e( 'Add', 'gamenight-league' ); ?></button>
			<span class="gnl-form-status" aria-live="polite"></span>
		</form>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Add new person', 'gamenight-league' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Creates a new league member and invites them to this event. If the email or phone matches someone already in the league, that existing person is invited instead (no duplicate is created).', 'gamenight-league' ); ?>
	</p>
	<form id="gnl-add-new-person-form" class="gnl-form">
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="gnl-np-name"><?php esc_html_e( 'Name', 'gamenight-league' ); ?></label></th>
				<td><input id="gnl-np-name" name="display_name" type="text" class="regular-text" required /></td>
			</tr>
			<tr>
				<th><label for="gnl-np-email"><?php esc_html_e( 'Email', 'gamenight-league' ); ?></label></th>
				<td><input id="gnl-np-email" name="email" type="email" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="gnl-np-phone"><?php esc_html_e( 'Phone', 'gamenight-league' ); ?></label></th>
				<td>
					<input id="gnl-np-phone" name="phone" type="tel" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Email or phone is required (one is enough).', 'gamenight-league' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Role', 'gamenight-league' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="manager" value="1" />
						<?php esc_html_e( 'Add as event manager', 'gamenight-league' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Add and invite', 'gamenight-league' ); ?></button>
			<span class="gnl-form-status" aria-live="polite"></span>
		</p>
	</form>
</div>
