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
                $title = $council_id ? get_the_title( $council_id ) : '';
                if ( $council_id ) {
                        printf( '<h1>%s</h1>', esc_html( sprintf( __( 'Editing %s', 'council-debt-counters' ), $title ) ) );
                } else {
                        echo '<h1>' . esc_html__( 'Add Council', 'council-debt-counters' ) . '</h1>';
                }
                $assigned       = $council_id ? intval( get_post_meta( $council_id, 'assigned_user', true ) ) : 0;
                $current_status = $council_id ? get_post_status( $council_id ) : 'draft';
                $users          = get_users( [ 'fields' => [ 'ID', 'display_name' ] ] );
                $reports        = $council_id ? count( get_posts( [ 'post_type' => \CouncilDebtCounters\Whistleblower_Form::CPT, 'numberposts' => -1, 'post_status' => 'private', 'meta_key' => 'council_id', 'meta_value' => $council_id ] ) ) : 0;
                $msg = isset( $_GET['updated'] ) ? '<div class="alert alert-success mb-0">' . esc_html__( 'Update successful.', 'council-debt-counters' ) . '</div>' : '';
                echo '<div id="cdc-toolbar" class="mb-2 d-flex justify-content-between align-items-center">';
                echo '<div class="d-flex align-items-center flex-nowrap">';
                echo '<select id="cdc-post-status" class="form-select me-2"><option value="publish"' . selected( $current_status, 'publish', false ) . '>' . esc_html__( 'Active', 'council-debt-counters' ) . '</option><option value="draft"' . selected( $current_status, 'draft', false ) . '>' . esc_html__( 'Draft', 'council-debt-counters' ) . '</option><option value="under_review"' . selected( $current_status, 'under_review', false ) . '>' . esc_html__( 'Under Review', 'council-debt-counters' ) . '</option></select>';
                echo '<select name="assigned_user" class="form-select me-2"><option value="0">' . esc_html__( 'Unassigned', 'council-debt-counters' ) . '</option>';
                foreach ( $users as $u ) {
                        printf( '<option value="%d"%s>%s</option>', $u->ID, selected( $assigned, $u->ID, false ), esc_html( $u->display_name ) );
                }
                echo '</select>';
                if ( $council_id ) {
                        echo '<span class="badge bg-secondary me-2">ID: ' . intval( $council_id ) . '</span>';
                        echo '<span class="badge bg-info me-2">' . esc_html__( 'Reports:', 'council-debt-counters' ) . ' ' . intval( $reports ) . '</span>';
                        $view_link = get_permalink( $council_id );
                        echo '<a href="' . esc_url( $view_link ) . '" class="btn btn-sm btn-primary me-2" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View Live Page', 'council-debt-counters' ) . '</a>';
                        $del = wp_nonce_url( admin_url( 'admin.php?page=cdc-manage-councils&action=delete&post=' . $council_id ), 'cdc_delete_council_' . $council_id );
                        echo '<a href="' . esc_url( $del ) . '" class="btn btn-sm btn-danger me-2" onclick="return confirm(\'' . esc_js( __( 'Delete this council?', 'council-debt-counters' ) ) . '\');">' . esc_html__( 'Trash Council', 'council-debt-counters' ) . '</a>';
                }
                echo '</div></div>';
                echo '<div id="cdc-status-msg" class="mb-3">' . $msg . '</div>';
        $fields  = \CouncilDebtCounters\Custom_Fields::get_fields();
        $enabled = (array) get_option( 'cdc_enabled_counters', array() );
        $groups  = array( 'general' => array() );
	foreach ( $enabled as $e ) {
		$groups[ $e ] = array();
	}
	$docs_field = null;
        foreach ( $fields as $field ) {
                if ( 'statement_of_accounts' === $field->name ) {
                        $docs_field = $field;
                        continue; }
                $tab = \CouncilDebtCounters\Custom_Fields::get_field_tab( $field->name );
                if ( isset( $groups[ $tab ] ) ) {
                        $groups[ $tab ][] = $field;
                } else {
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
<li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-docs" type="button" role="tab"><?php esc_html_e( 'Statement of Accounts', 'council-debt-counters' ); ?></button></li>
<?php endif; ?>
<?php if ( $council_id ) : ?>
				<!-- Whistleblower reports moved to dedicated admin page -->
		</ul>
		<div class="tab-content pt-3">
			<div class="tab-pane fade show active" id="tab-general" role="tabpanel">
				<table class="form-table" role="presentation">
				<?php
				$council_types     = array( 'Unitary', 'County', 'District', 'Metropolitan Borough', 'London Borough', 'Parish', 'Town', 'Combined Authority' );
$council_locations = array( 'England', 'Wales', 'Scotland', 'Northern Ireland' );
$no_accounts       = $council_id ? get_post_meta( $council_id, 'cdc_no_accounts', true ) : '';
foreach ( $groups['general'] as $field ) :
                                                $val         = $council_id ? \CouncilDebtCounters\Custom_Fields::get_value( $council_id, $field->name, \CouncilDebtCounters\CDC_Utils::current_financial_year() ) : '';
                                                if ( ! $council_id && $field->required && in_array( $field->type, array( 'number', 'money' ), true ) ) {
                                                        $val = '0';
                                                }
$input_type  = 'text' === $field->type ? 'text' : 'number';
$is_required = (bool) $field->required;
if ( 'status_message' === $field->name && $no_accounts ) {
$is_required = true;
}
$readonly    = in_array( $field->name, \CouncilDebtCounters\Custom_Fields::READONLY_FIELDS, true );
if ( 'status_message_type' === $field->name && $no_accounts ) {
$val      = 'danger';
$readonly = true;
}
					?>
					<tr>
						<th scope="row"><label for="cdc-field-<?php echo esc_attr( $field->id ); ?>"><?php echo esc_html( $field->label ); ?>
					<?php
                                                                       if ( $is_required ) {
                                                                               echo ' <span class="text-danger cdc-required-indicator">*</span>';}
					?>
						</label></th>
						<td>
										<?php if ( 'council_type' === $field->name ) : ?>
                                                                       <select id="cdc-field-<?php echo esc_attr( $field->id ); ?>" name="cdc_fields[<?php echo esc_attr( $field->id ); ?>]" class="form-select" <?php echo $is_required ? 'required' : ''; ?> <?php echo $readonly ? 'disabled' : ''; ?>>
											<?php foreach ( $council_types as $t ) : ?>
										<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $val, $t ); ?>><?php echo esc_html( $t ); ?></option>
									<?php endforeach; ?>
                                                                </select>
                                                                <?php if ( $is_required ) : ?>
                                                                        <div class="invalid-feedback"><?php esc_html_e( 'Required', 'council-debt-counters' ); ?></div>
                                                                <?php endif; ?>
<?php elseif ( 'council_location' === $field->name ) : ?>
                                                                       <select id="cdc-field-<?php echo esc_attr( $field->id ); ?>" name="cdc_fields[<?php echo esc_attr( $field->id ); ?>]" class="form-select" <?php echo $is_required ? 'required' : ''; ?> <?php echo $readonly ? 'disabled' : ''; ?>>
															<?php foreach ( $council_locations as $t ) : ?>
										<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $val, $t ); ?>><?php echo esc_html( $t ); ?></option>
									<?php endforeach; ?>
                                                                </select>
                                                                <?php if ( $is_required ) : ?>
                                                                        <div class="invalid-feedback"><?php esc_html_e( 'Required', 'council-debt-counters' ); ?></div>
                                                                <?php endif; ?>
<?php elseif ( 'financial_data_source_url' === $field->name ) : ?>
<input data-cdc-field="<?php echo esc_attr( $field->name ); ?>" data-initial-required="<?php echo $is_required ? '1' : '0'; ?>" type="url" id="cdc-field-<?php echo esc_attr( $field->id ); ?>" value="<?php echo esc_attr( $val ); ?>" class="form-control" <?php echo $readonly ? 'readonly disabled' : 'name="cdc_fields[' . esc_attr( $field->id ) . ']"'; ?> <?php echo $is_required ? 'required' : ''; ?> placeholder="https://example.com/statement.pdf">
<p class="description mt-1"><?php esc_html_e( 'Link to the published statement this data comes from.', 'council-debt-counters' ); ?></p>
<?php elseif ( 'status_message_type' === $field->name ) : ?>
<select id="cdc-field-<?php echo esc_attr( $field->id ); ?>" name="cdc_fields[<?php echo esc_attr( $field->id ); ?>]" class="form-select" <?php echo $is_required ? 'required' : ''; ?> <?php echo $readonly ? 'disabled' : ''; ?>>
<?php foreach ( array( 'info', 'warning', 'danger' ) as $t ) : ?>
<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $val, $t ); ?>><?php echo esc_html( ucfirst( $t ) ); ?></option>
<?php endforeach; ?>
</select>
<?php elseif ( 'financial_data_source_url' === $field->name ) : ?>
<input data-cdc-field="<?php echo esc_attr( $field->name ); ?>" data-initial-required="<?php echo $is_required ? '1' : '0'; ?>" type="url" id="cdc-field-<?php echo esc_attr( $field->id ); ?>" value="<?php echo esc_attr( $val ); ?>" class="form-control" <?php echo $readonly ? 'readonly disabled' : 'name="cdc_fields[' . esc_attr( $field->id ) . ']"'; ?> <?php echo $is_required ? 'required' : ''; ?> placeholder="https://example.com/statement.pdf">
<p class="description mt-1"><?php esc_html_e( 'Link to the published statement this data comes from.', 'council-debt-counters' ); ?></p>
<?php elseif ( 'status_message_type' === $field->name ) : ?>
<select id="cdc-field-<?php echo esc_attr( $field->id ); ?>" name="cdc_fields[<?php echo esc_attr( $field->id ); ?>]" <?php echo $is_required ? 'required' : ''; ?> <?php echo $readonly ? 'disabled' : ''; ?>>
<?php foreach ( array( 'info', 'warning', 'danger' ) as $t ) : ?>
<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $val, $t ); ?>><?php echo esc_html( ucfirst( $t ) ); ?></option>
<?php endforeach; ?>
</select>
<?php elseif ( 'money' === $field->type ) : ?>
<?php $na_val = $council_id ? get_post_meta( $council_id, 'cdc_na_' . $field->name, true ) : ''; ?>
<div class="input-group">
    <span class="input-group-text">&pound;</span>
    <input data-cdc-field="<?php echo esc_attr( $field->name ); ?>" data-initial-required="<?php echo $is_required ? '1' : '0'; ?>" type="number" step="0.01" id="cdc-field-<?php echo esc_attr( $field->id ); ?>" value="<?php echo esc_attr( $val ); ?>" class="form-control" <?php echo $readonly ? 'readonly disabled' : 'name="cdc_fields[' . esc_attr( $field->id ) . ']"'; ?> <?php echo $is_required ? 'required' : ''; ?>>
    <?php if ( ! $readonly && $field->name !== 'total_debt' ) : ?>
        <button type="button" class="btn btn-outline-secondary cdc-ask-ai" data-field="<?php echo esc_attr( $field->name ); ?>"><span class="dashicons dashicons-lightbulb me-1"></span><?php esc_html_e( 'Ask AI', 'council-debt-counters' ); ?></button>
        <div class="input-group-text">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="cdc-na-<?php echo esc_attr( $field->name ); ?>" name="cdc_na[<?php echo esc_attr( $field->name ); ?>]" value="1" <?php checked( $na_val, '1' ); ?>>
                <label class="form-check-label" for="cdc-na-<?php echo esc_attr( $field->name ); ?>"></label>
            </div>
        </div>
        <span class="input-group-text"><?php esc_html_e( 'N/A', 'council-debt-counters' ); ?></span>
    <?php endif; ?>
    <?php if ( $is_required ) : ?>
        <div class="invalid-feedback"><?php esc_html_e( 'Required', 'council-debt-counters' ); ?></div>
    <?php endif; ?>
</div>
<?php else : ?>
<?php $na_val = $council_id ? get_post_meta( $council_id, 'cdc_na_' . $field->name, true ) : ''; ?>
<div class="input-group">
    <input data-cdc-field="<?php echo esc_attr( $field->name ); ?>" data-initial-required="<?php echo $is_required ? '1' : '0'; ?>" type="<?php echo esc_attr( $input_type ); ?>" id="cdc-field-<?php echo esc_attr( $field->id ); ?>" value="<?php echo esc_attr( $val ); ?>" class="form-control" <?php echo $readonly ? 'readonly disabled' : 'name="cdc_fields[' . esc_attr( $field->id ) . ']"'; ?> <?php echo $is_required ? 'required' : ''; ?>>
    <?php if ( ! $readonly && $field->name !== 'total_debt' ) : ?>
        <button type="button" class="btn btn-outline-secondary cdc-ask-ai" data-field="<?php echo esc_attr( $field->name ); ?>"><span class="dashicons dashicons-lightbulb me-1"></span><?php esc_html_e( 'Ask AI', 'council-debt-counters' ); ?></button>
        <div class="input-group-text">
                <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="cdc-na-<?php echo esc_attr( $field->name ); ?>" name="cdc_na[<?php echo esc_attr( $field->name ); ?>]" value="1" <?php checked( $na_val, '1' ); ?>>
                        <label class="form-check-label" for="cdc-na-<?php echo esc_attr( $field->name ); ?>"></label>
                </div>
        </div>
        <span class="input-group-text"><?php esc_html_e( 'N/A', 'council-debt-counters' ); ?></span>
    <?php endif; ?>
    <?php if ( $is_required ) : ?>
        <div class="invalid-feedback"><?php esc_html_e( 'Required', 'council-debt-counters' ); ?></div>
    <?php endif; ?>
</div>
<?php if ( 'council_name' !== $field->name ) : ?>
<div class="cdc-ai-source mt-1"></div>
<?php endif; ?>
<?php endif; ?>
                                                </td>
                                        </tr>
                                <?php endforeach; ?>
                                <tr>
                                        <th scope="row"><?php esc_html_e( 'Sharing Image', 'council-debt-counters' ); ?></th>
                                        <td>
                                                <?php $share = $council_id ? absint( get_post_meta( $council_id, 'cdc_sharing_image', true ) ) : 0; ?>
                                                <div id="cdc-sharing-image-preview">
                                                        <?php if ( $share ) : ?>
                                                                <?php echo wp_get_attachment_image( $share, array( 150, 150 ) ); ?>
                                                        <?php endif; ?>
                                                </div>
                                                <input type="hidden" id="cdc-sharing-image" name="cdc_sharing_image" value="<?php echo esc_attr( $share ); ?>" data-url="<?php echo esc_url( $share ? wp_get_attachment_url( $share ) : '' ); ?>" />
                                                <button type="button" class="button" id="cdc-sharing-image-button"><?php esc_html_e( 'Select Image', 'council-debt-counters' ); ?></button>
<button type="button" class="button" id="cdc-sharing-image-remove" <?php if ( ! $share ) echo 'style="display:none"'; ?>><?php esc_html_e( 'Remove', 'council-debt-counters' ); ?></button>
</td>
</tr>
<tr>
<th scope="row"><?php esc_html_e( 'Taken over by', 'council-debt-counters' ); ?></th>
<td>
<?php $parent = $council_id ? intval( get_post_meta( $council_id, 'cdc_parent_council', true ) ) : 0; ?>
<select name="cdc_parent_council" class="form-select">
<option value="0"><?php esc_html_e( 'None', 'council-debt-counters' ); ?></option>
<?php foreach ( get_posts( [ 'post_type' => 'council', 'numberposts' => -1, 'exclude' => $council_id ? [ $council_id ] : [] ] ) as $c ) : ?>
<option value="<?php echo esc_attr( $c->ID ); ?>" <?php selected( $parent, $c->ID ); ?>><?php echo esc_html( $c->post_title ); ?></option>
<?php endforeach; ?>
</select>
<button type="submit" name="cdc_confirm_takeover" value="1" class="button mt-2"><?php esc_html_e( 'Confirm Takeover', 'council-debt-counters' ); ?></button>
</td>
</tr>
<tr>
<th scope="row"><?php esc_html_e( 'No Accounts Published', 'council-debt-counters' ); ?></th>
<td>
<?php $no_accounts = $council_id ? get_post_meta( $council_id, 'cdc_no_accounts', true ) : ''; ?>
<label>
<input type="checkbox" name="cdc_no_accounts" value="1" <?php checked( $no_accounts, '1' ); ?>>
<?php esc_html_e( 'Mark council as having no published accounts', 'council-debt-counters' ); ?>
</label>
</td>
</tr>
<tr>
<th scope="row"><label for="cdc-default-year"><?php esc_html_e( 'Default Financial Year', 'council-debt-counters' ); ?></label></th>
<td>
<?php $def_year_local = $council_id ? get_post_meta( $council_id, 'cdc_default_financial_year', true ) : ''; ?>
<select name="cdc_default_financial_year" id="cdc-default-year" class="form-select">
<?php foreach ( \CouncilDebtCounters\CDC_Utils::council_years( $council_id ) as $y ) : ?>
<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $def_year_local ? $def_year_local : get_option( 'cdc_default_financial_year', '2023/24' ), $y ); ?>><?php echo esc_html( $y ); ?></option>
<?php endforeach; ?>
</select>
<p class="description"><?php esc_html_e( 'Shown by default on this council\'s page.', 'council-debt-counters' ); ?></p>
</td>
</tr>
<tr>
<th scope="row"><?php esc_html_e( 'Enabled Financial Years', 'council-debt-counters' ); ?></th>
<td>
<?php $enabled_years = $council_id ? (array) get_post_meta( $council_id, 'cdc_enabled_years', true ) : array(); ?>
<?php foreach ( \CouncilDebtCounters\Docs_Manager::financial_years() as $y ) : ?>
    <div class="form-check">
        <input type="checkbox" class="form-check-input" id="cdc-year-<?php echo esc_attr( $y ); ?>" name="cdc_enabled_years[]" value="<?php echo esc_attr( $y ); ?>" <?php checked( empty( $enabled_years ) || in_array( $y, $enabled_years, true ) ); ?> />
        <label for="cdc-year-<?php echo esc_attr( $y ); ?>" class="form-check-label"><?php echo esc_html( $y ); ?></label>
    </div>
<?php endforeach; ?>
<p class="description"><?php esc_html_e( 'Unchecked years will not be selectable on the front-end or in these tabs.', 'council-debt-counters' ); ?></p>
</td>
</tr>
</table>
</div>
<?php endif; ?>
<?php
			foreach ( $enabled as $tab_key ) :
				$tab = $tab_key; // using tab key directly, no get_tab_by_key method exists
				if ( empty( $groups[ $tab_key ] ) ) {
					continue;
				}
				$tab_fields = $groups[ $tab_key ] ?? array();
				$na_tab_val = $council_id ? get_post_meta( $council_id, 'cdc_na_tab_' . $tab_key, true ) : '';
				?>
				<div class="tab-pane" id="tab-<?php echo esc_attr( $tab_key ); ?>" role="tabpanel">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div>
                                                        <label for="cdc-year-<?php echo esc_attr( $tab_key ); ?>" class="form-label me-2"><?php esc_html_e( 'Financial Year', 'council-debt-counters' ); ?></label>
                                                        <select class="form-select cdc-year-select d-inline w-auto" id="cdc-year-<?php echo esc_attr( $tab_key ); ?>" data-tab="<?php echo esc_attr( $tab_key ); ?>">
                                                                <?php foreach ( \CouncilDebtCounters\CDC_Utils::council_years( $council_id ) as $y ) : ?>
                                                                        <option value="<?php echo esc_attr( $y ); ?>" <?php selected( \CouncilDebtCounters\CDC_Utils::current_financial_year(), $y ); ?>><?php echo esc_html( $y ); ?></option>
                                                                <?php endforeach; ?>
                                                        </select>
                                                        <input type="hidden" name="cdc_tab_year[<?php echo esc_attr( $tab_key ); ?>]" value="<?php echo esc_attr( \CouncilDebtCounters\CDC_Utils::current_financial_year() ); ?>" class="cdc-selected-year">
                                                </div>
                                                <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="cdc-na-tab-<?php echo esc_attr( $tab_key ); ?>" name="cdc_na_tab[<?php echo esc_attr( $tab_key ); ?>]" value="1" <?php checked( $na_tab_val, '1' ); ?>>
                                                        <label class="form-check-label" for="cdc-na-tab-<?php echo esc_attr( $tab_key ); ?>"><?php esc_html_e( 'Turn off this counter', 'council-debt-counters' ); ?></label>
                                                </div>
                                        </div>
                                        <table class="form-table">
						<tbody>
							<?php
							foreach ( $tab_fields as $field ) :
                                                                $val         = $council_id ? \CouncilDebtCounters\Custom_Fields::get_value( $council_id, $field->name, \CouncilDebtCounters\CDC_Utils::current_financial_year() ) : '';
								$input_type  = 'text' === $field->type ? 'text' : 'number';
								$is_required = (bool) $field->required;
								$readonly    = in_array( $field->name, \CouncilDebtCounters\Custom_Fields::READONLY_FIELDS, true );
								?>
								<tr>
									<th scope="row"><label for="cdc-field-<?php echo esc_attr( $field->id ); ?>"><?php echo esc_html( $field->label ); ?>
										<?php if ( $is_required ) : ?>
											<span class="cdc-required-indicator text-danger">*</span>
										<?php endif; ?>
									</label></th>
									<td>
										<?php if ( 'money' === $field->type ) : ?>
											<?php $na_val = $council_id ? get_post_meta( $council_id, 'cdc_na_' . $field->name, true ) : ''; ?>
<div class="input-group">
    <span class="input-group-text">&pound;</span>
    <input data-cdc-field="<?php echo esc_attr( $field->name ); ?>" type="number" step="0.01" id="cdc-field-<?php echo esc_attr( $field->id ); ?>" value="<?php echo esc_attr( $val ); ?>" class="form-control" <?php echo $readonly ? 'readonly disabled' : 'name="cdc_fields[' . esc_attr( $field->id ) . ']"'; ?> <?php echo $is_required ? 'required' : ''; ?>>
    <?php if ( ! $readonly ) : ?>
    <button type="button" class="btn btn-outline-secondary cdc-ask-ai" data-field="<?php echo esc_attr( $field->name ); ?>"><span class="dashicons dashicons-lightbulb me-1"></span><?php esc_html_e( 'Ask AI', 'council-debt-counters' ); ?></button>
        <div class="input-group-text">
                <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="cdc-na-<?php echo esc_attr( $field->name ); ?>" name="cdc_na[<?php echo esc_attr( $field->name ); ?>]" value="1" <?php checked( $na_val, '1' ); ?>>
                        <label class="form-check-label" for="cdc-na-<?php echo esc_attr( $field->name ); ?>"></label>
                </div>
        </div>
    <span class="input-group-text"><?php esc_html_e( 'N/A', 'council-debt-counters' ); ?></span>
    <?php endif; ?>
    <?php if ( $is_required ) : ?>
        <div class="invalid-feedback"><?php esc_html_e( 'Required', 'council-debt-counters' ); ?></div>
    <?php endif; ?>
</div>
										<?php else : ?>
											<?php $na_val = $council_id ? get_post_meta( $council_id, 'cdc_na_' . $field->name, true ) : ''; ?>
<div class="input-group">
    <input data-cdc-field="<?php echo esc_attr( $field->name ); ?>" type="<?php echo esc_attr( $input_type ); ?>" id="cdc-field-<?php echo esc_attr( $field->id ); ?>" value="<?php echo esc_attr( $val ); ?>" class="form-control" <?php echo $readonly ? 'readonly disabled' : 'name="cdc_fields[' . esc_attr( $field->id ) . ']"'; ?> <?php echo $is_required ? 'required' : ''; ?>>
    <button type="button" class="btn btn-outline-secondary cdc-ask-ai" data-field="<?php echo esc_attr( $field->name ); ?>"><span class="dashicons dashicons-lightbulb me-1"></span><?php esc_html_e( 'Ask AI', 'council-debt-counters' ); ?></button>
	<div class="input-group-text">
		<div class="form-check">
			<input type="checkbox" class="form-check-input" id="cdc-na-<?php echo esc_attr( $field->name ); ?>" name="cdc_na[<?php echo esc_attr( $field->name ); ?>]" value="1" <?php checked( $na_val, '1' ); ?>>
			<label class="form-check-label" for="cdc-na-<?php echo esc_attr( $field->name ); ?>"></label>
		</div>
	</div>
    <span class="input-group-text"><?php esc_html_e( 'N/A', 'council-debt-counters' ); ?></span>
    <?php if ( $is_required ) : ?>
        <div class="invalid-feedback"><?php esc_html_e( 'Required', 'council-debt-counters' ); ?></div>
    <?php endif; ?>
</div>
										<?php endif; ?>
										<?php if ( 'council_name' !== $field->name ) : ?>
											<div class="cdc-ai-source mt-1"></div>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<?php
			endforeach;
			?>
			<?php if ( $docs_field ) : ?>
			<div class="tab-pane fade" id="tab-docs" role="tabpanel">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="cdc-soa"><?php echo esc_html( $docs_field->label ); ?></label></th>
						<td>
                                               <?php $val = $council_id ? \CouncilDebtCounters\Custom_Fields::get_value( $council_id, 'statement_of_accounts', \CouncilDebtCounters\CDC_Utils::current_financial_year() ) : ''; ?>
<?php if ( $val ) : ?>
        <p><a href="<?php echo esc_url( plugins_url( 'docs/' . $val, dirname( __DIR__, 2 ) . '/council-debt-counters.php' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View selected statement', 'council-debt-counters' ); ?></a></p>
<?php endif; ?>
        <input type="file" id="cdc-soa" name="statement_of_accounts_file" accept="application/pdf">
        <p class="description"><?php esc_html_e( 'or import from URL', 'council-debt-counters' ); ?></p>
        <input type="url" name="statement_of_accounts_url" class="form-control" placeholder="https://example.com/file.pdf">
        <p class="description mt-2">
                <label for="cdc-soa-type" class="form-label"><?php esc_html_e( 'Statement Type', 'council-debt-counters' ); ?></label>
                <select id="cdc-soa-type" name="statement_of_accounts_type" class="form-select">
                        <option value="draft_statement_of_accounts"><?php esc_html_e( 'Draft', 'council-debt-counters' ); ?></option>
                        <option value="audited_statement_of_accounts"><?php esc_html_e( 'Audited', 'council-debt-counters' ); ?></option>
                </select>
        </p>
        <p class="description mt-2">
                <label for="cdc-soa-year" class="form-label"><?php esc_html_e( 'Financial Year', 'council-debt-counters' ); ?></label>
                <select id="cdc-soa-year" name="statement_of_accounts_year" class="form-select">
                        <?php foreach ( \CouncilDebtCounters\CDC_Utils::council_years( $council_id ) as $y ) : ?>
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
                                                                <select name="statement_of_accounts_type" class="ms-2">
                                                                        <option value="draft_statement_of_accounts"><?php esc_html_e( 'Draft', 'council-debt-counters' ); ?></option>
                                                                        <option value="audited_statement_of_accounts"><?php esc_html_e( 'Audited', 'council-debt-counters' ); ?></option>
                                                                </select>
                                                                <button type="button" id="cdc-upload-doc" class="button button-secondary mt-2"><?php esc_html_e( 'Add Document', 'council-debt-counters' ); ?></button>
                                                        <?php endif; ?>
                                                </td>
                                        </tr>
                                </table>
				<?php if ( ! empty( $docs ) ) : ?>
                                <h2><?php esc_html_e( 'Uploaded Statements', 'council-debt-counters' ); ?></h2>
                                <table id="cdc-docs-table" class="widefat">
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
                                                                    <?php foreach ( \CouncilDebtCounters\CDC_Utils::council_years( $council_id ) as $y ) : ?>
                                                                        <option value="<?php echo esc_attr( $y ); ?>" <?php selected( $d->financial_year, $y ); ?>><?php echo esc_html( $y ); ?></option>
                                                                    <?php endforeach; ?>
								</select>
							</td>
							<td>
                                                                <select name="docs[<?php echo esc_attr( $d->id ); ?>][doc_type]">
                                                                        <option value="draft_statement_of_accounts" <?php selected( $d->doc_type, 'draft_statement_of_accounts' ); ?>><?php esc_html_e( 'Draft', 'council-debt-counters' ); ?></option>
                                                                        <option value="audited_statement_of_accounts" <?php selected( $d->doc_type, 'audited_statement_of_accounts' ); ?>><?php esc_html_e( 'Audited', 'council-debt-counters' ); ?></option>
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
                        </div>
                        <?php endif; ?>
                                                <?php if ( $council_id ) : ?>
                        <!-- Whistleblower reports moved to dedicated admin page -->
                </div>
                <?php endif; ?>
                <?php submit_button( __( 'Save Council', 'council-debt-counters' ) ); ?>
	</form>
</div>
<?php endif; ?>
<?php
	return;
}

$status_param   = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'publish';
$valid_statuses = array( 'publish', 'draft', 'under_review' );
if ( ! in_array( $status_param, $valid_statuses, true ) ) {
    $status_param = 'publish';
}

$table  = new \CouncilDebtCounters\Councils_Table( $status_param );
$table->process_bulk_action();
$table->prepare_items();
$counts = wp_count_posts( 'council' );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Councils', 'council-debt-counters' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=cdc-manage-councils&action=edit' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'council-debt-counters' ); ?></a>
	<ul class="subsubsub">
		<?php
		$statuses = [
			'publish'      => __( 'Active', 'council-debt-counters' ),
			'draft'        => __( 'Draft', 'council-debt-counters' ),
			'under_review' => __( 'Under Review', 'council-debt-counters' ),
		];
		$links = [];
		foreach ( $statuses as $status => $label ) {
			$url   = admin_url( 'admin.php?page=cdc-manage-councils&status=' . $status );
			$count = $counts->$status ?? 0;
			$class = ( $status_param === $status ) ? 'current' : '';
			$links[] = sprintf( '<li><a href="%s" class="%s">%s <span class="count">(%d)</span></a></li>', esc_url( $url ), esc_attr( $class ), esc_html( $label ), intval( $count ) );
		}
		echo implode( ' | ', $links );
		?>
	</ul>
	<form method="post">
		<?php
		$table->search_box( __( 'Search Councils', 'council-debt-counters' ), 'council-search' );
		$table->display();
		?>
	</form>
</div>
?>
