<div class="wrap">
    <h2><?php _e( 'Export', 'medansms-wp' ); ?></h2>
    <form id="export-filters" method="post" action="admin.php?page=medansms-wp">
        <table>
            <tr valign="top">
                <th scope="row">
                    <label for="export-file-type"><?php _e( 'Export Ke', 'medansms-wp' ); ?>:</label>
                </th>

                <td>
                    <select id="export-file-type" name="export-file-type">
                        <option value="0"><?php _e( 'Silahkan Pilih', 'medansms-wp' ); ?></option>
                        <option value="excel">Excel</option>
                        <option value="xml">XML</option>
                        <option value="csv">CSV</option>
                        <option value="tsv">TSV</option>
                    </select>
                    <p class="description"><?php _e( 'Pilih Format File', 'medansms-wp' ); ?></p>
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <a href="admin.php?page=medansms-wp-subscribers" class="button"><?php _e( 'Kembali', 'medansms-wp' ); ?></a>
                    <input type="submit" class="button-primary" name="wps_export_subscribe"
                           value="<?php _e( 'Export', 'medansms-wp' ); ?>"/>
                </td>
            </tr>
        </table>
    </form>
</div>