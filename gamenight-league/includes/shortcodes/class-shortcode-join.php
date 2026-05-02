<?php
namespace GameNight\League\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode_Join extends Shortcode_Base {

	protected $tag = 'gamenight_join';

	public function render( $atts ) {
		return gnl_render_template( 'join-form', array() );
	}
}
