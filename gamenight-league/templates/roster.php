<?php
/**
 * Template: league roster.
 *
 * Available vars: $members (array), $atts (array)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-local scope.
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

	<?php
	$show_join = isset( $atts['show_join'] ) && 'yes' === strtolower( (string) $atts['show_join'] );
	if ( $show_join ) :
		$join_form = gnl_render_template( 'join-form' );
		if ( $join_form ) :
			?>
			<div class="gnl-join-reveal" data-gnl-join-reveal>
				<button type="button" class="gnl-join-reveal__button">
					<?php esc_html_e( 'Want to join the league?', 'gamenight-league' ); ?>
				</button>
				<div class="gnl-join-reveal__slot" hidden>
					<?php echo $join_form; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template output already escaped ?>
				</div>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</section>
