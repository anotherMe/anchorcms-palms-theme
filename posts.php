<?php theme_include('header'); ?>

<section class="content">

	<?php if(has_tagged_posts()): ?>
		<ul class="items">
			<?php tagged_posts(); ?>
			<li>
				<article class="wrap">
					<h1>
						<a href="<?php echo article_url(); ?>" title="<?php echo article_title(); ?>"><?php echo article_title(); ?></a>
					</h1>

					<div class="content">
						<?php echo article_markdown(); ?>
					</div>

                                        <?php 
                                        $tags = get_tags_for_post(article_id());
                                        if ( count($tags) > 0 ) { 
                                        ?> 
                                        <section class="tags">    
                                            <?php foreach($tags as $tag):?>
                                            <span><?php echo $tag ?></span>
                                            <?php endforeach; ?>
                                        </section>
                                        <?php } ?>

					<footer>
						Posted <time datetime="<?php echo date(DATE_W3C, article_time()); ?>"><?php echo relative_time(article_time()); ?></time> by <?php echo article_author('real_name'); ?>.
					</footer>
				</article>
			</li>
			<?php $i = 0; while(tagged_posts()): ?>
			<?php $bg = sprintf('background: hsl(215, 28%%, %d%%);', round(((++$i / posts_per_page()) * 20) + 20)); ?>
			<li style="<?php echo $bg; ?>">
				<article class="wrap">
					<h2>
						<a href="<?php echo article_url(); ?>" title="<?php echo article_title(); ?>"><?php echo article_title(); ?></a>
					</h2>
				</article>
			</li>
			<?php endwhile; ?>
		</ul>

		<?php if(has_pagination()): ?>
		<nav class="pagination">
			<div class="wrap">
				<?php echo posts_prev(); ?>
				<?php echo posts_next(); ?>
			</div>
		</nav>
		<?php endif; ?>

	<?php else: ?>
		<p>Looks like you have some writing to do!</p>
	<?php endif; ?>

</section>

<?php theme_include('footer'); ?>