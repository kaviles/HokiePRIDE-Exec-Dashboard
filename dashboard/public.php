<?php

// if (!isset($_SERVER['HTTPS'])) {
//     // Redirect to https secured version to avoid infinite loop
//     header('Location: public');
//     die();
// }

include(__DIR__.'/includes/uiconfig.php');

// include(__DIR__.'/includes/utility.php');

// logMessage(__DIR__."/includes/logs/authorize.log", "Public Request: ".$_SERVER['REQUEST_URI']);

// cas_setup();

// $isAuthenticated = cas_isAuthenticated();

// if (!$isAuthenticated && isset($_REQUEST['login'])) {
//     cas_login();
// }
// else if ($isAuthenticated && isset($_REQUEST['logout'])) {
//     cas_logout();
// }

// if ($isAuthenticated) {

//     $user = cas_getUser();
//     if (authorizeAdmin($user)) {
//         header('Location: admin');
//         die();
//     }
// }

$user = 'testUser';

?>

<!DOCTYPE html>
<html>

    <head>
        <title><?php echo $ui_library_title; ?></title>
        <meta name="viewport" content="width=device-width, minimum-scale=1.0, initial-scale=1.0, user-scalable=yes">

        <!-- Load jQuery -->
        <script src="../bower_components/jquery/dist/jquery.min.js"></script>
        <!-- Load rasterizehtml -->
        <script src="../node_modules/rasterizehtml/dist/rasterizeHTML.allinone.js"></script>
        <!-- Load webcomponents.min.js for polyfill support. -->
        <script src="../bower_components/webcomponentsjs/webcomponents.min.js"></script>

        <!-- Load standard Polymer Project library. -->
        <link rel="import" href="../bower_components/paper-drawer-panel/paper-drawer-panel.html">
        <link rel="import" href="../bower_components/paper-header-panel/paper-header-panel.html">
        <link rel="import" href="../bower_components/paper-toolbar/paper-toolbar.html">
        <link rel="import" href="../bower_components/paper-menu/paper-menu.html">
        <link rel="import" href="../bower_components/paper-item/paper-icon-item.html">
        <link rel="import" href="../bower_components/paper-icon-button/paper-icon-button.html">
        <link rel="import" href="../bower_components/iron-icon/iron-icon.html">
        <link rel="import" href="../bower_components/iron-icons/iron-icons.html">
        <link rel="import" href="../bower_components/iron-icons/maps-icons.html">
        <link rel="import" href="../bower_components/iron-icons/social-icons.html">
        <link rel="import" href="../bower_components/iron-pages/iron-pages.html">
        <link rel="import" href="../bower_components/iron-flex-layout/iron-flex-layout.html">

        <!-- Load custom components -->
        <link rel="import" href="custom_components/custom-tile/custom-tile-layout.html">
        <link rel="import" href="custom_components/custom-tile/custom-tile.html">
        <link rel="import" href="custom_components/custom-tile/custom-tile-loginout.html">
        <link rel="import" href="custom_components/custom-tile/custom-tile-browse-layout-public.html">

        <style>
            html, body {
                height: 100%;
                margin: 0;
                background-color: white;
                /*background-color: #CECECE;*/ /* Grey */
                font-family: sans-serif;
            }
            #mainMenuToolbar {
                background-color: maroon; /* Maroon */
                color: white;
            }
            #panel1 {
                border-right-style: solid;
                border-right-width: 1px;
                border-right-color: #CECECE;;
            }
            #mainContentToolbar {
                background-color: maroon; /* Maroon */
                color: white;
            }
            /*.content {
                height: 100%;
                padding: 20px;
            }*/
        </style>
    </head>

    <body>

        <paper-drawer-panel id="navDrawerPanel">

            <paper-header-panel id='panel1' class='list-panel' drawer>
                <paper-toolbar id='mainMenuToolbar'>
                    <div>Menu</div>
                </paper-toolbar>

                <paper-menu id='mainMenu' selected='1'>
                    <paper-icon-item>
                        <iron-icon icon='icons:dashboard' item-icon></iron-icon>
                        Account
                    </paper-icon-item>
                    <paper-icon-item>
                        <iron-icon icon='icons:search' item-icon></iron-icon>
                        Browse
                    </paper-icon-item>
                </paper-menu>
            </paper-header-panel>

            <paper-header-panel id='panel2' class='content-panel' main>
                <paper-toolbar id='mainContentToolbar'>
                    <paper-icon-button icon="menu" paper-drawer-toggle></paper-icon-button>
                    <div id='bannerTitle'>
                        <?php echo $ui_library_menu; if($user){echo " | Current User: ".$user;} ?>
                    </div>
                </paper-toolbar>

                <iron-pages id='mainPages' selected='1'>
                    <custom-tile-layout>
                        <?php  
                        if (!$user) {
                            echo "<custom-tile-loginout id='login' tile label='Login'></custom-tile-loginout>";
                        }
                        else {
                            echo "<custom-tile-loginout id='logout' tile label='Logout'></custom-tile-loginout>";
                        }
                        ?>
                    </custom-tile-layout>
                    
                    <custom-tile-browse-layout-public>
                    </custom-tile-browse-layout-public>
                </iron-pages>
            </paper-header-panel>
        </paper-drawer-panel>

        <script>
            $(document).ready(function() {
                // console.log("Document ready!"); 

                $('#mainMenu').bind('iron-select', function() {
                    // console.log('Element: mainMenu, Event fired: core-activate');
                    // console.log('\t' + this.selectedItem.label + ' selected');
                    
                    // Switch main pages
                    var mainPages = document.querySelector('#mainPages');
                    mainPages.selected = this.selected;
                });

                $('#login').bind('tap', function() {
                    // console.log('Login tile tapped.');
                    window.location = './public?login=';
                });

                $('#logout').bind('tap', function() {
                    // console.log('Logout tile tapped.');
                    window.location = './public?logout=';
                });
            });
        </script>
    </body>
</html>