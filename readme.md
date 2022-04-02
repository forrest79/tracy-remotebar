# Tracy - RemoteBar (proof of concept)

Tracy bars (and AJAX bluescreens) are saved to the remote server (as rendered HTML) and displayed at RemoteTracy app (simple web page) or Chrome Dev panel.

The most advantage is, with this you can use Tracy bars also on API, cli, etc. Also PHP SESSION is not started for this setup.

> IMPORTANT! With this, bars are not rendered on page.

## How to use it

- to enabled remote rendering just set `Debugger::$remoteServerUrl` with correct server URL (for example `http://127.0.0.1:7979`)
   - or use setting via DI:

```yaml
tracy:
    remoteServerUrl: 'http://127.0.0.1:7979'
```

- then open URL `http://127.0.0.1:7979` and you will all your Tracy Bars (and AJAX errors) rendered

> IMPORTANT! replace `127.0.0.1` with your local IP address or server name

- to run test remote server, just use `run-remote-server` batch (this will use integrated PHP server on the port `7979` and all available IP addresses)

> HINT: you can also set nginx/Apache virtual host - pointing to `remote-tracy/src/Remote/public` with PHP (min verion 7.4) support. 

- in long running cli scripts, you can manually dispatch bars calling `Debugger::remoteDispatchBars()`

## Sample app

To see all the features, there is simple sample Nette application. Run it with `run-sample-app` at port `8080`. You can see Tracy bar for all types (web page, redirects, AJAX, API, cli...).

Before running, just update correct server URL in `sample-app/config/local.neon`.
