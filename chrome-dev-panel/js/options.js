function saveOptions() {
	const serverUrl = document.getElementById('serverUrl').value;
	chrome.storage.sync.set({
		serverUrl: serverUrl
	}, () => {
		chrome.runtime.sendMessage('options-saved');

		const status = document.getElementById('status');
		status.textContent = 'Options saved.';
		setTimeout(function() {
			status.textContent = '';
		}, 750);
	});
}

function restoreOptions() {
	chrome.storage.sync.get({
		serverUrl: defaultServerUrl
	}, function(items) {
		document.getElementById('serverUrl').value = items.serverUrl;
	});
}

document.addEventListener('DOMContentLoaded', restoreOptions);
document.getElementById('save').addEventListener('click', saveOptions);
