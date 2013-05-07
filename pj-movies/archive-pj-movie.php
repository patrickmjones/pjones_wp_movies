<?php
get_header(); ?>

	<div class="hfeed content">

			<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
			<?php $meta = get_post_meta($post->ID,'_pjmovie_meta',TRUE); ?>
			<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<h1 class="entry-title">
					<a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?> <?php if($meta['releaseyear']) echo "(".$meta["releaseyear"].")"; ?></a>
					<span class="pj-movies-rating"><span class="pj-movies-stars"><?php echo $meta["personalscore"]; ?></span></span>
				</h1>
				<p class="byline">
					Directed by <?php echo $meta["director"]; ?>
				</p>
				<div class="entry-summary">
					<?php if($meta["thumbnailimage"]) { ?>
						<img src="<?php echo $meta["thumbnailimage"]; ?>" class="thumbnail alignleft" />
					<?php } else { 
						the_post_thumbnail('thumbnail', array('class' => 'alignleft')); 
					} ?>
					<?php the_excerpt(); ?>
				</div>
				<div class="entry-meta">
					<?php 
						$rtid = $meta["rtid"];
						$imdbid = $meta["imdbid"];
						if($rtid || $imdbid) { ?>
						<p class="pj-movies-viewmore">
							View more at: 
								<?php if($rtid) { ?>
									<a href="http://www.rottentomatoes.com/m/<?php echo $rtid; ?>" rel="external">Rotten Tomatoes</a><?php 
								} 
								if($rtid && $imdbid) { ?>, <?php }?>
								<?php if($imdbid) { ?>
									<a href="http://www.imdb.com/title/tt<?php echo $imdbid; ?>" rel="external">IMDB</a>
								<?php } ?>
							<span class="meta-sep">|</span>
							<?php if ( count( get_the_terms(get_the_ID(), "movie_categories") ) ) : ?>
								<span class="cat-links">
								<?php printf( __( '<span class="%1$s">Posted in</span> %2$s', '' ), 'entry-utility-prep entry-utility-prep-cat-links', get_the_term_list(get_the_ID(), "movie_categories", ' ', ', ', ' ' ) ); ?>
								</span>
							<?php endif; ?>
						</p>
					<?php } ?>

				</div>
			</div>

			<?php comments_template( '', true ); ?>

			<?php endwhile; ?>
			<div class="navigation-links">
				<!-- now show the paging links -->
				<div class="alignleft"><?php previous_posts_link('Previous Entries'); ?></div>
				<div class="alignright"><?php next_posts_link('Next Entries'); ?></div>
			</div>

		<?php else: ?>

			<p class="no-data">
				<?php _e('Sorry, no page matched your criteria.', 'hybrid'); ?>
			</p><!-- .no-data -->

		<?php endif; ?>

	</div><!-- .content .hfeed -->

<?php get_footer(); ?>
