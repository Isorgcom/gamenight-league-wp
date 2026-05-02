<?php
/**
 * Admin view: members list with inline role-change select.
 *
 * Available vars: $members (array|WP_Error), $api
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap gnl-admin" data-gnl-admin-page="members">
	<h1><?php esc_html_e( 'Members', 'gamenight-league' ); ?></h1>

	<?php if ( is_wp_error( $members ) ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $members->get_error_message() ); ?></p></div>
	<?php elseif ( empty( $members ) ) : ?>
		<p><?php esc_html_e( 'No members yet.', 'gamenight-league' ); ?></p>
	<?php else : ?>
		<table class="widefat striped gnl-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'gamenight-league' ); ?></th>
					<th><?php esc_html_e( 'Role', 'gamenight-league' ); ?></th>
					<th><?php esc_html_e( 'Joined', 'gamenight-league' ); ?></th>
					<th><?php esc_html_e( 'Status', 'gamenight-league' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $members as $m ) :
					$user_id   = isset( $m['user_id'] ) ? (int) $m['user_id'] : 0;
					$role      = (string) ( $m['role'] ?? '' );
					$pending   = ! empty( $m['pending'] );
					$is_owner  = ( 'owner' === $role );
					$can_edit  = $user_id > 0 && ! $pending && ! $is_owner;
					?>
					<tr data-user-id="<?php echo esc_attr( (string) $user_id ); ?>">
						<td><?php echo esc_html( $m['display_name'] ?? '' ); ?></td>
						<td>
							<?php if ( $can_edit ) : ?>
								<select class="gnl-role-select" data-current="<?php echo esc_attr( $role ); ?>">
									<option value="member" <?php selected( $role, 'member' ); ?>><?php esc_html_e( 'Member', 'gamenight-league' ); ?></option>
									<option value="manager" <?php selected( $role, 'manager' ); ?>><?php esc_html_e( 'Manager', 'gamenight-league' ); ?></option>
								</select>
							<?php else : ?>
								<?php echo esc_html( ucfirst( $role ) ); ?>
								<?php if ( $is_owner ) : ?>
									<span class="gnl-badge gnl-badge--owner"><?php esc_html_e( 'owner', 'gamenight-league' ); ?></span>
								<?php endif; ?>
							<?php endif; ?>
							<span class="gnl-row-status" aria-live="polite"></span>
						</td>
						<td><?php echo esc_html( gnl_format_datetime( $m['joined_at'] ?? '' ) ); ?></td>
						<td>
							<?php if ( $pending ) : ?>
								<span class="gnl-badge gnl-badge--pending"><?php esc_html_e( 'pending', 'gamenight-league' ); ?></span>
							<?php else : ?>
								<?php esc_html_e( 'active', 'gamenight-league' ); ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
