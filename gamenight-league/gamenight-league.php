<?php
/**
 * Plugin Name:       GameNight League
 * Plugin URI:        https://github.com/Isorgcom/gamenight-league-wp
 * Description:       Display your GameNight league roster, events, posts, and accept RSVPs on any WordPress site. Powered by the GameNight API.
 * Version:           0.4.4
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            GameNight
 * Author URI:        https://gamenight.poker/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gamenight-league
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GNL_VERSION', '0.4.4' );
define( 'GNL_FILE', __FILE__ );
define( 'GNL_PATH', plugin_dir_path( __FILE__ ) );
define( 'GNL_URL', plugin_dir_url( __FILE__ ) );
define( 'GNL_API_DEFAULT_BASE', 'https://gamenight.poker' );

require_once GNL_PATH . 'includes/helpers.php';
require_once GNL_PATH . 'includes/class-cache.php';
require_once GNL_PATH . 'includes/class-api-client.php';
require_once GNL_PATH . 'includes/class-settings.php';
require_once GNL_PATH . 'includes/class-rest-controller.php';
require_once GNL_PATH . 'includes/shortcodes/class-shortcode-base.php';
require_once GNL_PATH . 'includes/shortcodes/class-shortcode-league.php';
require_once GNL_PATH . 'includes/shortcodes/class-shortcode-events.php';
require_once GNL_PATH . 'includes/shortcodes/class-shortcode-event.php';
require_once GNL_PATH . 'includes/shortcodes/class-shortcode-roster.php';
require_once GNL_PATH . 'includes/shortcodes/class-shortcode-posts.php';
require_once GNL_PATH . 'includes/shortcodes/class-shortcode-rules.php';
require_once GNL_PATH . 'includes/shortcodes/class-shortcode-rsvp.php';
require_once GNL_PATH . 'includes/shortcodes/class-shortcode-join.php';
require_once GNL_PATH . 'includes/admin/class-admin-menu.php';
require_once GNL_PATH . 'includes/admin/class-admin-assets.php';
require_once GNL_PATH . 'includes/class-plugin.php';

add_action( 'plugins_loaded', static function () {
	\GameNight\League\Plugin::instance()->init();
} );
