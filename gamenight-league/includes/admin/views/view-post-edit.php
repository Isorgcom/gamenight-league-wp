<?php
/**
 * Admin view: create or edit a post.
 *
 * Available vars: $post_id (int), $post (array|null|WP_Error), $api
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$is_new = ( 0 === $post_id || ! is_array( $post ) );
$err    = is_wp_error( $post ) ? $post->get_error_message() : '';
$p      = is_array( $post ) ? $post : array();

$title        = (string) ( $p['title'] ?? '' );
$content_html = (string) ( $p['content_html'] ?? '' );
$pinned       = ! empty( $p['pinned'] );
$hidden       = ! empty( $p['hidden'] );
?>
<div class="wrap gnl-admin" data-gnl-admin-page="post-edit" data-post-id="<?php echo esc_attr( (string) $post_id ); ?>">
	<h1>
		<?php
		echo $is_new
			? esc_html__( 'New post', 'gamenight-league' )
			: esc_html__( 'Edit post', 'gamenight-league' );
		?>
	</h1>

	<?php if ( $err ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $err ); ?></p></div>
	<?php endif; ?>

	<form id="gnl-post-form" class="gnl-form">
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="gnl-post-title"><?php esc_html_e( 'Title', 'gamenight-league' ); ?></label></th>
				<td>
					<input id="gnl-post-title" name="title" type="text" class="regular-text" required maxlength="200" value="<?php echo esc_attr( $title ); ?>" />
					<p class="description"><?php esc_html_e( 'Up to 200 characters.', 'gamenight-league' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="gnl-post-content"><?php esc_html_e( 'Content', 'gamenight-league' ); ?></label></th>
				<td>
					<?php
					wp_editor(
						$content_html,
						'gnl-post-content',
						array(
							'textarea_name' => 'content',
							'textarea_rows' => 12,
							'media_buttons' => false,
							'teeny'         => false,
						)
					);
					?>
					<p class="description"><?php esc_html_e( 'Sanitized server-side. Script tags, event handlers, and untrusted iframes are stripped before storage.', 'gamenight-league' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Visibility', 'gamenight-league' ); ?></th>
				<td>
					<label><input type="checkbox" name="pinned" value="1" <?php checked( $pinned ); ?> /> <?php esc_html_e( 'Pinned (sorts above unpinned posts)', 'gamenight-league' ); ?></label>
					<br />
					<label><input type="checkbox" name="hidden" value="1" <?php checked( $hidden ); ?> /> <?php esc_html_e( 'Hidden (not shown in feeds; useful for drafts)', 'gamenight-league' ); ?></label>
				</td>
			</tr>
			<?php if ( $is_new ) : ?>
				<tr>
					<th><label for="gnl-post-published-at"><?php esc_html_e( 'Publish date', 'gamenight-league' ); ?></label></th>
					<td>
						<input id="gnl-post-published-at" name="published_at" type="datetime-local" />
						<p class="description"><?php esc_html_e( 'Leave blank to publish immediately. Future dates schedule the post (the row exists but is hidden until the time arrives). Cannot be changed after creation.', 'gamenight-league' ); ?></p>
					</td>
				</tr>
			<?php endif; ?>
		</table>
		<p class="submit">
			<button type="submit" class="button button-primary">
				<?php echo $is_new ? esc_html__( 'Create post', 'gamenight-league' ) : esc_html__( 'Save changes', 'gamenight-league' ); ?>
			</button>
			<span class="gnl-form-status" aria-live="polite"></span>
		</p>
	</form>
</div>
