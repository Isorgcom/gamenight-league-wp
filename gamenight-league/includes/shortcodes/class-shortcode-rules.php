<?php
namespace GameNight\League\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode_Rules extends Shortcode_Base {

	protected $tag = 'gamenight_rules';

	public function render( $atts ) {
		$result = $this->api->get_rules();
		if ( is_wp_error( $result ) ) {
			return $this->render_error( $result );
		}
		$rules = isset( $result['rules'] ) ? $result['rules'] : null;
		return gnl_render_template( 'rules', array( 'rules' => $rules ) );
	}
}
