/**
 * @link https://github.com/mikoweb/webui
 */
function startapp (data) {
    "use strict";

    var k, i, publicWebUi = data.path.resources + '/webui';

    // stop loading after timeout
    jsloader.timeout(data.timeout);
    // load es5-shim if browser not support
    jsloader.requirement({
        test: Modernizr.es5array && Modernizr.es5date && Modernizr.es5function && Modernizr.es5object && Modernizr.es5string,
        nope: [publicWebUi + '/es5-shim.min.js', publicWebUi + '/es5-sham.min.js']
    });

    for (k in data.res.resources) {
        if (data.res.resources.hasOwnProperty(k)) {
            jsloader.group(k, data.res.dependencies[k]);
            for (i = 0; i < data.res.resources[k].length; i++) {
                jsloader.add(k, data.res.resources[k][i].url, data.res.resources[k][i].async);
            }
        }
    }

    // load jQuery Mobile Events on touch device
    if (Modernizr.touchevents) {
        jsloader.add('core', [publicWebUi + '/jquery.mobile.only-events.min.js'], true);
    }

    if (data.res.unknown) {
        for (i = 0; i < data.res.unknown.length; i++) {
            jsloader.add('', data.res.unknown[i].url, data.res.unknown[i].url.async);
        }
    }

    jsloader.onLoad('framework', function () {
        require(['webui-vendor'], function (vendor) {
            vendor(data.path.cdn_javascript + data.path.lib, data.locale, data.requirejs);
        });

        require(['webui-cssloader'], function (loader) {
            loader.mode('dynamic');
            loader.setPatternPath(data.path.resources + '/webui_css/{package-name}/' + data.theme_name);
            loader.setBasePath(data.path.resources);
            loader.setDomainPath(data.path.cdn_css);
            loader.definePath(data.cssloader);
        });
    });

    jsloader.onLoad('core', function () {
        jQuery.app.define('root', data.root);
        jQuery.app.define('locale', data.locale);
        jQuery.app.define('path_base', data.path.base);
        jQuery.app.define('path_theme', data.path.theme);
        jQuery.app.define('path_resources', data.path.resources);
        jQuery.app.define('path_lib', data.path.lib);
        jQuery.app.trans.add(data.translations);
    });

    startapp = undefined;
}
