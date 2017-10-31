var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('src/Resources/public/')
    .setPublicPath('/')
    .enableSassLoader()
    .autoProvidejQuery()
    .enableSourceMaps(!Encore.isProduction())
    // .cleanupOutputBeforeBuild()
    .addEntry('javascript/easyadmin-all.min', './assets/js/app.js')
    .addStyleEntry('stylesheet/easyadmin-all.min', './assets/css/app.scss')
    .addStyleEntry('stylesheet/easyadmin-rtl.min', [
        './assets/css/bootstrap-rtl.css',
        './assets/css/adminlte-rtl.css',
    ])
;

module.exports = Encore.getWebpackConfig();
