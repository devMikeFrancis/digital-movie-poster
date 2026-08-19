# Development

Running DMP on your own machine.

## With Docker

1. `cp .env.example .env`
2. `docker compose build`
3. `docker compose up -d`
4. Then, inside the app container:
    - `docker compose exec app composer install`
    - `docker compose exec app php artisan key:generate`
    - `docker compose exec app php artisan migrate`
    - `docker compose exec app php artisan storage:link`
    - `docker compose exec app npm install && docker compose exec app npm run build`
5. Visit `http://localhost:8074`.
6. If you are loading the site in a browser attached to a TV, add the `rotate`
   param: `http://localhost:8074?rotate=true`

## Without Docker

You need PHP 8.3+ (with `gd` or `imagick`, `sqlite3`, `intl`, `zip`, `mbstring`),
Node 22+, and Redis if you want the socket features.

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate
php artisan storage:link
npm run build
php artisan serve          # http://127.0.0.1:8000
node socketserver/server.js # separate terminal, needs Redis
```

Run the PHP test suite with `php artisan test` and the front-end one with
`npm test` (`npm run test:watch` while working). Check formatting with
`./vendor/bin/pint`.

Front-end tests live in `tests/js/` and run under Vitest with
`@vue/test-utils`. They exist because the display and the admin forms are where
the regressions have actually happened - a checkbox reading 0 instead of true, a
select bound to null rendering blank, a form reset clearing a required field -
and none of that is reachable from a PHP test.
