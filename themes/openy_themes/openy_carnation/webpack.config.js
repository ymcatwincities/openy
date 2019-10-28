const SassLintPlugin = require('sass-lint-webpack')
const MiniCssExtractPlugin = require("mini-css-extract-plugin")
const path = require('path')
const CopyWebpackPlugin = require('copy-webpack-plugin')
var entry = require('webpack-glob-entry')

let smth = path.resolve(__dirname, './src/js/bootstrap.js')
console.log(smth)

module.exports = {
    //entry: entry('./src/scss/style.scss', './src/js/**/*.js'),
    entry: './webpack-entry.js',
    plugins: [
        ///...
        new SassLintPlugin({
            files: path.resolve(__dirname) + '/src/**/*.scss'
        }),
        new CopyWebpackPlugin([
            { from: 'node_modules/bootstrap/dist/js/bootstrap.js', to: './../src/js' },
            { from: 'node_modules/jquery-match-height/dist/jquery.matchHeight.js', to: './../src/js' }
        ]),
        new MiniCssExtractPlugin({
            filename: "./css/style.css",
            sourceMap: true,
            options: {
                sourceMap: true,
                watch: true,
            }
        }),
    ],

    mode: 'development',
    devtool: 'source-map',
    output: {
        sourceMapFilename: 'css/style.css.map'
    },

    module: {
        rules: [
            {
                test: /.js/,
                enforce: 'pre',
                exclude: [
                    /node_modules/,
                    /webpack-entry.js/,
                    /.\/src\/js\/bootstrap.js/,
                    /jquery.matchHeight.js/,
                    path.resolve(__dirname, './src/js/bootstrap.js'),
                ],
                use: [
                    {
                        loader: 'jshint-loader',
                        options: {
                            sourceMap: false,
                            curly: true,
                            eqeqeq: true,
                            eqnull: true,
                            browser: true,
                            globals: {
                                jQuery: true
                            },
                            ignores: [
                                path.resolve(__dirname, './src/js/bootstrap.js'),
                            ],
                        }
                    }
                ]
            },
            {
                test: /\.s[ac]ss$/i,
                use: [
                     MiniCssExtractPlugin.loader,
                    {
                        loader: 'css-loader',
                        options: {
                            url: false,
                            sourceMap: true,
                        }
                    },
                    {
                        loader: 'sass-loader',
                        options: {
                            sourceMap: true,
                        }
                    },
                ]
            },
        ]
    }
};
