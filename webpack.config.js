const path = require('path');

module.exports = {
    mode: 'production',
    context: path.resolve(__dirname, 'frontend'),
    entry: './main',
    output: {
        filename: 'main.js',
        path: path.resolve(__dirname, 'src/htdocs/js'),
        library: {
            type: 'amd',
            export: 'default',
        },
    },
    module: {
        rules: [
            {
                test: /\.js$/i,
                exclude: /node_modules/,
                loader: 'babel-loader',
            },
            {
                test: /\.(png|gif|svg|jpg|woff|woff2)$/i,
                type: 'asset/resource',
                generator: {
                    filename: '../images/[name][ext]?[hash]',
                    publicPath: 'images/',
                },
            },
        ],
    },
    resolve: {
        alias: {
            react$: path.resolve(__dirname, 'frontend/react-shim.js'),
            'react-dom$': path.resolve(__dirname, 'frontend/react-shim.js'),
        },
    },
    externals: {
        '@plesk/ui-library': { amd: 'plesk-ui-library' },
    },
};
