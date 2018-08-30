<?php
/*
Plugin Name: Gravity Forms Add-on AutoExport Entries to CSV - Basic
Plugin URI: http://fallriver.digital
Description: Automatically export a CSV report of form entries attached and send it to your email.
Version: 1.0.4
Author: Lukas Conant
Author URI: http://fallriver.digital
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'GF_AUTOEXPORT_CSV', '2.0' );

class GF_AutoExport_CSV_AddOn {
	
	
	
	public function __construct() {

		if ( class_exists( 'GFAPI' ) ) {
			
			// Hook into WordPress functions to add export schedules

			add_action( 'cron_schedules', array($this, 'add_my_intervals' ));
			
			add_action( 'admin_init', array( $this, 'gforms_create_schedules' ));

			global $wpdb;
			$prefix = $wpdb->prefix;
			$forms = $wpdb->get_results( "SELECT * FROM " . $prefix . "rg_form_meta" );
			
			// Add export function to each scheduled cron event
			
			foreach ( $forms as $form ) {
				$form_id = $form->form_id;
				$display_meta = $form->display_meta;
				$decode = json_decode( $display_meta );

				if ( $decode ){
					$enabled = isset( $decode->autoexport_csv_addon ) ? $decode->autoexport_csv_addon->enabled : 0;
					if ( $enabled == 1 ) {
						add_action( 'csv_export_' . $form_id , array( $this, 'gforms_automated_export' ) );
					}
				}
			}

		}

	}

	/**
		* Adding my custom intervals
		*
		* @since 1.0.0
		*
		* @param array $schedules.
		* @return array $schedules.
	*/
	public function add_my_intervals( $schedules ) {
		
			$schedules['hourly'] = array(
			'interval' => 60*60,
			'display' => __('Once Hourly')
			);
			$schedules['weekly'] = array(
			'interval' => 604800,
			'display' => __('Once Weekly')
			);
			$schedules['monthly'] = array(
				'interval' => 2635200,
				'display' => __('Once a month')
			);
		return $schedules;
			
	}

	public function gforms_create_schedules(){

		$forms = GFAPI::get_forms();

		foreach ( $forms as $form ) {

			$form_id = $form['id'];

			$enabled = isset( $form['autoexport_csv_addon'] ) ? $form['autoexport_csv_addon']['enabled'] : 0;

			if ( $enabled == 1 ) {

				if ( ! wp_next_scheduled( 'csv_export_' . $form_id ) ) {
					
					$frequency = $form['autoexport_csv_addon']['type_of_interval'];

					wp_schedule_event( time(), $frequency, 'csv_export_' . $form_id );
				}

			}

			else {

				$timestamp = wp_next_scheduled( 'csv_export_' . $form_id );

				wp_unschedule_event( $timestamp, 'csv_export_' . $form_id );

			}

		}

	}
	
	 


	/**
		* Run Automated Exports
		*
		* @since 0.1
		*
		* @param void
		* @return void
	*/
	
	public function gforms_automated_export() {

		global $export_count;
		
		$output = "";
		$form_id = explode('_', current_filter())[2];
		$form = GFAPI::get_form( $form_id ); // get form by ID
		
		//Set the date range for the export data
		
		$search_criteria = array();

		if ( $form['autoexport_csv_addon']['size_of_export'] == 'all_of_time' ) {
			$search_criteria = array();
		}

		if ( $form['autoexport_csv_addon']['size_of_export'] == 'one_day' ) {

			$search_criteria['start_date'] = date('Y-m-d', time() - 60 * 60 * 24 * 2 );

			$search_criteria['end_date'] = date('Y-m-d', time() - 60 * 60 * 24 );

		}

		if ( $form['autoexport_csv_addon']['size_of_export'] == 'one_week' ) {

			$search_criteria['start_date'] = date('Y-m-d', time() - 60 * 60 * 24 * 7 );

			$search_criteria['end_date'] = date('Y-m-d', time() - 60 * 60 * 24 );

		}

		if ( $form['autoexport_csv_addon']['size_of_export'] == 'one_month' ) {

			$search_criteria['start_date'] = date('Y-m-d', time() - 60 * 60 * 24 * 30 );

			$search_criteria['end_date'] = date('Y-m-d', time() - 60 * 60 * 24 );

		}
		
		if ( $form['autoexport_csv_addon']['size_of_export'] == 'one_quarter' ) {

			$search_criteria['start_date'] = date('Y-m-d', time() - 60 * 60 * 24 * 30 * 4 );

			$search_criteria['end_date'] = date('Y-m-d', time() - 60 * 60 * 24 );

		}
		
		if ( $form['autoexport_csv_addon']['size_of_export'] == 'one_year' ) {

			$search_criteria['start_date'] = date('Y-m-d', time() - 60 * 60 * 24 * 365 );

			$search_criteria['end_date'] = date('Y-m-d', time() - 60 * 60 * 24 );

		}

		require_once( GFCommon::get_base_path() . '/export.php' );

		$_POST['export_field'] = array();
		
		// Add in entry meta data
		
		array_push( $form['fields'], array( 'id' => 'created_by', 'label' => __( 'Created By (User Id)', 'autoexport_csv_addon' ) ) );
		array_push( $form['fields'], array( 'id' => 'id', 'label' => __( 'Entry Id', 'autoexport_csv_addon' ) ) );
		array_push( $form['fields'], array( 'id' => 'date_created', 'label' => __( 'Entry Date', 'autoexport_csv_addon' ) ) );
		array_push( $form['fields'], array( 'id' => 'source_url', 'label' => __( 'Source Url', 'autoexport_csv_addon' ) ) );
		array_push( $form['fields'], array( 'id' => 'transaction_id', 'label' => __( 'Transaction Id', 'autoexport_csv_addon' ) ) );
		array_push( $form['fields'], array( 'id' => 'payment_amount', 'label' => __( 'Payment Amount', 'autoexport_csv_addon' ) ) );
		array_push( $form['fields'], array( 'id' => 'payment_date', 'label' => __( 'Payment Date', 'autoexport_csv_addon' ) ) );
		array_push( $form['fields'], array( 'id' => 'payment_status', 'label' => __( 'Payment Status', 'autoexport_csv_addon' ) ) );
		//array_push($form['fields'],array('id' => 'payment_method' , 'label' => __('Payment Method', 'autoexport_csv_addon'))); //wait until all payment gateways have been released
		array_push( $form['fields'], array( 'id' => 'post_id', 'label' => __( 'Post Id', 'autoexport_csv_addon' ) ) );
		array_push( $form['fields'], array( 'id' => 'user_agent', 'label' => __( 'User Agent', 'autoexport_csv_addon' ) ) );
		array_push( $form['fields'], array( 'id' => 'ip', 'label' => __( 'User IP', 'autoexport_csv_addon' ) ) );
		
		$form = apply_filters( 'gform_export_fields', $form );
		$form = GFFormsModel::convert_field_objects( $form );
		
		$entry_meta = GFFormsModel::get_entry_meta( $form['id'] );
		$keys       = array_keys( $entry_meta );
		foreach ( $keys as $key ) {
			array_push( $form['fields'], array( 'id' => $key, 'label' => $entry_meta[ $key ]['label'] ) );
		}
		
		// Remove unselected fields from export
		
		$fields_to_remove = array();
			
			foreach ( $form['autoexport_csv_addon'] as $field_key => $field_export_selection) {			
				if ( ($field_export_selection == 0) ) {
					array_push($fields_to_remove, $field_key);		
				}
			} 
			foreach ( $form['fields'] as $key => $field ) {
				$field_id = is_object( $field ) ? $field->id : $field['id'];
				if ( in_array( $field_id, $fields_to_remove ) ) {
					unset ( $form['fields'][ $key ] );
					}
				}
		
		// Done removing unselected fields
		
		
		foreach( $form['fields'] as $field ){
			$_POST['export_field'][] = $field->id;
		}
		
		do_action( 'gform_export_fields', $form);

		$_POST['export_date_start'] = $search_criteria['start_date'];

		$_POST['export_date_end']   = $search_criteria['end_date'];

		$export = self::start_automated_export( $form, $offset = 0, $form_id . '-' . date('Y-m-d g:i A') );
		
		$upload_dir = wp_upload_dir();

		$baseurl = $upload_dir['baseurl'];

		$path = $upload_dir['path'];

		$server = $_SERVER['HTTP_HOST'];

		$email_address_1 = $form['autoexport_csv_addon']['email-1'];
		$email_address_2 = $form['autoexport_csv_addon']['email-2'];
		$email_address_3 = $form['autoexport_csv_addon']['email-3'];
		$email_address_4 = $form['autoexport_csv_addon']['email-4'];
		$email_address_5 = $form['autoexport_csv_addon']['email-5'];
		
		$email_recipients = array($email_address_1,$email_address_2,$email_address_3,$email_address_4,$email_address_5);

		// Send an email using the latest csv file
		$attachments = $path . '/export-' . $form_id . '-' . date('Y-m-d g:i A') . '.csv';

		$headers[] = 'From: WordPress <wordpress@' . $server . '>';
		
		add_filter( 'wp_mail_content_type','wpse27856_set_content_type' );
		
		$date = new DateTime("now", aecsv_get_blog_timezone() );
		
		$current_time = $date->format('H:i A');
		
		wp_mail( $email_recipients , 'Automatic Gravity Form Export', 'Your CSV Export is attached to this message. <p>Like this automatic export you just got at ' . $current_time . '? <a href="https://wordpress.org/support/plugin/gf-add-on-autoexport-entries-to-csv/reviews/#new-post">Click here to leave us a review!</a></p> ', $headers, $attachments);
		
		
		remove_filter( 'wp_mail_content_type','wpse27856_set_content_type' );
		
	}
	
	
	
	/**
		* Get GMT date
		*
		* @param String	$local_date Local date
		* @return String $date GMT date
	*/
	
	public static function get_gmt_date( $local_date ) {
		$local_timestamp = strtotime( $local_date );
		$gmt_timestamp   = GFCommon::get_gmt_timestamp( $local_timestamp );
		$date            = gmdate( 'Y-m-d H:i:s', $gmt_timestamp );
		return $date;
	}


	public static function start_automated_export( $form, $offset = 0, $export_id = '' ) {

		$time_start         = microtime( true );
		/***
		 * Allows the export max execution time to be changed.
		 *
		 * When the max execution time is reached, the export routine stop briefly and submit another AJAX request to continue exporting entries from the point it stopped.
		 *
		 * @since 2.0.3.10
		 *
		 * @param int   100    The amount of time, in seconds, that each request should run for.  Defaults to 100 seconds.
		 * @param array $form The Form Object
		 */
		$max_execution_time = apply_filters( 'gform_export_max_execution_time', 150, $form ); // seconds
		$page_size          = 10000;

		$form_id = $form['id'];
		$fields  = $_POST['export_field'];

		$start_date = empty( $_POST['export_date_start'] ) ? '' : self::get_gmt_date( $_POST['export_date_start'] . ' 00:00:00' );
		$end_date   = empty( $_POST['export_date_end'] ) ? '' : self::get_gmt_date( $_POST['export_date_end'] . ' 23:59:59' );

		$search_criteria['status']        = 'active';
		$search_criteria['field_filters'] = GFCommon::get_field_filters_from_post( $form );
		if ( ! empty( $start_date ) ) {
			$search_criteria['start_date'] = $start_date;
		}

		if ( ! empty( $end_date ) ) {
			$search_criteria['end_date'] = $end_date;
		}

		//$sorting = array( 'key' => 'date_created', 'direction' => 'DESC', 'type' => 'info' );
		$sorting = array( 'key' => 'id', 'direction' => 'DESC', 'type' => 'info' );

		$form = GFExport::add_default_export_fields( $form );

		$total_entry_count     = GFAPI::count_entries( $form_id, $search_criteria );
		$remaining_entry_count = $offset == 0 ? $total_entry_count : $total_entry_count - $offset;

		// Adding BOM marker for UTF-8
		$lines = '';

		// Set the separator
		$separator = gf_apply_filters( array( 'gform_export_separator', $form_id ), ',', $form_id );

		$field_rows = GFExport::get_field_row_count( $form, $fields, $remaining_entry_count );

		if ( $offset == 0 ) {

			//Adding BOM marker for UTF-8
			$lines = chr( 239 ) . chr( 187 ) . chr( 191 );

			//writing header
			$headers = array();
			foreach ( $fields as $field_id ) {
				$field = RGFormsModel::get_field( $form, $field_id );
				$label = gf_apply_filters( array( 'gform_entries_field_header_pre_export', $form_id, $field_id ), GFCommon::get_label( $field, $field_id ), $form, $field );
				$value = str_replace( '"', '""', $label );

				GFCommon::log_debug( "GFExport::start_export(): Header for field ID {$field_id}: {$value}" );

				if ( strpos( $value, '=' ) === 0 ) {
					// Prevent Excel formulas
					$value = "'" . $value;
				}

				$headers[ $field_id ] = $value;

				$subrow_count = isset( $field_rows[ $field_id ] ) ? intval( $field_rows[ $field_id ] ) : 0;
				if ( $subrow_count == 0 ) {
					$lines .= '"' . $value . '"' . $separator;
				} else {
					for ( $i = 1; $i <= $subrow_count; $i ++ ) {
						$lines .= '"' . $value . ' ' . $i . '"' . $separator;
					}
				}

				//GFCommon::log_debug( "GFExport::start_export(): Lines: {$lines}" );
			}
			$lines = substr( $lines, 0, strlen( $lines ) - 1 ) . "\n";

			if ( $remaining_entry_count == 0 ) {
				GFExport::write_file( $lines, $export_id );
			}
		}

		// Paging through results for memory issues
		while ( $remaining_entry_count > 0 ) {

			$paging = array(
				'offset'    => $offset,
				'page_size' => $page_size,
			);
			$leads = GFAPI::get_entries( $form_id, $search_criteria, $sorting, $paging );

			$leads = gf_apply_filters( array( 'gform_leads_before_export', $form_id ), $leads, $form, $paging );

			GFCommon::log_debug( __METHOD__ . '(): search criteria: ' . print_r( $search_criteria, true ) );
			GFCommon::log_debug( __METHOD__ . '(): sorting: ' . print_r( $sorting, true ) );
			GFCommon::log_debug( __METHOD__ . '(): paging: ' . print_r( $paging, true ) );

			foreach ( $leads as $lead ) {
				GFCommon::log_debug( __METHOD__ . '(): Processing entry #' . $lead['id'] );

				foreach ( $fields as $field_id ) {
					switch ( $field_id ) {
						case 'date_created' :
							$lead_gmt_time   = mysql2date( 'G', $lead['date_created'] );
							$lead_local_time = GFCommon::get_local_timestamp( $lead_gmt_time );
							$value           = date_i18n( 'Y-m-d H:i:s', $lead_local_time, true );
							break;
						default :
							$field = RGFormsModel::get_field( $form, $field_id );

							$value = is_object( $field ) ? $field->get_value_export( $lead, $field_id, false, true ) : rgar( $lead, $field_id );
							$value = apply_filters( 'gform_export_field_value', $value, $form_id, $field_id, $lead );

							//GFCommon::log_debug( "GFExport::start_export(): Value for field ID {$field_id}: {$value}" );
							break;
					}

					if ( isset( $field_rows[ $field_id ] ) ) {
						$list = empty( $value ) ? array() : unserialize( $value );

						foreach ( $list as $row ) {
							$row_values = array_values( $row );
							$row_str    = implode( '|', $row_values );

							if ( strpos( $row_str, '=' ) === 0 ) {
								// Prevent Excel formulas
								$row_str = "'" . $row_str;
							}

							$lines .= '"' . str_replace( '"', '""', $row_str ) . '"' . $separator;
						}

						//filling missing subrow columns (if any)
						$missing_count = intval( $field_rows[ $field_id ] ) - count( $list );
						for ( $i = 0; $i < $missing_count; $i ++ ) {
							$lines .= '""' . $separator;
						}
					} else {
						$value = maybe_unserialize( $value );
						if ( is_array( $value ) ) {
							$value = implode( '|', $value );
						}

						if ( strpos( $value, '=' ) === 0 ) {
							// Prevent Excel formulas
							$value = "'" . $value;
						}

						$lines .= '"' . str_replace( '"', '""', $value ) . '"' . $separator;
					}
				}
				$lines = substr( $lines, 0, strlen( $lines ) - 1 );

				//GFCommon::log_debug( "GFExport::start_export(): Lines: {$lines}" );

				$lines .= "\n";
			}

			$offset += $page_size;
			$remaining_entry_count -= $page_size;

			if ( ! seems_utf8( $lines ) ) {
				$lines = utf8_encode( $lines );
			}

			$lines = apply_filters( 'gform_export_lines', $lines );

			GFExport::write_file( $lines, $export_id );

			/*
			Changes
			*/

			$upload_dir = wp_upload_dir();

			$baseurl = $upload_dir['baseurl'];

			$path = $upload_dir['path'];

			$myfile = fopen( $path . "/export-" . $form_id . '-' . date('Y-m-d g:i A') . ".csv", "w") or die("Unable to open file!");

			fwrite($myfile, $lines);
			fclose($myfile);

			/*
			End of changes
			*/



			$time_end       = microtime( true );
			$execution_time = ( $time_end - $time_start );

			if ( $execution_time >= $max_execution_time ) {
				GFCommon::log_debug( __METHOD__ . '(): Max Execution Time Exceeded: ' . print_r( $execution_time, true ) );
				break;
			}

			$lines = '';
		}

		$complete = $remaining_entry_count <= 0;

		if ( $complete ) {
			/**
			 * Fires after exporting all the entries in form
			 *
			 * @param array  $form       The Form object to get the entries from
			 * @param string $start_date The start date for when the export of entries should take place
			 * @param string $end_date   The end date for when the export of entries should stop
			 * @param array  $fields     The specified fields where the entries should be exported from
			 */
			do_action( 'gform_post_export_entries', $form, $start_date, $end_date, $fields );
		}

		$offset = $complete ? 0 : $offset;

		$status = array(
			'status'   => $complete ? 'complete' : 'in_progress',
			'offset'   => $offset,
			'exportId' => $export_id,
			'progress' => $remaining_entry_count > 0 ? intval( 100 - ( $remaining_entry_count / $total_entry_count ) * 100 ) . '%' : '',
		);

		GFCommon::log_debug( __METHOD__ . '(): Status: ' . print_r( $status, 1 ) );

		return $status;
	}
	
	public static function add_default_export_fields( $form ) {

		//adding default fields
		array_push( $form['fields'], array( 'id' => 'created_by', 'label' => __( 'Created By (User Id)', 'gravityforms' ) ) );
		array_push( $form['fields'], array( 'id' => 'id', 'label' => __( 'Entry Id', 'gravityforms' ) ) );
		array_push( $form['fields'], array( 'id' => 'date_created', 'label' => __( 'Entry Date', 'gravityforms' ) ) );
		array_push( $form['fields'], array( 'id' => 'source_url', 'label' => __( 'Source Url', 'gravityforms' ) ) );
		array_push( $form['fields'], array( 'id' => 'transaction_id', 'label' => __( 'Transaction Id', 'gravityforms' ) ) );
		array_push( $form['fields'], array( 'id' => 'payment_amount', 'label' => __( 'Payment Amount', 'gravityforms' ) ) );
		array_push( $form['fields'], array( 'id' => 'payment_date', 'label' => __( 'Payment Date', 'gravityforms' ) ) );
		array_push( $form['fields'], array( 'id' => 'payment_status', 'label' => __( 'Payment Status', 'gravityforms' ) ) );
		//array_push($form['fields'],array('id' => 'payment_method' , 'label' => __('Payment Method', 'gravityforms'))); //wait until all payment gateways have been released
		array_push( $form['fields'], array( 'id' => 'post_id', 'label' => __( 'Post Id', 'gravityforms' ) ) );
		array_push( $form['fields'], array( 'id' => 'user_agent', 'label' => __( 'User Agent', 'gravityforms' ) ) );
		array_push( $form['fields'], array( 'id' => 'ip', 'label' => __( 'User IP', 'gravityforms' ) ) );
		$form = GFExport::get_entry_meta( $form );

		$form = apply_filters( 'gform_export_fields', $form );
		$form = GFFormsModel::convert_field_objects( $form );

		return $form;
	}
	
	private static function get_entry_meta( $form ) {
		$entry_meta = GFFormsModel::get_entry_meta( $form['id'] );
		$keys       = array_keys( $entry_meta );
		foreach ( $keys as $key ) {
			array_push( $form['fields'], array( 'id' => $key, 'label' => $entry_meta[ $key ]['label'] ) );
		}

		return $form;
	}

}
//END OF GFCSVAutoExportAddOn CLASS

function plugin_add_upgrade_link( $links ) {
    $settings_link = '<a href="http://fallriver.digital/gravity-forms-autoexport-csv-add/">' . __( '<strong>Upgrade</strong>' ) . '</a>';
    array_push( $links, $settings_link );
  	return $links;
}
$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'plugin_add_upgrade_link' );

// Timezone Function

function aecsv_get_blog_timezone() {

    $tzstring = get_option( 'timezone_string' );
    $offset   = get_option( 'gmt_offset' );

    //Manual offset...
    //@see http://us.php.net/manual/en/timezones.others.php
    //@see https://bugs.php.net/bug.php?id=45543
    //@see https://bugs.php.net/bug.php?id=45528
    //IANA timezone database that provides PHP's timezone support uses POSIX (i.e. reversed) style signs
    if( empty( $tzstring ) && 0 != $offset && floor( $offset ) == $offset ){
        $offset_st = $offset > 0 ? "-$offset" : '+'.absint( $offset );
        $tzstring  = 'Etc/GMT'.$offset_st;
    }

    //Issue with the timezone selected, set to 'UTC'
    if( empty( $tzstring ) ){
        $tzstring = 'UTC';
    }

    $timezone = new DateTimeZone( $tzstring );
    return $timezone; 
}


//AJAX PHP and Javascript functions to clear out the form schedule

		add_action( 'wp_ajax_clear_form_schedule', 'clear_form_schedule' );

	function clear_form_schedule() {
			global $wpdb;

			$form_id = intval( $_POST['formID'] );

			$timestamp = wp_next_scheduled( 'csv_export_' . $form_id );

			wp_unschedule_event( $timestamp, 'csv_export_' . $form_id );

				echo 'Form ' . $form_id . ' Export Schedule Cleared!';

			wp_die();
		}

		add_action( 'admin_footer', 'clear_form_schedule_javascript' ); // Write our JS below here


	function clear_form_schedule_javascript() { ?>
			<script type="text/javascript" >
			jQuery(document).ready(function($) {
				$('button.schedule-clear').click(function(){
					var formID = $(this).attr('id');
					var data = {
						'action': 'clear_form_schedule',
						'formID': formID
					};

					
					jQuery.post(ajaxurl, data, function(response) {
						alert(response);
					});
				});
			});
			</script> <?php
		}
		
		
	function wpse27856_set_content_type(){
			return "text/html";
		}


// End of AJAX to clear schedule

		$automatedexportclass = new GF_AutoExport_CSV_AddOn();

		add_action( 'gform_loaded', array( 'GF_CSV_AutoExport_AddOn_Bootstrap', 'load' ), 5 );
		 
		class GF_CSV_AutoExport_AddOn_Bootstrap {
		 
			public static function load() {
		 
				if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
					return;
				}
		 
				require_once( 'class-autoexport-csv-gf.php' );
		 
				GFAddOn::register( 'GFCSVAutoExportAddOn' );
			}
		 
		}
		 
		function gf_autoexport_csv_addon() {
			return GFCSVAutoExportAddOn::get_instance();
		}




			
			
		
?>