(function($){
	$(function(){
		// Hide modal initially
		$("#pj-movie-rtsearchmodal").hide();
		// Wire up modal open
		$(".pj-movies-rtsearchbutton").click(function(event){
			event.preventDefault();
			jQuery("#pj-movie-rtsearchmodal").dialog({dialogClass: "ui-dialog-pjmovie", width: 650, height: 500 });
		});
		// Performing search
		$(".rt-search-submit").click(function(event){
			event.preventDefault();
			var data = {
				action: 'pjmovie_search',
				searchterm: jQuery('.rt-search-name').val()
			};
			jQuery.post(ajaxurl, data, function(response) {
				$('.rt-search-results').html('');
				// Building html for search results
				for(var i=0; i<response.movies.length; i++) {
					var m = response.movies[i];
					$('<a href="#" title="' + m.title + ' (' + m.year + ')' + '" rel="' + m.id + '" class="rt-search-result-link"><img src="' + m.posters.profile + '" class="rt-search-result" /></a>').appendTo('.rt-search-results');
				}
				$('<p>'+response.total+' total results</p>').appendTo('.rt-search-results');
			}, 'json');
		});
		// Setting up click action on search results which will set appropriate fields and close the search modal
		$(".rt-search-result-link").live("click", function(event) {
			event.preventDefault();
			var rtid = jQuery(this).attr('rel');
			$("#pjmovie_meta\\[rtid\\]").val(rtid);
			$('#pjmovie_processrtid')[0].checked=true;
			$('.rt-search-results').html('');
			$("#pj-movie-rtsearchmodal").dialog('close');				
		});
	});
})(jQuery);
