<?php
/**
 * Admin view: members list with role change, delete, pending-contact edit/delete,
 * and an add-member form.
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
		<p><?php esc_html_e( 'No members yet. Add one with the form below.', 'gamenight-league' ); ?></p>
	<?php else : ?>
		<table class="widefat striped gnl-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'gamenight-league' ); ?></th>
					<th><?php esc_html_e( 'Role', 'gamenight-league' ); ?></th>
					<th><?php esc_html_e( 'Joined', 'gamenight-league' ); ?></th>
					<th><?php esc_html_e( 'Status', 'gamenight-league' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'gamenight-league' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $members as $m ) :
					$user_id   = isset( $m['user_id'] ) ? (int) $m['user_id'] : 0;
					$member_id = isset( $m['member_id'] ) ? (int) $m['member_id'] : 0;
					$role      = (string) ( $m['role'] ?? '' );
					$pending   = ! empty( $m['pending'] );
					$is_owner  = ( 'owner' === $role );
					$display   = (string) ( $m['display_name'] ?? '' );
					$row_attrs = sprintf(
						'data-user-id="%d" data-member-id="%d" data-pending="%s" data-name="%s"',
						$user_id,
						$member_id,
						$pending ? '1' : '0',
						esc_attr( $display )
					);
					?>
					<tr <?php echo $row_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						<td class="gnl-cell-name"><?php echo esc_html( $display ); ?></td>
						<td>
							<?php if ( $user_id > 0 && ! $is_owner ) : ?>
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
								<?php if ( ! empty( $m['invited_by_username'] ) ) : ?>
									<div class="gnl-cell-meta">
										<?php
										printf(
											/* translators: %s: invited-by username */
											esc_html__( 'invited by %s', 'gamenight-league' ),
											esc_html( $m['invited_by_username'] )
										);
										?>
									</div>
								<?php endif; ?>
							<?php else : ?>
								<?php esc_html_e( 'active', 'gamenight-league' ); ?>
							<?php endif; ?>
						</td>
						<td class="gnl-cell-actions">
							<?php if ( $is_owner ) : ?>
								<span class="description"><?php esc_html_e( 'owner — protected', 'gamenight-league' ); ?></span>
							<?php else : ?>
								<?php if ( $pending && $member_id > 0 ) : ?>
									<button type="button" class="button button-small gnl-pending-edit"><?php esc_html_e( 'Edit', 'gamenight-league' ); ?></button>
									<button type="button" class="button button-small button-link-delete gnl-pending-delete"><?php esc_html_e( 'Remove', 'gamenight-league' ); ?></button>
								<?php elseif ( $user_id > 0 ) : ?>
									<button type="button" class="button button-small button-link-delete gnl-member-delete"><?php esc_html_e( 'Remove', 'gamenight-league' ); ?></button>
								<?php endif; ?>
							<?php endif; ?>
						</td>
					</tr>
					<?php if ( $pending && $member_id > 0 ) : ?>
						<tr class="gnl-pending-edit-row" hidden>
							<td colspan="5">
								<form class="gnl-pending-edit-form" data-member-id="<?php echo esc_attr( (string) $member_id ); ?>">
									<table class="form-table" role="presentation">
										<tr>
											<th><label><?php esc_html_e( 'Name', 'gamenight-league' ); ?></label></th>
											<td><input type="text" name="display_name" value="<?php echo esc_attr( $display ); ?>" class="regular-text" required /></td>
										</tr>
										<tr>
											<th><label><?php esc_html_e( 'Email', 'gamenight-league' ); ?></label></th>
											<td>
												<input type="email" name="email" value="" class="regular-text" placeholder="<?php esc_attr_e( '(unchanged — type to update, blank to clear)', 'gamenight-league' ); ?>" />
												<p class="description"><?php esc_html_e( 'Leave the field exactly as shown to keep the current value. Type a new value to update, or clear it to remove.', 'gamenight-league' ); ?></p>
											</td>
										</tr>
										<tr>
											<th><label><?php esc_html_e( 'Phone', 'gamenight-league' ); ?></label></th>
											<td><input type="tel" name="phone" value="" class="regular-text" placeholder="<?php esc_attr_e( '(unchanged — type to update, blank to clear)', 'gamenight-league' ); ?>" /></td>
										</tr>
									</table>
									<p>
										<button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'gamenight-league' ); ?></button>
										<button type="button" class="button gnl-pending-edit-cancel"><?php esc_html_e( 'Cancel', 'gamenight-league' ); ?></button>
										<span class="gnl-form-status" aria-live="polite"></span>
									</p>
									<p class="description"><?php esc_html_e( 'Note: the API does not return a pending contact\'s current email or phone, so the fields above start blank. Pass an empty string to clear; type a new value to overwrite.', 'gamenight-league' ); ?></p>
								</form>
							</td>
						</tr>
					<?php endif; ?>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Add member', 'gamenight-league' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Creates a user and adds them to the league. If the email or phone matches an existing user, that existing user is added (no duplicate created).', 'gamenight-league' ); ?>
	</p>
	<form id="gnl-add-member-form" class="gnl-form">
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="gnl-add-name"><?php esc_html_e( 'Name', 'gamenight-league' ); ?></label></th>
				<td><input id="gnl-add-name" name="display_name" type="text" class="regular-text" required /></td>
			</tr>
			<tr>
				<th><label for="gnl-add-email"><?php esc_html_e( 'Email', 'gamenight-league' ); ?></label></th>
				<td><input id="gnl-add-email" name="email" type="email" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="gnl-add-phone"><?php esc_html_e( 'Phone', 'gamenight-league' ); ?></label></th>
				<td>
					<input id="gnl-add-phone" name="phone" type="tel" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Email or phone is required (one is enough).', 'gamenight-league' ); ?></p>
				</td>
			</tr>
		</table>
		<p>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Add member', 'gamenight-league' ); ?></button>
			<span class="gnl-form-status" aria-live="polite"></span>
		</p>
	</form>
</div>
