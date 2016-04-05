<?php

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
					<?php if($meta["director"] != "") { ?>
					Directed by <?php echo $meta["director"]; ?>
					<?php } ?>
				</p>
				<div class="entry-summary">
					<?php if($meta["thumbnailimage"]) { ?>						
						<img src="<?php echo $meta["thumbnailimage"]; ?>" class="thumbnail alignleft" />
					<?php } else { 
						$large_image_url = wp_get_attachment_image_src(get_post_thumbnail_id(), 'large');
						echo '<a href="' . $large_image_url[0] . '" title="' . the_title_attribute( 'echo=0' ) . '" rel="lightbox">';
						the_post_thumbnail('medium',array('class' => 'alignleft')); 
						echo '</a>';
					} ?>
					<?php the_content(); ?>
				</div>
				<div class="entry-meta">
					<?php 
						$rtid = $meta["rtid"];
						$imdbid = $meta["imdbid"];
						if($rtid || $imdbid) { ?>
						<p class="pj-movies-viewmore">
							Seen on <?php the_time('F j, Y'); ?> 
							<span class="meta-sep">|</span>
							View more at: 
								<?php if($rtid) { ?>
									<a href="http://www.rottentomatoes.com/m/<?php echo $rtid; ?>" rel="external">Rotten Tomatoes</a><?php 
								} 
								if($rtid && $imdbid) { ?>, <?php }?>
								<?php if($imdbid) { ?>
									<a href="http://www.imdb.com/title/tt<?php echo $imdbid; ?>" rel="external">IMDB</a>
								<?php } ?>
							<?php if ( get_the_terms(get_the_ID(), "movie_categories") ) : ?>
								<span class="meta-sep">|</span>
								<span class="cat-links">
								<?php printf( __( '<span class="%1$s">Posted in</span> %2$s', '' ), 'entry-utility-prep entry-utility-prep-cat-links', get_the_term_list(get_the_ID(), "movie_categories", ' ', ', ', ' ' ) ); ?>
								</span>
							<?php endif; ?>
							
						</p>
					<?php } ?>

				</div>
			</div>
			<?php /*
			<script type="application/ld+json">
				{
				  "@context" : "http://schema.org",
				  "@type" : "Review",
				  "author" : {
				    "@type" : "Person",
				    "name" : "<?php the_author(); ?>",
				    "sameAs" : "<?php the_author_url(); ?>"
				  },
				  "datePublished" : "<?php the_date("c"); ?>",
				  "description" : "<?php strip_tags(get_the_excerpt(true)); ?>",
				  "itemReviewed" : {
				    "@type" : "Movie",
				    "name" : "<?php the_title(); ?>",
				    "sameAs" : "http://www.imdb.com/title/tt<?php echo $imdbid; ?>/",
				    "director" : {
				      "@type" : "Person",
				      "name" : "<?php echo $meta["director"]; ?>"
				    }
				  },
				  "publisher" : {
				    "@type" : "Organization",
				    "name" : "<?php the_author(); ?>"
				  },
				  "reviewRating" : {
				    "@type" : "Rating",
				    "worstRating" : 1,
				    "bestRating" : 5,
				    "ratingValue" : <?php echo $meta["personalscore"]; ?>
				  },
				  "url" : "<?php the_permalink(); ?>"
				}
			</script>	
			*/ ?>

			<?php endwhile; ?>

		<?php else : ?>

			<?php get_template_part( 'loop-error' ); ?>

		<?php endif; ?>

		<?php comments_template(); ?>

	</div>
<?php get_footer(); // Loads the footer.php template. ?>

