<?php
namespace GameNight\League\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode_Event extends Shortcode_Base {

	protected $tag = 'gamenight_event';

	protected function defaults() {
		return array(
			'id'             => 0,
			'show_invitees'  => 'no',
		);
	}

	public function render( $atts ) {
		$atts = shortcode_atts( $this->defaults(), $atts, $this->tag );
		$id   = (int) $atts['id'];
		if ( $id <= 0 ) {
			return $this->render_error( __( 'Missing event id attribute.', 'gamenight-league' ) );
		}
		$event = $this->api->get_event( $id );
		if ( is_wp_error( $event ) ) {
			return $this->render_error( $event );
		}
		$invitees = array();
		if ( 'yes' === strtolower( (string) $atts['show_invitees'] ) ) {
			$inv = $this->api->get_event_invites( $id );
			if ( ! is_wp_error( $inv ) && isset( $inv['invitees'] ) ) {
				$invitees = $inv['invitees'];
			}
		}
		return gnl_render_template( 'event-detail', array( 'event' => $event, 'invitees' => $invitees, 'atts' => $atts ) );
	}
}
