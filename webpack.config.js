var Encore = require('@symfony/webpack-encore');
const WebpackRTLPlugin = require('@automattic/webpack-rtl-plugin');

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
        from: './node_modules/country-flag-icons/3x2/',
        to: 'images/flags/[path][name].[ext]',
        pattern: /\.svg$/
    })
    // this is needed for special 'flags' such as UNKNOWN.svg (which is used for missing flags)
    .copyFiles({
        from: './assets/images/flags/',
        to: 'images/flags/[path][name].[ext]',
    })

    .addPlugin(new WebpackRTLPlugin())

    .configureCssMinimizerPlugin((options) => {
        options.minimizerOptions = {
            preset: [
                'default',
                {
                    // disabled to fix these issues: https://github.com/EasyCorp/EasyAdminBundle/pull/5171
                    // reenable when Symfony Webpack Encore updates its css-minimizer-webpack-plugin to ^3
                    // (see https://github.com/symfony/webpack-encore/pull/1033)
                    svgo: false,
                },
            ]
        };
    })

    .addEntry('app', './assets/js/app.js')
    .addEntry('form', './assets/js/form.js')
    .addEntry('page-layout', './assets/js/page-layout.js')
    .addEntry('page-color-scheme', './assets/js/page-color-scheme.js')
    .addEntry('field-boolean', './assets/js/field-boolean.js')
    .addEntry('field-code-editor', './assets/js/field-code-editor.js')
    .addEntry('field-collection', './assets/js/field-collection.js')
    .addEntry('field-file-upload', './assets/js/field-file-upload.js')
    .addEntry('field-image', './assets/js/field-image.js')
    .addEntry('field-slug', './assets/js/field-slug.js')
    .addEntry('field-textarea', './assets/js/field-textarea.js')
    .addEntry('field-text-editor', './assets/js/field-text-editor.js')
    .addEntry('login', './assets/js/login.js')
;

module.exports = Encore.getWebpackConfig();
