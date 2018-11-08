var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('./src/Resources/public/')
    .setPublicPath('/bundles/easyadmin')
    .setManifestKeyPrefix('bundles/easyadmin')

    .cleanupOutputBeforeBuild()
    .enableSassLoader()
    .enableSourceMaps(false)
    .enableVersioning(false)
    .autoProvidejQuery()

    .addEntry('app', './assets/js/app.js')
    .addEntry('app-rtl', './assets/js/app-rtl.js')
    .addEntry('bootstrap-all', './assets/js/bootstrap-all.js')
;

module.exports = Encore.getWebpackConfig();
