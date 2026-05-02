<?php
/**
 * Admin view: shortcode reference with copy-to-clipboard buttons.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$shortcodes = array(
	array(
		'tag'      => 'gamenight_league',
		'desc'     => __( 'League name, description, and member count.', 'gamenight-league' ),
		'example'  => '[gamenight_league]',
		'atts'     => array(),
	),
	array(
		'tag'      => 'gamenight_events',
		'desc'     => __( 'Upcoming events with RSVP counts.', 'gamenight-league' ),
		'example'  => '[gamenight_events limit="5"]',
		'atts'     => array(
			array( 'from',      __( 'date YYYY-MM-DD', 'gamenight-league' ), __( 'today', 'gamenight-league' ),       __( 'Start of date window.', 'gamenight-league' ) ),
			array( 'to',        __( 'date YYYY-MM-DD', 'gamenight-league' ), __( 'today + 90d', 'gamenight-league' ), __( 'End of date window.', 'gamenight-league' ) ),
			array( 'limit',     __( 'integer', 'gamenight-league' ),         __( '0 (no cap)', 'gamenight-league' ),  __( 'Maximum events to render.', 'gamenight-league' ) ),
			array( 'show_past', __( 'yes / no', 'gamenight-league' ),        'no',                                    __( 'Include past events that fall in the window.', 'gamenight-league' ) ),
		),
	),
	array(
		'tag'      => 'gamenight_event',
		'desc'     => __( 'Single event detail. Pair with [gamenight_rsvp event_id="N"] on the same page for an RSVP form.', 'gamenight-league' ),
		'example'  => '[gamenight_event id="123"]',
		'atts'     => array(
			array( 'id',            __( 'integer', 'gamenight-league' ), __( 'required', 'gamenight-league' ), __( 'GameNight event id.', 'gamenight-league' ) ),
			array( 'show_invitees', __( 'yes / no', 'gamenight-league' ), 'no',                                __( 'List invitees with their RSVP status.', 'gamenight-league' ) ),
		),
	),
	array(
		'tag'      => 'gamenight_roster',
		'desc'     => __( 'League roster, with an optional "Want to join the league?" link that expands an inline join form.', 'gamenight-league' ),
		'example'  => '[gamenight_roster]',
		'atts'     => array(
			array( 'hide_pending', __( 'yes / no', 'gamenight-league' ),    'no',     __( 'Omit members who have not accepted yet.', 'gamenight-league' ) ),
			array( 'sort',         __( 'name | role | joined', 'gamenight-league' ), 'name', __( 'Sort order.', 'gamenight-league' ) ),
			array( 'show_join',    __( 'yes / no', 'gamenight-league' ),    'yes',    __( 'Append a "Want to join?" link that reveals a join form. Set to "no" to suppress.', 'gamenight-league' ) ),
		),
	),
	array(
		'tag'      => 'gamenight_posts',
		'desc'     => __( 'League posts and announcements.', 'gamenight-league' ),
		'example'  => '[gamenight_posts limit="10"]',
		'atts'     => array(
			array( 'limit',  __( 'integer', 'gamenight-league' ), '10', __( 'Maximum posts to render.', 'gamenight-league' ) ),
			array( 'offset', __( 'integer', 'gamenight-league' ), '0',  __( 'Pagination offset.', 'gamenight-league' ) ),
		),
	),
	array(
		'tag'      => 'gamenight_rules',
		'desc'     => __( 'The league\'s designated rules post.', 'gamenight-league' ),
		'example'  => '[gamenight_rules]',
		'atts'     => array(),
	),
	array(
		'tag'      => 'gamenight_rsvp',
		'desc'     => __( 'Anonymous-visitor RSVP form. Visitors enter name + email/phone + yes/maybe/no; the plugin creates them as a league member (idempotent on email/phone) and records the RSVP.', 'gamenight-league' ),
		'example'  => '[gamenight_rsvp event_id="123"]',
		'atts'     => array(
			array( 'event_id', __( 'integer', 'gamenight-league' ), __( 'required', 'gamenight-league' ), __( 'GameNight event id.', 'gamenight-league' ) ),
		),
	),
	array(
		'tag'      => 'gamenight_join',
		'desc'     => __( 'Standalone "Join the league" form. Visitors enter name + email/phone (one required); the plugin creates them as a league member (idempotent on email/phone). No event involved — useful for a sign-up page or sidebar widget.', 'gamenight-league' ),
		'example'  => '[gamenight_join]',
		'atts'     => array(),
	),
);
?>
<div class="wrap gnl-admin" data-gnl-admin-page="shortcodes">
	<h1><?php esc_html_e( 'Shortcodes', 'gamenight-league' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Drop these into any post, page, or widget. Click "Copy" next to an example to put it on your clipboard, then paste it into the editor.', 'gamenight-league' ); ?>
	</p>

	<?php foreach ( $shortcodes as $sc ) : ?>
		<div class="gnl-shortcode-card">
			<h2 class="gnl-shortcode-card__tag">
				<code>[<?php echo esc_html( $sc['tag'] ); ?>]</code>
			</h2>
			<p class="gnl-shortcode-card__desc"><?php echo esc_html( $sc['desc'] ); ?></p>

			<div class="gnl-shortcode-card__example">
				<input type="text" readonly class="gnl-copy-input" value="<?php echo esc_attr( $sc['example'] ); ?>" />
				<button type="button" class="button gnl-copy-btn" data-copy="<?php echo esc_attr( $sc['example'] ); ?>"><?php esc_html_e( 'Copy', 'gamenight-league' ); ?></button>
				<span class="gnl-copy-status" aria-live="polite"></span>
			</div>

			<?php if ( ! empty( $sc['atts'] ) ) : ?>
				<table class="widefat striped gnl-shortcode-card__atts">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Attribute', 'gamenight-league' ); ?></th>
							<th><?php esc_html_e( 'Type', 'gamenight-league' ); ?></th>
							<th><?php esc_html_e( 'Default', 'gamenight-league' ); ?></th>
							<th><?php esc_html_e( 'Description', 'gamenight-league' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $sc['atts'] as $row ) : ?>
							<tr>
								<td><code><?php echo esc_html( $row[0] ); ?></code></td>
								<td><?php echo esc_html( $row[1] ); ?></td>
								<td><?php echo esc_html( $row[2] ); ?></td>
								<td><?php echo esc_html( $row[3] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>

	<h2><?php esc_html_e( 'Theme template overrides', 'gamenight-league' ); ?></h2>
	<p>
		<?php
		/* translators: 1: template path, 2: theme example path */
		printf(
			esc_html__( 'Every shortcode renders through a PHP template you can override from your theme. Copy any file from %1$s to a folder named %2$s in your theme.', 'gamenight-league' ),
			'<code>gamenight-league/templates/</code>',
			'<code>your-theme/gamenight-league/</code>'
		);
		?>
	</p>
	<p><?php esc_html_e( 'Available templates: league.php, events-list.php, event-card.php, event-detail.php, roster.php, posts-list.php, post.php, rules.php, rsvp-form.php', 'gamenight-league' ); ?></p>
</div>
