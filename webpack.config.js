const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');

module.exports = (env, argv) => {
    const isProduction = argv.mode === 'production';

    return {
        entry: {
            'admin-dashboard': './src/js/admin/admin-dashboard.js',
            'product-sync': './src/js/admin/product-sync.js',
            'admin-dashboard-style': './src/sass/admin/admin-dashboard.scss',
            'product-sync-style': './src/sass/admin/product-sync.scss'
        },
        output: {
            filename: 'js/admin/[name].js',
            path: path.resolve(__dirname, 'assets'),
        },
        module: {
            rules: [
                {
                    test: /\.scss$/,
                    use: [
                        MiniCssExtractPlugin.loader,
                        'css-loader',
                        'sass-loader'
                    ],
                },
                {
                    test: /\.js$/,
                    exclude: /node_modules/,
                    use: {
                        loader: 'babel-loader',
                        options: {
                            presets: ['@babel/preset-env']
                        }
                    }
                }
            ],
        },
        plugins: [
            new MiniCssExtractPlugin({
                filename: 'css/admin/[name].css',
            }),
        ],
        optimization: {
            minimize: isProduction,
            minimizer: [
                new CssMinimizerPlugin(),
                new TerserPlugin(),
            ],
        },
        devtool: isProduction ? 'source-map' : 'inline-source-map',
    };
};