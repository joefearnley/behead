# behead

Behead is a super basic WordPress theme used to make it headless. 

### Configurations

There are two wordpress configs that need to be added to get the most our this theme:

#### Allowed Domains

To add some level of security, you can add the domains you want the REST API to accept requests from:

```
$allowed_domains = array(
    '', // domain names as strings
);
define( 'BEHEAD_ALLOWED_DOMAINS', serialize($allowed_domains));
```

#### Redirect URL

Since this theme is headless, there is no index/home/front page that is typically part of a WordPress theme, so add the URL of the site that is used as the front end.

```
define( 'BEHEAD_REDIRECT_URL', '');
```
