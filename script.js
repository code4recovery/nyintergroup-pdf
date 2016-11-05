jQuery(function($){
	$('#search').on('submit', function(){
		if (window.ga && ga.create) {
			var value = $('#search input[name="query"]').val();
			ga('send', 'event', 'Search', 'query', value);
			//console.log('search recorded "' + value + '"');
		} else {
			//console.log('google analytics not installed');
		}
	});
});
