var Encore = require('@symfony/webpack-encore');

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

    // needed to avoid this bug: https://github.com/symfony/webpack-encore/issues/436
    .configureCssLoader(options => { options.minimize = false; })
    .enablePostCssLoader()

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

    .addEntry('app', './assets/js/app.js')
    .addEntry('app-rtl', './assets/js/app-rtl.js')
    .addEntry('form-type-code-editor', './assets/js/form-type-code-editor.js')
    .addEntry('form-type-text-editor', './assets/js/form-type-text-editor.js')
;

module.exports = Encore.getWebpackConfig();
