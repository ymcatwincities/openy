const SassLintPlugin = require('sass-lint-webpack')
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const path = require('path')

module.exports = {
    entry: './src/js/webpack.js',
    //entry: path.resolve(__dirname) + '/src/scss/style.scss',
    plugins: [
        ///...
        new SassLintPlugin({
            ///files: path.resolve(__dirname) + '/src/scss/**/*.scss'
            files: path.resolve(__dirname) + '/src/**/*.scss'
        }),
        new MiniCssExtractPlugin({
            filename: "./css/stylessss.css",
        }),
    ],

    mode: 'development',

    module: {
        rules: [
            {
                test: /\.s[ac]ss$/i,
                use: [
                     MiniCssExtractPlugin.loader,
                    {
                        loader: 'css-loader',
                        options: {
                            url: false,
                        }
                    },
                    'sass-loader'
                ]
            },
        ]
    }
};
