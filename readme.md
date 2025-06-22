# Tracy - Remote bar

[![Latest Stable Version](https://poser.pugx.org/forrest79/tracy-remotebar/v)](//packagist.org/packages/forrest79/tracy-remotebar)
[![Monthly Downloads](https://poser.pugx.org/forrest79/tracy-remotebar/d/monthly)](//packagist.org/packages/forrest79/tracy-remotebar)
[![License](https://poser.pugx.org/forrest79/tracy-remotebar/license)](//packagist.org/packages/forrest79/tracy-remotebar)
[![Build](https://github.com/forrest79/tracy-remotebar/actions/workflows/build.yml/badge.svg?branch=master)](https://github.com/forrest79/tracy-remotebar/actions/workflows/build.yml)


## Introduction

Remote bar renders Tracy bars not to a web page but to a separate browser tab or Chrome Dev tab.

Benefits:
- you can see Tracy bar from non-HTML request (API)
- you can see Tracy bar for cli (even tests)
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

Classic Tracy can't handle this, so there is a little bit of hacking (to use private functions) and code coping from the original Tracy.

By enabling this extension, bars on the original Tracy are disabled. Then bar HTML is collected in the separated shutdown handler
and send to the server where it is saved to the file and simple client loads HTML this files, and renders it to the separate iframes. 

This package should only be in the `require-dev` section, so on the production, you still have the original Tracy.

> There is created directory `tracy-remote-bar` in your system temp directory, where the HTML is saved.


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

> `client_max_body_size` is important, bar HTML code is sending via POST method in body, and the HTML can be big

Use the correct path in the `root`. You need also host `tracy.test` pointing to your server, and then you can open in your browser `http://tracy.test`.

In your application DI configuration, add this:

```yaml
extensions:
    tracyRemoteBar: Forrest79\TracyRemoteBar\Bridges\Nette\TracyRemoteBarExtension

tracyRemoteBar:
    enabled: true # default is false
    serverUrl: http://127.0.0.1:7979 # or http://tracy.test or null
    #curlConnectTimeout: 1 # default value
    #curlTimeout: 1 # default value
```

> If you're running your app and Tracy RemoveBar server on the same "filesystem", you can set `serverUrl` to null and then bars are directly saved to the data file and not transmitted via local network. 

And that's it. Refresh your app page or run something from the cli, and you should see bar in the client page.

If not, there is a log file `tracy-remote-bar.log` in your logs directory (if it's set on `Tracy\Debugger::logsDir`, otherwise simple `echo` is used), where you can see what goes wrong.

You can also activate remote rendering manually:

```php
Forrest79\TracyRemoteBar\Remote::enable('http://127.0.0.1:7979'); // or http://tracy.test
```

> `Tracy\Debugger` must be enabled before `Forrest79\TracyRemoteBar\Remote::enable()` is call, otherwise enabling is ignored. 

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
