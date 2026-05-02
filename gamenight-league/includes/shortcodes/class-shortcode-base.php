<?php
/**
 * Base class for all GameNight shortcodes.
 */

namespace GameNight\League\Shortcodes;

use GameNight\League\Api_Client;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Shortcode_Base {

	/** @var Api_Client */
	protected $api;

	/** @var string Shortcode tag, e.g. "gamenight_events" */
	protected $tag = '';

	public function __construct( Api_Client $api ) {
		$this->api = $api;
	}

	public function register() {
		if ( $this->tag ) {
			add_shortcode( $this->tag, array( $this, 'render' ) );
		}
	}

	/**
	 * Default attributes for this shortcode.
	 */
	protected function defaults() {
		return array();
	}

	/**
	 * Concrete shortcodes implement render().
	 *
	 * @param array|string $atts
	 * @return string HTML
	 */
	abstract public function render( $atts );

	/**
	 * Render a friendly error/admin notice without leaking detail to public visitors.
	 */
	protected function render_error( $err ) {
		if ( $err instanceof WP_Error ) {
			return gnl_admin_only_notice( '[GameNight] ' . $err->get_error_message() );
		}
		return gnl_admin_only_notice( '[GameNight] ' . (string) $err );
	}
}
