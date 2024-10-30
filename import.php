<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if(!current_user_can( 'administrator' )){
	exit;
}

include_once dirname( __FILE__ ) . "/includes/classes/excel-reader.class.php";

global $wpdb, $table_prefix;
$get_mobile = $wpdb->get_col( "SELECT `mobile` FROM {$table_prefix}sms_subscribes" );
$result     = [];
$duplicate  = [];

if ( isset( $_POST['wps_import'] ) ) {
	if ( ! $_FILES['wps-import-file']['error'] ) {


		if ( ! isset( $_POST['nonce_field_import_file'] ) || ! wp_verify_nonce( $_POST['nonce_field_import_file'], 'import_file' ) ) {
		   print 'Sorry, your nonce did not verify.';
		   exit;
		}
	
		if ( ! empty( $_POST ) && check_admin_referer( 'import_file', 'nonce_field_import_file' ) ) {
			
			$data = new Spreadsheet_Excel_Reader( $_FILES["wps-import-file"]["tmp_name"] );

			foreach ( $data->sheets[0]['cells'] as $items ) {

				// Check and count duplicate items
				if ( in_array( $items[2], $get_mobile ) ) {
					$duplicate[] = $items[2];
					continue;
				}

				// Count submitted items.
				$total_submit[] = $data->sheets[0]['cells'];

				$result = $wpdb->insert( "{$table_prefix}sms_subscribes",
					array(
						'date'     => MEDANSMS_WP_CURRENT_DATE,
						'name'     => sanitize_text_field($items[1]),
						'mobile'   => sanitize_text_field(((substr($items[2],0,1)=='0')? $items[2]:'0'.$items[2])),
						'status'   => '1',
						'group_ID' => sanitize_text_field($_POST['medansmswp_group_name'])
					)
				);

			}

			if ( $result ) {
				echo "<div class='updated'><p>" . sprintf( __( '<strong>%s</strong> Produk berhasil ditambahkan.', 'medansms-wp' ), count( $total_submit ) ) . "</div></p>";
			}

			if ( $duplicate ) {
				echo "<div class='error'><p>" . sprintf( __( '<strong>%s</strong> No.Ponsel diulang.', 'medansms-wp' ), count( $duplicate ) ) . "</div></p>";
			}
		}
	} else {
		echo "<div class='error'><p>" . __( 'Data yang anda masukkan belum lengkap', 'medansms-wp' ) . "</div></p>";
	}
}
