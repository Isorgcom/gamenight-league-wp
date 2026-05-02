<?php
/**
 * Admin view: posts list with edit/delete + pinned/hidden badges.
 *
 * Available vars: $result (array|WP_Error), $api
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$new_url = admin_url( 'admin.php?page=' . \GameNight\League\Admin\Admin_Menu::SLUG_POST_NEW );
$posts   = is_wp_error( $result ) ? array() : ( $result['posts'] ?? array() );
?>
<div class="wrap gnl-admin" data-gnl-admin-page="posts">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Posts', 'gamenight-league' ); ?></h1>
	<a href="<?php echo esc_url( $new_url ); ?>" class="page-title-action"><?php esc_html_e( '+ New post', 'gamenight-league' ); ?></a>

	<?php if ( is_wp_error( $result ) ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $result->get_error_message() ); ?></p></div>
	<?php elseif ( empty( $posts ) ) : ?>
		<p><?php esc_html_e( 'No posts yet. Create one with the button above.', 'gamenight-league' ); ?></p>
	<?php else : ?>
		<p class="description"><?php esc_html_e( 'Note: hidden posts are not returned by the API and so are not shown here. Unhide them on the post editor if you need to see them.', 'gamenight-league' ); ?></p>
		<table class="widefat striped gnl-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Title', 'gamenight-league' ); ?></th>
					<th><?php esc_html_e( 'Author', 'gamenight-league' ); ?></th>
					<th><?php esc_html_e( 'Created', 'gamenight-league' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'gamenight-league' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $posts as $p ) :
					$id        = (int) ( $p['id'] ?? 0 );
					$title     = (string) ( $p['title'] ?? '' );
					$pinned    = ! empty( $p['pinned'] );
					$edit_url  = add_query_arg(
						array( 'page' => \GameNight\League\Admin\Admin_Menu::SLUG_POST_NEW, 'id' => $id ),
						admin_url( 'admin.php' )
					);
					?>
					<tr data-post-id="<?php echo esc_attr( (string) $id ); ?>" data-post-title="<?php echo esc_attr( $title ); ?>">
						<td>
							<strong><?php echo esc_html( $title ); ?></strong>
							<?php if ( $pinned ) : ?>
								<span class="gnl-badge gnl-badge--owner"><?php esc_html_e( 'pinned', 'gamenight-league' ); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $p['author_display_name'] ?? '' ); ?></td>
						<td><?php echo esc_html( gnl_format_datetime( $p['created_at'] ?? '' ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'gamenight-league' ); ?></a>
							<button type="button" class="button button-small button-link-delete gnl-post-delete"><?php esc_html_e( 'Delete', 'gamenight-league' ); ?></button>
							<span class="gnl-row-status" aria-live="polite"></span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
