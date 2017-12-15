<?php get_header(); ?>
<?php $alone_sidebar_position = function_exists( 'fw_ext_sidebars_get_current_position' ) ? fw_ext_sidebars_get_current_position() : 'right'; ?>
<div class="no-header-image"></div>
<section class="bt-default-page bt-404-page bt-main-row <?php alone_get_content_class( 'main', $alone_sidebar_position ); ?>">
	<div class="container">
		<div class="row">

			<div class="content-area <?php alone_get_content_class( 'content', $alone_sidebar_position ); ?>">
				<div class="wrap-entry-404 text-center">
					<h1 class="entry-title fw-title-404"><?php esc_html_e( '404', 'alone' ); ?></h1>
					<h3 class="entry-title fw-title-404-sub"><?php esc_html_e( 'Ohh! Page not found', 'alone' ); ?></h3>
					<div class="page-content">
						<p><?php esc_html_e( 'It looks like nothing was found at this location. Maybe try a search?', 'alone' ); ?></p>
						<?php get_search_form(); ?>
						<?php $page = get_page_by_title('Contact'); ?>
						<p><?php esc_html_e( 'Do you have a question? Feel free to ask us using', 'alone' ) ?> <a href="<?php echo esc_attr(get_permalink($page->ID)); ?>"><?php esc_html_e('our form', 'alone') ?></a>.</p>
						<p><?php esc_html_e('Or go back to', 'alone') ?> <a href="<?php echo esc_attr(get_home_url()); ?>"><?php esc_html_e('the homepage', 'alone') ?></a>.</p>
					</div><!-- .page-content -->
				</div>
			</div><!-- /.content-area-->

			<?php get_sidebar(); ?>
		</div><!-- /.row-->
	</div><!-- /.container-->
</section>
<?php get_footer(); ?>