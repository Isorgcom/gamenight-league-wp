<?php
/**
 * Settings page under Settings → GameNight League.
 */

namespace GameNight\League;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {

	const PAGE_SLUG    = 'gamenight-league';
	const OPTION_GROUP = 'gamenight_league';
	const NONCE_TEST   = 'gnl_test_connection';

	/** @var Api_Client */
	private $api;

	public function __construct( Api_Client $api ) {
		$this->api = $api;
	}

	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_gnl_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	public function add_menu() {
		add_options_page(
			__( 'GameNight League', 'gamenight-league' ),
			__( 'GameNight League', 'gamenight-league' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			Api_Client::OPT_KEY,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_key' ),
				'default'           => '',
			)
		);
		register_setting(
			self::OPTION_GROUP,
			Api_Client::OPT_BASE,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_base_url' ),
				'default'           => GNL_API_DEFAULT_BASE,
			)
		);
		register_setting(
			self::OPTION_GROUP,
			Api_Client::OPT_TTL,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_ttl' ),
				'default'           => 60,
			)
		);
	}

	public function sanitize_key( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		// If the form posted the masked placeholder back unchanged, keep the existing value.
		if ( '' !== $value && false !== strpos( $value, '••••' ) ) {
			return (string) get_option( Api_Client::OPT_KEY, '' );
		}
		return sanitize_text_field( $value );
	}

	public function sanitize_base_url( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		if ( '' === $value ) {
			return GNL_API_DEFAULT_BASE;
		}
		$value = esc_url_raw( $value );
		return $value ?: GNL_API_DEFAULT_BASE;
	}

	public function sanitize_ttl( $value ) {
		$value = (int) $value;
		return $value > 0 ? $value : 60;
	}

	public function enqueue_admin_assets( $hook ) {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_script( 'jquery' );
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$key       = (string) get_option( Api_Client::OPT_KEY, '' );
		$masked    = $key ? str_repeat( '•', max( 0, strlen( $key ) - 4 ) ) . substr( $key, -4 ) : '';
		$base      = (string) get_option( Api_Client::OPT_BASE, GNL_API_DEFAULT_BASE );
		$ttl       = (int) get_option( Api_Client::OPT_TTL, 60 );
		$ajax_url  = admin_url( 'admin-ajax.php' );
		$test_nonce = wp_create_nonce( self::NONCE_TEST );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'GameNight League', 'gamenight-league' ); ?></h1>
			<p><?php esc_html_e( 'Connect this site to your league on gamenight.poker. The API key is bound to one league; mint a key from your league\'s page → API tab.', 'gamenight-league' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( self::OPTION_GROUP ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="gnl_api_key"><?php esc_html_e( 'API key', 'gamenight-league' ); ?></label></th>
						<td>
							<input
								name="<?php echo esc_attr( Api_Client::OPT_KEY ); ?>"
								id="gnl_api_key"
								type="password"
								class="regular-text"
								autocomplete="new-password"
								value="<?php echo esc_attr( $masked ); ?>"
							/>
							<p class="description"><?php esc_html_e( 'Mint or revoke keys from your league page on gamenight.poker.', 'gamenight-league' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="gnl_api_base_url"><?php esc_html_e( 'API base URL', 'gamenight-league' ); ?></label></th>
						<td>
							<input
								name="<?php echo esc_attr( Api_Client::OPT_BASE ); ?>"
								id="gnl_api_base_url"
								type="url"
								class="regular-text"
								value="<?php echo esc_attr( $base ); ?>"
								placeholder="<?php echo esc_attr( GNL_API_DEFAULT_BASE ); ?>"
							/>
							<p class="description"><?php
								/* translators: %s: default API base URL */
								echo esc_html( sprintf( __( 'Defaults to %s. Change only if you run a self-hosted GameNight instance.', 'gamenight-league' ), GNL_API_DEFAULT_BASE ) );
							?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="gnl_cache_ttl"><?php esc_html_e( 'Cache TTL (seconds)', 'gamenight-league' ); ?></label></th>
						<td>
							<input
								name="<?php echo esc_attr( Api_Client::OPT_TTL ); ?>"
								id="gnl_cache_ttl"
								type="number"
								min="1"
								step="1"
								class="small-text"
								value="<?php echo esc_attr( (string) $ttl ); ?>"
							/>
							<p class="description"><?php esc_html_e( 'How long to cache API responses. The API itself sets a 60-second cache hint.', 'gamenight-league' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<hr />
			<h2><?php esc_html_e( 'Test connection', 'gamenight-league' ); ?></h2>
			<p>
				<button type="button" class="button" id="gnl-test-connection"><?php esc_html_e( 'Run test', 'gamenight-league' ); ?></button>
				<span id="gnl-test-result" style="margin-left:8px;"></span>
			</p>
			<script>
			jQuery(function($){
				$('#gnl-test-connection').on('click', function(){
					var $out = $('#gnl-test-result').text(<?php echo wp_json_encode( __( 'Testing…', 'gamenight-league' ) ); ?>);
					$.post(<?php echo wp_json_encode( $ajax_url ); ?>, {
						action: 'gnl_test_connection',
						_wpnonce: <?php echo wp_json_encode( $test_nonce ); ?>
					}).done(function(resp){
						if (resp && resp.success) {
							$out.css('color','green').text('✓ ' + resp.data.message);
						} else {
							$out.css('color','#c00').text('✗ ' + (resp && resp.data ? resp.data.message : 'Unknown error'));
						}
					}).fail(function(xhr){
						var msg = 'Request failed.';
						if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
							msg = xhr.responseJSON.data.message;
						} else if (xhr && xhr.status) {
							msg = 'Request failed (HTTP ' + xhr.status + ').';
						}
						$out.css('color','#c00').text('✗ ' + msg);
					});
				});
			});
			</script>
		</div>
		<?php
	}

	public function ajax_test_connection() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'gamenight-league' ) ) );
		}
		check_admin_referer( self::NONCE_TEST );

		$league = $this->api->get_league();
		if ( is_wp_error( $league ) ) {
			wp_send_json_error( array( 'message' => $league->get_error_message() ) );
		}

		$name = isset( $league['name'] ) ? (string) $league['name'] : '(unnamed)';
		$id   = isset( $league['id'] ) ? (int) $league['id'] : 0;
		wp_send_json_success(
			array(
				/* translators: 1: league name, 2: league id */
				'message' => sprintf( __( 'Connected to league "%1$s" (id %2$d).', 'gamenight-league' ), $name, $id ),
			)
		);
	}
}
