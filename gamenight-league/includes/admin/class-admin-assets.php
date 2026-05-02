<?php
/**
 * Enqueue admin CSS/JS only on GameNight admin pages.
 */

namespace GameNight\League\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin_Assets {

	private static $slugs = array(
		'toplevel_page_gnl-admin',
		'gamenight_page_gnl-admin-events',
		'gamenight_page_gnl-admin-event-new',
		'gamenight_page_gnl-admin-posts',
		'gamenight_page_gnl-admin-post-new',
		'gamenight_page_gnl-admin-shortcodes',
	);

	public function register() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function enqueue( $hook ) {
		if ( ! in_array( $hook, self::$slugs, true ) ) {
			return;
		}
		wp_enqueue_style(
			'gnl-admin',
			GNL_URL . 'assets/css/gamenight-admin.css',
			array(),
			GNL_VERSION
		);
		wp_enqueue_script(
			'gnl-admin',
			GNL_URL . 'assets/js/gamenight-admin.js',
			array(),
			GNL_VERSION,
			true
		);
		wp_localize_script(
			'gnl-admin',
			'GNLAdmin',
			array(
				'endpoint' => esc_url_raw( rest_url( 'gamenight/v1/' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'eventsPage' => admin_url( 'admin.php?page=' . Admin_Menu::SLUG_EVENTS ),
				'postsPage'  => admin_url( 'admin.php?page=' . Admin_Menu::SLUG_POSTS ),
				'strings'  => array(
					'role_updated'    => __( 'Role updated.', 'gamenight-league' ),
					'role_failed'     => __( 'Could not update role.', 'gamenight-league' ),
					'rsvp_updated'    => __( 'RSVP updated.', 'gamenight-league' ),
					'rsvp_failed'     => __( 'Could not update RSVP.', 'gamenight-league' ),
					'invitee_added'   => __( 'Invitee added.', 'gamenight-league' ),
					'invitee_failed'  => __( 'Could not add invitee.', 'gamenight-league' ),
					'person_added'    => __( 'Added and invited.', 'gamenight-league' ),
					'person_failed'   => __( 'Could not add person.', 'gamenight-league' ),
					'invitee_removed' => __( 'Invitee removed.', 'gamenight-league' ),
					'remove_failed'   => __( 'Could not remove invitee.', 'gamenight-league' ),
					'event_saved'     => __( 'Event saved.', 'gamenight-league' ),
					'event_failed'    => __( 'Could not save event.', 'gamenight-league' ),
					'event_deleted'   => __( 'Event deleted.', 'gamenight-league' ),
					'delete_failed'   => __( 'Could not delete event.', 'gamenight-league' ),
					/* translators: %s: event title */
					'confirm_delete'  => __( 'Delete "%s"? Future events will notify invitees.', 'gamenight-league' ),
					'confirm_remove'  => __( 'Remove this invitee?', 'gamenight-league' ),
					'saving'          => __( 'Saving…', 'gamenight-league' ),
					'post_saved'      => __( 'Post saved.', 'gamenight-league' ),
					'post_failed'     => __( 'Could not save post.', 'gamenight-league' ),
					'post_deleted'    => __( 'Post deleted.', 'gamenight-league' ),
					/* translators: %s: post title */
					'confirm_post_delete' => __( 'Delete "%s"? Comments on this post will also be deleted.', 'gamenight-league' ),
					'copied'              => __( 'Copied!', 'gamenight-league' ),
					'copy_failed'         => __( 'Copy failed — select and copy manually.', 'gamenight-league' ),
				),
			)
		);
	}
}
