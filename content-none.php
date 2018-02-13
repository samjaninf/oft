<?php
/**
 * The template for displaying a "No posts found" message
 */
?>
<div class="entry-content bt-content-none" itemprop="text">
	<h2 class="entry-title"><?php esc_html_e( 'Niets gevonden!', 'oft' ); ?></h2>
	<?php if ( is_search() ) : ?>
		<p><?php echo __( 'We konden helaas niets vinden met die zoektermen.', 'oft' ).'<br>'.__( 'Probeer het eens opnieuw met een korter woord, of volg de automatische suggestie hierboven.', 'oft' ); ?></p>
		<div class="row">
			<div class="col-md-12" style="text-align: center;">
				<p>&nbsp;</p>
				<?php get_search_form(); ?>
			</div>
		</div>
		<div class="clearfix"></div>
	<?php else : ?>
		<p><?php echo __( 'Die pagina kunnen we niet vinden.', 'oft' ).'<br>'.__( 'Misschien kan een kleine zoektocht raad brengen?', 'oft' ); ?></p>
		<div class="row">
			<div class="col-md-12" style="text-align: center;">
				<p>&nbsp;</p>
				<?php get_search_form(); ?>
			</div>
		</div>
		<div class="clearfix"></div>
	<?php endif; ?>
</div>
