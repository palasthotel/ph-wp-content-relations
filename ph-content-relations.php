<?php

/**
 * Plugin Name:       Content Relations - DEV
 * Description:       Dev inc file
 * Version:           X.X.X
 * Requires at least: X.X
 * Tested up to:      X.X.X
 * Author:            PALASTHOTEL by Edward
 * Author URI:        http://www.palasthotel.de
 * Domain Path:       /plugin/languages
 */


use ContentRelations\Plugin;

include dirname( __FILE__ ) . "/plugin/ph-content-relations.php";

register_activation_hook(__FILE__, function(){
	Plugin::instance()->activate();
});