<!doctype html>
<html>
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <script type="text/javascript" src="//dcsaascdn.net/js/mootools.js"></script>
        <script src="//dcsaascdn.net/js/appstore-sdk.js"></script>

        <script>
            (function () {
                'use strict';

                var styles;

                if (localStorage.getItem('styles')) {
                    styles = JSON.parse(localStorage.getItem('styles'));
                    injectStyles(styles);
                }

                window.shopAppInstance = new ShopApp(function (app) {
                    app.init(null, function (params, app) {
                        if (localStorage.getItem('styles') === null) {
                            injectStyles(params.styles);
                        }
                        localStorage.setItem('styles', JSON.stringify(params.styles));

                        app.show(null, function () {
                            
                            new app.VisibilitySwitch();
                            $$('[data-switch-handle]').each(function(el) {
                                console.log(el);
                                el.addEvent('change', function() {
                                    app.adjustIframeSize();
                                });
                            });
                            app.adjustIframeSize();
                            
                        });
                        <?php if(isset($message) and isset($type)) { ?>
                        app.flashMessage({
                            msg : '<?php echo $message; ?>',
                            type : '<?php echo $type; ?>'
                        });
                        <?php } ?>
                    }, function (errmsg, app) {
                        alert(errmsg);
                    });
                    
                }, true);

                function injectStyles (styles) {
                    var i;
                    var el;
                    var sLength;

                    sLength = styles.length;
                    for (i = 0; i < sLength; ++i) {
                        el = document.createElement('link');
                        el.rel = 'stylesheet';
                        el.type = 'text/css';
                        el.href = styles[i];
                        document.getElementsByTagName('head')[0].appendChild(el);
                    }
                }
            }());
        </script>
    </head>
    <body>
        <main>
            <section class="rwd-layout-col-12">
                <div class="edition-form">

                        <fieldset>

                            <header>MSG91 API RESPONSE CONTENT</header>
                            <div style="padding:20px 50px;">
                                <?php
                                    if (preg_match('/^([a-zA-Z0-9]{24})$/', $body)) {
                                        echo 'Message created in MSG91 panel. Delivery report available in <a href="https://control.msg91.com/user/index.php#delivery_reports_text" target="_blank">MSG91 panel</a>. Message ID:<br><br>';
                                    }
                                    print_r($body);
                                ?>
                            </div>
                            <header>MSG91 API RESPONSE HEADRES</header>
                            <div style="padding:20px 50px;">
                                <?php print_r($header); ?>
                            </div>

                        </fieldset>

                    <div class="clearfix"></div>
                </div>
            </section>
        </main>
    </body>
</html>
