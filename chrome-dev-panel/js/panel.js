let url = '';

const onSuccess = () => {
	console.log('onSuccess');
	serverUrlDiv.classList.add('ok');
	serverUrlDiv.classList.remove('error');
};

const onError = () => {
	console.log('onError');
	serverUrlDiv.classList.add('error');
	serverUrlDiv.classList.remove('ok');
};

const serverUrlDiv = document.getElementById('serverUrl');

function reloadOptions() {
	chrome.storage.sync.get({
		serverUrl: defaultServerUrl
	}, (items) => {
		url = items.serverUrl;
		serverUrlDiv.textContent = url;
		serverUrlDiv.classList.remove('ok');
		serverUrlDiv.classList.remove('error');
	});
}

reloadOptions();

document.addEventListener('DOMContentLoaded', () => {
	chrome.runtime.onMessage.addListener((request) => {
		if (request === 'options-saved') {
			reloadOptions();
		}
		return true;
	});

	document.querySelector('button#options').addEventListener('click', function() {
		if (chrome.runtime.openOptionsPage) {
			chrome.runtime.openOptionsPage();
		} else {
			window.open(chrome.runtime.getURL('html/options.html'));
		}
	});
});
