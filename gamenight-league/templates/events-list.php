<?php
/**
 * Template: list of events.
 *
 * Available vars: $events (array), $atts (array)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<section class="gnl gnl-events">
	<?php if ( empty( $events ) ) : ?>
		<p class="gnl-events__empty"><?php esc_html_e( 'No upcoming events.', 'gamenight-league' ); ?></p>
	<?php else : ?>
		<ul class="gnl-events__list">
			<?php foreach ( $events as $event ) : ?>
				<li class="gnl-event-card">
					<div class="gnl-event-card__when">
						<?php echo esc_html( gnl_format_datetime( $event['start_at'] ?? '' ) ); ?>
					</div>
					<div class="gnl-event-card__title">
						<?php echo esc_html( $event['title'] ?? '' ); ?>
					</div>
					<?php if ( ! empty( $event['description'] ) ) : ?>
						<div class="gnl-event-card__desc">
							<?php echo esc_html( $event['description'] ); ?>
						</div>
					<?php endif; ?>
					<div class="gnl-event-card__rsvp">
						<?php
						printf(
							/* translators: 1: yes count, 2: maybe count, 3: no count */
							esc_html__( 'Yes %1$d · Maybe %2$d · No %3$d', 'gamenight-league' ),
							(int) ( $event['rsvp_yes_count'] ?? 0 ),
							(int) ( $event['rsvp_maybe_count'] ?? 0 ),
							(int) ( $event['rsvp_no_count'] ?? 0 )
						);
						?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
