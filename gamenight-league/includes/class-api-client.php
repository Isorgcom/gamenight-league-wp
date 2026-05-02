<?php
/**
 * GameNight API HTTP client.
 *
 * Single point for all outbound requests to the GameNight API. Handles auth,
 * envelope unwrapping, error mapping, and read-side caching.
 */

namespace GameNight\League;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Api_Client {

	const OPT_KEY      = 'gnl_api_key';
	const OPT_BASE     = 'gnl_api_base_url';
	const OPT_TTL      = 'gnl_cache_ttl';
	const TIMEOUT_SECS = 10;

	/** @var Cache */
	private $cache;

	public function __construct( Cache $cache ) {
		$this->cache = $cache;
	}

	public function is_configured() {
		return '' !== $this->key() && '' !== $this->base_url();
	}

	public function key() {
		return (string) get_option( self::OPT_KEY, '' );
	}

	public function base_url() {
		$base = (string) get_option( self::OPT_BASE, GNL_API_DEFAULT_BASE );
		return rtrim( $base, '/' );
	}

	public function cache_ttl() {
		$ttl = (int) get_option( self::OPT_TTL, 60 );
		return max( 1, $ttl );
	}

	/**
	 * Low-level request. Returns decoded `data` on success or WP_Error on failure.
	 *
	 * @param string $method HTTP verb.
	 * @param string $path   API path beginning with /api/v1/...
	 * @param array  $args   Optional. ['query' => [], 'body' => []]
	 * @return array|WP_Error
	 */
	public function request( $method, $path, array $args = array() ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'gnl_not_configured', __( 'GameNight API key is not configured.', 'gamenight-league' ) );
		}

		$url = $this->base_url() . $path;
		if ( ! empty( $args['query'] ) && is_array( $args['query'] ) ) {
			$url = add_query_arg( $args['query'], $url );
		}

		$request_args = array(
			'method'  => strtoupper( $method ),
			'timeout' => self::TIMEOUT_SECS,
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->key(),
				'Accept'        => 'application/json',
				'User-Agent'    => sprintf( 'GameNightLeague-WP/%s; %s', GNL_VERSION, home_url( '/' ) ),
			),
		);

		if ( ! empty( $args['body'] ) ) {
			$request_args['headers']['Content-Type'] = 'application/json';
			$request_args['body']                    = wp_json_encode( $args['body'] );
		}

		$response = wp_remote_request( $url, $request_args );

		if ( is_wp_error( $response ) ) {
			error_log( '[GameNight] Transport error on ' . $method . ' ' . $path . ': ' . $response->get_error_message() );
			return $response;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );
		$json   = json_decode( $body, true );

		if ( ! is_array( $json ) ) {
			error_log( '[GameNight] Non-JSON response on ' . $method . ' ' . $path . ' (status ' . $status . ')' );
			return new WP_Error( 'gnl_bad_response', __( 'Unexpected response from GameNight API.', 'gamenight-league' ), array( 'status' => $status ) );
		}

		if ( empty( $json['ok'] ) ) {
			$msg = isset( $json['error'] ) ? (string) $json['error'] : 'Unknown API error.';
			error_log( '[GameNight] API error on ' . $method . ' ' . $path . ' (status ' . $status . '): ' . $msg );
			return new WP_Error( 'gnl_api_error', $msg, array( 'status' => $status ) );
		}

		return isset( $json['data'] ) ? $json['data'] : array();
	}

	/**
	 * GET with caching.
	 *
	 * @return array|WP_Error
	 */
	private function cached_get( $path, array $query = array() ) {
		$key    = $this->cache->key( $path, $query );
		$cached = $this->cache->get( $key );
		if ( false !== $cached ) {
			return $cached;
		}
		$data = $this->request( 'GET', $path, array( 'query' => $query ) );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		$this->cache->set( $key, $data, $this->cache_ttl() );
		return $data;
	}

	/* ---------- Read endpoints ---------- */

	public function get_league() {
		return $this->cached_get( '/api/v1/league' );
	}

	public function get_events( $from = '', $to = '' ) {
		$query = array();
		if ( $from ) {
			$query['from'] = $from;
		}
		if ( $to ) {
			$query['to'] = $to;
		}
		return $this->cached_get( '/api/v1/events', $query );
	}

	public function get_event( $id ) {
		return $this->cached_get( '/api/v1/events/' . (int) $id );
	}

	public function get_event_invites( $id ) {
		return $this->cached_get( '/api/v1/events/' . (int) $id . '/invites' );
	}

	public function get_members() {
		return $this->cached_get( '/api/v1/members' );
	}

	public function get_posts( $limit = 10, $offset = 0 ) {
		return $this->cached_get( '/api/v1/posts', array(
			'limit'  => (int) $limit,
			'offset' => (int) $offset,
		) );
	}

	public function get_rules() {
		return $this->cached_get( '/api/v1/rules' );
	}

	/* ---------- Write endpoints (no caching) ---------- */

	public function create_user( array $payload ) {
		return $this->request( 'POST', '/api/v1/users', array( 'body' => $payload ) );
	}

	public function add_invitee( $event_id, $user_id ) {
		return $this->request(
			'POST',
			'/api/v1/events/' . (int) $event_id . '/invites',
			array(
				'body' => array(
					'invitees' => array(
						array( 'user_id' => (int) $user_id ),
					),
				),
			)
		);
	}

	public function set_rsvp( $event_id, $user_id, $rsvp ) {
		$allowed = array( 'yes', 'no', 'maybe' );
		if ( ! in_array( $rsvp, $allowed, true ) ) {
			return new WP_Error( 'gnl_bad_rsvp', __( 'Invalid RSVP value.', 'gamenight-league' ) );
		}
		return $this->request(
			'PATCH',
			'/api/v1/events/' . (int) $event_id . '/invites/' . (int) $user_id,
			array( 'body' => array( 'rsvp' => $rsvp ) )
		);
	}

	/* ---------- Admin write endpoints (v0.2) ---------- */

	public function update_member_role( $user_id, $role ) {
		$allowed = array( 'member', 'manager' );
		if ( ! in_array( $role, $allowed, true ) ) {
			return new WP_Error( 'gnl_bad_role', __( 'Invalid league role.', 'gamenight-league' ) );
		}
		return $this->request(
			'PATCH',
			'/api/v1/members/' . (int) $user_id,
			array( 'body' => array( 'league_role' => $role ) )
		);
	}

	public function create_event( array $payload ) {
		return $this->request( 'POST', '/api/v1/events', array( 'body' => $payload ) );
	}

	public function update_event( $event_id, array $payload ) {
		return $this->request( 'PATCH', '/api/v1/events/' . (int) $event_id, array( 'body' => $payload ) );
	}

	public function delete_event( $event_id ) {
		return $this->request( 'DELETE', '/api/v1/events/' . (int) $event_id );
	}

	public function remove_invitee( $event_id, $user_id ) {
		return $this->request(
			'DELETE',
			'/api/v1/events/' . (int) $event_id . '/invites/' . (int) $user_id
		);
	}

	public function set_invitee_role( $event_id, $user_id, $role ) {
		$allowed = array( 'invitee', 'manager' );
		if ( ! in_array( $role, $allowed, true ) ) {
			return new WP_Error( 'gnl_bad_event_role', __( 'Invalid event role.', 'gamenight-league' ) );
		}
		return $this->request(
			'PATCH',
			'/api/v1/events/' . (int) $event_id . '/invites/' . (int) $user_id,
			array( 'body' => array( 'event_role' => $role ) )
		);
	}

	public function clear_rsvp( $event_id, $user_id ) {
		return $this->request(
			'PATCH',
			'/api/v1/events/' . (int) $event_id . '/invites/' . (int) $user_id,
			array( 'body' => array( 'rsvp' => null ) )
		);
	}
}
