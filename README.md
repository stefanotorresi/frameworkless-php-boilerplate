# ToDo API

## Table of Contents

- [Design](#design)
  - [Infrastructure](#infrastructure)
- [First run](#first-run)
  - [Dev tools](#dev-tools)
- [Configuration](#configuration)

## Design

The project is a PHP framework-less web application based on [PSR-7](https://www.php-fig.org/psr/psr-7/), [PSR-11](https://www.php-fig.org/psr/psr-11/) and [PSR-15](https://www.php-fig.org/psr/psr-15/) standard interfaces (which I contributed to develop).

The main third party libraries used as building blocks are:
- [`zendframweork/zend-diactoros`](https://github.com/zendframework/zend-diactoros) for the PSR-7 HTTP messages;
- [`league/route`](https://github.com/thephpleague/route) for a PSR-15 HTTP router;
- [`php-di/php-di`](https://github.com/PHP-DI/PHP-DI) for a PSR-11 Dependency Injection container.

This allows for a very clean architecture with a straightforward lifecycle:
1. bootstrap the application
2. instantiate a request
3. the application processes the request, producing a response
4. the response is emitted

The application composes a PSR-15 middleware pipeline, the main middleware being the router, which forwards the request to the appropriate HTTP handler (see [src/Http/RouteHandler](src/Http/RouteHandler)).

The handlers themselves interact with the core domain model, which is a simple DBAL for the CRUD operations.

The resulting overall design is much simpler than a run-of-the-mill MVC framework application.

The storage backend is abstracted by a very simple DataMapper implementation built upon the native PDO extension.

The API itself is specified with the OpenAPI 3.0 standard (see [todos.openapi.yml](docs/todos.openapi.yml)), and user readable docs are generated on the fly.

Additional features like HTTP authentication and content negotiation are implemented via middleware.

A small CLI is provided to remove some toil.

### Infrastructure

The container setup has its main entry point in the reverse proxy service (NGINX).

Requests are forwarded to the `index.php` file by explicitly setting FASTCGI parameters, and this allows to get entirely rid of the document root in the main application container.

The main [Dockerfile](Dockerfile) produces a production-ready, multi-stage image, and the provided `docker-compose.yml` file uses the `dev` stage, which includes development tools like `phpunit`, `php-cs-fixer`, `phpstan` and others.

The HTTP reverse proxy also forwards requests in the `/docs` path to a `swagger-ui` instance. 

HTTPS traffic is not provided, as something like this would probably live behind a TLS terminating LB.

A GitLab CI configuration has been included, because I'm very used to CI and I quickly get bothered to run all the checks manually... ;)


## First run

Simply spin it up with `docker-compose`, wait for the database to be ready, and and create a database schema with the provided CLI:

```
docker-compose up -d
docker-compose exec app wait-for database:5432
docker-compose exec app cli create-schema
```

The application will be exposed at [http://localhost](http://localhost).  
API docs can be reached at [http://localhost/docs](http://localhost/docs).

### Dev tools

There are two suites of tests provided, plus configurations for coding style enforcement and static analysis.

```
docker-compose exec app phpunit --testsuite unit
docker-compose exec app phpunit --testsuite integration
docker-compose exec app phpstan analyse src test
docker-compose exec app php-cs-fixer fix
```

Note: the integration tests will wipe out the database!

### Configuration

Configuration is performed via the environment variables, which should be loaded into the app container with `docker-compose`.

[Defaults](.env.example) are provided so that everything should work out of the box.

Further customization can be done by providing a `docker-compose.override.yml` file, which is ignored by the VCS.

For example:
```yaml
# docker-compose.override.yml
version: '3.7'
services:
  app:
    environment:
      - DEBUG=false
```
