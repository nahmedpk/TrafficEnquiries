<?php
ignore_user_abort( true );
include( $_SERVER['DOCUMENT_ROOT'] . '/wp-config.php' );
if ( ! empty( $_REQUEST['file'] ) ) {
	$filename = $_REQUEST['file'];
	header( 'Content-Type: application/csv' );
	header( "Content-disposition: attachment; filename=\"" . $filename . "\"" );
	header( 'Pragma: no-cache' );
	if ( ! empty( $filename ) ) {
		readfile( $filename );
		unlink( $filename );
	}
	exit;
}
?>
