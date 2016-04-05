<?php
/*
Plugin Name: Movie Review
Plugin URI: http://www.codeandkill.com
Description: Movie Review plugin for WordPress 3.0 and above.
Author: Patrick Jones
Version: 1.0
Author URI: http://www.codeandkill.com
*/

class PJMovieReviews {
	protected $rt_apikey = "kc4wwhw58rhbcxx7y94dcs52";

	function PJMovieReviews() {
		$plugin_dir = basename(dirname(__FILE__));
		$lang_dir = realpath($plugin_dir . DIRECTORY_SEPARATOR . "lang");
		load_plugin_textdomain( 'pj-movies', false, $lang_dir );


		// Register Post Type		
		$args = array(
			'label' => _x("Movies", "label", "pj-movies"),
			'labels' => array(
				'add_new_item' => _x("Add New Movie Review", "add_new_item", "pj-movies"),
				'edit_item' => _x("Edit Movie Review", "edit_item", "pj-movies"),
				'new_item' => _x("New Movie Review", "new_item", "pj-movies"),
				'view_item' => _x("View Movie Review", "view_item", "pj-movies"),
				'search_items' => _x("Search Movie Reviews", "search_items", "pj-movies"),
				'not_found' => _x("No Movie Reviews Found", "not_found", "pj-movies"),
				'not_found_in_trash' => _x("No Movie Reviews found in Trash", "not_found_in_trash", "pj-movies")
			),
			'singular_label' => _x("Movie", "singular_label", "pj-movies"),
			'public' => true,
			'show_ui' => true,
			'capability_type' => 'post',
			'has_archive' => true,
			'menu_icon' => 'dashicons-video-alt3',
			'hierarchical' => false,
			'rewrite' => array("slug" => "movies"),
			'supports' => array('title', 'excerpt', 'editor', 'thumbnail', 'comments', 'revisions')
		);
		register_post_type( 'movie' , $args );
		$this->register_post_type_archives('movie', 'movies');

		register_taxonomy(  
			'movie_categories',  
			'movie',
			array(  
				'hierarchical' => true,  
				'label' => _x('Categories', 'taxonomy label', "pj-movies"),  
				'query_var' => true,  
				'rewrite' => array('slug' => 'movie-categories', 'hierarchical' => true)  
			)
		);

		add_action('save_post', array(&$this, 'save_pjmovie_meta'));  
		add_action("admin_init",array(&$this, "admin_init")); 
		add_filter("manage_edit-movie_columns", array(&$this, "edit_columns"));
		add_action("manage_posts_custom_column", array(&$this, "custom_columns"));
		add_filter("manage_edit-movie_sortable_columns", array(&$this, 'movies_sort'));
		add_filter("template_include", array(&$this, 'template_include'), 1 );
		add_action( 'wp_print_scripts', array(&$this, 'enqueue_my_scripts') );
		add_action( 'wp_print_styles', array(&$this, 'enqueue_my_styles') );
		add_action( 'admin_enqueue_scripts', array(&$this, 'enqueue_admin_scripts') );
		add_action( 'admin_print_styles', array(&$this, 'enqueue_admin_styles') );
		add_action( 'load-edit.php', array(&$this, 'edit_load') );
		add_action('wp_ajax_pjmovie_search', array(&$this, 'search_callback'));
		add_action('wp_head', array(&$this, 'noindex_movies'));

		add_theme_support( 'post-thumbnails', array('movie'));
		flush_rewrite_rules();
	}
	function noindex_movies () {
		if(get_post_type(get_the_ID()) == 'movie') {
			echo '<meta name="robots" content="noindex">';
		}
	}
	function register_post_type_archives( $post_type, $base_path = '' ) {
		global $wp_rewrite;
		if ( !$base_path ) {
			$base_path = $post_type;
		}
		$rules = $wp_rewrite->generate_rewrite_rules($base_path);
		$rules[$base_path.'/?$'] = 'index.php?paged=1';
		foreach ( $rules as $regex=>$redirect ) {
			if ( strpos($redirect, 'attachment=') == FALSE ) {
				$redirect .= '&post_type='.$post_type;
				if (  0 < preg_match_all('@\$([0-9])@', $redirect, $matches) ) {
					for ( $i=0 ; $i < count($matches[0]) ; $i++ ) {
						$redirect = str_replace($matches[0][$i], '$matches['.$matches[1][$i].']', $redirect);
					}
				}
			}
			add_rewrite_rule($regex, $redirect, 'top');
		}
	}

	function search_callback() {
		$searchterm = urlencode($_POST['searchterm']);

		$rturl = "http://api.rottentomatoes.com/api/public/v1.0/movies.json?apikey=".$this->rt_apikey."&q=".$searchterm."&page_limit=10";
		$response = wp_remote_get($rturl);
		if(200 == $response['response']['code']) {
			echo $response['body'];
		}else{
			// There was a problem
			// TODO: Add better error handling
			echo "{total:0,movies:[]}";
		}

		die(); // this is required to return a proper result
	}

	function edit_load() {
		add_filter( 'request', array(&$this, 'column_orderby') );
	}

	function enqueue_my_styles() {
		wp_register_style( 'pj-movies-style', plugins_url( '/css/pj-movies.css', __FILE__ ), array(), '20121224', 'all' ); 
		wp_enqueue_style ('pj-movies-style');
	}
	function enqueue_my_scripts() {
		wp_register_script( 'pj-movies-script', plugins_url( '/js/pj-movies.js', __FILE__ ) ); 
		wp_enqueue_script('pj-movies-script');
	}
	function enqueue_admin_styles() {
		wp_register_style( 'pj-movies-admin-style', plugins_url( '/css/pj-movies-admin.css', __FILE__ ), array(), '20121224', 'all' ); 
		wp_enqueue_style ('pj-movies-admin-style');
	}
	function enqueue_admin_scripts() {
		wp_register_script( 'pj-movies-admin-script', plugins_url( '/js/pj-movies-admin.js', __FILE__ ) ); 
		wp_enqueue_script('pj-movies-admin-script');
	}


	function template_include( $template_path ) {
		if ( get_post_type() == 'movie' ) {
			if ( is_single() ) {
				// checks if the file exists in the theme first,
				// otherwise serve the file from the plugin
				if ( $theme_file = locate_template( array ( 'single-pj-movie.php' ) ) ) {
					$template_path = $theme_file;
				} else {
					$template_path = plugin_dir_path( __FILE__ ) . '/single-pj-movie.php';
				}
			}else if ( is_archive() ) {
				if ( $theme_file = locate_template( array ( 'archive-pj-movie.php' ) ) ) {
					$template_path = $theme_file;
				} else {
					$template_path = plugin_dir_path( __FILE__ ) . '/archive-pj-movie.php';
				}
			}
		}
		return $template_path;
	}

	/**
	* Setup columns for listing
	*/
	function edit_columns($columns) {
		$columns = array(
			"cb" => "<input type=\"checkbox\" />",
			"title" => _x("Movie Title", "column label", "pj-movies"),
			"description" => _x("Description", "column label", "pj-movies"),
			"releaseyear" => _x("Release Year", "column label", "pj-movies"),
			"personalscore" => _x("Personal Score", "column label", "pj-movies"),
			"category" => _x("Categories", "column label", "pj-movies"),
			"date" => _x("Date", "column label", "pj-movies")
		);
		if($_GET["mode"] != "excerpt") {
			unset($columns["description"]);
		}
		return $columns;
	}

	function movies_sort($columns) {
		$custom = array(
			'releaseyear' => 'releaseyear',
			'personalscore' => 'personalscore'
		);
		return wp_parse_args($custom, $columns);
	}

	function column_orderby( $vars ) {
		// TODO: Revisit this and make sure column sorting is working fine

		return $vars;
	}

	function custom_columns($column){
		global $post;
		$meta = get_post_meta($post->ID,'_pjmovie_meta',TRUE); 
		switch ($column)
		{
			case "description":
				the_excerpt();
				break;
			case "releaseyear":
				echo $meta["releaseyear"];
				break;
			case "personalscore":
				echo $meta["personalscore"];
				break;
			case "category":
				$cats = get_the_terms($post->ID, 'movie_categories');
				$cnames = array();
				foreach($cats as $cat) {
					array_push($cnames, $cat->name);
				}
				echo implode($cnames, ", ");
				break;
		}
	}


	/**
	* Perform admini init functions
	*/
	function admin_init(){ 
		// Register the meta box
		add_meta_box("movieInfo-meta", "Movie Details", array(&$this, "meta_options"), "movie", "side", "core");
	}
 
	/**
	* Display the meta box
	*/
	function meta_options() { 
		global $post;

		/**
		* Use nonce for verification
		*/
		wp_nonce_field( plugin_basename( __FILE__ ), 'pjmovie_noncename' );

		/**
		* Get the product data
		*/
		$meta = get_post_meta($post->ID,'_pjmovie_meta',TRUE); 
?>
<p>
	<label for="pjmovie_meta[rtid]"><?php echo _x("Rotten Tomatoes ID", "form label", "pj-movies") ?>:</label><br />
	<input name="pjmovie_meta[rtid]" id="pjmovie_meta[rtid]" value="<?php echo $meta['rtid']; ?>" onchange="document.getElementById('pjmovie_processrtid').checked=true;" /> 
	<input name="pjmovie_processrtid" id="pjmovie_processrtid" type="checkbox" title="<?php echo _x("Process Rotten Tomatoes ID", "form label", "pj-movie") ?>" />

	<button class="button pj-movies-rtsearchbutton">Search</button>
	<div id="pj-movie-rtsearchmodal" title="Rotten Tomatoes ID Lookup" >
		<form id="pj-movie-rtsearchform">
			<input name="rt-search-name" class="rt-search-name" />
			<button name="rt-search-submit" class="rt-search-submit">Submit</button>
		</form>
		<div class="rt-search-results"></div>
	</div>
	<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" />
</p>
<p>
	<label for="pjmovie_meta[imdbid]"><?php echo _x("IMDB ID", "form label", "pj-movies") ?>:</label><br />
	<input name="pjmovie_meta[imdbid]" id="pjmovie_meta[imdbid]" value="<?php echo $meta['imdbid']; ?>" style="width:99%" />
</p>
<p>
	<?php 
		$personal_score = $meta["personalscore"]; 
		if($personal_score == "") $personal_score = 0;
	?>
	<label for="pjmovie_meta[personalscore]"><?php echo _x("Personal Review Score", "form label", "pj-movies") ?>:</label> <span id="pjmovie_personal_score_display"><?php echo $personal_score; ?></span><br />
	<input name="pjmovie_meta[personalscore]" id="pjmovie_meta[personalscore]" value="<?php echo $personal_score; ?>" type="range" step="0.5" min="0" max="5"
		onchange="document.getElementById('pjmovie_personal_score_display').innerHTML = this.value;" style="width:99%" />	
</p>
<p>
	<label for="pjmovie_meta[version]"><?php echo _x("Version", "form label", "pj-movies") ?>:</label><br />
	<input name="pjmovie_meta[version]" id="pjmovie_meta[version]" value="<?php echo $meta['version']; ?>" style="width:99%" />
</p>
<p>
	<label for="pjmovie_meta[director]"><?php echo _x("Director", "form label", "pj-movies") ?>:</label><br />
	<input name="pjmovie_meta[director]" id="pjmovie_meta[director]" value="<?php echo $meta['director']; ?>" style="width:99%" />
</p>
<p>
	<label for="pjmovie_meta[releaseyear]"><?php echo _x("Release Year", "form label", "pj-movies") ?>:</label><br />
	<input name="pjmovie_meta[releaseyear]" id="pjmovie_meta[releaseyear]" value="<?php echo $meta['releaseyear']; ?>" type="number" min="1900" max="2100" maxlength="4" style="width:99%" />
</p>
<p>
	<label for="pjmovie_meta[thumbnailimage]"><?php echo _x("Thumbnail Image", "form label", "pj-movies") ?>:</label><br />
	<input name="pjmovie_meta[thumbnailimage]" id="pjmovie_meta[thumbnailimage]" value="<?php echo $meta["thumbnailimage"]; ?>" style="width:99%" /><br />
	<?php if( isset($meta["thumbnailimage"]) ) { ?>
		<span><?php echo _x("Preview", "form label", "pj-movies") ?>:</span><br />
		<img src="<?php echo $meta["thumbnailimage"]; ?>" id="pjmovie-thumbnail-preview" />
	<?php } ?>
</p>


<?php 
	}

	/**
	* Save the meta information
	*/
	function save_pjmovie_meta( $post_id ) {
		/**
		* Verify if this is an auto save routine. 
		* If it is our form has not been submitted, so we dont want to do anything
		*/
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;

		/**
		* Verify this came from the our screen and with proper authorization,
		* because save_post can be triggered at other times
		*/
		if ( !wp_verify_nonce( $_POST['pjmovie_noncename'], plugin_basename( __FILE__ ) ) )
			return;

		/**
		* Check permissions
		*/
		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ) )
				return;
		}
		else {
			if ( !current_user_can( 'edit_post', $post_id ) )
				return;
		}

		/**
		* OK, we're authenticated: we need to find and save the data
		*/
		$meta = get_post_meta($post_id,'_pjmovie_meta',TRUE); 
		$mydata = $_POST['pjmovie_meta'];
		$mydata["rtid"] = trim($mydata["rtid"]);
		if(!empty($mydata["rtid"]) && $_POST["pjmovie_processrtid"]) {
			// The Rotten Tomatoes ID changed, let's fetch some data for the user
			$rturl = "http://api.rottentomatoes.com/api/public/v1.0/movies/".$mydata["rtid"].".json?apikey=".$this->rt_apikey;
			$response = wp_remote_get($rturl);
			if(200 == $response['response']['code']) {
				$json = json_decode( $response['body'] );
				$mydata['releaseyear'] = $json->year;
				$mydata['imdbid'] = $json->alternate_ids->imdb;
				$mydata['director'] = '';
				$mydata['thumbnailimage'] = $json->posters->profile;
				if(isset($json->abridged_directors)) {
					foreach($json->abridged_directors as $d) {
						if($mydata['director'] != "") 
							$mydata["director"] .= ", ";
						$mydata["director"] .= $d->name;
					}
				}
			}
		}

		update_post_meta($post_id, '_pjmovie_meta', $mydata);
	}

}
// Add Shortcode
function pjones_movies_moviedata_shortcode() {
	$args = array (
		'post_type'		=> 'movie',
		'post_status'	=> 'published',
		'nopaging'		=> true
	);

	// The Query
	$query = new WP_Query( $args );

	$cat_scores = array();
	$cat_nums = array();

	foreach($query->posts as $post) {
		$cats = get_the_terms($post->ID, 'movie_categories');
		$meta = get_post_meta($post->ID,'_pjmovie_meta',TRUE); 
		$personalscore = $meta['personalscore'];

		foreach($cats as $cat) {
			$catname = html_entity_decode($cat->name);

			/* Nums */
			if(isset($cat_nums[$catname])) {
				$cat_nums[$catname]++;
			}else{
				$cat_nums[$catname] = 1;
			}			
			/* Scores */
			if(isset($cat_scores[$catname])) {
				$cat_scores[$catname][] = $personalscore;
			}else{
				$cat_scores[$catname] = array();
				$cat_scores[$catname][] = $personalscore;
			}
		}
	}
	arsort($cat_nums);
	$encodedCatNums = json_encode($cat_nums);

	foreach($cat_scores as $key => $val) {
		$cat_scores[$key] = round(array_sum($val) / count($val), 2);
	}
	arsort($cat_scores);

	$cat_scores_categories = array_keys($cat_scores);
	$cat_scores_values = array_values($cat_scores);
	$cat_scores_categories_encoded = json_encode($cat_scores_categories);
	$cat_scores_values_encoded = json_encode($cat_scores_values);

	$js_highcharts = plugins_url('js/highcharts.js', __FILE__);
	$js_exporting = plugins_url('js/exporting.js', __FILE__);

	return <<<HTML
		<div id="moviepiechart-bycategory"></div>
		<div id="moviecolchart-byaverage"></div>

		<script src="$js_highcharts"></script>
		<script src="$js_exporting"></script>
		<script type="text/javascript">
			
			(function($){
				var category_counts= $encodedCatNums ;
				var catCountsRestructured = [];
				for(var key in category_counts) {
					catCountsRestructured[catCountsRestructured.length] = [key, category_counts[key]];
				}

				$(function(){
					$('#moviepiechart-bycategory').highcharts({
						chart: {
							type: 'pie',
							options3d: { enabled: true, alpha: 45 }
					        },
					        title: {
					            text: 'Most Watched Movies by Category'
					        },
					        plotOptions: {
					            pie: {
							innerSize: 100,
							depth: 45,
							cursor: 'pointer',
							dataLabels: { enabled: false},
							showInLegend: true
					            }
					        },
				        	series: [{
					            name: 'Movies Watched',
					            data: catCountsRestructured 
					        }]
					});
					$('#moviecolchart-byaverage').highcharts({
						chart: {
							type: 'column'
						},
						title: {
							text: "Highest Average Rating by Category"
						},
						xAxis: {
							categories: $cat_scores_categories_encoded,
							crosshair: true
						},
						yAxis: {
							min: 0,
							max: 5,
							title: { text: 'Average Rating' }
						},
						plotOptions: {
							column: {
								pointPadding: 0.2,
								borderWidth: 0
							}
						},
						series: [{ name: 'Average Rating', data: $cat_scores_values_encoded }]
					});
				});
			})(jQuery);
		</script>
HTML;

}
add_shortcode( 'moviedata', 'pjones_movies_moviedata_shortcode' );

add_action("init", "PJMovieReviewsInit");
function PJMovieReviewsInit() { global $pjmr; $pjmr = new PJMovieReviews(); }


?>
