<?php
/**
 * Admin view: events list with edit/invitees/delete actions.
 *
 * Available vars: $result (array|WP_Error), $tab (string), $api
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-local scope.

$events_url   = admin_url( 'admin.php?page=' . \GameNight\League\Admin\Admin_Menu::SLUG_EVENTS );
$new_url      = admin_url( 'admin.php?page=' . \GameNight\League\Admin\Admin_Menu::SLUG_EVENT_NEW );
$upcoming_url = add_query_arg( 'tab', 'upcoming', $events_url );
$past_url     = add_query_arg( 'tab', 'past', $events_url );

$events = is_wp_error( $result ) ? array() : ( $result['events'] ?? array() );
?>
<div class="wrap gnl-admin" data-gnl-admin-page="events">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Events', 'gamenight-league' ); ?></h1>
	<a href="<?php echo esc_url( $new_url ); ?>" class="page-title-action"><?php esc_html_e( '+ New event', 'gamenight-league' ); ?></a>

	<h2 class="nav-tab-wrapper">
		<a href="<?php echo esc_url( $upcoming_url ); ?>" class="nav-tab <?php echo 'past' === $tab ? '' : 'nav-tab-active'; ?>"><?php esc_html_e( 'Upcoming', 'gamenight-league' ); ?></a>
		<a href="<?php echo esc_url( $past_url ); ?>" class="nav-tab <?php echo 'past' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Past', 'gamenight-league' ); ?></a>
	</h2>

	<?php if ( is_wp_error( $result ) ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $result->get_error_message() ); ?></p></div>
	<?php elseif ( empty( $events ) ) : ?>
		<p><?php esc_html_e( 'No events in this range.', 'gamenight-league' ); ?></p>
	<?php else : ?>
		<table class="widefat striped gnl-table">
			<thead>
				<tr>
					<th class="gnl-cell-id"><?php esc_html_e( 'ID', 'gamenight-league' ); ?></th>
					<th><?php esc_html_e( 'When', 'gamenight-league' ); ?></th>
					<th><?php esc_html_e( 'Title', 'gamenight-league' ); ?></th>
					<th><?php esc_html_e( 'RSVPs (Y / M / N)', 'gamenight-league' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'gamenight-league' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $events as $e ) :
					$id           = (int) ( $e['id'] ?? 0 );
					$edit_url     = add_query_arg( array( 'page' => \GameNight\League\Admin\Admin_Menu::SLUG_EVENT_NEW, 'id' => $id ), admin_url( 'admin.php' ) );
					$invitees_url = add_query_arg( array( 'page' => \GameNight\League\Admin\Admin_Menu::SLUG_EVENTS, 'view' => 'invitees', 'id' => $id ), admin_url( 'admin.php' ) );
					$title        = (string) ( $e['title'] ?? '' );
					?>
					<tr data-event-id="<?php echo esc_attr( (string) $id ); ?>" data-event-title="<?php echo esc_attr( $title ); ?>">
						<td class="gnl-cell-id"><code><?php echo (int) $id; ?></code></td>
						<td><?php echo esc_html( gnl_format_datetime( $e['start_at'] ?? '' ) ); ?></td>
						<td><strong><?php echo esc_html( $title ); ?></strong></td>
						<td>
							<?php echo (int) ( $e['rsvp_yes_count'] ?? 0 ); ?> /
							<?php echo (int) ( $e['rsvp_maybe_count'] ?? 0 ); ?> /
							<?php echo (int) ( $e['rsvp_no_count'] ?? 0 ); ?>
						</td>
						<td>
							<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'gamenight-league' ); ?></a>
							<a href="<?php echo esc_url( $invitees_url ); ?>" class="button button-small"><?php esc_html_e( 'Invitees', 'gamenight-league' ); ?></a>
							<button type="button" class="button button-small button-link-delete gnl-event-delete"><?php esc_html_e( 'Delete', 'gamenight-league' ); ?></button>
							<span class="gnl-row-status" aria-live="polite"></span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
