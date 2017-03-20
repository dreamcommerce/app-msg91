<!doctype html>
<html>
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <script type="text/javascript" src="//cdn.dcsaas.net/js/mootools.js"></script>
        <script src="//cdn.dcsaas.net/js/appstore-sdk.js"></script>

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
                    
                    <form method="POST" action="<?php echo App::getUrl('/index/index'); ?>">
                        
                        <fieldset>

                            <header>General settings</header>
                            
                            <div class="edition-form-line">
                                <div class="edition-form-line-label <?php if (isset($errors['authkey'])) { echo 'input-warning'; } ?>">
                                    <label for="authkey" class="aicon-required">Auth key:</label>
                                </div>
                                <div class="edition-form-line-field">
                                    <input name="authkey" id="authkey" value="<?php echo $authkey; ?>" type="text">
                                    <div class="edition-form-comment">
                                        <div class="edition-form-comment-content">
                                            Auth key from API settings in MSG91 panel
                                        </div>
                                    </div>
                                <?php
                                    if (isset($errors['authkey'])) {
                                        echo '<ul class="error-list"><li><label for="id" class="aicon-exclamation error">';
                                        echo $errors['authkey'];
                                        echo '</label></li></ul>';
                                    }
                                ?>
                                </div>
                            </div>
                            
                            <div class="edition-form-line">
                                <div class="edition-form-line-label <?php if (isset($errors['sender'])) { echo 'input-warning'; } ?>">
                                    <label for="sender" class="aicon-required">Sender:</label>
                                </div>
                                <div class="edition-form-line-field">
                                    <input name="sender" id="sender" value="<?php echo $sender; ?>" type="text">
                                    <div class="edition-form-comment">
                                        <div class="edition-form-comment-content">
                                            Receiver will see this as sender's ID
                                        </div>
                                    </div>
                                <?php
                                    if (isset($errors['sender'])) {
                                        echo '<ul class="error-list"><li><label for="id" class="aicon-exclamation error">';
                                        echo $errors['sender'];
                                        echo '</label></li></ul>';
                                    }
                                ?>
                                </div>
                            </div>

                            <div class="edition-form-line">
                                <div class="edition-form-line-label">
                                    <label for="route" class="aicon-required">Route:</label>
                                </div>
                                <div class="edition-form-line-field">
                                    <div class="select-wrapper">
                                        <select style="width:33%"name="route">
                                            <?php
                                            if($route == '') {
                                                echo '<option selected="" value=""></option>';
                                            }
                                            if ($route == '1') {
                                                echo '<option value="1" selected="">promotional</option>';
                                                echo '<option value="4">transactional</option>';
                                            } elseif ($route == '4') {
                                                echo '<option value="1">promotional</option>';
                                                echo '<option value="4" selected="">transactional</option>';
                                            } else {
                                                echo '<option value="1">promotional</option>';
                                                echo '<option value="4">transactional</option>';
                                            }
                                            ?>
                                        </select><div class="edition-form-comment">
                                            <div class="edition-form-comment-content">
                                                Choose route to use
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </fieldset>
                        
                        <fieldset>

                            <header>Messages</header>
                            <?php if (isset($errors['translations'])) { ?>
                            <div class="edition-form-line">
                                <div class="edition-form-line-label">
                                        <label style='color:#f86d52;' for="statuseserrors">Enabled statuses without messages:</label>
                                    </div>
                                    <div class="edition-form-line-field">
                                        <div class="select-wrapper">
                                            <?php
                                            echo '<ul class="error-list">';
                                            foreach ($errors['translations'] as $value) {
                                                echo '<li><label for="statuseserrors" class="aicon-exclamation error">' . $value . '</label></li>';
                                            }
                                            echo '</ul>';
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <div class="edition-form-line">
                                <div class="edition-form-line-label">
                                    <label for="status">Order status:</label>
                                </div>
                                <div class="edition-form-line-field">
                                    <div class="select-wrapper">
                                        <select style="font-family: 'Symbol-Font', Arial;" class="extended-width" id="status" name="status" data-switch-handle="<?php foreach($settings as $status){ echo 'status-'.$status['id'].','; } ?>" data-switch-disable="false">
                                            <?php
                                                foreach($settings as $status){
                                                    $id = $status['id'];
                                                    $name = $status['name'];
                                                    $on = $status['on'];
                                                    echo "<option ";
                                                    if($editedStatusId == $id) {
                                                        echo 'selected ';
                                                    }
                                                    echo "style=\"font-family: 'Symbol-Font', Arial;\" value='$id' data-to-switch='status-$id'>$name";
                                                    if($on){
                                                        echo ' &#xe002;';
                                                    }
                                                    echo "</option>";
                                                }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <?php
                                foreach($settings as $status){
                                    $id = $status['id'];
                                    $name = $status['name'];
                                    $on = $status['on'];
                                    if(isset($status['message'][$translations])){
                                        $message = $status['message'][$translations];
                                    }else{
                                        $message = '';
                                    }
                            ?>
                                <div data-visibility-switch="false" data-switch="status-<?php echo $id; ?>" class="edition-form-line">
                                    
                                    <div class="edition-form-line-label">
                                        <label for="on<?php echo $id; ?>">Enabled:</label>
                                    </div>
                                    <div class="edition-form-line-field">
                                        <span class="checkbox-wrap-yesno">
                                            <input type="checkbox" <?php if($on) { echo 'checked=""' ; } ?> value="1" name="on<?php echo $id; ?>" id="on<?php echo $id; ?>">
                                            <label for="on<?php echo $id; ?>" data-no="NO" data-yes="YES"></label>
                                        </span>
                                    </div>
                                    
                                    <div class="edition-form-line">
                                        <div class="edition-form-line-label">
                                            <label for="msg<?php echo $id; ?>">Message:</label>
                                        </div>
                                        <div class="edition-form-line-field">
                                             <textarea cols="30" name="msg<?php echo $id; ?>" id="msg<?php echo $id; ?>"><?php echo $message; ?></textarea>
                                        </div>
                                    </div>
                                    
                                </div>
                            <?php
                                }
                            ?>
                                
                                                        
                        </fieldset>
                        
                        <fieldset>
                            <div class="edition-form-buttons">
                                <button class="button button-bg button-larger button-important save-button" type="submit" name="save" value="1">Save</button>
                            </div>
                        </fieldset>
                        
                    </form>
                    <div class='user-tag-helper'>
                        <h3>Tags which you can use in this message:</h3>
                        <ul>
                            <li>
                                <em>{order_id}</em> - Order ID
                            </li>
                            <li>
                                <em>{sum}</em> - Total amount
                            </li>
                            <li>
                                <em>{shipping_cost}</em> - Shipping cost
                            </li>
                            <li>
                                <em>{status_name}</em> - Status name
                            </li>
                            <li>
                                <em>{delivery_city}</em> - Delivery city
                            </li>
                            <li>
                                <em>{delivery_postcode}</em> - Delivery postcode
                            </li>
                            <li>
                                <em>{delivery_street}</em> - Delivery address
                            </li>
                            <li>
                                <em>{delivery_country}</em> - Delivery country
                            </li>
                        </ul>
                    </div>

                    <div style="border-bottom: 0.6em solid #1d4b6e"></div>
                    <form method="POST" action="<?php echo App::getUrl('/index/test'); ?>">

                        <fieldset>

                            <header>Test message</header>

                            <div class="edition-form-line">
                                <div class="edition-form-line-label">
                                    <label for="testAuthkey" class="aicon-required">Auth key:</label>
                                </div>
                                <div class="edition-form-line-field">
                                    <input name="testAuthkey" id="testAuthkey" value="<?php echo $authkey; ?>" type="text" readonly="">
                                </div>
                            </div>

                            <div class="edition-form-line">
                                <div class="edition-form-line-label">
                                    <label for="testSender" class="aicon-required">Sender:</label>
                                </div>
                                <div class="edition-form-line-field">
                                    <input name="testSender" id="testSender" value="<?php echo $sender; ?>" type="text" readonly="">
                                </div>
                            </div>

                            <div class="edition-form-line">
                                <div class="edition-form-line-label">
                                    <label for="testMessage" class="aicon-required">Message:</label>
                                </div>
                                <div class="edition-form-line-field">
                                    <input name="testMessage" id="testMessage" value="Test message" type="text" readonly="">
                                </div>
                            </div>

                            <div class="edition-form-line">
                                <div class="edition-form-line-label">
                                    <label for="testMessage" class="aicon-required">Route:</label>
                                </div>
                                <div class="edition-form-line-field">
                                    <input name="testMessage" id="testMessage" value="<?php if (1==$route) { echo 'promotional'; } else { echo 'transactional'; } ?>" type="text" readonly="">
                                </div>
                            </div>

                            <div class="edition-form-line">
                                <div class="edition-form-line-label">
                                    <label for="testMobile" class="aicon-required">Mobile:</label>
                                </div>
                                <div class="edition-form-line-field">
                                    <input name="testMobile" id="testMobile" value="" type="text">
                                    <div class="edition-form-comment">
                                        <div class="edition-form-comment-content">
                                            With country code at beginning
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </fieldset>

                        <fieldset>
                            <div class="edition-form-buttons">
                                <button class="button button-bg button-larger button-important save-button" type="submit" name="save" value="1">Send test message</button>
                            </div>
                        </fieldset>

                    </form>

                    <div class="clearfix"></div>
                </div>
            </section>
        </main>
    </body>
</html>
