<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

GFForms::include_addon_framework();
 
class GFCSVAutoExportAddOn extends GFAddOn {
 
    protected $_version = GF_AUTOEXPORT_CSV;
    protected $_min_gravityforms_version = '1.9';
    protected $_slug = 'autoexport_csv_addon';
    protected $_path = 'AutoExport-CSV-GF/autoexport-csv-gf.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Gravity Form Add-on AutoExport Entries to CSV';
    protected $_short_title = 'AutoExport CSV';
 
    private static $_instance = null;
 
    public static function get_instance() {
        if ( self::$_instance == null ) {
            self::$_instance = new GFCSVAutoExportAddOn();
        }
 
        return self::$_instance;
    }
 
    public function init() {
        parent::init();
        add_filter( 'gform_submit_button', array( $this, 'form_submit_button' ), 10, 2 );
		
    }
	
	
 
    public function scripts() {
        $scripts = array(
            array(
                'handle'  => 'my_script_js',
                'src'     => $this->get_base_url() . '/js/select-all.js',
                'version' => $this->_version,
                'deps'    => array( 'jquery' ),
                'strings' => array(
                    'first'  => esc_html__( 'First Choice', 'autoexport_csv_addon' ),
                    'second' => esc_html__( 'Second Choice', 'autoexport_csv_addon' ),
                    'third'  => esc_html__( 'Third Choice', 'autoexport_csv_addon' )
                ),
                'enqueue' => array(
                    array(
                        'admin_page' => array( 'form_settings' ),
                        'tab'        => 'autoexport_csv_addon'
                    )
                )
            ),
 
        );
 
        return array_merge( parent::scripts(), $scripts );
    }
 
    function form_submit_button( $button, $form ) {
        $settings = $this->get_form_settings( $form );
        if ( isset( $settings['enabled'] ) && true == $settings['enabled'] ) {
            $text   = $this->get_plugin_setting( 'mytextbox' );
            $button = "<div>{$text}</div>" . $button;
        }
 
        return $button;
    }
 
   
   
	
	/* Get form fields and format for checkboxes
   
   */
 
	public function get_form_fields_as_checkbox_choices( $form, $args = array() ) {

        $fields = array();

        if ( ! is_array( $form['fields'] ) ) {
            return $fields;
        }

        $args = wp_parse_args(
            $args, array(
                'field_types'    => array(),
                'input_types'    => array(),
                'callback'       => false
            )
        );
		
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
		
		
        foreach ( $form['fields'] as $field ) {
			
            $input_type               = GFFormsModel::get_input_type( $field );
            $is_applicable_input_type = empty( $args['input_types'] ) || in_array( $input_type, $args['input_types'] );
			

            if ( is_callable( $args['callback'] ) ) {
                $is_applicable_input_type = call_user_func( $args['callback'], $is_applicable_input_type, $field, $form );
            }

            if ( ! $is_applicable_input_type ) {
                continue;
            }

            if ( ! empty( $args['property'] ) && ( ! isset( $field->$args['property'] ) || $field->$args['property'] != $args['property_value'] ) ) {
                continue;
            }

            $inputs = $field->get_entry_inputs();

          if ( ! $field->displayOnly ) {
                $fields[] = array( 
					'label' => esc_html__( GFCommon::get_label( $field ), 'autoexport_csv_addon'), 
					'name' => $field['id'],
					
				);
            } 
        }
        return $fields;
    }
	
	
	public function plugin_page() {
    echo '<ul style="font-size: 18px;">';
    echo '<li>Fully custom export schedules.</li>';
    echo '<li>Select and deselect the form fields that you want in your export.</li>';
    echo '<li>Specify the exact date of your first export.</li>';
    echo '</ul>';
    echo '<p>&nbsp;</p>';
    echo '<img style="width: 70%;" src="http://fallriver.digital/wp-content/uploads/2017/11/autoexportcsv-image-1.png">';
    echo '</br>';
    echo '<img style="width: 70%;" src="http://fallriver.digital/wp-content/uploads/2017/11/autoexportcsv-image-3.png">';
    echo '<p>&nbsp;</p>';
    echo '<a class="button-primary" style="font-size: 22px;" href="http://fallriver.digital/gravity-forms-autoexport-csv-add/">Get Gravity Forms AutoExport Premium</a>';
}
	
 
    public function form_settings_fields( $form ) {
		$field_choices = self::get_form_fields_as_checkbox_choices($form);
        return array(
			array(
				'title'  => esc_html__( 'AutoExport CSV Settings', 'autoexport_csv_addon' ),
                'fields' => array(
			array(
						'label'   => esc_html__( 'Next Scheduled Export', 'autoexport_csv_addon' ),
                        'type'  => 'my_schedule_clearing_ui',
                        'name'  => 'Clear Export Schedule',     
                       
                    )
				)
			),
			array(
                'title'  => esc_html__( 'Export Fields', 'autoexport_csv_addon' ),
                'fields' => array(
				    array(
                       'label'   => esc_html__( 'Fields to Include in Report', 'autoexport_csv_addon' ),
                        'type'    => 'checkbox',
                        'name'    => 'select_all',
                        'tooltip' => esc_html__( 'Select the form fields that you want to include as columns on your CSV export.', 'autoexport_csv_addon' ),
                        'choices' => array(
							 array(
							'label'         => esc_html__( 'Select All', 'sometextdomain' ),
							'name'          => 'select_all',
							'default_value' => 0,
							),
						)         
					),
				    array(
                        'type'    => 'checkbox',
                        'name'    => 'field_choices',
                        'choices' => $field_choices,         
						),
				)
			),
				
			array(
				'title'  => esc_html__( 'Export Schedule', 'autoexport_csv_addon' ),
                'fields' => array(
                    array(
                    
                        'type'    => 'select',
                        'name'    => 'type_of_interval',
                        'choices' => array(
							array(
                                'label' => esc_html__( 'Hourly', 'autoexport_csv_addon' ),
                                'value' => 'hourly',
                            ),
							array(
                                'label' => esc_html__( 'Daily', 'autoexport_csv_addon' ),
                                'value' => 'daily',
                            ),
                            array(
                                'label' => esc_html__( 'Weekly', 'autoexport_csv_addon' ),
                                'value' => 'weekly',
                            ),
                            array(
                                'label' => esc_html__( 'Monthly', 'autoexport_csv_addon' ),
                                'value' => 'monthly',
                            ),
                        ),
                    ),
				),
			),
				
				array(
				'title'  => esc_html__( 'Export Range', 'autoexport_csv_addon' ),
                'fields' => array(
					 array(
                        'label'   => esc_html__( 'How far back should the export go?', 'autoexport_csv_addon' ),
                        'type'    => 'select',
                        'name'    => 'size_of_export',
                        'tooltip' => esc_html__( 'This is the setting that determines how far back the export should go. Do you want it to just go back one day, one week, or even all of time?', 'autoexport_csv_addon' ),
                        'choices' => array(
                            array(
                                'label' => esc_html__( 'One Day', 'autoexport_csv_addon' ),
                                'value' => 'one_day',
                            ),
							array(
                                'label' => esc_html__( 'One Week', 'autoexport_csv_addon' ),
                                'value' => 'one_week',
                            ),
                            array(
                                'label' => esc_html__( 'One Month', 'autoexport_csv_addon' ),
                                'value' => 'one_month',
                            ),
                            array(
                                'label' => esc_html__( 'One Quarter', 'autoexport_csv_addon' ),
                                'value' => 'one_quarter',
                            ),
							array(
                                'label' => esc_html__( 'One Year', 'autoexport_csv_addon' ),
                                'value' => 'one_year',
                            ),
							array(
                                'label' => esc_html__( 'All of Time', 'autoexport_csv_addon' ),
                                'value' => 'all_of_time',
                            ),
                        ),
                    ),
				
				),
			
			),
				array(
						'title'  => esc_html__( 'Send Export', 'autoexport_csv_addon' ),
						'fields' => array(
							 array(
								'label'             => esc_html__( 'Emails to Send Report to.', 'autoexport_csv_addon' ),
								'type'              => 'text',
								'name'              => 'email-1',
								'tooltip'           => esc_html__( 'These are the emails that the csv report will be sent to.', 'autoexport_csv_addon' ),
								'class'             => 'medium',
								'feedback_callback' => array( $this, 'is_valid_email' ),
							),
							array(
								
								'type'              => 'text',
								'name'              => 'email-2',
								'class'             => 'medium',
								'feedback_callback' => array( $this, 'is_valid_email' ),
							),
							array(
							 
								'type'              => 'text',
								'name'              => 'email-3',
								'class'             => 'medium',
								'feedback_callback' => array( $this, 'is_valid_email' ),
							),
							array(
							 
								'type'              => 'text',
								'name'              => 'email-4',
								'class'             => 'medium',
								'feedback_callback' => array( $this, 'is_valid_email' ),
							),
							array(
						 
								'type'              => 'text',
								'name'              => 'email-5',  
								'class'             => 'medium',
								'feedback_callback' => array( $this, 'is_valid_email' ),
							),
							array(
								'label'   => esc_html__( 'Enable Automatic export', 'autoexport_csv_addon' ),
								'type'    => 'checkbox',
								'name'    => 'enable_export',
								'tooltip' => esc_html__( 'This will enable the automatic export of the csv file for this form.', 'autoexport_csv_addon' ),
								'choices' => array(
									array(
										'label' => esc_html__( 'Enabled', 'autoexport_csv_addon' ),
										'name'  => 'enabled',
										),
									),
							),
					
						),
					),
        );
    }
	//function to create datetime-local input
	   public function settings_datetime_local( $field, $echo = true ) {
 
        $field['type']       = 'datetime-local'; 
        $field['input_type'] = rgar( $field, 'input_type' ) ? rgar( $field, 'input_type' ) : $field['type'];
        $attributes          = $this->get_field_attributes( $field );
        $default_value       = rgar( $field, 'value' ) ? rgar( $field, 'value' ) : rgar( $field, 'default_value' );
        $value               = $this->get_setting( $field['name'], $default_value );

        $html    = '';

        $html .= '<input
                    type="' . esc_attr( $field['input_type'] ) . '"
                    name="_gaddon_setting_' . esc_attr( $field['name'] ) . '"
                    value="' . esc_attr( htmlspecialchars( $value, ENT_QUOTES ) ) . '" ' .
                 implode( ' ', $attributes ) .
                 ' /> PST';
		
		 $html .= rgar( $field, 'after_input' );

        $feedback_callback = rgar( $field, 'feedback_callback' );
        if ( is_callable( $feedback_callback ) ) {
            $is_valid = call_user_func_array( $feedback_callback, array( $value, $field ) );
            $icon     = '';
            if ( $is_valid === true ) {
                $icon = 'icon-check fa-check gf_valid'; // check icon
            } elseif ( $is_valid === false ) {
                $icon = 'icon-remove fa-times gf_invalid'; // x icon
            }

            if ( ! empty( $icon ) ) {
                $html .= "&nbsp;&nbsp;<i class=\"fa {$icon}\"></i>";
            }
        }

        if ( $this->field_failed_validation( $field ) ) {
            $html .= $this->get_error_icon( $field );
        }

        if ( $echo ) {
            echo $html;
        }

        return $html;
		
    }
	
	// function to display the schedule clearing UI
	
	public function settings_schedule_clearing_ui( $field, $echo = true) {
		
		$form = parent::get_current_form();
		
		$form_id = $form['id'];
		
		if (wp_next_scheduled( 'csv_export_' . $form_id )) {
		
		
		$next_export_stamp = wp_next_scheduled( 'csv_export_' . $form_id );
		
		$correct_timezone_time = get_date_from_gmt( date('Y-m-d H:i:s', $next_export_stamp), 'Y-m-d H:i:s A' );
		
		$current_frequency = wp_get_schedule( 'csv_export_' . $form_id );
		
		$html = '';

		$html.= '
		<p class="notice notice-warning is-dismissible">There is currently an export for this form that is scheduled next for <b> ' . $correct_timezone_time . '</b>. 
		If you want to start a new export time and schedule, you will have to clear out the current export. To do this, set your desired settings, save those settings and then click this button to clear the current export schedule.
		<button type="click" class="button schedule-clear" style="margin: 2px;" id="'. $form_id .'">Clear The Current Export Schedule</button>
		</p>
		';
		
		 if ( $echo ) {
            echo $html;
        }

        return $html;
	
	}
	
	else {
			
		$html = '';

		$html.= '
		<p class="notice notice-info is-dismissible">There is currently no export for this form scheduled at any time in the future.
		</p>
		';
		
		 if ( $echo ) {
            echo $html;
        }
	}
	
	}

	
    public function settings_my_date_time_field( $field, $echo = true ) {
  
        $date_time_field = $field['args']['Date and Time'];
        $this->settings_datetime_local( $date_time_field );
 
    }
	
	public function settings_my_schedule_clearing_ui( $field, $echo = true ) {

		$schedule_clear_ui = $field['args']['Clear The Export Schedule'];
        $this->settings_schedule_clearing_ui( $schedule_clear_ui );

	}
	
 
	// Validation functions
 
    public function is_valid_email( $value ) {
       if( filter_var($value, FILTER_VALIDATE_EMAIL) ) 
			{return TRUE;}
	   else 
			{return FALSE;}  
    }
	
	public function is_valid_number( $value ) {
		$int_value = preg_replace("/[^0-9,.]/", "", $value);
        return is_int(intval($int_value));
    }
	
	
	//timezone function 
	
	
 
}