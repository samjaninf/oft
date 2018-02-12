<?php
/**
 * The template for displaying a "No posts found" message
 */
?>
<div class="entry-content bt-content-none" itemprop="text">
	<h2 class="entry-title"><?php esc_html_e( 'Niets gevonden!', 'oft' ); ?></h2>
	<?php if ( is_search() ) : ?>
		<p><?php esc_html_e( 'We konden helaas niets vinden met die zoektermen. Probeer het eens opnieuw met een korter woord, of volg de automatische suggestie hierboven.', 'oft' ); ?></p>
		<div class="row">
			<div class="col-md-6">
				<?php get_search_form(); ?>
			</div>
		</div>
		<div class="clearfix"></div>
	<?php else : ?>
		<p><?php esc_html_e( 'Die pagina kunnen we niet vinden. Misschien kan een kleine zoektocht raad brengen?', 'oft' ); ?></p>
		<div class="row">
			<div class="col-md-6">
				<?php get_search_form(); ?>
			</div>
		</div>
		<div class="clearfix"></div>
	<?php endif; ?>
</div>
