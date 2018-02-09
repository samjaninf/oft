<?php
$post_comment_num = wp_count_comments(get_the_ID())->total_comments;
$post_view_num = alone_get_post_views(get_the_ID());
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'search-post-item' ); ?> itemscope="itemscope" itemtype="http://schema.org/BlogPosting" itemprop="blogPost">
	<div class="post-inner">
		<div class="post-type"><?php echo get_post_type(); ?></div><?php echo "Score: $post->relevance_score"; ?>
		<a href="<?php the_permalink(); ?>" class="title-link"><h4 class="title"><?php the_title(); ?></h4></a>
		<p><?php the_excerpt(); ?></p>
		<div class="extra-meta">
			<!-- post date -->
			<div class="post-date" title="<?php _e('Date', 'alone'); ?>">
			<?php the_date(); ?>
		</div>
	</div>
</div>
</article>
