<?php
/**
 * Plugin Name: Disposable Email Blocker - WPForms
 * Plugin URI: https://wordpress.org/plugins/disposable-email-blocker-wpforms/
 * Author: Sajjad Hossain Sagor
 * Description: Prevent Submitting Spammy Disposable/Temporary Emails On WPForms Contact Form.
 * Version: 1.0.3
 * Author URI: https://sajjadhsagor.com
 * Text Domain: disposable-email-blocker-wpforms
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// plugin root path....
define( 'DEBWPFORMS_ROOT_DIR', dirname( __FILE__ ) );

// plugin root url....
define( 'DEBWPFORMS_ROOT_URL', plugin_dir_url( __FILE__ ) );

// plugin version
define( 'DEBWPFORMS_VERSION', '1.0.3' );

// load translation files...
add_action( 'plugins_loaded', 'debwpforms_load_plugin_textdomain' );

function debwpforms_load_plugin_textdomain()
{	
	load_plugin_textdomain( 'disposable-email-blocker-wpforms', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

// add toggle to enable email blocking
add_action( 'wpforms_form_settings_general', function( $form )
{
	wpforms_panel_field(
		'checkbox',
		'settings',
		'block_disposable_emails',
		$form->form_data,
		esc_html__( 'Block Disposable/Temporary Emails', 'disposable-email-blocker-wpforms' ),
		array(
			'tooltip' => esc_html__( 'Enables blocking disposable and temporary emails from submitting.', 'disposable-email-blocker-wpforms' ),
		)
	);

	wpforms_panel_field(
		'text',
		'settings',
		'disposable_emails_found_msg',
		$form->form_data,
		esc_html__( 'Disposable Email Found Error Text', 'disposable-email-blocker-wpforms' ),
		array(
			'default' => esc_html__( 'Disposable/Temporary emails are not allowed! Please use a non temporary email', 'disposable-email-blocker-wpforms' ),
		)
	);
} );

// check if disposable email is found and if so then mark form as invalid and show message
add_action( 'wpforms_process_validate_email', 'debwpforms_block_disposable_emails', 10, 3 );

function debwpforms_block_disposable_emails( $field_id, $field_submit, $form_data )
{
	// if not blocking is enabled return early	
	if ( $form_data['settings']['block_disposable_emails'] !== '1' ) return;
	
	if( filter_var( $field_submit, FILTER_VALIDATE_EMAIL ) )
	{
		// split on @ and return last value of array (the domain)
		$domain = explode('@', $field_submit );
		
		$domain = array_pop( $domain );

		// get domains list from json file
		$disposable_emails_db = file_get_contents( DEBWPFORMS_ROOT_DIR . '/assets/data/domains.min.json' );

		// convert json to php array
		$disposable_emails = json_decode( $disposable_emails_db );

		// check if domain is in disposable db
		if ( in_array( $domain, $disposable_emails ) )
		{	
			wpforms()->process->errors[ $form_data['id'] ][ $field_id ] = $form_data['settings']['disposable_emails_found_msg'];
			
			return;
		}
	}
}
