# HtmGem

This program aims to provide access to Gemini pages through a web server.

It’s in alpha: advanced features available soon.

## Usage

Place "htmgem.php" on the root of your webserver.

Your "page.gmi" is reachable using [http://thesite/htmgem.php?directory/page.gmi] with HTML markup:

## URL Rewriting

With Nginx, you can use:

```
rewrite ^(.+\.gmi)$ /htmgem.php?url=$1 last;
```

## Install

php-mbstring is required

So the page is available at [http://thesite/htmgem.php/directory/page.gmi].
