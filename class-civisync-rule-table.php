<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class Civisync_Rule_Table extends WP_List_Table {

	private $memberships;
	private $statuses;
	/**
	 * Constructor, we override the parent to pass our own arguments
	 * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
	 */
	function __construct( $memberships, $statuses ) {
		parent::__construct( array(
			'singular' => 'civisync_rule', // Singular label
			'plural'   => 'civisync_rules', // plural label, also this well be one of the table css class
			'ajax'     => false // We won't support Ajax for this table
		) );
		$this->memberships = $memberships;
		$this->statuses = $statuses;
	}

	function get_columns() {
		return array(
			// 'cb'             => '<input type="checkbox" />',
			// 'id'             => __( 'ID', 'civisync' ),
			'name'           => __( 'Civi Membership Type', 'civisync' ),
			'wp_role'        => __( 'Wordpress Role', 'civisync' ),
			'expire_wp_role' => __( 'Expiry Assign Role', 'civisync' ),
			'current_rule'   => __( 'Current Codes', 'civisync' ),
			// 'expiry_rule'    => __( 'Expired Codes', 'civisync' )
			);
	}

	function get_sortable_columns() {
		return array(
			// 'id'              => array( 'id', false ),
			'name'            => array( 'civi_mem_type', false ),
			'wp_role'         => array( 'wp_role', false ),
			'expire_wp_role'  => array( 'expire_wp_role', true )
		);
	}

	function prepare_items()
	{
		global $wpdb;

		$table = "{$wpdb->base_prefix}civi_member_sync";

		// Pagination Args
		$totalitems = $wpdb->get_var("SELECT count(`id`) FROM `{$table}`");
		$perpage = $this->get_items_per_page( 'rules_per_page', 10 );
		// This is done as early as possible because it might result in a redirect.
		$this->set_pagination_args( array(
			"total_items" => $totalitems,
			"per_page"    => $perpage,
		) );

		$query = "SELECT * FROM `{$table}`";

		// Dr Search
		// if ( ! empty( $_GET['s'] ) ) {
		// 	$query .= $wpdb->prepare(' WHERE `nicename` LIKE %s', '%' . $_GET['s'] . '%');
		// }

		// Ordering
		if ( ! empty( $_GET['orderby'] ) ) {
			$orderby = mysql_real_escape_string( $_GET['orderby'] );
			$order = isset( $_GET['order'] ) && strtolower( $_GET['order'] ) == 'desc' ? 'desc' : 'asc';
			$query .= " ORDER BY {$orderby} {$order}";
		}

		// Actual Pagination
		$paged = $this->get_pagenum();
		$offset = ( $paged - 1 ) * $perpage;
		$query .= " LIMIT {$offset}, {$perpage}";

		// Items
		$this->items = $wpdb->get_results( $query );
	}

	function get_bulk_actions()
	{
		return array(
			// 'edit'   => 'Edit',
			// 'delete' => 'Delete Permanently'
		);
	}
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="rule[]" value="%s" />', $item->id
		);
	}

	function column_name( $item )
	{
		// Use bulk- just so the damn thing works
		$url = wp_nonce_url( "?page={$_REQUEST['page']}&rule={$item->id}", 'bulk-' . $this->_args['plural'] );
		$link = '<a href="'. $url .'&action=%s">%s</a>';
		$actions = array();
		$actions['edit']   = sprintf( $link, 'edit', "Edit" );
		$actions['delete'] = sprintf( $link, 'delete',  "Delete Permanently"  );
		$name = $item->civi_mem_type;
		if ( isset( $this->memberships[ $name ] ) )
			$name = $this->memberships[ $name ];
		return $name . ' ' . $this->row_actions( $actions );
	}
	function column_current_rule( $item ) {
		return $this->_column_membership_status( $item->current_rule );
	}
	function column_expiry_rule( $item ) {
		return $this->_column_membership_status( $item->expiry_rule );
	}

	function column_default( $item, $column )
	{
		return $item->{$column};
	}

	function _column_membership_status( $rules )
	{
		$rules = unserialize( $rules );
		$rules = array_map( array( $this, '_column_membership_status_name' ), $rules );
		return implode( '<br>', $rules );
	}

	function _column_membership_status_name( $id )
	{
		if ( isset( $this->statuses[ $id ] ) )
			return $this->statuses[ $id ];
		return $id;
	}

}

