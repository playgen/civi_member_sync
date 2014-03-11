<?php

function civisync_manual_sync()
{
	$errors = array();
	foreach( (array) get_users() as $user ) {
		try {
			civisync_perform_sync( $user );
		} catch( CiviCRM_API3_Exception $e ) {
			$errors[ $user->display_name ] = $e->getErrorMessage();
		}
	}
	add_action( 'admin_notices', function() use( $errors )
	{
?>
<div class="updated">
	<p>Manual Synchronisation completed
<?php if ( count( $errors ) > 0 ): ?>
	with <?= count( $errors ) ?> errors.
<?php endif; ?>
	</p>
</div>
<?php foreach( $errors as $user => $message ): ?>
<div class="error">
	<p><strong><?= $user ?></strong>: <?= $message ?></p>
</div>
<?php endforeach;
	} );
}

function _civisync_get_name( array $arr ) {
	return $arr['name'];
}
function _civisync_get_the_thing( $name )
{
	try {
		civicrm_initialize();
		$things = civicrm_api3( $name, "get" );
		return array_map( '_civisync_get_name', $things['values'] );
	} catch ( CiviCRM_API3_Exception $e ) {
		CRM_Core_Error::handleUnhandledException( $e );
	}
}

function civisync_rule_message( $action, $error = false )
{
	add_action( 'admin_notices', function() use( $action, $error )
	{
		if ( $error ):
?>
<div class="error">
	<p><strong>Unable to <?= $action; ?> rule</strong>: <?= $error; ?></p>
</div>
<?php else: ?>
<div class="updated">
	<p>Rule <?= $action; ?>d</p>
</div>
<?php
		endif;
	} );
}

function _civisync_param_require( $name, $optional = false )
{
	if ( empty( $_REQUEST[ $name ] ) ) {
		if ( $optional && isset( $_REQUEST[ $name ] ) )
			return '';
		wp_die( "Missing parameter '$name'!", "Missing parameter" );
	}
	return $_REQUEST[ $name ];
}

function _civisync_get_req_data()
{
	$params = array(
		'civi_mem_type'  => _civisync_param_require( 'civi_mem_type' ),
		'wp_role'        => _civisync_param_require( 'wp_role' ),
		'expire_wp_role' => _civisync_param_require( 'expire_wp_role', true ),
		'current_rule'   => _civisync_param_require( 'activation_rules' ),
	);
	if ( ! is_array( $params['current_rule'] ) )
		wp_die( "Parameter 'activation_rules' is supposed to be an array!" );
	$params['current_rule'] = serialize( $params['current_rule'] );
	return $params;
}

function civisync_rule_create()
{
	global $wpdb;
	$params = _civisync_get_req_data();
	$wpdb->insert( $wpdb->prefix . 'civi_member_sync', $params );
	civisync_rule_message( 'create' );
}
function civisync_rule_edit()
{
	global $wpdb;
	$id = _civisync_param_require( 'rule' );
	$params = _civisync_get_req_data();
	$wpdb->update( $wpdb->prefix . 'civi_member_sync', $params, array(
		'id' => $id
	), null, array( '%d' ) );
	civisync_rule_message( 'update' );
}

function civisync_rule_delete( $id )
{
	global $wpdb;
	$wpdb->delete( $wpdb->prefix . 'civi_member_sync', array(
		'id' => $id
	), array( '%d' ) );
}

function civisync_handle_table_actions( $list_table )
{
	$action = $list_table->current_action();
	if ( ! $action )
		return;
	// These actions don't require anything happening
	if ( 'new' == $action || 'edit' == $action ) {
		return;
	} elseif ( 'post-new' == $action ) {
		check_admin_referer( 'civisync-rule-new' );
		return civisync_rule_create();
	} elseif ( 'post-edit' == $action ) {
		check_admin_referer( 'civisync-rule-edit' );
		return civisync_rule_edit();
	} elseif ( 'sync-confirm' == $action ) {
		check_admin_referer( 'civisync-manual-sync' );
		return civisync_manual_sync();
	}

	if ( empty( $_REQUEST['rule'] ) )
		return;

	check_admin_referer( 'bulk-' . $list_table->_args['plural'] );
	$rules = (array) $_REQUEST['rule']; // Abuse that (array) "2" == array("2")
	// if ( 'disable' == $action )
	// 	array_walk($rules, 'shib_provider_disable');
	// elseif ( 'enable' == $action )
	// 	array_walk($rules, 'shib_provider_enable');
	// else
	if ( 'delete' == $action )
		array_walk($rules, 'civisync_rule_delete');
}

function civisync_get_memberships()
{
	return _civisync_get_the_thing( "MembershipType" );
}
function civisync_get_stati()
{
	return _civisync_get_the_thing( "MembershipStatus" );
}

add_action( 'admin_menu', function() {
	$list_table = null;
	// add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function );
	$id = add_options_page( "CiviCRM Membership to WordPress Roles", "CiviCRM ↔ WP Sync", 'manage_options', 'civisync', function() use( &$list_table )
	{
		$action = $list_table->current_action();
		if ( $action == 'sync' )
			include "civisync-options-page-manual-sync.php";
		if ( $action == 'new' || $action == 'edit' )
			include "civisync-options-page-editor.php";
		else
			include "civisync-options-page-table.php";
	} );
	add_action( "load-{$id}", function() use( &$list_table )
	{
		$ms = civisync_get_memberships();
		$ss = civisync_get_stati();
		require 'class-civisync-rule-table.php';
		$list_table = new Civisync_Rule_Table( $ms, $ss );
		civisync_get_memberships();
		civisync_handle_table_actions( $list_table );
		$list_table->prepare_items();
		add_screen_option( 'per_page', array(
			'label' => 'Rules per page',
			'default' => 10,
			'option' => 'civisync_rules_per_page'
		) );
	} );
} );
add_filter('set-screen-option', function( $status, $option, $value )
{
	if ( 'civisync_rules_per_page' == $option )
		return $value;
	return $status;
}, 10, 3);
