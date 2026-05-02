<?php
/**
 * Singleton bootstrap. Wires services together and registers hooks.
 */

namespace GameNight\League;

use GameNight\League\Shortcodes\Shortcode_League;
use GameNight\League\Shortcodes\Shortcode_Events;
use GameNight\League\Shortcodes\Shortcode_Event;
use GameNight\League\Shortcodes\Shortcode_Roster;
use GameNight\League\Shortcodes\Shortcode_Posts;
use GameNight\League\Shortcodes\Shortcode_Rules;
use GameNight\League\Shortcodes\Shortcode_Rsvp;
use GameNight\League\Admin\Admin_Menu;
use GameNight\League\Admin\Admin_Assets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {

	/** @var Plugin */
	private static $instance;

	/** @var Api_Client */
	public $api;

	/** @var Cache */
	public $cache;

	/** @var Settings */
	public $settings;

	/** @var Rest_Controller */
	public $rest;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		load_plugin_textdomain( 'gamenight-league', false, dirname( plugin_basename( GNL_FILE ) ) . '/languages' );

		$this->cache    = new Cache();
		$this->api      = new Api_Client( $this->cache );
		$this->settings = new Settings( $this->api );
		$this->rest     = new Rest_Controller( $this->api, $this->cache );

		$this->settings->register();
		$this->rest->register();

		( new Shortcode_League( $this->api ) )->register();
		( new Shortcode_Events( $this->api ) )->register();
		( new Shortcode_Event( $this->api ) )->register();
		( new Shortcode_Roster( $this->api ) )->register();
		( new Shortcode_Posts( $this->api ) )->register();
		( new Shortcode_Rules( $this->api ) )->register();
		( new Shortcode_Rsvp( $this->api ) )->register();

		if ( is_admin() ) {
			( new Admin_Menu( $this->api ) )->register();
			( new Admin_Assets() )->register();
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
	}

	/**
	 * Enqueue plugin CSS only if a GameNight shortcode is present on the current page.
	 */
	public function maybe_enqueue_assets() {
		if ( ! is_singular() ) {
			return;
		}
		$post = get_post();
		if ( ! $post ) {
			return;
		}
		$tags = array(
			'gamenight_league',
			'gamenight_events',
			'gamenight_event',
			'gamenight_roster',
			'gamenight_posts',
			'gamenight_rules',
			'gamenight_rsvp',
		);
		$found = false;
		foreach ( $tags as $tag ) {
			if ( has_shortcode( $post->post_content, $tag ) ) {
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			return;
		}
		wp_enqueue_style( 'gamenight-league', GNL_URL . 'assets/css/gamenight-league.css', array(), GNL_VERSION );

		if ( has_shortcode( $post->post_content, 'gamenight_rsvp' ) ) {
			wp_enqueue_script( 'gamenight-league-rsvp', GNL_URL . 'assets/js/rsvp.js', array(), GNL_VERSION, true );
			wp_localize_script(
				'gamenight-league-rsvp',
				'GNLRsvp',
				array(
					'endpoint' => esc_url_raw( rest_url( 'gamenight/v1/rsvp' ) ),
					'nonce'    => wp_create_nonce( 'wp_rest' ),
					'strings'  => array(
						'submitting' => __( 'Submitting…', 'gamenight-league' ),
						'thanks'     => __( 'Thanks — your RSVP was recorded.', 'gamenight-league' ),
						'error'      => __( 'Something went wrong. Please try again.', 'gamenight-league' ),
					),
				)
			);
		}
	}
}
