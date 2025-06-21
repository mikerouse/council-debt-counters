<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$req_action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
$council_id = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : 0;

if ( 'delete' === $req_action && $council_id ) {
		check_admin_referer( 'cdc_delete_council_' . $council_id );
		wp_delete_post( $council_id, true );
		echo '<div class="alert alert-success"><p>' . esc_html__( 'Council deleted.', 'council-debt-counters' ) . '</p></div>';
		$req_action = '';
}

if ( 'edit' === $req_action ) {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html( $council_id ? __( 'Edit Council', 'council-debt-counters' ) : __( 'Add Council', 'council-debt-counters' ) ) . '</h1>';
	$fields  = \CouncilDebtCounters\Custom_Fields::get_fields();
	$enabled = (array) get_option( 'cdc_enabled_counters', array() );
	$mapping = array(
		'debt'        => array( 'current_liabilities', 'long_term_liabilities', 'finance_lease_pfi_liabilities', 'manual_debt_entry', 'interest_paid_on_debt', 'total_debt' ),
		'spending'    => array( 'annual_spending' ),
		'income'      => array( 'total_income' ),
		'deficit'     => array( 'annual_deficit' ),
		'interest'    => array( 'interest_paid' ),
		'reserves'    => array( 'usable_reserves' ),
		'consultancy' => array( 'consultancy_spend' ),
	);
	$groups  = array( 'general' => array() );
	foreach ( $enabled as $e ) {
		$groups[ $e ] = array();
	}
	$docs_field = null;
	foreach ( $fields as $field ) {
		if ( 'statement_of_accounts' === $field->name ) {
			$docs_field = $field;
			continue; }
		$placed = false;
		foreach ( $mapping as $group_key => $field_names ) {
			if ( in_array( $field->name, $field_names, true ) ) {
				if ( isset( $groups[ $group_key ] ) ) {
					$groups[ $group_key ][] = $field; }
					$placed = true;
					break;
			}
		}
		if ( ! $placed ) {
			$groups['general'][] = $field; }
	}
		$docs = $council_id ? \CouncilDebtCounters\Docs_Manager::list_documents( $council_id ) : array();
	?>
	<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="cdc_save_council">
		<?php wp_nonce_field( 'cdc_save_council' ); ?>
				<input type="hidden" name="post_id" value="<?php echo esc_attr( $council_id ); ?>">
		<ul class="nav nav-tabs" role="tablist">
			<li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab"><?php esc_html_e( 'General', 'council-debt-counters' ); ?></button></li>
			<?php
			foreach ( $enabled as $tab_key ) :
				if ( empty( $groups[ $tab_key ] ) ) {
								continue;}
				?>
								<li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-<?php echo esc_attr( $tab_key ); ?>" type="button" role="tab"><?php echo esc_html( ucfirst( $tab_key ) ); ?></button></li>
						<?php endforeach; ?>
			<?php if ( $docs_field ) : ?>
				<li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-docs" type="button" role="tab"><?php esc_html_e( 'Documents', 'council-debt-counters' ); ?></button></li>
			<?php endif; ?>
						<?php if ( $council_id ) : ?>
				<!-- Whistleblower reports moved to dedicated admin page -->
			<?php endif; ?>
		</ul>
		<div class="tab-content pt-3">
			<div class="tab-pane fade show active" id="tab-general" role="tabpanel">
				<table class="form-table" role="presentation">
				<?php
				$council_types     = array( 'Unitary', 'County', 'District', 'Metropolitan Borough', 'London Borough', 'Parish', 'Town', 'Combined Authority' );
				$council_locations = array( 'England', 'Wales', 'Scotland', 'Northern Ireland' );
				foreach ( $groups['general'] as $field ) :
						$val         = $council_id ? \CouncilDebtCounters\Custom_Fields::get_value( $council_id, $field->name ) : '';
						$input_type  = 'text' === $field->type ? 'text' : 'number';
						$is_required = (bool) $field->required;
						$readonly    = in_array( $field->name, \CouncilDebtCounters\Custom_Fields::READONLY_FIELDS, true );
					?>
					<tr>
						<th scope="row"><label for="cdc-field-<?php echo esc_attr( $field->id ); ?>"><?php echo esc_html( $field->label ); ?>
					<?php
					if ( $field->required ) {
						echo ' *';}
					?>
						</label></th>
						<td>
										<?php if ( 'council_type' === $field->name ) : ?>
																<select id="cdc-field-<?php echo esc_attr( $field->id ); ?>" name="cdc_fields[<?php echo esc_attr( $field->id ); ?>]" <?php echo $is_required ? 'required' : ''; ?> <?php echo $readonly ? 'disabled' : ''; ?>>
											<?php foreach ( $council_types as $t ) : ?>
										<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $val, $t ); ?>><?php echo esc_html( $t ); ?></option>
									<?php endforeach; ?>
								</select>
														<?php elseif ( 'council_location' === $field->name ) : ?>
																<select id="cdc-field-<?php echo esc_attr( $field->id ); ?>" name="cdc_fields[<?php echo esc_attr( $field->id ); ?>]" <?php echo $is_required ? 'required' : ''; ?> <?php echo $readonly ? 'disabled' : ''; ?>>
															<?php foreach ( $council_locations as $t ) : ?>
										<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $val, $t ); ?>><?php echo esc_html( $t ); ?></option>
									<?php endforeach; ?>
								</select>
														<?php elseif ( 'money' === $field->type ) : ?>
								<div class="input-group">
									<span class="input-group-text">&pound;</span>
																		<input data-cdc-field="<?php echo esc_attr( $field->name ); ?>" type="number" step="0.01" id="cdc-field-<?php echo esc_attr( $field->id ); ?>" value="<?php echo esc_attr( $val ); ?>" class="regular-text" <?php echo $readonly ? 'readonly disabled' : 'name="cdc_fields[' . esc_attr( $field->id ) . ']"'; ?> <?php echo $is_required ? 'required' : ''; ?>>
								</div>
							<?php else : ?>
																<input data-cdc-field="<?php echo esc_attr( $field->name ); ?>" type="<?php echo esc_attr( $input_type ); ?>" id="cdc-field-<?php echo esc_attr( $field->id ); ?>" value="<?php echo esc_attr( $val ); ?>" class="regular-text" <?php echo $readonly ? 'readonly disabled' : 'name="cdc_fields[' . esc_attr( $field->id ) . ']"'; ?> <?php echo $is_required ? 'required' : ''; ?>>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<th scope="row"><label for="cdc-post-status"><?php esc_html_e( 'Status', 'council-debt-counters' ); ?></label></th>
					<td>
												<?php $current_status = $council_id ? get_post_status( $council_id ) : 'draft'; ?>
						<select id="cdc-post-status" name="post_status">
							<option value="publish" <?php selected( $current_status, 'publish' ); ?>><?php esc_html_e( 'Active', 'council-debt-counters' ); ?></option>
							<option value="draft" <?php selected( $current_status, 'draft' ); ?>><?php esc_html_e( 'Draft', 'council-debt-counters' ); ?></option>
							<option value="under_review" <?php selected( $current_status, 'under_review' ); ?>><?php esc_html_e( 'Under Review', 'council-debt-counters' ); ?></option>
						</select>
					</td>
				</tr>
				</table>
			</div>
			<?php
			foreach ( $enabled as $tab_key ) :
				if ( empty( $groups[ $tab_key ] ) ) {
								continue;}
				?>
						<div class="tab-pane fade" id="tab-<?php echo esc_attr( $tab_key ); ?>" role="tabpanel">
								<p class="description"><code>[council_counter id="<?php echo esc_attr( $council_id ); ?>" type="<?php echo esc_attr( $tab_key ); ?>"]</code></p>
								<table class="form-table" role="presentation">
								<?php
								foreach ( $groups[ $tab_key ] as $field ) :
										$val         = $council_id ? \CouncilDebtCounters\Custom_Fields::get_value( $council_id, $field->name ) : '';
										$input_type  = 'text' === $field->type ? 'text' : 'number';
										$is_required = (bool) $field->required;
										$readonly    = in_array( $field->name, \CouncilDebtCounters\Custom_Fields::READONLY_FIELDS, true );
									?>
					<tr>
						<th scope="row"><label for="cdc-field-<?php echo esc_attr( $field->id ); ?>"><?php echo esc_html( $field->label ); ?>
									<?php
									if ( $field->required ) {
										echo ' *';}
									?>
						</label></th>
						<td>
														<?php if ( 'council_type' === $field->name ) : ?>
																<select id="cdc-field-<?php echo esc_attr( $field->id ); ?>" name="cdc_fields[<?php echo esc_attr( $field->id ); ?>]" <?php echo $is_required ? 'required' : ''; ?> <?php echo $readonly ? 'disabled' : ''; ?>>
															<?php foreach ( $council_types as $t ) : ?>
										<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $val, $t ); ?>><?php echo esc_html( $t ); ?></option>
									<?php endforeach; ?>
								</select>
														<?php elseif ( 'council_location' === $field->name ) : ?>
																<select id="cdc-field-<?php echo esc_attr( $field->id ); ?>" name="cdc_fields[<?php echo esc_attr( $field->id ); ?>]" <?php echo $is_required ? 'required' : ''; ?> <?php echo $readonly ? 'disabled' : ''; ?>>
															<?php foreach ( $council_locations as $t ) : ?>
										<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $val, $t ); ?>><?php echo esc_html( $t ); ?></option>
									<?php endforeach; ?>
								</select>
														<?php elseif ( 'money' === $field->type ) : ?>
								<div class="input-group">
									<span class="input-group-text">&pound;</span>
																		<input data-cdc-field="<?php echo esc_attr( $field->name ); ?>" type="number" step="0.01" id="cdc-field-<?php echo esc_attr( $field->id ); ?>" value="<?php echo esc_attr( $val ); ?>" class="regular-text" <?php echo $readonly ? 'readonly disabled' : 'name="cdc_fields[' . esc_attr( $field->id ) . ']"'; ?> <?php echo $is_required ? 'required' : ''; ?>>
								</div>
							<?php else : ?>
																<input data-cdc-field="<?php echo esc_attr( $field->name ); ?>" type="<?php echo esc_attr( $input_type ); ?>" id="cdc-field-<?php echo esc_attr( $field->id ); ?>" value="<?php echo esc_attr( $val ); ?>" class="regular-text" <?php echo $readonly ? 'readonly disabled' : 'name="cdc_fields[' . esc_attr( $field->id ) . ']"'; ?> <?php echo $is_required ? 'required' : ''; ?>>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</table>
			</div>
			<?php endforeach; ?>
			<?php if ( $docs_field ) : ?>
			<div class="tab-pane fade" id="tab-docs" role="tabpanel">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="cdc-soa"><?php echo esc_html( $docs_field->label ); ?></label></th>
						<td>
														<?php $val = $council_id ? \CouncilDebtCounters\Custom_Fields::get_value( $council_id, 'statement_of_accounts' ) : ''; ?>
							<?php if ( $val ) : ?>
								<p><a href="<?php echo esc_url( plugins_url( 'docs/' . $val, dirname( __DIR__, 2 ) . '/council-debt-counters.php' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View current document', 'council-debt-counters' ); ?></a></p>
							<?php endif; ?>
							<input type="file" id="cdc-soa" name="statement_of_accounts_file" accept="application/pdf">
							<p class="description"><?php esc_html_e( 'or import from URL', 'council-debt-counters' ); ?></p>
							<input type="url" name="statement_of_accounts_url" class="regular-text" placeholder="https://example.com/file.pdf">
							<p class="description mt-2">
								<label for="cdc-soa-year" class="form-label"><?php esc_html_e( 'Financial Year', 'council-debt-counters' ); ?></label>
								<select id="cdc-soa-year" name="statement_of_accounts_year">
									<?php foreach ( \CouncilDebtCounters\Docs_Manager::financial_years() as $y ) : ?>
										<option value="<?php echo esc_attr( $y ); ?>" <?php selected( \CouncilDebtCounters\Docs_Manager::current_financial_year(), $y ); ?>><?php echo esc_html( $y ); ?></option>
									<?php endforeach; ?>
								</select>
							</p>
							<?php $orphans = \CouncilDebtCounters\Docs_Manager::list_orphan_documents(); ?>
							<?php if ( ! empty( $orphans ) ) : ?>
								<p class="description mt-2"><?php esc_html_e( 'Or attach an existing document', 'council-debt-counters' ); ?></p>
								<select name="statement_of_accounts_existing">
									<option value=""><?php esc_html_e( 'Select document', 'council-debt-counters' ); ?></option>
									<?php foreach ( $orphans as $doc ) : ?>
										<option value="<?php echo esc_attr( $doc->filename ); ?>"><?php echo esc_html( $doc->filename ); ?></option>
									<?php endforeach; ?>
								</select>
							<?php endif; ?>
						</td>
					</tr>
				</table>
				<?php if ( ! empty( $docs ) ) : ?>
				<h2><?php esc_html_e( 'Existing Documents', 'council-debt-counters' ); ?></h2>
				<table class="widefat">
					<thead>
						<tr>
							<th><?php esc_html_e( 'File', 'council-debt-counters' ); ?></th>
							<th><?php esc_html_e( 'Year', 'council-debt-counters' ); ?></th>
							<th><?php esc_html_e( 'Type', 'council-debt-counters' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'council-debt-counters' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $docs as $d ) : ?>
						<tr>
							<td><?php echo esc_html( $d->filename ); ?></td>
							<td>
								<select name="docs[<?php echo esc_attr( $d->id ); ?>][financial_year]">
									<?php foreach ( \CouncilDebtCounters\Docs_Manager::financial_years() as $y ) : ?>
										<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $d->financial_year, $y ); ?>><?php echo esc_html( $y ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
							<td>
								<select name="docs[<?php echo esc_attr( $d->id ); ?>][doc_type]">
									<option value="statement_of_accounts" <?php selected( $d->doc_type, 'statement_of_accounts' ); ?>><?php esc_html_e( 'Statement of Accounts', 'council-debt-counters' ); ?></option>
								</select>
							</td>
							<td>
								<button type="button" value="<?php echo esc_attr( $d->id ); ?>" class="button cdc-extract-ai"><span class="dashicons dashicons-lightbulb"></span> <?php esc_html_e( 'Extract Figures', 'council-debt-counters' ); ?></button>

								<button type="submit" name="update_doc" value="<?php echo esc_attr( $d->id ); ?>" class="button button-secondary"><?php esc_html_e( 'Update', 'council-debt-counters' ); ?></button>
								<button type="submit" name="delete_doc" value="<?php echo esc_attr( $d->id ); ?>" class="button button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this document?', 'council-debt-counters' ); ?>');"><?php esc_html_e( 'Delete', 'council-debt-counters' ); ?></button>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>
			</div>
			<?php endif; ?>
						<?php if ( $council_id ) : ?>
			<!-- Whistleblower reports moved to dedicated admin page -->
			<?php endif; ?>
		</div>
		<?php submit_button( __( 'Save Council', 'council-debt-counters' ) ); ?>
	</form>
	</div>
	<?php
	return;
}

$status_param   = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'publish';
$valid_statuses = array( 'publish', 'draft', 'under_review' );
if ( ! in_array( $status_param, $valid_statuses, true ) ) {
		$status_param = 'publish';
}
$councils = get_posts(
	array(
		'post_type'   => 'council',
		'numberposts' => -1,
		'post_status' => $status_param,
	)
);
$counts   = wp_count_posts( 'council' );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Councils', 'council-debt-counters' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=cdc-manage-councils&action=edit' ) ); ?>" class="btn btn-primary mb-3"><?php esc_html_e( 'Add New', 'council-debt-counters' ); ?></a>
	<ul class="nav nav-tabs mb-3">
		<?php
		$status_labels = array(
			'publish'      => __( 'Active', 'council-debt-counters' ),
			'draft'        => __( 'Draft', 'council-debt-counters' ),
			'under_review' => __( 'Under Review', 'council-debt-counters' ),
		);
		foreach ( $status_labels as $s_key => $s_label ) :
			$count = $counts->$s_key ?? 0;
			?>
			<li class="nav-item">
				<a class="nav-link <?php echo $status_param === $s_key ? 'active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=cdc-manage-councils&status=' . $s_key ) ); ?>">
					<?php echo esc_html( $s_label . ' (' . $count . ')' ); ?>
				</a>
			</li>
				<?php endforeach; ?>
	</ul>
	<table class="table table-striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'council-debt-counters' ); ?></th>
				<th><?php esc_html_e( 'Debt Counter', 'council-debt-counters' ); ?></th>
				<th><?php esc_html_e( 'Status', 'council-debt-counters' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'council-debt-counters' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $councils ) ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No councils found.', 'council-debt-counters' ); ?></td></tr>
				<?php
			else :
				foreach ( $councils as $council ) :
					?>
				<tr>
					<td><?php echo esc_html( get_the_title( $council ) ); ?></td>
					<td>
										<?php echo do_shortcode( '[council_counter id="' . $council->ID . '"]' ); ?>
						<code>[council_counter id="<?php echo esc_attr( $council->ID ); ?>"]</code>
					</td>
					<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $council->post_status ) ) ); ?></td>
					<td>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=cdc-manage-councils&action=edit&post=' . $council->ID ) ); ?>" class="btn btn-sm btn-secondary"><?php esc_html_e( 'Edit', 'council-debt-counters' ); ?></a>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=cdc-manage-councils&action=delete&post=' . $council->ID ), 'cdc_delete_council_' . $council->ID ) ); ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?php esc_attr_e( 'Delete this council?', 'council-debt-counters' ); ?>');"><?php esc_html_e( 'Delete', 'council-debt-counters' ); ?></a>
					</td>
				</tr>
							<?php
			endforeach;
endif;
			?>
		</tbody>
	</table>
</div>
