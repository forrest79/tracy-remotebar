$(function(){

	// default settings:
	// window.TracyAutoRefresh = true;
	// window.TracyMaxAjaxRows = 3;

	var resultFetch = document.getElementById('result-fetch');

	document.querySelectorAll('button.fetch').forEach((button) => {
		button.addEventListener('click', () => {
			resultFetch.innerText = 'loading…';

			fetch(
				'/homepage/ajax/' + (button.classList.contains('error') ? '?error=1' : ''),
				{
					method: 'GET',
					headers: {
						'Content-Type': 'application/json',
						'X-Requested-With': 'XMLHttpRequest'
						// 'X-Tracy-Ajax': Tracy.getAjaxHeader()}, // use when auto-refresh is disabled via window.TracyAutoRefresh = false;
					}
				}
			)
			.then( response => response.json() )
			.then( json => resultFetch.innerText = 'loaded: ' + json )
			.catch( e => resultFetch.innerText = 'error' )
		});
	});

	// ---

	var jqxhr;

	$('button.jquery').click(function() {
		$('#result-jquery').text('loading…');

		if (jqxhr) {
			jqxhr.abort();
		}

		jqxhr = $.ajax({
			url: '/homepage/ajax/' + ($(this).hasClass('error') ? '?error=1' : ''),
			dataType: 'json',
			jsonp: false,
			// headers: {'X-Tracy-Ajax': Tracy.getAjaxHeader()}, // use when auto-refresh is disabled via window.TracyAutoRefresh = false;
		}).done(function(data) {
			$('#result-jquery').text('loaded: ' + data);

		}).fail(function() {
			$('#result-jquery').text('error');
		});
	});

});
