<?php
/**
 * Template: league posts list.
 *
 * Available vars: $posts (array), $atts (array)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-local scope.
?>
<section class="gnl gnl-posts">
	<?php if ( empty( $posts ) ) : ?>
		<p class="gnl-posts__empty"><?php esc_html_e( 'No posts yet.', 'gamenight-league' ); ?></p>
	<?php else : ?>
		<?php foreach ( $posts as $p ) : ?>
			<article class="gnl-post">
				<h3 class="gnl-post__title"><?php echo esc_html( $p['title'] ?? '' ); ?></h3>
				<p class="gnl-post__meta">
					<?php
					printf(
						/* translators: 1: author name, 2: formatted date */
						esc_html__( 'By %1$s on %2$s', 'gamenight-league' ),
						esc_html( $p['author_display_name'] ?? '' ),
						esc_html( gnl_format_datetime( $p['created_at'] ?? '' ) )
					);
					?>
				</p>
				<div class="gnl-post__body">
					<?php echo gnl_kses_rich( $p['content_html'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</article>
		<?php endforeach; ?>
	<?php endif; ?>
</section>
