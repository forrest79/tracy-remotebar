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
			processLastId(parseInt(data.toString(), 10));

			onSuccess();
			errorCount = 0;
		}).catch(() => {
			processLastId(0);

			errorCount++
			if (errorCount >= 3) {
				onError();
				errorCount = 0;
			}
		});
	};

	const processLastId = (id) => {
		if (id > lastId) {
			for (let i = lastId + 1; i <= id; i++) {
				addNewBar(i);
			}
		}
		setTimeout(() => checkNewBar(), 2000);
	};

	const addNewBar = (id) => {
		const iframe = document.createElement('iframe');
		iframe.setAttribute('src', url + '/api/?id=' + id);

		bars.prepend(iframe);

		lastId = id;
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
