<?php
/**
 * Plugin-wide helper functions.
 *
 * All functions in this file are prefixed with `gnl_` (the plugin's two-letter
 * shorthand for "GameNight League"). Plugin Check's NonPrefixedFunctionFound
 * sniff doesn't recognize a 3-character prefix as "long enough" but the
 * Plugin Review Team accepts 3+ character prefixes per its public guidelines.
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Locate a template, allowing themes to override by placing a file at
 * {theme}/gamenight-league/{name}.php.
 *
 * @param string $name Template basename without extension.
 * @return string Absolute path to the template file, or empty string if missing.
 */
function gnl_locate_template( $name ) {
	$name     = sanitize_file_name( $name );
	$relative = 'gamenight-league/' . $name . '.php';
	$found    = locate_template( $relative );
	if ( $found ) {
		return $found;
	}
	$fallback = GNL_PATH . 'templates/' . $name . '.php';
	return file_exists( $fallback ) ? $fallback : '';
}

/**
 * Render a template into a string with extracted data.
 *
 * @param string $name Template basename.
 * @param array  $data Variables exposed to the template.
 * @return string
 */
function gnl_render_template( $name, array $data = array() ) {
	$path = gnl_locate_template( $name );
	if ( ! $path ) {
		return '';
	}
	ob_start();
	extract( $data, EXTR_SKIP );
	include $path;
	return (string) ob_get_clean();
}

/**
 * Render an admin-only inline notice that anonymous visitors will not see.
 *
 * @param string $message
 * @return string
 */
function gnl_admin_only_notice( $message ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return '';
	}
	return '<div class="gnl-admin-notice" style="border:1px dashed #c00;padding:8px;margin:8px 0;font-family:sans-serif;font-size:13px;color:#c00;">'
		. esc_html( $message )
		. '</div>';
}

/**
 * Sanitize "rich" HTML coming back from the GameNight API.
 *
 * wp_kses_post() strips inline style="" attributes by default, which destroys
 * the formatting the API preserves for posts and rules (Word-pasted color,
 * background, font-size, etc.). The API sanitizes content server-side before
 * storage, so we trust it enough to keep style/class/bgcolor on common content
 * tags. Themers can override the allowlist with the `gnl_allowed_html` filter.
 *
 * @param string $html Raw HTML from the API.
 * @return string Sanitized HTML.
 */
function gnl_kses_rich( $html ) {
	if ( ! is_string( $html ) || '' === $html ) {
		return '';
	}
	static $allowed = null;
	if ( null === $allowed ) {
		$base = wp_kses_allowed_html( 'post' );

		// Add `style`, `class`, `bgcolor`, `width`, `height` to every tag that
		// already accepts attrs. Word-pasted HTML uses a wide attribute surface.
		$extra = array(
			'style'   => true,
			'class'   => true,
			'bgcolor' => true,
			'width'   => true,
			'height'  => true,
			'align'   => true,
			'valign'  => true,
		);
		foreach ( $base as $tag => $attrs ) {
			if ( is_array( $attrs ) ) {
				$base[ $tag ] = array_merge( $attrs, $extra );
			}
		}

		// Tags wp_kses_allowed_html('post') doesn't include but Word-pasted HTML
		// commonly produces. Allowlist them with the same attrs as <p>/<span>.
		$content_tags = array( 'span', 'font', 'header', 'footer', 'section', 'article', 'aside', 'figure', 'figcaption' );
		foreach ( $content_tags as $tag ) {
			if ( ! isset( $base[ $tag ] ) ) {
				$base[ $tag ] = array(
					'class'   => true,
					'id'      => true,
					'style'   => true,
					'bgcolor' => true,
					'lang'    => true,
					'dir'     => true,
				);
			}
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- 'gnl_' is the plugin's documented 3-char prefix.
		$allowed = apply_filters( 'gnl_allowed_html', $base );
	}

	// WordPress's safecss_filter_attr() (run on every `style` attribute via
	// wp_kses) has its own CSS-property allowlist. The shorthand `background:`
	// is NOT on it (only `background-color`), and several others Word commonly
	// emits get stripped too. Hook the filter just for the duration of this
	// call so we keep them.
	$css_filter = static function ( $props ) {
		return array_merge( $props, array(
			'background',
			'background-image',
			'background-position',
			'background-repeat',
			'background-size',
			'background-attachment',
			'background-clip',
			'background-origin',
			'mso-yfti-irow',
			'mso-yfti-firstrow',
			'mso-yfti-lastrow',
			'page-break-before',
			'page-break-after',
			'page-break-inside',
		) );
	};
	// WordPress's safecss_filter_attr() also rejects CSS values that look like
	// function calls — e.g. `background: rgb(...)` is stripped because `rgb()`
	// isn't on its var()/calc()/min()/max() allowlist. Override the per-value
	// safety check to allow rgb/rgba/hsl/hsla as well.
	$css_value_filter = static function ( $allow_css, $css_test_string ) {
		if ( $allow_css ) {
			return $allow_css;
		}
		// Strip rgb()/rgba()/hsl()/hsla() from the test string and re-check.
		$stripped = preg_replace( '/\b(?:rgb|rgba|hsl|hsla)\([^()]*\)/', '', (string) $css_test_string );
		return ! preg_match( '%[\\\(&=}]|/\*%', $stripped );
	};

	add_filter( 'safe_style_css', $css_filter );
	add_filter( 'safecss_filter_attr_allow_css', $css_value_filter, 10, 2 );
	$out = wp_kses( $html, $allowed );
	remove_filter( 'safecss_filter_attr_allow_css', $css_value_filter, 10 );
	remove_filter( 'safe_style_css', $css_filter );
	return $out;
}

/**
 * Format an ISO-8601 timestamp using the site's timezone and date/time format.
 *
 * @param string $iso Either YYYY-MM-DD or full ISO-8601 with Z suffix.
 * @return string
 */
function gnl_format_datetime( $iso ) {
	if ( empty( $iso ) ) {
		return '';
	}
	try {
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $iso ) ) {
			$dt = new DateTimeImmutable( $iso . ' 00:00:00', new DateTimeZone( 'UTC' ) );
			return wp_date( get_option( 'date_format' ), $dt->getTimestamp() );
		}
		$dt = new DateTimeImmutable( $iso );
		return wp_date(
			get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
			$dt->getTimestamp()
		);
	} catch ( \Exception $e ) {
		return $iso;
	}
}
