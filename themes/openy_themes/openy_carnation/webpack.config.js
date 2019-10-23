const SassLintPlugin = require('sass-lint-webpack')
const glob = require('glob')
const path = require('path')

console.log(path.resolve(__dirname) + '/src/scss/**/*.scss')
// var global_vars = {
//     theme_dist_css: 'dist/css',
//     theme_dist_js: 'dist/js',
//     theme_src_scss: 'src/scss',
//     theme_src_js: 'src/js',
// };
var scssFiles = glob.sync('./src/scss/**/*.scss')
//console.log(scssFiles)

module.exports = {
    entry: './src/js/webpack.js',
    plugins: [
        ///...
        new SassLintPlugin({
            ///files: path.resolve(__dirname) + '/src/scss/**/*.scss'
            files: path.resolve(__dirname) + '/src/**/*.scss'
        }),
    ],

    mode: 'development',

    module: {
        rules: [
            {

            }
        ]
    }
};
