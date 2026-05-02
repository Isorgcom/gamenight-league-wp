<?php
namespace GameNight\League\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode_Posts extends Shortcode_Base {

	protected $tag = 'gamenight_posts';

	protected function defaults() {
		return array(
			'limit'  => 10,
			'offset' => 0,
		);
	}

	public function render( $atts ) {
		$atts   = shortcode_atts( $this->defaults(), $atts, $this->tag );
		$result = $this->api->get_posts( (int) $atts['limit'], (int) $atts['offset'] );
		if ( is_wp_error( $result ) ) {
			return $this->render_error( $result );
		}
		$posts = isset( $result['posts'] ) && is_array( $result['posts'] ) ? $result['posts'] : array();
		return gnl_render_template( 'posts-list', array( 'posts' => $posts, 'atts' => $atts ) );
	}
}
