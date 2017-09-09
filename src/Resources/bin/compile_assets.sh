# This scripts minifies and compresses the JavaScript and CSS files used by the bundle
# It requires to have 'uglifyjs' and ' uglifycss' installed as global commands
# -----------------------------------------------------------------------------

__DIR__="`dirname \"$0\"`"
COMPILED_CSS_FILE=${__DIR__}/../public/stylesheet/easyadmin-all.min.css
COMPILED_JS_FILE=${__DIR__}/../public/javascript/easyadmin-all.min.js

CSS_FILES=(
    bootstrap.min.css
    font-awesome.min.css
    adminlte.min.css
    featherlight.min.css
    bootstrap-toggle.min.css
    select2-bootstrap.min.css
)

JS_FILES=(
    jquery.min.js
    bootstrap.min.js
    jquery.slimscroll.min.js
    adminlte.min.js
    jquery.featherlight.min.js
    jquery.are-you-sure.min.js
    jquery.waypoints.min.js
    jquery.easyadmin-sticky.min.js
    select2.full.min.js
    bootstrap-toggle.min.js
    jquery.highlight.min.js
    easyadmin.js
)

# Empty the current compiled CSS file
echo "" > ${COMPILED_CSS_FILE}

# Minify and compress each CSS file and append it to the compiled file
for file in "${CSS_FILES[@]}"
do
    uglifycss ${__DIR__}/../public/stylesheet/${file} >> ${COMPILED_CSS_FILE}
done

# Empty the current compiled JavaScript file
echo "" > ${COMPILED_JS_FILE}

# Minify and compress each JavaScript file and append it to the compiled file
for file in "${JS_FILES[@]}"
do
    uglifyjs ${__DIR__}/../public/javascript/${file} -c -m >> ${COMPILED_JS_FILE}
done
