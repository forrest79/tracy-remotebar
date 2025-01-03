document.addEventListener('DOMContentLoaded', () => {
	const bars = document.querySelector('#bars');

	let errorCount = 0;
	let lastId = 0;

	const clear = () => {
		lastId = 0;
		bars.innerHTML = '';
	};

	const checkNewBar = () => {
		fetch(url + '/api/').then((response) => {
			if (response.ok) {
				return response.text();
			}

			return Promise.reject(response);
		}).then((data) => {
			const indexes = data.toString().split('-', 2);
			const firstIdOnServer = parseInt(indexes[0], 10);
			const lastIdOnServer = parseInt(indexes[1], 10);

			if (firstIdOnServer > lastId) {
				lastId = firstIdOnServer - 1;
			}

			process(lastIdOnServer);

			onSuccess();
			errorCount = 0;
		}).catch(() => {
			process(0);

			errorCount++
			if (errorCount >= 3) {
				onError();
				errorCount = 0;
			}
		});
	};

	const process = (lastIdOnServer) => {
		if (lastIdOnServer > lastId) {
			for (let id = lastId + 1; id <= lastIdOnServer; id++) {
				addNewBar(id);
				lastId = id;
			}
		}
		setTimeout(() => checkNewBar(), 2000);
	};

	const addNewBar = (id) => {
		const iframe = document.createElement('iframe');
		iframe.setAttribute('src', url + '/api/?id=' + id);

		bars.prepend(iframe);
	};

	checkNewBar();

	// --- //

	document.getElementById('clear').addEventListener('click', () => {
		if (!confirm('Really?')) {
			return;
		}

		fetch(url + '/api/', {
			method: 'DELETE',
		}).then((response) => {
			if (response.ok) {
				clear();
			}
		});
	});

	// --- //

	document.addEventListener('mousemove', (event) => {
		if (event.target.tagName === 'IFRAME') {
			if (document.querySelector('iframe.locked') !== null) {
				return;
			}

			document.querySelectorAll('iframe').forEach((el) => {
				if (el === event.target) {
					el.classList.add('big');
				} else {
					el.classList.remove('big');
				}
			});
		}
	});

	// --- //

	document.addEventListener('click', (event) => {
		if (event.target.tagName === 'IFRAME') {
			document.querySelectorAll('iframe').forEach((el) => {
				if (el === event.target) {
					if (el.classList.contains('locked')) {
						el.classList.remove('locked');
					} else {
						el.classList.add('locked');
						el.classList.add('big');
					}
				} else {
					el.classList.remove('locked');
					el.classList.remove('big');
				}
			});
		}
	});
});
