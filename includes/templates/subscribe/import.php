<?php
if(!current_user_can( 'administrator' )){
	exit;
}
?>
<div class="wrap">
    <h2><?php _e( 'Import', 'medansms-wp' ); ?></h2>
    <form method="post" action="" enctype="multipart/form-data">
        <div id="html-upload-ui">
            <p id="async-upload-wrap">
                <input id="async-upload" type="file" name="wps-import-file"/>
            <p class="upload-html-bypass"><?php echo sprintf( __( 'Format File yang didukung hanya <code>Excel 97-2003 Workbook (*.xls)</code>. Silahkan klik <a href="%s">disini</a> untuk melihat contoh File Import', 'medansms-wp' ), plugins_url( 'medansms-wp/assets/images/standard-xml-file.png' ) ); ?></p>
            </p>
			<?php echo wp_nonce_field( 'import-file');?>
            <p id="async-upload-wrap">
                <label for="medansmswp_group_name"><?php _e( 'Group', 'medansms-wp' ); ?>:</label>
                <select name="medansmswp_group_name" id="medansmswp_group_name">
					<?php foreach ( $this->subscribe->get_groups() as $items ): ?>
                        <option value="<?php echo $items->ID; ?>"><?php echo $items->name; ?></option>
					<?php endforeach; ?>
                </select>
            </p>

            <a href="admin.php?page=medansms-wp-subscribers" class="button"><?php _e( 'Kembali', 'medansms-wp' ); ?></a>
            <input type="submit" class="button-primary" name="wps_import" value="<?php _e( 'Upload', 'medansms-wp' ); ?>"/>
        </div>
		<?php wp_nonce_field( 'import_file', 'nonce_field_import_file' ); ?>
    </form>
</div>