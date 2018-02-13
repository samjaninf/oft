<?php get_header('404'); ?>
<?php $alone_sidebar_position = function_exists( 'fw_ext_sidebars_get_current_position' ) ? fw_ext_sidebars_get_current_position() : 'right'; ?>
<div class="no-header-image"></div>
<section class="bt-default-page bt-404-page bt-main-row <?php alone_get_content_class( 'main', $alone_sidebar_position ); ?>">
	<div class="container">
		<div class="row">

			<div class="content-area <?php alone_get_content_class( 'content', $alone_sidebar_position ); ?>">
				<div class="wrap-entry-404 text-center">
					<h1 class="entry-title fw-title-404"><?php _e( 'Error 404', 'oft' ); ?></h1>
					<h3 class="entry-title fw-title-404-sub"><?php _e( 'Oeps! Hier liep iets mis.', 'oft' ); ?></h3>
					<div class="page-content">
						<p><?php _e( 'We konden de pagina die je opvroeg niet vinden. Misschien kan een kleine zoektocht raad brengen?', 'oft' ); ?></p>
						<?php get_search_form(); ?>
						<?php $page = get_page_by_path('contact'); ?>
						<p><?php printf( __( 'Heb je een vraag voor ons? Stel die dan gerust via <a href="%s">ons formulier</a>.', 'oft' ), get_permalink($page->ID) ); ?></p>
						<p><?php printf( __( 'Of keer terug naar <a href="%s">de startpagina</a>.', 'oft' ), get_home_url() ); ?></p>
					</div><!-- .page-content -->
				</div>
			</div><!-- /.content-area-->

			<?php get_sidebar(); ?>
		</div><!-- /.row-->
	</div><!-- /.container-->
</section>
<?php get_footer(); ?>