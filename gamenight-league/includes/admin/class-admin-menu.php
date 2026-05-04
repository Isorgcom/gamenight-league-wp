<?php
/**
 * Top-level GameNight admin menu and page render dispatch.
 */

namespace GameNight\League\Admin;

use GameNight\League\Api_Client;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin_Menu {

	const SLUG_ROOT      = 'gnl-admin';
	const SLUG_EVENTS    = 'gnl-admin-events';
	const SLUG_EVENT_NEW = 'gnl-admin-event-new';
	const SLUG_POSTS      = 'gnl-admin-posts';
	const SLUG_POST_NEW   = 'gnl-admin-post-new';
	const SLUG_SHORTCODES = 'gnl-admin-shortcodes';
	const CAPABILITY     = 'manage_options';

	/** @var Api_Client */
	private $api;

	public function __construct( Api_Client $api ) {
		$this->api = $api;
	}

	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	public function add_menu() {
		add_menu_page(
			__( 'GameNight', 'gamenight-league' ),
			__( 'GameNight', 'gamenight-league' ),
			self::CAPABILITY,
			self::SLUG_ROOT,
			array( $this, 'render_members' ),
			'dashicons-groups',
			30
		);
		add_submenu_page(
			self::SLUG_ROOT,
			__( 'Members', 'gamenight-league' ),
			__( 'Members', 'gamenight-league' ),
			self::CAPABILITY,
			self::SLUG_ROOT,
			array( $this, 'render_members' )
		);
		add_submenu_page(
			self::SLUG_ROOT,
			__( 'Events', 'gamenight-league' ),
			__( 'Events', 'gamenight-league' ),
			self::CAPABILITY,
			self::SLUG_EVENTS,
			array( $this, 'render_events' )
		);
		add_submenu_page(
			self::SLUG_ROOT,
			__( 'New event', 'gamenight-league' ),
			__( 'New event', 'gamenight-league' ),
			self::CAPABILITY,
			self::SLUG_EVENT_NEW,
			array( $this, 'render_event_edit' )
		);
		add_submenu_page(
			self::SLUG_ROOT,
			__( 'Posts', 'gamenight-league' ),
			__( 'Posts', 'gamenight-league' ),
			self::CAPABILITY,
			self::SLUG_POSTS,
			array( $this, 'render_posts' )
		);
		add_submenu_page(
			self::SLUG_ROOT,
			__( 'New post', 'gamenight-league' ),
			__( 'New post', 'gamenight-league' ),
			self::CAPABILITY,
			self::SLUG_POST_NEW,
			array( $this, 'render_post_edit' )
		);
		add_submenu_page(
			self::SLUG_ROOT,
			__( 'Shortcodes', 'gamenight-league' ),
			__( 'Shortcodes', 'gamenight-league' ),
			self::CAPABILITY,
			self::SLUG_SHORTCODES,
			array( $this, 'render_shortcodes' )
		);
	}

	public function render_members() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$api     = $this->api;
		$members = $api->get_members();
		include GNL_PATH . 'includes/admin/views/view-members.php';
	}

	public function render_events() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$api = $this->api;
		// Admin page routing reads — capability-gated above; no state changes
		// happen here, so a nonce isn't appropriate.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '';
		if ( 'invitees' === $view ) {
			$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
			if ( $id <= 0 ) {
				echo '<div class="wrap"><p>' . esc_html__( 'Missing event id.', 'gamenight-league' ) . '</p></div>';
				return;
			}
			$event    = $api->get_event( $id );
			$invitees = $api->get_event_invites( $id );
			$members  = $api->get_members();
			include GNL_PATH . 'includes/admin/views/view-event-invitees.php';
			return;
		}
		$tab_raw = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$tab    = ( 'past' === $tab_raw ) ? 'past' : 'upcoming';
		$result = ( 'past' === $tab )
			? $api->get_events( gmdate( 'Y-m-d', strtotime( '-365 days' ) ), gmdate( 'Y-m-d' ) )
			: $api->get_events();
		include GNL_PATH . 'includes/admin/views/view-events.php';
	}

	public function render_event_edit() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$api = $this->api;
		// Admin page routing read — capability-gated above; no state changes here.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$event_id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		$event    = null;
		if ( $event_id > 0 ) {
			$event = $api->get_event( $event_id );
		}
		include GNL_PATH . 'includes/admin/views/view-event-edit.php';
	}

	public function render_posts() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$api    = $this->api;
		$result = $api->get_posts( 100, 0 );
		include GNL_PATH . 'includes/admin/views/view-posts.php';
	}

	public function render_post_edit() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$api = $this->api;
		// Admin page routing read — capability-gated above; no state changes here.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		$post    = null;
		if ( $post_id > 0 ) {
			$post = $api->get_post( $post_id );
		}
		include GNL_PATH . 'includes/admin/views/view-post-edit.php';
	}

	public function render_shortcodes() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		include GNL_PATH . 'includes/admin/views/view-shortcodes.php';
	}
}
