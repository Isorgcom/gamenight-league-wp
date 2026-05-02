<?php
namespace GameNight\League\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode_Events extends Shortcode_Base {

	protected $tag = 'gamenight_events';

	protected function defaults() {
		return array(
			'from'      => '',
			'to'        => '',
			'limit'     => 0,
			'show_past' => 'no',
		);
	}

	public function render( $atts ) {
		$atts   = shortcode_atts( $this->defaults(), $atts, $this->tag );
		$result = $this->api->get_events( $atts['from'], $atts['to'] );
		if ( is_wp_error( $result ) ) {
			return $this->render_error( $result );
		}
		$events = isset( $result['events'] ) && is_array( $result['events'] ) ? $result['events'] : array();

		if ( 'yes' !== strtolower( (string) $atts['show_past'] ) ) {
			$now    = time();
			$events = array_values( array_filter( $events, static function ( $e ) use ( $now ) {
				$end = ! empty( $e['end_at'] ) ? $e['end_at'] : ( $e['start_at'] ?? '' );
				if ( ! $end ) {
					return true;
				}
				try {
					$dt = new \DateTimeImmutable( $end );
					return $dt->getTimestamp() >= $now;
				} catch ( \Exception $ex ) {
					return true;
				}
			} ) );
		}

		$limit = (int) $atts['limit'];
		if ( $limit > 0 ) {
			$events = array_slice( $events, 0, $limit );
		}

		return gnl_render_template( 'events-list', array( 'events' => $events, 'atts' => $atts ) );
	}
}
