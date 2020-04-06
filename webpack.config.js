var Encore = require('@symfony/webpack-encore');
const WebpackRTLPlugin = require('webpack-rtl-plugin');

Encore
    .setOutputPath('./src/Resources/public/')
    .setPublicPath('./')
    .setManifestKeyPrefix('bundles/easyadmin')

    .cleanupOutputBeforeBuild()
    .enableSassLoader()
    .enableSourceMaps(false)
    .enableVersioning(false)
    .disableSingleRuntimeChunk()
    .autoProvidejQuery()

    // copy select2 i18n files
    .copyFiles({
        from: './node_modules/select2/dist/js/i18n/',
        // relative to the output dir
        to: 'select2/i18n/[name].[ext]',
        // only copy files matching this pattern
        pattern: /\.js$/
    })

    // copy flag images for country type
    .copyFiles({
        from: './assets/images/flags/',
        to: 'images/flags/[path][name].[ext]',
        pattern: /\.png$/
    })

    .addPlugin(new WebpackRTLPlugin({
        // this regexp matches all files except 'app-custom-rtl.css', which contains
        // some RTL styles created manually because the plugin doesn't generate them yet
        test: '^((?!(app-custom-rtl.css)).)*$',
        diffOnly: true,
    }))

    .addEntry('app', './assets/js/app.js')
    .addEntry('app-custom-rtl', './assets/js/app-custom-rtl.js')
    .addEntry('form-type-code-editor', './assets/js/form-type-code-editor.js')
    .addEntry('form-type-text-editor', './assets/js/form-type-text-editor.js')
;

module.exports = Encore.getWebpackConfig();
