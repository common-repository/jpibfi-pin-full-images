<?php

//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

delete_option( 'jpibfi_pin_full_images_options' );
delete_option( 'jpibfi_pin_full_images_version' );