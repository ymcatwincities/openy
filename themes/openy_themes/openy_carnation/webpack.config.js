const SassLintPlugin = require('sass-lint-webpack')
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const path = require('path')

module.exports = {
    entry: './src/scss/style.scss',
    plugins: [
        ///...
        new SassLintPlugin({
            files: path.resolve(__dirname) + '/src/**/*.scss'
        }),
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
