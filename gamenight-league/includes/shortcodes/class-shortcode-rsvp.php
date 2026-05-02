<?php
namespace GameNight\League\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode_Rsvp extends Shortcode_Base {

	protected $tag = 'gamenight_rsvp';

	protected function defaults() {
		return array(
			'event_id' => 0,
		);
	}

	public function render( $atts ) {
		$atts     = shortcode_atts( $this->defaults(), $atts, $this->tag );
		$event_id = (int) $atts['event_id'];
		if ( $event_id <= 0 ) {
			return $this->render_error( __( 'Missing event_id attribute.', 'gamenight-league' ) );
		}
		return gnl_render_template( 'rsvp-form', array( 'event_id' => $event_id ) );
	}
}
