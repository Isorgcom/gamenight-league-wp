<?php
/**
 * Template: league summary card.
 *
 * Available vars: $league
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<section class="gnl gnl-league">
	<h2 class="gnl-league__name"><?php echo esc_html( $league['name'] ?? '' ); ?></h2>
	<?php if ( ! empty( $league['description'] ) ) : ?>
		<p class="gnl-league__desc"><?php echo esc_html( $league['description'] ); ?></p>
	<?php endif; ?>
	<?php if ( isset( $league['member_count'] ) ) : ?>
		<p class="gnl-league__meta">
			<?php
			printf(
				/* translators: %d: number of members */
				esc_html( _n( '%d member', '%d members', (int) $league['member_count'], 'gamenight-league' ) ),
				(int) $league['member_count']
			);
			?>
		</p>
	<?php endif; ?>
</section>
