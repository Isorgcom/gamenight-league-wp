<?php
namespace GameNight\League\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode_Roster extends Shortcode_Base {

	protected $tag = 'gamenight_roster';

	protected function defaults() {
		return array(
			'hide_pending' => 'no',
			'sort'         => 'name',
		);
	}

	public function render( $atts ) {
		$atts    = shortcode_atts( $this->defaults(), $atts, $this->tag );
		$members = $this->api->get_members();
		if ( is_wp_error( $members ) ) {
			return $this->render_error( $members );
		}
		if ( ! is_array( $members ) ) {
			$members = array();
		}

		if ( 'yes' === strtolower( (string) $atts['hide_pending'] ) ) {
			$members = array_values( array_filter( $members, static function ( $m ) {
				return empty( $m['pending'] );
			} ) );
		}

		switch ( strtolower( (string) $atts['sort'] ) ) {
			case 'role':
				usort( $members, static function ( $a, $b ) {
					return strcmp( (string) ( $a['role'] ?? '' ), (string) ( $b['role'] ?? '' ) );
				} );
				break;
			case 'joined':
				usort( $members, static function ( $a, $b ) {
					return strcmp( (string) ( $b['joined_at'] ?? '' ), (string) ( $a['joined_at'] ?? '' ) );
				} );
				break;
			case 'name':
			default:
				usort( $members, static function ( $a, $b ) {
					return strcasecmp( (string) ( $a['display_name'] ?? '' ), (string) ( $b['display_name'] ?? '' ) );
				} );
		}

		return gnl_render_template( 'roster', array( 'members' => $members, 'atts' => $atts ) );
	}
}
