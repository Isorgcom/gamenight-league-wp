<?php
/**
 * Template: league rules post.
 *
 * Available vars: $rules (array|null)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<section class="gnl gnl-rules">
	<?php if ( empty( $rules ) ) : ?>
		<p class="gnl-rules__empty"><?php esc_html_e( 'No rules posted yet.', 'gamenight-league' ); ?></p>
	<?php else : ?>
		<h3 class="gnl-rules__title"><?php echo esc_html( $rules['title'] ?? '' ); ?></h3>
		<div class="gnl-rules__body">
			<?php echo wp_kses_post( $rules['content_html'] ?? '' ); ?>
		</div>
	<?php endif; ?>
</section>
