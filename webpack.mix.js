const mix = require('laravel-mix');
const tailwindcss = require('tailwindcss');

mix.js('resources/js/main.js', 'public/js/app.js')
    .vue()
    .sass('resources/sass/app.scss', 'public/css')
    .options({
        postCss: [tailwindcss('./tailwind.config.js')],
    })
    .webpackConfig(require('./webpack.config'));

if (mix.inProduction()) {
    mix.version();
}
