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

    // copy FontAwesome fonts
    .copyFiles({
        from: './node_modules/@fortawesome/fontawesome-free/webfonts/',
        // relative to the output dir
        to: 'fonts/[name].[hash].[ext]'
    })

    // copy flag images for country type
    .copyFiles({
        from: './assets/images/flags/',
        to: 'images/flags/[path][name].[ext]',
        pattern: /\.png$/
    })

    .addPlugin(new WebpackRTLPlugin())

    .addEntry('app', './assets/js/app.js')
    .addEntry('form-type-code-editor', './assets/js/form-type-code-editor.js')
    .addEntry('form-type-text-editor', './assets/js/form-type-text-editor.js')
    .addEntry('form-type-collection', './assets/js/form-type-collection.js')
    .addEntry('form-type-slug', './assets/js/form-type-slug.js')
    .addEntry('form-type-textarea', './assets/js/form-type-textarea.js')
;

module.exports = Encore.getWebpackConfig();
