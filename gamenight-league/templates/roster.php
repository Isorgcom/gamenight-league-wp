<?php
/**
 * Template: league roster.
 *
 * Available vars: $members (array), $atts (array)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<section class="gnl gnl-roster">
	<?php if ( empty( $members ) ) : ?>
		<p class="gnl-roster__empty"><?php esc_html_e( 'No members yet.', 'gamenight-league' ); ?></p>
	<?php else : ?>
		<ul class="gnl-roster__list">
			<?php foreach ( $members as $m ) : ?>
				<li class="gnl-roster-row<?php echo ! empty( $m['pending'] ) ? ' gnl-roster-row--pending' : ''; ?>">
					<span class="gnl-roster-row__name"><?php echo esc_html( $m['display_name'] ?? '' ); ?></span>
					<?php if ( ! empty( $m['role'] ) ) : ?>
						<span class="gnl-roster-row__role"> — <?php echo esc_html( $m['role'] ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $m['pending'] ) ) : ?>
						<span class="gnl-roster-row__pending"> (<?php esc_html_e( 'pending', 'gamenight-league' ); ?>)</span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
