# Bibliography

CNCFlora bibliographic manager.

## Deploy

Deploy to any Apache or Nginx with PHP5.3+ and mod\_rewrite.

Copy resources/config.ini-dist to resources/config.ini and fill in properly.

## Run Local

    php -S localhost:8000 router.php

## About the app

- Based on [composer](http://getcomposer.org) lib to organize dependencies;
- Edit the composer.json to include new libs;
- Edit config.ini at resources to change app configs;
- Uses [chill](https://github.com/dancryer/Chill) to connect to CouchDB;
- Uses [moustache](https://github.com/bobthecow/mustache.php) for templates;
- Templates are located at resources/templates;
- Statics are located at resources;
- Use [phake](https://github.com/jaz303/phake) to handle project tasks;

## License

Licensed under the Apache License 2.0.

