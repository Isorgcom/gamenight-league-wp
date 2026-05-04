<?php
/**
 * Template: single event detail.
 *
 * Available vars: $event (array), $invitees (array), $atts (array)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-local scope.
?>
<section class="gnl gnl-event">
	<h3 class="gnl-event__title"><?php echo esc_html( $event['title'] ?? '' ); ?></h3>
	<p class="gnl-event__when">
		<strong><?php esc_html_e( 'When:', 'gamenight-league' ); ?></strong>
		<?php echo esc_html( gnl_format_datetime( $event['start_at'] ?? '' ) ); ?>
		<?php if ( ! empty( $event['end_at'] ) ) : ?>
			– <?php echo esc_html( gnl_format_datetime( $event['end_at'] ) ); ?>
		<?php endif; ?>
	</p>
	<?php if ( ! empty( $event['description'] ) ) : ?>
		<p class="gnl-event__desc"><?php echo esc_html( $event['description'] ); ?></p>
	<?php endif; ?>
	<p class="gnl-event__rsvp">
		<?php
		printf(
			/* translators: 1: yes count, 2: maybe count, 3: no count */
			esc_html__( 'Yes %1$d · Maybe %2$d · No %3$d', 'gamenight-league' ),
			(int) ( $event['rsvp_yes_count'] ?? 0 ),
			(int) ( $event['rsvp_maybe_count'] ?? 0 ),
			(int) ( $event['rsvp_no_count'] ?? 0 )
		);
		?>
	</p>
	<?php if ( ! empty( $invitees ) ) : ?>
		<h4 class="gnl-event__invitees-heading"><?php esc_html_e( 'Invitees', 'gamenight-league' ); ?></h4>
		<ul class="gnl-event__invitees">
			<?php foreach ( $invitees as $inv ) : ?>
				<li class="gnl-invitee gnl-invitee--<?php echo esc_attr( $inv['rsvp'] ?? 'none' ); ?>">
					<?php echo esc_html( $inv['display_name'] ?? '' ); ?>
					<?php if ( ! empty( $inv['rsvp'] ) ) : ?>
						<span class="gnl-invitee__rsvp">— <?php echo esc_html( $inv['rsvp'] ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
