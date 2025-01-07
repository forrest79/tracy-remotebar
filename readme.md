# Tracy - Remote bar

[![Latest Stable Version](https://poser.pugx.org/forrest79/tracy-remotebar/v)](//packagist.org/packages/forrest79/tracy-remotebar)
[![Monthly Downloads](https://poser.pugx.org/forrest79/tracy-remotebar/d/monthly)](//packagist.org/packages/forrest79/tracy-remotebar)
[![License](https://poser.pugx.org/forrest79/tracy-remotebar/license)](//packagist.org/packages/forrest79/tracy-remotebar)
[![Build](https://github.com/forrest79/tracy-remotebar/actions/workflows/build.yml/badge.svg?branch=master)](https://github.com/forrest79/tracy-remotebar/actions/workflows/build.yml)


## Introduction

Remote bar renders Tracy bars not to a web page but to a separate browser tab or Chrome Dev tab.

Benefits:
- you can see Tracy bar from non-HTML request (API)
- you can see Tracy bar for cli
- you can see old Tracy bar
- exceptions in AJAX calls are also rendered remotely, and you can still use the source page without refreshing it

[![Watch the video how remote bars work](https://github.com/forrest79/tracy-remotebar/raw/master/tracy-remotebar.gif)](https://www.youtube.com/watch?v=QlfuULJbgFw)

[Watch the video how remote bars work on YouTube](https://www.youtube.com/watch?v=ELMyJ9pygCk)

Comments, bug reports, PRs and ideas are welcome!


## Installation

To use this extension, require it in [Composer](https://getcomposer.org/):

```
composer require --dev forrest79/tracy-remotebar
```

> Use this extension only in your DEV environment! Using in a production environment is possible, but at your own risk.


## How does it work?

Remote bars uses a running standalone HTTP server that collects bars and also have a simple HTML interface to show them.

Classic Tracy can't handle this, so there is a little bit of hacking. Two files from the original Tracy are changed (and saved in `/tmp`) and loaded instead of
the original one. This is made with Composer files autoloading, and this package needs to be autoloaded before Tracy, and it is, because the package name
`Forrest79\Tracy-RemoteBar` is in the alphabet before `Tracy\Tracy`. Also, there can't be `require: Tracy\Tracy` in our `composer.json`, this will load original Tracy
before our package.

> I'm not updating Tracy files directly in the `vendor` directory because of the projects with commited vendor.

HTML of bars is collected and send to the server where it is saved to the file and simple client loads HTML this files, and renders it to the separate iframes. 

This package should be only in `require-dev` section, so on the production, you still have the original Tracy.

> I hope the original Tracy will be updated in a way, that this hack will be no longer needed...


## How to use it?

There must be server running. You can use PHP internal HTTP server via `vendor/bin/run-tracy-remote-bar-server [port=7979] [ip=0.0.0.0]`
(this will run server on all interfaces on port 7979 - to see the bars, open http://127.0.0.1:7979) or you can create nginx/Apache virtual
host pointed to `src/Server/public`.

This is the sample configuration for nginx:

```
server {
        listen 80;

        server_name tracy.test;

        root <...>/vendor/forrest79/tracy-remotebar/src/Server/public;
        index index.php;

        client_max_body_size 100M;

        location / {
                try_files $uri $uri/ /index.php$is_args$args;
        }

        location ~* \.php$ {
                include snippets/fastcgi-php.conf;

                fastcgi_pass 127.0.0.1:9000;
                fastcgi_param DOCUMENT_ROOT $document_root;
                fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        }
}
```

Use the correct path in the `root`. You need also host `tracy.test` pointing to your server, and then you can open in your browser `http://tracy.test`.

In your application DI configuration, add this:

```yaml
extensions:
    tracyRemoteBar: Forrest79\TracyRemoteBar\Bridges\Nette\TracyRemoteBarExtension

tracyRemoteBar:
    serverUrl: http://127.0.0.1:7979 # or http://tracy.test
```

And that's it. Refresh your app page or run something from the cli, and you should see bar in the client page.

If not, there is a log file `tracy-remote-bar` in your logs directory, where you can see what is wrong.

You can also activate remote rendering manually:

```php
Forrest79\TracyRemoteBar\Remote::setServerUrl('http://127.0.0.1:7979'); // or http://tracy.test
``` 

And there are also some useful methods:

```php
Forrest79\TracyRemoteBar\Remote::setCurlTimeouts(...); // if your server is slow, you can adjust cURL timeouts...
Forrest79\TracyRemoteBar\Remote::dispatchBars(); // this is usefull for long running services in cli - calling this immediately send bars to the server and you can do it many times during one execution (just be aware that some bars can grow because they are not reset)
```


## Developer extension

There is a simple developer extension (tested in Chrome):

- open `Manage extensions`
- activate developer mode
- click `Load unpacked`
- choose `vendor/forrest79/tracy-remotebar/chrome-dev-panel`

Now you have a new tab in your developer tools. In the settings, enter the correct server URL.


## Example app

In this repository (not in the package installed via composer) is a simple example application where you can test everything this
package can handle.

Run `composer update` in `example/app` directory (this probably won't run on Windows, because of symlinks).

Use internal PHP HTTP server via `example/run-app [port=8000] [ip=0.0.0.0]` or create nginx/Apache virtual host: 

Sample nginx configuration for host `tracy.app.test` (use the correct `root` path):

```
server {
	listen 80;

	server_name tracy.app.test;

	root <...>/example/app/public;

	index index.php;

	client_max_body_size 100M;

	location / {
		try_files $uri $uri/ /index.php$is_args$args;
	}

	location ~* \.php$ {
		include snippets/fastcgi-php.conf;

		fastcgi_pass 127.0.0.1:9000;
		fastcgi_param DOCUMENT_ROOT $document_root;
		fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
	}
}
```

Default server URL is `http://127.0.0.1:7979`, to change it, create file `example/app/config/local.neon` with:

```yaml
parameters:
    remoteServerUrl: http://tracy.test
```
