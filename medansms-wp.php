<?php
/**
 * Plugin Name: MedanSMS WP
 * Plugin URI: https://medansms.co.id/medansms-wp/
 * Description: Kirim SMS secara otomatis ketika ada Pesanan, Pelanggan Baru, Komentar, dll
 * Version: 4.0.21
 * Author: CV.Medan Media Utama
 * Author URI: https://medansms.co.id/
 * Text Domain: medansms-wp
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

define( 'MEDANSMS_WP_VERSION', '4.0.21' );
define( 'MEDANSMS_WP_DIR_PLUGIN', plugin_dir_url( __FILE__ ) );
define( 'MEDANSMS_WP_ADMIN_URL', get_admin_url() );
define( 'MEDANSMS_WP_SITE', 'https://medansms.co.id' );
define( 'MEDANSMS_WP_MOBILE_REGEX', '/^[\+|\(|\)|\d|\- ]*$/' );
define( 'MEDANSMS_WP_CURRENT_DATE', date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) );

$medansmswp_option = get_option( 'medansmswp_settings' );

include_once dirname( __FILE__ ) . '/includes/functions.php';
$sms = initial_gateway();

$MEDANSMS_WP_Plugin = new MEDANSMS_WP_Plugin;

register_activation_hook( __FILE__, array( 'MEDANSMS_WP_Plugin', 'install' ) );


class MEDANSMS_WP_Plugin {
	public $admin_url = MEDANSMS_WP_ADMIN_URL;
	public $sms;
	public $subscribe;
	protected $db;
	protected $tb_prefix;
	protected $options;

	public function __construct() {
		global $wpdb, $table_prefix, $medansmswp_option, $sms;

		$this->db        = $wpdb;
		$this->tb_prefix = $table_prefix;
		$this->options   = $medansmswp_option;

		__( 'WP SMS', 'medansms-wp' );
		__( 'Kirim SMS secara otomatis ketika ada Pesanan, Pelanggan Baru, Komentar, dll', 'medansms-wp' );

		
		$this->includes();
		$this->sms = $sms;
		$this->init();
		$this->subscribe = new MEDANSMS_WP_Subscriptions();

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'front_assets' ) );

		add_action( 'admin_bar_menu', array( $this, 'adminbar' ) );
		add_action( 'dashboard_glance_items', array( $this, 'dashboard_glance' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'widgets_init', array( $this, 'register_widget' ) );

		add_filter( 'medansms_wp_to', array( $this, 'modify_bulk_send' ) );
	}


	static function install() {
		global $medansms_wp_db_version;

		include_once dirname( __FILE__ ) . '/install.php';
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( "CREATE TABLE IF NOT EXISTS {$table_prefix}sms_subscribes(
					ID int(10) NOT NULL auto_increment,
					date DATETIME,
					name VARCHAR(20),
					mobile VARCHAR(20) NOT NULL,
					status tinyint(1),
					activate_key INT(11),
					group_ID int(5),
					PRIMARY KEY(ID)) CHARSET=utf8
				" );
		dbDelta( "CREATE TABLE IF NOT EXISTS {$table_prefix}sms_subscribes_group(
					ID int(10) NOT NULL auto_increment,
					name VARCHAR(250),
					PRIMARY KEY(ID)) CHARSET=utf8
				" );
		dbDelta( "CREATE TABLE IF NOT EXISTS {$table_prefix}sms_send(
					ID int(10) NOT NULL auto_increment,
					date DATETIME,
					sender VARCHAR(20) NOT NULL,
					message TEXT NOT NULL,
					recipient TEXT NOT NULL,
					PRIMARY KEY(ID)) CHARSET=utf8
				" );

		add_option( 'medansms_wp_db_version', MEDANSMS_WP_VERSION );

		delete_option( 'wp_notification_new_wp_version' );
	}

	public function add_cap() {
		// get administrator role
		$role = get_role( 'administrator' );

		$role->add_cap( 'medansmswp_sendsms' );
		$role->add_cap( 'medansmswp_outbox' );
		$role->add_cap( 'medansmswp_subscribers' );
		$role->add_cap( 'medansmswp_subscribe_groups' );
		$role->add_cap( 'medansmswp_setting' );
	}

	public function includes() {
		$files = array(
			'includes/class-medansms-wp-gateway',
			'includes/class-medansms-wp-settings',
			'includes/class-medansms-wp-settings-pro',
			'includes/class-medansms-wp-features',
			'includes/class-medansms-wp-notifications',
			'includes/class-medansms-wp-integrations',
			'includes/class-medansms-wp-gravityforms',
			'includes/class-medansms-wp-quform',
			'includes/class-medansms-wp-subscribers',
			'includes/class-medansms-wp-widget',
			'includes/class-medansms-wp-rest-api',
			'includes/class-medansms-wp-version',
		);

		foreach ( $files as $file ) {
			include_once dirname( __FILE__ ) . '/' . $file . '.php';
		}
	}

	private function init() {
		// Check exists require function
		if ( ! function_exists( 'wp_get_current_user' ) ) {
			include( ABSPATH . "wp-includes/pluggable.php" );
		}

		// Add plugin caps to admin role
		if ( is_admin() and is_super_admin() ) {
			$this->add_cap();
		}		
	}

	public function admin_assets() {
		wp_register_style( 'medansmswp-admin-css', plugin_dir_url( __FILE__ ) . 'assets/css/admin.css', true, '1.3' );
		wp_enqueue_style( 'medansmswp-admin-css' );

		wp_enqueue_style( 'medansmswp-chosen-css', plugin_dir_url( __FILE__ ) . 'assets/css/chosen.min.css', true, '1.2.0' );
		wp_enqueue_script( 'medansmswp-chosen-js', plugin_dir_url( __FILE__ ) . 'assets/js/chosen.jquery.min.js', true, '1.2.0' );
		wp_enqueue_script( 'medansmswp-word-and-character-counter-js', plugin_dir_url( __FILE__ ) . 'assets/js/jquery.word-and-character-counter.min.js', true, '2.5.0' );
		wp_enqueue_script( 'medansmswp-admin-js', plugin_dir_url( __FILE__ ) . 'assets/js/admin.js', true, '1.2.0' );
	}

	public function front_assets() {
		wp_register_style( 'medansmswp-subscribe', plugin_dir_url( __FILE__ ) . 'assets/css/subscribe.css', true, '1.1' );
		wp_enqueue_style( 'medansmswp-subscribe' );
	}

	public function adminbar() {
		global $wp_admin_bar, $medansmswp_option;

		if ( is_super_admin() && is_admin_bar_showing() ) {
			if ( get_option( 'wp_last_credit' ) && isset( $medansmswp_option['account_credit_in_menu'] ) ) {
				$wp_admin_bar->add_menu( array(
					'id'    => 'wp-credit-sms',
					'title' => '<span class="ab-icon"></span>' . get_option( 'wp_last_credit' ),
					'href'  => $this->admin_url . '/admin.php?page=medansms-wp-settings',
				) );
			}

			$wp_admin_bar->add_menu( array(
				'id'     => 'wp-send-sms',
				'parent' => 'new-content',
				'title'  => __( 'SMS', 'medansms-wp' ),
				'href'   => $this->admin_url . '/admin.php?page=medansms-wp'
			) );
		}
	}

	public function dashboard_glance() {
		$subscribe = $this->db->get_var( "SELECT COUNT(*) FROM {$this->tb_prefix}sms_subscribes" );
		echo "<li class='medansmswp-subscribe-count'><a href='" . $this->admin_url . "admin.php?page=medansms-wp-subscribers'>" . sprintf( __( '%s Subscriber', 'medansms-wp' ), $subscribe ) . "</a></li>";
		echo "<li class='medansmswp-credit-count'><a href='" . $this->admin_url . "admin.php?page=medansms-wp-settings&tab=gateway'>" . sprintf( __( '%s SMS Kredit', 'medansms-wp' ), get_option( 'wp_last_credit' ) ) . "</a></li>";
	}

	public function admin_menu() {
		add_menu_page( __( 'SMS', 'medansms-wp' ), __( 'SMS', 'medansms-wp' ), 'medansmswp_sendsms', 'medansms-wp', array(
			$this,
			'send_page'
		), 'dashicons-email-alt' );
		add_submenu_page( 'medansms-wp', __( 'Kirim SMS', 'medansms-wp' ), __( 'Kirim SMS', 'medansms-wp' ), 'medansmswp_sendsms', 'medansms-wp', array(
			$this,
			'send_page'
		) );
		add_submenu_page( 'medansms-wp', __( 'Kotak Keluar', 'medansms-wp' ), __( 'Kotak Keluar', 'medansms-wp' ), 'medansmswp_outbox', 'medansms-wp-outbox', array(
			$this,
			'outbox_page'
		) );
		add_submenu_page( 'medansms-wp', __( 'Subscriber', 'medansms-wp' ), __( 'Subscriber', 'medansms-wp' ), 'medansmswp_subscribers', 'medansms-wp-subscribers', array(
			$this,
			'subscribe_page'
		) );
		add_submenu_page( 'medansms-wp', __( 'Grup Subscriber', 'medansms-wp' ), __( 'Grup Subscriber', 'medansms-wp' ), 'medansmswp_subscribe_groups', 'medansms-wp-subscribers-group', array(
			$this,
			'groups_page'
		) );
	}

	public function register_widget() {
		register_widget( 'medansmswp_Widget' );
	}

	public function modify_bulk_send( $to ) {
		if ( ! $this->sms->bulk_send ) {
			return array( $to[0] );
		}

		return $to;
	}

	public function send_page() {
		global $medansmswp_option;

		$get_group_result = $this->db->get_results( "SELECT * FROM `{$this->tb_prefix}sms_subscribes_group`" );
		$get_users_mobile = $this->db->get_col( "SELECT `meta_value` FROM `{$this->tb_prefix}usermeta` WHERE `meta_key` = 'mobile'" );

		if ( isset( $_POST['SendSMS'] ) ) {
			if ( $_POST['wp_get_message'] ) {
				if ( $_POST['wp_send_to'] == "wp_subscribe_username" ) {
					if ( $_POST['medansmswp_group_name'] == 'all' ) {
						$this->sms->to = $this->db->get_col( "SELECT mobile FROM {$this->tb_prefix}sms_subscribes WHERE `status` = '1'" );
					} else {
						$this->sms->to = $this->db->get_col( "SELECT mobile FROM {$this->tb_prefix}sms_subscribes WHERE `status` = '1' AND `group_ID` = '" . sanitize_text_field($_POST['medansmswp_group_name']) . "'" );
					}
				} else if ( $_POST['wp_send_to'] == "wp_users" ) {
					$this->sms->to = $get_users_mobile;
				} else if ( $_POST['wp_send_to'] == "wp_tellephone" ) {
					$this->sms->to = explode( ",", sanitize_text_field($_POST['wp_get_number']) );
				}

				$this->sms->from = sanitize_text_field($_POST['wp_get_sender']);
				$this->sms->msg  = sanitize_text_field($_POST['wp_get_message']);

				if ( isset( $_POST['wp_flash'] ) ) {
					$this->sms->isflash = true;
				} else {
					$this->sms->isflash = false;
				}

				// Send sms
				$response = $this->sms->SendSMS();

				if ( is_wp_error( $response ) ) {
					if ( is_array( $response->get_error_message() ) ) {
						$response = print_r( $response->get_error_message(), 1 );
					} else {
						$response = $response->get_error_message();
					}

					echo "<div class='error'><p>" . sprintf( __( '<strong style="color:red">SMS Gagal Dikirim. </strong>  Error: %s', 'medansms-wp' ), $response ) . "</p></div>";
				} else {
					echo "<div class='updated'><p>" . __( '<strong style="color:green">SMS Berhasil Dikirim </strong>', 'medansms-wp' ) . "</p></div>";
					update_option( 'wp_last_credit', $this->sms->GetCredit() );
				}
			} else {
				echo "<div class='error'><p>" . __( 'Silahkan Masukkan Isi Pesan SMS', 'medansms-wp' ) . "</p></div>";
			}
		}

		include_once dirname( __FILE__ ) . "/includes/templates/send/send-sms.php";
	}

	public function outbox_page() {
		include_once dirname( __FILE__ ) . '/includes/class-medansms-wp-outbox.php';

		//Create an instance of our package class...
		$list_table = new MEDANSMS_WP_Outbox_List_Table();

		//Fetch, prepare, sort, and filter our data...
		$list_table->prepare_items();

		include_once dirname( __FILE__ ) . "/includes/templates/outbox/outbox.php";
	}

	public function subscribe_page() {

		if ( isset( $_GET['action'] ) ) {
			// Add subscriber page
			if ( $_GET['action'] == 'add' ) {
				include_once dirname( __FILE__ ) . "/includes/templates/subscribe/add-subscriber.php";

				if ( isset( $_POST['wp_add_subscribe'] ) ) {
					$result = $this->subscribe->add_subscriber( sanitize_text_field($_POST['wp_subscribe_name']), sanitize_text_field($_POST['wp_subscribe_mobile']), sanitize_text_field($_POST['medansmswp_group_name']) );
					echo $this->notice_result( $result['result'], $result['message'] );
				}

				return;
			}

			// Edit subscriber page
			if ( $_GET['action'] == 'edit' ) {
				if ( isset( $_POST['wp_update_subscribe'] ) ) {
					$result = $this->subscribe->update_subscriber( sanitize_text_field($_GET['ID']), sanitize_text_field($_POST['wp_subscribe_name']), sanitize_text_field($_POST['wp_subscribe_mobile']), sanitize_text_field($_POST['medansmswp_group_name']), sanitize_text_field($_POST['medansmswp_subscribe_status']) );
					echo $this->notice_result( $result['result'], $result['message'] );
				}

				$get_subscribe = $this->subscribe->get_subscriber( sanitize_text_field($_GET['ID']) );
				include_once dirname( __FILE__ ) . "/includes/templates/subscribe/edit-subscriber.php";

				return;
			}

			// Import subscriber page
			if ( $_GET['action'] == 'import' ) {
				include_once dirname( __FILE__ ) . "/import.php";
				include_once dirname( __FILE__ ) . "/includes/templates/subscribe/import.php";

				return;
			}

			// Export subscriber page
			if ( $_GET['action'] == 'export' ) {
				include_once dirname( __FILE__ ) . "/includes/templates/subscribe/export.php";

				return;
			}
		}

		include_once dirname( __FILE__ ) . '/includes/class-medansms-wp-subscribers-table.php';

		//Create an instance of our package class...
		$list_table = new MEDANSMS_WP_Subscribers_List_Table();

		//Fetch, prepare, sort, and filter our data...
		$list_table->prepare_items();

		include_once dirname( __FILE__ ) . "/includes/templates/subscribe/subscribes.php";
	}

	public function groups_page() {

		if ( isset( $_GET['action'] ) ) {
			// Add group page
			if ( $_GET['action'] == 'add' ) {
				include_once dirname( __FILE__ ) . "/includes/templates/subscribe/add-group.php";
				if ( isset( $_POST['wp_add_group'] ) ) {
					$result = $this->subscribe->add_group( sanitize_text_field($_POST['wp_group_name']) );
					echo $this->notice_result( $result['result'], $result['message'] );
				}

				return;
			}

			// Manage group page
			if ( $_GET['action'] == 'edit' ) {
				if ( isset( $_POST['wp_update_group'] ) ) {
					$result = $this->subscribe->update_group( sanitize_text_field($_GET['ID']), sanitize_text_field($_POST['wp_group_name']) );
					echo $this->notice_result( $result['result'], $result['message'] );
				}

				$get_group = $this->subscribe->get_group( sanitize_text_field($_GET['ID']) );
				include_once dirname( __FILE__ ) . "/includes/templates/subscribe/edit-group.php";

				return;
			}
		}

		include_once dirname( __FILE__ ) . '/includes/class-medansms-wp-groups-table.php';

		//Create an instance of our package class...
		$list_table = new MEDANSMS_WP_Subscribers_Groups_List_Table();

		//Fetch, prepare, sort, and filter our data...
		$list_table->prepare_items();

		include_once dirname( __FILE__ ) . "/includes/templates/subscribe/groups.php";
	}

	public function notice_result( $result, $message ) {
		if ( empty( $result ) ) {
			return;
		}

		if ( $result == 'error' ) {
			return '<div class="updated settings-error notice error is-dismissible"><p><strong>' . $message . '</strong></p><button class="notice-dismiss" type="button"><span class="screen-reader-text">' . __( 'Tutup', 'medansms-wp' ) . '</span></button></div>';
		}

		if ( $result == 'update' ) {
			return '<div class="updated settings-update notice is-dismissible"><p><strong>' . $message . '</strong></p><button class="notice-dismiss" type="button"><span class="screen-reader-text">' . __( 'Tutup', 'medansms-wp' ) . '</span></button></div>';
		}
	}

	public function shortcode( $atts, $content = null ) {

	}
	
}


include_once dirname( __FILE__ ) . '/function-medansms-wp.php';
