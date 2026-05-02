<?php
namespace GameNight\League\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode_League extends Shortcode_Base {

	protected $tag = 'gamenight_league';

	public function render( $atts ) {
		$league = $this->api->get_league();
		if ( is_wp_error( $league ) ) {
			return $this->render_error( $league );
		}
		return gnl_render_template( 'league', array( 'league' => $league ) );
	}
}
