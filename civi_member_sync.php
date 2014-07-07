<?php
/*
Plugin Name: CiviMember Role Synchronisation
Depends: CiviCRM
Plugin URI: https://github.com/jeevajoy/Wordpress-CiviCRM-Member-Role-Sync/
Description: Ensures your CiviCRM users have roles to match their memberships.
Author: Jag Kandasamy, Playgen
Version: 2.0.0
Author URI: http://www.orangecreative.net
*/

define( 'CIVISYNC_USER_ROLE', 'civi_sync' );

function civisync_setup_db()
{
	global $wpdb;
	$table_name = "{$wpdb->prefix}civi_member_sync";

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`civi_mem_type` int(11) NOT NULL,
		`wp_role` varchar(255) NOT NULL,
		`expire_wp_role` varchar(255) NOT NULL,
		`current_rule` varchar(255) NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `civi_mem_type` (`civi_mem_type`)
		) DEFAULT CHARSET=utf8";

	$wpdb->query( $sql );
}
function civisync_register_roles()
{
	add_role( 'civi_sync', 'Civi Sync User', array( 'read' ) );
}
register_activation_hook( __FILE__, function()
{
	civisync_setup_db();
	civisync_register_roles();
} );

function civisync_wp_login( /* string */ $user_login, WP_User $user )
{
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	if ( ! is_plugin_active( "civicrm/civicrm.php" ) )
		return;
	civicrm_initialize(); // In case it's not already
	civisync_perform_sync( $user );
}
add_action( 'wp_login', 'civisync_wp_login', 10, 2 );

/**
 * Syncs a wordpress user's roles with their memberships.
 * This only works if the user's primary role is the civisync role.
 * Warning: This will override any other roles assigned to it.
 * Note: CiviCRM must be active when this function is called.
 * @param WP_User $user
 * @throws CiviCRM_API3_Exception
 */
function civisync_perform_sync( WP_User $user )
{
	if ( ! in_array( CIVISYNC_USER_ROLE, $user->roles ) )
		return;
	$match = civicrm_api3( "UFMatch", "get", array(
		'sequential' => true,
		'uf_id' => $user->ID
	) );
	if ( 0 == $match['count'] )
		return; // hmm
	$match = reset( $match['values'] );
	$contact_id = $match['contact_id'];

	$membershibs = civicrm_api3( "Membership", "get", array(
		'sequential' => true,
		'contact_id' => $contact_id
	) );

	$roles = array();

	global $wpdb;
	$query = "SELECT * FROM `{$wpdb->prefix}civi_member_sync` WHERE `civi_mem_type`=%s LIMIT 1";
	foreach( (array) $membershibs['values'] as $membershibe ) { // wow
		$type   = $membershibe['membership_type_id'];
		$status = $membershibe['status_id'];
		$res = $wpdb->get_row( $wpdb->prepare( $query, $type ) );
		if ( ! $res )
			continue;
		$current_rule = unserialize( $res->current_rule );
		if ( ! isset( $roles[ $type ] ) )
			$roles[ $type ] = array();
		if ( in_array( $status, $current_rule ) ) {
			$roles[ $type ]['active'] = $res->wp_role;
		} elseif ( $res->expire_wp_role ) {
			$roles[ $type ]['inactive'] = $res->expire_wp_role;
		}
	}

	$civi_roles = array( CIVISYNC_USER_ROLE );
	foreach( $roles as $deets ) {
		if ( isset( $deets['active'] ) ) {
			$civi_roles[] = $deets['active'];
		} elseif ( ! empty( $deets['inactive'] ) ) {
			$civi_roles[] = $deets['inactive'];
		}
	}

	$user_roles = (array) $user->roles;
	$to_remove  = array_diff( $user_roles, $civi_roles );
	$to_add     = array_diff( $civi_roles, $user_roles );

	// Both remove role and add role call update_user_meta every call. Jazzhands!
	foreach( $to_remove as $role_name )
		$user->remove_role( $role_name );
	foreach( $to_add as $role_name )
		$user->add_role( $role_name );
}

if ( is_admin() )
	include 'civisync-options-page.php';
