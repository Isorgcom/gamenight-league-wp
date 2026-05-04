<?php
/**
 * WP REST proxy: exposes /wp-json/gamenight/v1/rsvp to anonymous visitors,
 * forwards the call to the GameNight API server-side so the key never leaves PHP.
 */

namespace GameNight\League;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rest_Controller {

	const NAMESPACE_V1     = 'gamenight/v1';
	const RATE_LIMIT_MAX   = 5;
	const RATE_LIMIT_SECS  = 600;

	/** @var Api_Client */
	private $api;

	/** @var Cache */
	private $cache;

	public function __construct( Api_Client $api, Cache $cache ) {
		$this->api   = $api;
		$this->cache = $cache;
	}

	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		// Public: anonymous-visitor RSVP.
		register_rest_route(
			self::NAMESPACE_V1,
			'/rsvp',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_rsvp' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'event_id'     => array( 'required' => true, 'type' => 'integer' ),
					'display_name' => array( 'required' => true, 'type' => 'string' ),
					'rsvp'         => array( 'required' => true, 'type' => 'string' ),
					'email'        => array( 'required' => false, 'type' => 'string' ),
					'phone'        => array( 'required' => false, 'type' => 'string' ),
				),
			)
		);

		// Public: anonymous-visitor "join the league" (no event involved).
		register_rest_route(
			self::NAMESPACE_V1,
			'/join',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_join' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'display_name' => array( 'required' => true, 'type' => 'string' ),
					'email'        => array( 'required' => false, 'type' => 'string' ),
					'phone'        => array( 'required' => false, 'type' => 'string' ),
				),
			)
		);

		// Admin: capability-gated mirrors of the GameNight write endpoints.
		$admin_perm = array( $this, 'admin_permission_check' );

		register_rest_route( self::NAMESPACE_V1, '/admin/members', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'admin_list_members' ),
				'permission_callback' => $admin_perm,
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'admin_create_member' ),
				'permission_callback' => $admin_perm,
			),
		) );
		register_rest_route( self::NAMESPACE_V1, '/admin/members/(?P<user_id>\d+)', array(
			array(
				'methods'             => 'PATCH',
				'callback'            => array( $this, 'admin_update_member' ),
				'permission_callback' => $admin_perm,
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'admin_delete_member' ),
				'permission_callback' => $admin_perm,
			),
		) );
		register_rest_route( self::NAMESPACE_V1, '/admin/pending-contacts/(?P<member_id>\d+)', array(
			array(
				'methods'             => 'PATCH',
				'callback'            => array( $this, 'admin_update_pending_contact' ),
				'permission_callback' => $admin_perm,
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'admin_delete_pending_contact' ),
				'permission_callback' => $admin_perm,
			),
		) );

		register_rest_route( self::NAMESPACE_V1, '/admin/events', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'admin_list_events' ),
				'permission_callback' => $admin_perm,
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'admin_create_event' ),
				'permission_callback' => $admin_perm,
			),
		) );
		register_rest_route( self::NAMESPACE_V1, '/admin/events/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'admin_get_event' ),
				'permission_callback' => $admin_perm,
			),
			array(
				'methods'             => 'PATCH',
				'callback'            => array( $this, 'admin_update_event' ),
				'permission_callback' => $admin_perm,
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'admin_delete_event' ),
				'permission_callback' => $admin_perm,
			),
		) );

		register_rest_route( self::NAMESPACE_V1, '/admin/events/(?P<id>\d+)/invitees', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'admin_list_invitees' ),
				'permission_callback' => $admin_perm,
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'admin_add_invitee' ),
				'permission_callback' => $admin_perm,
			),
		) );
		register_rest_route( self::NAMESPACE_V1, '/admin/events/(?P<id>\d+)/invitees/new-person', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'admin_invite_new_person' ),
			'permission_callback' => $admin_perm,
		) );
		register_rest_route( self::NAMESPACE_V1, '/admin/events/(?P<id>\d+)/invitees/(?P<user_id>\d+)', array(
			array(
				'methods'             => 'PATCH',
				'callback'            => array( $this, 'admin_update_invitee' ),
				'permission_callback' => $admin_perm,
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'admin_remove_invitee' ),
				'permission_callback' => $admin_perm,
			),
		) );

		register_rest_route( self::NAMESPACE_V1, '/admin/posts', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'admin_list_posts' ),
				'permission_callback' => $admin_perm,
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'admin_create_post' ),
				'permission_callback' => $admin_perm,
			),
		) );
		register_rest_route( self::NAMESPACE_V1, '/admin/posts/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'admin_get_post' ),
				'permission_callback' => $admin_perm,
			),
			array(
				'methods'             => 'PATCH',
				'callback'            => array( $this, 'admin_update_post' ),
				'permission_callback' => $admin_perm,
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'admin_delete_post' ),
				'permission_callback' => $admin_perm,
			),
		) );
	}

	public function admin_permission_check() {
		return current_user_can( 'manage_options' );
	}

	/* ---------- Admin handlers ---------- */

	public function admin_list_members() {
		$res = $this->api->get_members();
		return is_wp_error( $res ) ? $res : new WP_REST_Response( array( 'ok' => true, 'data' => $res ), 200 );
	}

	public function admin_update_member( WP_REST_Request $req ) {
		$user_id = (int) $req['user_id'];
		$role    = sanitize_text_field( (string) $req->get_param( 'role' ) );
		$res     = $this->api->update_member_role( $user_id, $role );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$this->cache->flush_all();
		return new WP_REST_Response( array( 'ok' => true, 'data' => $res ), 200 );
	}

	public function admin_create_member( WP_REST_Request $req ) {
		$display_name = sanitize_text_field( (string) $req->get_param( 'display_name' ) );
		$email        = sanitize_email( (string) $req->get_param( 'email' ) );
		$phone        = sanitize_text_field( (string) $req->get_param( 'phone' ) );

		if ( '' === $display_name ) {
			return new WP_Error( 'gnl_bad_name', __( 'Name is required.', 'gamenight-league' ), array( 'status' => 400 ) );
		}
		if ( '' === $email && '' === $phone ) {
			return new WP_Error( 'gnl_need_contact', __( 'Email or phone is required.', 'gamenight-league' ), array( 'status' => 400 ) );
		}
		if ( '' !== $email && ! is_email( $email ) ) {
			return new WP_Error( 'gnl_bad_email', __( 'Invalid email address.', 'gamenight-league' ), array( 'status' => 400 ) );
		}

		$payload = array( 'display_name' => $display_name );
		if ( '' !== $email ) { $payload['email'] = $email; }
		if ( '' !== $phone ) { $payload['phone'] = $phone; }

		$res = $this->api->create_user( $payload );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$this->cache->flush_all();
		return new WP_REST_Response( array( 'ok' => true, 'data' => $res ), 200 );
	}

	public function admin_delete_member( WP_REST_Request $req ) {
		$user_id = (int) $req['user_id'];
		$res     = $this->api->delete_member( $user_id );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$this->cache->flush_all();
		return new WP_REST_Response( array( 'ok' => true, 'data' => $res ), 200 );
	}

	public function admin_update_pending_contact( WP_REST_Request $req ) {
		$member_id = (int) $req['member_id'];
		$raw       = $req->get_json_params() ?: $req->get_params();
		$payload   = array();
		if ( isset( $raw['display_name'] ) ) {
			$payload['display_name'] = sanitize_text_field( (string) $raw['display_name'] );
		}
		if ( array_key_exists( 'email', $raw ) ) {
			$payload['email'] = sanitize_email( (string) $raw['email'] );
		}
		if ( array_key_exists( 'phone', $raw ) ) {
			$payload['phone'] = sanitize_text_field( (string) $raw['phone'] );
		}
		if ( empty( $payload ) ) {
			return new WP_Error( 'gnl_no_fields', __( 'Nothing to update.', 'gamenight-league' ), array( 'status' => 400 ) );
		}
		$res = $this->api->update_pending_contact( $member_id, $payload );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$this->cache->flush_all();
		return new WP_REST_Response( array( 'ok' => true, 'data' => $res ), 200 );
	}

	public function admin_delete_pending_contact( WP_REST_Request $req ) {
		$member_id = (int) $req['member_id'];
		$res       = $this->api->delete_pending_contact( $member_id );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$this->cache->flush_all();
		return new WP_REST_Response( array( 'ok' => true, 'data' => $res ), 200 );
	}

	public function admin_list_events( WP_REST_Request $req ) {
		$from         = sanitize_text_field( (string) $req->get_param( 'from' ) );
		$to           = sanitize_text_field( (string) $req->get_param( 'to' ) );
		$include_past = (bool) $req->get_param( 'include_past' );
		if ( $include_past && '' === $from ) {
			$from = gmdate( 'Y-m-d', strtotime( '-365 days' ) );
		}
		$res = $this->api->get_events( $from, $to );
		return is_wp_error( $res ) ? $res : new WP_REST_Response( array( 'ok' => true, 'data' => $res ), 200 );
	}

	public function admin_get_event( WP_REST_Request $req ) {
		$id  = (int) $req['id'];
		$res = $this->api->get_event( $id );
		return is_wp_error( $res ) ? $res : new WP_REST_Response( array( 'ok' => true, 'data' => $res ), 200 );
	}

	public function admin_create_event( WP_REST_Request $req ) {
		$payload = $this->sanitize_event_payload( $req->get_json_params() ?: $req->get_params(), true );
		$res     = $this->api->create_event( $payload );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$this->cache->flush_all();
		return new WP_REST_Response( array( 'ok' => true, 'data' => $res ), 200 );
	}

	public function admin_update_event( WP_REST_Request $req ) {
		$id      = (int) $req['id'];
		$payload = $this->sanitize_event_payload( $req->get_json_params() ?: $req->get_params(), false );
		$res     = $this->api->update_event( $id, $payload );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$this->cache->flush_all();
		return new WP_REST_Response( array( 'ok' => true, 'data' => $res ), 200 );
	}

	public function admin_delete_event( WP_REST_Request $req ) {
		$id  = (int) $req['id'];
		$res = $this->api->delete_event( $id );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$this->cache->flush_all();
		return new WP_REST_Response( array( 'ok' => true, 'data' => $res ), 200 );
	}

	public function admin_list_invitees( WP_REST_Request $req ) {
		$id  = (int) $req['id'];
		$res = $this->api->get_event_invites( $id );
		return is_wp_error( $res ) ? $res : new WP_REST_Response( array( 'ok' => true, 'data' => $res ), 200 );
	}

	public function admin_add_invitee( WP_REST_Request $req ) {
		$id      = (int) $req['id'];
		$user_id = (int) $req->get_param( 'user_id' );
		$manager = (bool) $req->get_param( 'manager' );
		if ( $user_id <= 0 ) {
			return new WP_Error( 'gnl_bad_user', __( 'user_id is required.', 'gamenight-league' ), array( 'status' => 400 ) );
		}
		$res = $this->api->add_invitee( $id, $user_id, $manager );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$this->cache->flush_all();
		return new WP_REST_Response( array( 'ok' => true, 'data' => $res ), 200 );
	}

	public function admin_invite_new_person( WP_REST_Request $req ) {
		$id           = (int) $req['id'];
		$display_name = sanitize_text_field( (string) $req->get_param( 'display_name' ) );
		$email        = sanitize_email( (string) $req->get_param( 'email' ) );
		$phone        = sanitize_text_field( (string) $req->get_param( 'phone' ) );
		$manager      = (bool) $req->get_param( 'manager' );

		if ( '' === $display_name ) {
			return new WP_Error( 'gnl_bad_name', __( 'Name is required.', 'gamenight-league' ), array( 'status' => 400 ) );
		}
		if ( '' === $email && '' === $phone ) {
			return new WP_Error( 'gnl_need_contact', __( 'Email or phone is required.', 'gamenight-league' ), array( 'status' => 400 ) );
		}
		if ( '' !== $email && ! is_email( $email ) ) {
			return new WP_Error( 'gnl_bad_email', __( 'Invalid email address.', 'gamenight-league' ), array( 'status' => 400 ) );
		}

		$payload = array( 'display_name' => $display_name );
		if ( '' !== $email ) { $payload['email'] = $email; }
		if ( '' !== $phone ) { $payload['phone'] = $phone; }

		$user = $this->api->create_user( $payload );
		if ( is_wp_error( $user ) ) {
			return $user;
		}
		$user_id = isset( $user['user_id'] ) ? (int) $user['user_id'] : 0;
		if ( $user_id <= 0 ) {
			return new WP_Error( 'gnl_no_user_id', __( 'Could not resolve user id.', 'gamenight-league' ), array( 'status' => 502 ) );
		}

		$add = $this->api->add_invitee( $id, $user_id, $manager );
		if ( is_wp_error( $add ) ) {
			$status = (int) ( $add->get_error_data()['status'] ?? 0 );
			if ( $status && 409 !== $status ) {
				return $add;
			}
		}

		$this->cache->flush_all();
		return new WP_REST_Response(
			array(
				'ok'   => true,
				'data' => array(
					'user_id'      => $user_id,
					'display_name' => $display_name,
					'created'      => ! empty( $user['created'] ),
				),
			),
			200
		);
	}

	public function admin_remove_invitee( WP_REST_Request $req ) {
		$id      = (int) $req['id'];
		$user_id = (int) $req['user_id'];
		$res     = $this->api->remove_invitee( $id, $user_id );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$this->cache->flush_all();
		return new WP_REST_Response( array( 'ok' => true, 'data' => $res ), 200 );
	}

	public function admin_update_invitee( WP_REST_Request $req ) {
		$id      = (int) $req['id'];
		$user_id = (int) $req['user_id'];
		$rsvp    = $req->get_param( 'rsvp' );
		$role    = $req->get_param( 'event_role' );

		if ( null !== $rsvp ) {
			$rsvp = is_string( $rsvp ) ? strtolower( sanitize_text_field( $rsvp ) ) : '';
			if ( '' === $rsvp || 'clear' === $rsvp || 'null' === $rsvp ) {
				$res = $this->api->clear_rsvp( $id, $user_id );
			} else {
				$res = $this->api->set_rsvp( $id, $user_id, $rsvp );
			}
			if ( is_wp_error( $res ) ) {
				return $res;
			}
		}
		if ( null !== $role ) {
			$role = sanitize_text_field( (string) $role );
			$res  = $this->api->set_invitee_role( $id, $user_id, $role );
			if ( is_wp_error( $res ) ) {
				return $res;
			}
		}
		$this->cache->flush_all();
		return new WP_REST_Response( array( 'ok' => true, 'data' => array( 'updated' => true ) ), 200 );
	}

	/* ---------- Posts admin handlers (v0.3.0) ---------- */

	public function admin_list_posts( WP_REST_Request $req ) {
		// Re-fetch large window so admin sees hidden + pinned across recent history.
		$limit  = (int) $req->get_param( 'limit' ) ?: 100;
		$offset = (int) $req->get_param( 'offset' );
		$res    = $this->api->get_posts( $limit, $offset );
		return is_wp_error( $res ) ? $res : new WP_REST_Response( array( 'ok' => true, 'data' => $res ), 200 );
	}

	public function admin_get_post( WP_REST_Request $req ) {
		$id  = (int) $req['id'];
		$res = $this->api->get_post( $id );
		return is_wp_error( $res ) ? $res : new WP_REST_Response( array( 'ok' => true, 'data' => $res ), 200 );
	}

	public function admin_create_post( WP_REST_Request $req ) {
		$payload = $this->sanitize_post_payload( $req->get_json_params() ?: $req->get_params(), true );
		if ( empty( $payload['title'] ) || empty( $payload['content'] ) ) {
			return new WP_Error( 'gnl_post_required', __( 'Title and content are required.', 'gamenight-league' ), array( 'status' => 400 ) );
		}
		$res = $this->api->create_post( $payload );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$this->cache->flush_all();
		return new WP_REST_Response( array( 'ok' => true, 'data' => $res ), 200 );
	}

	public function admin_update_post( WP_REST_Request $req ) {
		$id      = (int) $req['id'];
		$payload = $this->sanitize_post_payload( $req->get_json_params() ?: $req->get_params(), false );
		if ( empty( $payload ) ) {
			return new WP_Error( 'gnl_no_fields', __( 'Nothing to update.', 'gamenight-league' ), array( 'status' => 400 ) );
		}
		$res = $this->api->update_post( $id, $payload );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$this->cache->flush_all();
		return new WP_REST_Response( array( 'ok' => true, 'data' => $res ), 200 );
	}

	public function admin_delete_post( WP_REST_Request $req ) {
		$id  = (int) $req['id'];
		$res = $this->api->delete_post( $id );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$this->cache->flush_all();
		return new WP_REST_Response( array( 'ok' => true, 'data' => $res ), 200 );
	}

	/**
	 * Whitelist + sanitize post fields. PATCH (allow_published_at=false) rejects
	 * published_at because the API returns 400 on it.
	 */
	private function sanitize_post_payload( $raw, $allow_published_at ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		if ( isset( $raw['title'] ) && '' !== trim( (string) $raw['title'] ) ) {
			$out['title'] = sanitize_text_field( (string) $raw['title'] );
		}
		if ( isset( $raw['content'] ) && '' !== trim( (string) $raw['content'] ) ) {
			$out['content'] = wp_kses_post( (string) $raw['content'] );
		}
		if ( array_key_exists( 'pinned', $raw ) ) {
			$out['pinned'] = (bool) $raw['pinned'];
		}
		if ( array_key_exists( 'hidden', $raw ) ) {
			$out['hidden'] = (bool) $raw['hidden'];
		}
		if ( $allow_published_at && ! empty( $raw['published_at'] ) ) {
			$out['published_at'] = sanitize_text_field( (string) $raw['published_at'] );
		}
		return $out;
	}

	/**
	 * Whitelist + sanitize fields the admin UI may send to GameNight.
	 *
	 * @param array $raw
	 * @param bool  $allow_invitees Whether the 'invitees' field is permitted (create only).
	 * @return array
	 */
	private function sanitize_event_payload( $raw, $allow_invitees ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();

		$strings = array( 'title', 'description', 'start_at', 'end_at', 'color', 'poker_game_type', 'visibility' );
		foreach ( $strings as $field ) {
			if ( isset( $raw[ $field ] ) && '' !== $raw[ $field ] ) {
				$out[ $field ] = sanitize_text_field( (string) $raw[ $field ] );
			}
		}

		$bools = array( 'is_poker', 'requires_approval', 'waitlist_enabled', 'reminders_enabled' );
		foreach ( $bools as $field ) {
			if ( isset( $raw[ $field ] ) ) {
				$out[ $field ] = (bool) $raw[ $field ];
			}
		}

		$ints = array( 'rsvp_deadline_hours', 'poker_buyin', 'poker_tables', 'poker_seats' );
		foreach ( $ints as $field ) {
			if ( isset( $raw[ $field ] ) && '' !== $raw[ $field ] ) {
				$out[ $field ] = (int) $raw[ $field ];
			}
		}

		if ( ! empty( $raw['reminder_offsets'] ) ) {
			if ( is_array( $raw['reminder_offsets'] ) ) {
				$out['reminder_offsets'] = array_values( array_map( 'intval', $raw['reminder_offsets'] ) );
			} elseif ( is_string( $raw['reminder_offsets'] ) ) {
				$parts = array_filter( array_map( 'trim', explode( ',', $raw['reminder_offsets'] ) ), 'strlen' );
				$out['reminder_offsets'] = array_values( array_map( 'intval', $parts ) );
			}
		}

		if ( $allow_invitees && ! empty( $raw['invitees'] ) && is_array( $raw['invitees'] ) ) {
			$out['invitees'] = array();
			foreach ( $raw['invitees'] as $inv ) {
				if ( is_array( $inv ) && ! empty( $inv['user_id'] ) ) {
					$out['invitees'][] = array(
						'user_id' => (int) $inv['user_id'],
						'manager' => ! empty( $inv['manager'] ),
					);
				}
			}
		}

		return $out;
	}

	public function handle_rsvp( WP_REST_Request $req ) {
		$event_id     = (int) $req->get_param( 'event_id' );
		$display_name = sanitize_text_field( (string) $req->get_param( 'display_name' ) );
		$rsvp         = strtolower( sanitize_text_field( (string) $req->get_param( 'rsvp' ) ) );
		$email        = sanitize_email( (string) $req->get_param( 'email' ) );
		$phone        = sanitize_text_field( (string) $req->get_param( 'phone' ) );

		if ( $event_id <= 0 ) {
			return new WP_Error( 'gnl_bad_event', __( 'Invalid event id.', 'gamenight-league' ), array( 'status' => 400 ) );
		}
		if ( '' === $display_name ) {
			return new WP_Error( 'gnl_bad_name', __( 'Please provide your name.', 'gamenight-league' ), array( 'status' => 400 ) );
		}
		if ( ! in_array( $rsvp, array( 'yes', 'no', 'maybe' ), true ) ) {
			return new WP_Error( 'gnl_bad_rsvp', __( 'Invalid RSVP value.', 'gamenight-league' ), array( 'status' => 400 ) );
		}
		if ( '' === $email && '' === $phone ) {
			return new WP_Error( 'gnl_need_contact', __( 'Please provide an email or phone number.', 'gamenight-league' ), array( 'status' => 400 ) );
		}
		if ( '' !== $email && ! is_email( $email ) ) {
			return new WP_Error( 'gnl_bad_email', __( 'Please provide a valid email address.', 'gamenight-league' ), array( 'status' => 400 ) );
		}

		// Per-IP rate limit (transient counter).
		$ip          = $this->client_ip();
		$rl_key      = 'gnl_rl_' . md5( $ip );
		$current     = (int) get_transient( $rl_key );
		if ( $current >= self::RATE_LIMIT_MAX ) {
			return new WP_Error( 'gnl_rate_limit', __( 'Too many attempts. Please try again in a few minutes.', 'gamenight-league' ), array( 'status' => 429 ) );
		}
		set_transient( $rl_key, $current + 1, self::RATE_LIMIT_SECS );

		// 1. Idempotent user create. Returns existing user_id if email/phone already known.
		$user_payload = array( 'display_name' => $display_name );
		if ( '' !== $email ) {
			$user_payload['email'] = $email;
		}
		if ( '' !== $phone ) {
			$user_payload['phone'] = $phone;
		}
		$user = $this->api->create_user( $user_payload );
		if ( is_wp_error( $user ) ) {
			return $user;
		}
		$user_id = isset( $user['user_id'] ) ? (int) $user['user_id'] : 0;
		if ( $user_id <= 0 ) {
			return new WP_Error( 'gnl_no_user_id', __( 'Could not resolve user id.', 'gamenight-league' ), array( 'status' => 502 ) );
		}

		// 2. Best-effort invite (no-op / 409 if already invited — we ignore that error).
		$add = $this->api->add_invitee( $event_id, $user_id );
		if ( is_wp_error( $add ) ) {
			$status = (int) ( $add->get_error_data()['status'] ?? 0 );
			if ( $status && $status !== 409 ) {
				return $add;
			}
		}

		// 3. Set the RSVP.
		$rsvp_result = $this->api->set_rsvp( $event_id, $user_id, $rsvp );
		if ( is_wp_error( $rsvp_result ) ) {
			return $rsvp_result;
		}

		// 4. Bust caches so the event's RSVP counts refresh.
		$this->cache->flush_all();

		return new WP_REST_Response(
			array(
				'ok'      => true,
				'message' => __( 'RSVP recorded.', 'gamenight-league' ),
			),
			200
		);
	}

	public function handle_join( WP_REST_Request $req ) {
		$display_name = sanitize_text_field( (string) $req->get_param( 'display_name' ) );
		$email        = sanitize_email( (string) $req->get_param( 'email' ) );
		$phone        = sanitize_text_field( (string) $req->get_param( 'phone' ) );

		if ( '' === $display_name ) {
			return new WP_Error( 'gnl_bad_name', __( 'Please provide your name.', 'gamenight-league' ), array( 'status' => 400 ) );
		}
		if ( '' === $email && '' === $phone ) {
			return new WP_Error( 'gnl_need_contact', __( 'Please provide an email or phone number.', 'gamenight-league' ), array( 'status' => 400 ) );
		}
		if ( '' !== $email && ! is_email( $email ) ) {
			return new WP_Error( 'gnl_bad_email', __( 'Please provide a valid email address.', 'gamenight-league' ), array( 'status' => 400 ) );
		}

		// Per-IP rate limit (transient counter), shared with RSVP route.
		$ip      = $this->client_ip();
		$rl_key  = 'gnl_rl_' . md5( $ip );
		$current = (int) get_transient( $rl_key );
		if ( $current >= self::RATE_LIMIT_MAX ) {
			return new WP_Error( 'gnl_rate_limit', __( 'Too many attempts. Please try again in a few minutes.', 'gamenight-league' ), array( 'status' => 429 ) );
		}
		set_transient( $rl_key, $current + 1, self::RATE_LIMIT_SECS );

		$payload = array( 'display_name' => $display_name );
		if ( '' !== $email ) {
			$payload['email'] = $email;
		}
		if ( '' !== $phone ) {
			$payload['phone'] = $phone;
		}

		$user = $this->api->create_user( $payload );
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$this->cache->flush_all();

		$created = ! empty( $user['created'] );
		return new WP_REST_Response(
			array(
				'ok'      => true,
				'message' => $created
					? __( 'Welcome — you\'re in the league.', 'gamenight-league' )
					: __( 'You\'re already a member of this league.', 'gamenight-league' ),
				'created' => $created,
			),
			200
		);
	}

	private function client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
	}
}
