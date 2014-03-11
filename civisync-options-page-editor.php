<?php
$membershibs = civisync_get_memberships();
$statizzles  = civisync_get_stati();

	/*
	if ( empty( $post_id ) ) {
		wp_redirect( admin_url('post.php') );
		exit();
	}

	if ( ! $post )
		wp_die( __( 'You attempted to edit an item that doesn&#8217;t exist. Perhaps it was deleted?' ) );
	*/

function start_row( $id, $label )
{
?>
	<tr class="form-required">
		<th scope="row" valign="top"><label for="<?= $id; ?>"><?= $label; ?></label></th>
		<td>
<?php
}
function end_row( $desc = '' )
{
	if ( $desc ): ?>
<p class="description"><?= $desc; ?></p>
<?php endif; ?>
	</td>
</tr>
<?php
}
function do_dropdown( $id, array $values, $selected = false, $required = true )
{
?>
	<select id="<?= $id; ?>" name="<?= $id; ?>" <?php if ( $required ) echo 'required'; ?>>
		<option value="" <?php if ( ! $selected ) echo 'selected'; if ( $required ) echo 'disabled'; ?>></option>
	<?php foreach( $values as $id => $name ): ?>
		<option value="<?= $id; ?>" <?php if ( $selected == $id ) echo 'selected'; ?> >
			<?= $name; ?>
		</option>
	<?php endforeach; ?>
	</select>
<?php
}
function do_flags( $id, array $flags, array $selected = array() )
{
?>
	<fieldset id="<?= $id; ?>" required>
<?php foreach( $flags as $flag_id => $name ): $cbox_name = $id . '[' . $flag_id . ']';?>
		<input type="checkbox" name="<?= $cbox_name ?>" value="<?= $flag_id ?>">
		<label for="<?= $cbox_name ?>"><?= $name; ?></label>
		<br>
<?php endforeach; ?>
	</fieldset>
<?php
}
/* <input id="<?= $id; ?>" name="<?= $id; ?>" type="<?= $type; ?>" required value="<?= $value; ?>"> */
/*
			'name'           => __( 'Civi Membership Type', 'civisync' ),
			'wp_role'        => __( 'Wordpress Role', 'civisync' ),
			'expire_wp_role' => __( 'Expiry Assign Role', 'civisync' ),
			'current_rule'   => __( 'Current Codes', 'civisync' ),
			'expiry_rule'    => __( 'Expired Codes', 'civisync' )
*/
global $wp_roles;
$roles = $wp_roles->get_names();
?>
<div class="wrap">
	<h2><?php if ( $action == 'new' ) echo 'New'; else echo 'Edit'; ?>
		CiviCRM Membership Sync Rule</h2>
	<p>
		Choose a CiviMember Membership Type and a Wordpress Role below.
		This will associate that Membership with the Role.
		<br>
		If you would like the have the same Membership be associated with more than one role,
		 you will need to add a second association rule after you have completed this one.
	</p>
	<form method="post" action="?page=<?php echo $_REQUEST['page']; ?>">
		<?php wp_nonce_field( 'civisync-new-rule' ); ?>
		<input type="hidden" name="action" value="post-new">
		<table class="form-table">
		<?php
			start_row( 'civi_member_type', __( 'Civi Membership Type', 'civisync' ) );
			do_dropdown( 'civi_member_type', $membershibs );
			end_row( '' );
			start_row( 'wp_role', __( 'Active Wordpress Role', 'civisync' ) );
			do_dropdown( 'wp_role', $roles );
			end_row( 'What role this membership grants when active.' );
			start_row( 'expired_wp_role', __( 'Expired Wordpress Role', 'civisync' ) );
			do_dropdown( 'expired_wp_role', $roles, false, false );
			end_row( '(optional) What role a user should be given if their membership expires.' );
			start_row( 'activation_rules', __( 'Active Statuses', 'civisync' ) );
			do_flags( 'activation_rules', $statizzles );
			end_row( __( 'A membership with one of these statuses is considered to be active, otherwise it is inactive.', 'civisync' ) );
		?>
		</table>
		<?php submit_button(
			__( 'Add Rule', 'civisync' ),
			'primary',
			'submit'
		); ?>
	</form>
</div>
