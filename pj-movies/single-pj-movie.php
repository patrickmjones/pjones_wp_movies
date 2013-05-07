<?

get_header(); // Loads the header.php template. ?>

	<div id="content" class="content">

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
						the_post_thumbnail('medium',array('class' => 'alignleft')); 
					} ?>
					<?php the_content(); ?>
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

			<?php endwhile; ?>

		<?php else : ?>

			<?php get_template_part( 'loop-error' ); ?>

		<?php endif; ?>

		<?php comments_template(); ?>

	</div>
<?php get_footer(); // Loads the footer.php template. ?>

