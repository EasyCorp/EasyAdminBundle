var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('./src/Resources/public/')
    .setPublicPath('/bundles/easyadmin')
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

    .addEntry('app', './assets/js/app.js')
    .addEntry('app-rtl', './assets/js/app-rtl.js')
    .addEntry('bootstrap-all', './assets/js/bootstrap-all.js')
;

module.exports = Encore.getWebpackConfig();
