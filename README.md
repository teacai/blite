# bLite
Hackable minimalist blogging platform with PHP and SQLite
Deploy a custom website with content management in just 6 files:
  1. .htacces
  2. administration.php
  3. bootstrapped.php
  4. index.inline.css
  5. index.inline.js
  6. index.php 

## Deployment

### Simple php hosting
  * Just copy all the files from [src](./src) to your hosting root.
  * Update `jwtKey`, `adminPassword` and `adminUsername` at the top of `index.php`.
  * Edit `index.php`, `index.inline.css` or `index.inline.js` as you wish to customize how your site looks.

### Administration
To configure or to add new pages go to `/admin` page.

### Building a docker image of PHP 8

```shell
docker build -t php-local .
```

### Running with docker compose
Docker compose will need the docker image created in previous step.
```shell
docker compose up -d
```

The website will be available at [localhost:7003](http://localhost:7003)