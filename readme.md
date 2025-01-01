# Remote bars

Remote bars renders Tracy bars not to a web page, but to a separate browser tab or Chrome Dev tab. This allows also using bars for non-HTML requests and cli.

[![Watch the video how remote bars work](https://github.com/forrest79/tracy/raw/master/remote-bars.gif)](https://www.youtube.com/watch?v=QlfuULJbgFw)

[Watch the video how remote bars work on YouTube](https://www.youtube.com/watch?v=QlfuULJbgFw)

Remote bars uses a running standalone HTTP server that collects bars and also have a simple HTML interface to show them.

How to use rendering bars to remote server:

1. run HTTP server - use internal PHP HTTP server `php -S 0.0.0.0:7979 -t src/Remote/public` or create virtual host in your favorite web server (nginx/Apache) pointed to `src/Remote/public` and with PHP support

2. set server URL (where the HTTP server is running) in your application via `Tracy\Debugger::$remoteServerUrl` property or via extension:
```
tracy:
    remoteServerUrl: 'http://127.0.0.1:7979'
```

> Until you do this, Tracy act normally and renders bars into HTML page.

3. to see remote rendered bars open your server URL (mostly `http://127.0.0.1:7979`) in your browser or use Chrome Dev extension (open `Manage extensions`, switch `Developer mode` on, click `Load unpacked` and choose `src/chrome-dev-panel` directory - you can set custom server URL in extension options)

> `Cli` - there is one new function especially for cli - `Tracy\Debugger::dispatchBars()` - this is handy for long-running scripts, call this and bars will be sent to server immediately (you can call this repeatedly).

### How to install remote bars version instead of classic Tracy

Update your `composer.json`. Add new repository and use branch name with the version you want as `tracy\tracy` version:

```json
{
	"require": {
		"tracy/tracy": "dev-remote-bars-v2.10.8 as 2.10.8"
	},
	"repositories": [
		{
			"type": "vcs",
			"url": "https://github.com/forrest79/tracy.git"
		}
	]
}
```

And run composer update:

```shell
composer update tracy/tracy
```

### Sample nginx configuration

Instead of the internal PHP HTTP server you can use for example nginx virtual host. This is simple working configuration with HTTPS support:

```
server {
	listen 80;
	server_name tracy.local;
	return 301 https://$host$request_uri;
}

server {
	listen 443 ssl http2;

	ssl_certificate     /data/share/csfd/conf/ssl/certs/local.crt;
	ssl_certificate_key /data/share/csfd/conf/ssl/certs/local.key;

	server_name tracy.local;

	client_max_body_size 100m;

	root /var/www/project/vendor/tracy/tracy/src/Remote/public;

	location / {
		try_files $uri $uri/ /index.php$is_args$args;
	}

	location ~ \.php$ {
		try_files $uri /index.php$is_args$args;

		fastcgi_pass         127.0.0.1:9000;
		#fastcgi_pass         unix:/var/run/php/php8.1-fpm.sock;
		fastcgi_read_timeout 30s;
		fastcgi_index        index.php;

		include fastcgi_params;

		fastcgi_param DOCUMENT_ROOT $realpath_root;
		fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
	}
}

```

Update it with the correct domain (replace `tracy.local` in `server_name`), path to the `vendor/tracy/tracy/src/Remote/public` (replate `root` parameter) and SSL certificates (`ssl_certificate` and `ssl_certificate_key` parameters). Don't forget to use correct settings in `neon` too:

```
tracy:
    remoteServerUrl: 'https://tracy.local'
```
