<div class="wrap">
	<h2>Manual Synchronisation</h2>
	<p>This will synchronise the roles of <strong>ALL</strong> users with the &quot;<em>Civi Sync User</em>&quot; role.</p>
	<p>This may take a while with a lot of users</p>
	<form method="post" action="?page=<?php echo $_REQUEST['page']; ?>">
		<?php wp_nonce_field( 'civisync-manual-sync' ); ?>
		<input type="hidden" name="action" value="sync-confirm">
		<?php submit_button(
			__( 'Synchronise Now','civisync' ),
			'primary',
			'submit'
		); ?>
	</form>
</div>
