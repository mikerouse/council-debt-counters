<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$action  = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
$post_id = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : 0;

if ( $action === 'delete' && $post_id ) {
    check_admin_referer( 'cdc_delete_council_' . $post_id );
    wp_delete_post( $post_id, true );
    echo '<div class="alert alert-success"><p>' . esc_html__( 'Council deleted.', 'council-debt-counters' ) . '</p></div>';
    $action = '';
}

if ( $action === 'edit' ) {
    echo '<div class="wrap">';
    echo '<h1>' . esc_html( $post_id ? __( 'Edit Council', 'council-debt-counters' ) : __( 'Add Council', 'council-debt-counters' ) ) . '</h1>';
    $fields = \CouncilDebtCounters\Custom_Fields::get_fields();
    $enabled = (array) get_option( 'cdc_enabled_counters', [] );
    $mapping = [
        'debt' => [ 'current_liabilities','long_term_liabilities','finance_lease_pfi_liabilities','manual_debt_entry','interest_paid_on_debt','total_debt' ],
        'spending' => [ 'annual_spending' ],
        'income' => [ 'total_income' ],
        'deficit' => [ 'annual_deficit' ],
        'interest' => [ 'interest_paid' ],
        'reserves' => [ 'usable_reserves' ],
        'consultancy' => [ 'consultancy_spend' ],
    ];
    $groups = [ 'general' => [] ];
    foreach ( $enabled as $e ) {
        $groups[ $e ] = [];
    }
    $docs_field = null;
    foreach ( $fields as $field ) {
        if ( $field->name === 'statement_of_accounts' ) { $docs_field = $field; continue; }
        $placed = false;
        foreach ( $mapping as $type => $names ) {
            if ( in_array( $field->name, $names, true ) ) {
                if ( isset( $groups[ $type ] ) ) { $groups[ $type ][] = $field; }
                $placed = true; break;
            }
        }
        if ( ! $placed ) { $groups['general'][] = $field; }
    }
    $docs = $post_id ? \CouncilDebtCounters\Docs_Manager::list_documents( $post_id ) : [];
    ?>
    <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="cdc_save_council">
        <?php wp_nonce_field( 'cdc_save_council' ); ?>
        <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab"><?php esc_html_e( 'General', 'council-debt-counters' ); ?></button></li>
            <?php foreach ( $enabled as $tab ) : if ( empty( $groups[ $tab ] ) ) continue; ?>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-<?php echo esc_attr( $tab ); ?>" type="button" role="tab"><?php echo esc_html( ucfirst( $tab ) ); ?></button></li>
            <?php endforeach; ?>
            <?php if ( $docs_field ) : ?>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-docs" type="button" role="tab"><?php esc_html_e( 'Documents', 'council-debt-counters' ); ?></button></li>
            <?php endif; ?>
            <?php if ( $post_id ) : ?>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-reports" type="button" role="tab"><?php esc_html_e( 'Whistleblowers', 'council-debt-counters' ); ?></button></li>
            <?php endif; ?>
        </ul>
        <div class="tab-content pt-3">
            <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                <table class="form-table" role="presentation">
                <?php foreach ( $groups['general'] as $field ) :
                    $val = $post_id ? \CouncilDebtCounters\Custom_Fields::get_value( $post_id, $field->name ) : '';
                    $type = $field->type === 'text' ? 'text' : 'number';
                    $required = $field->required ? 'required' : '';
                    $readonly = in_array( $field->name, \CouncilDebtCounters\Custom_Fields::READONLY_FIELDS, true ); ?>
                    <tr>
                        <th scope="row"><label for="cdc-field-<?php echo esc_attr( $field->id ); ?>"><?php echo esc_html( $field->label ); ?><?php if ( $field->required ) echo ' *'; ?></label></th>
                        <td>
                            <?php if ( $field->type === 'money' ) : ?>
                                <div class="input-group">
                                    <span class="input-group-text">&pound;</span>
                                    <input data-cdc-field="<?php echo esc_attr( $field->name ); ?>" type="number" step="0.01" id="cdc-field-<?php echo esc_attr( $field->id ); ?>" value="<?php echo esc_attr( $val ); ?>" class="regular-text" <?php echo $readonly ? 'readonly disabled' : 'name="cdc_fields[' . esc_attr( $field->id ) . ']" ' . $required; ?>>
                                </div>
                            <?php else : ?>
                                <input data-cdc-field="<?php echo esc_attr( $field->name ); ?>" type="<?php echo esc_attr( $type ); ?>" id="cdc-field-<?php echo esc_attr( $field->id ); ?>" value="<?php echo esc_attr( $val ); ?>" class="regular-text" <?php echo $readonly ? 'readonly disabled' : 'name="cdc_fields[' . esc_attr( $field->id ) . ']" ' . $required; ?>>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </table>
            </div>
            <?php foreach ( $enabled as $tab ) : if ( empty( $groups[ $tab ] ) ) continue; ?>
            <div class="tab-pane fade" id="tab-<?php echo esc_attr( $tab ); ?>" role="tabpanel">
                <p class="description"><code>[council_counter id="<?php echo esc_attr( $post_id ); ?>" type="<?php echo esc_attr( $tab ); ?>"]</code></p>
                <table class="form-table" role="presentation">
                <?php foreach ( $groups[ $tab ] as $field ) :
                    $val = $post_id ? \CouncilDebtCounters\Custom_Fields::get_value( $post_id, $field->name ) : '';
                    $type = $field->type === 'text' ? 'text' : 'number';
                    $required = $field->required ? 'required' : '';
                    $readonly = in_array( $field->name, \CouncilDebtCounters\Custom_Fields::READONLY_FIELDS, true ); ?>
                    <tr>
                        <th scope="row"><label for="cdc-field-<?php echo esc_attr( $field->id ); ?>"><?php echo esc_html( $field->label ); ?><?php if ( $field->required ) echo ' *'; ?></label></th>
                        <td>
                            <?php if ( $field->type === 'money' ) : ?>
                                <div class="input-group">
                                    <span class="input-group-text">&pound;</span>
                                    <input data-cdc-field="<?php echo esc_attr( $field->name ); ?>" type="number" step="0.01" id="cdc-field-<?php echo esc_attr( $field->id ); ?>" value="<?php echo esc_attr( $val ); ?>" class="regular-text" <?php echo $readonly ? 'readonly disabled' : 'name="cdc_fields[' . esc_attr( $field->id ) . ']" ' . $required; ?>>
                                </div>
                            <?php else : ?>
                                <input data-cdc-field="<?php echo esc_attr( $field->name ); ?>" type="<?php echo esc_attr( $type ); ?>" id="cdc-field-<?php echo esc_attr( $field->id ); ?>" value="<?php echo esc_attr( $val ); ?>" class="regular-text" <?php echo $readonly ? 'readonly disabled' : 'name="cdc_fields[' . esc_attr( $field->id ) . ']" ' . $required; ?>>
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
                            <?php $val = $post_id ? \CouncilDebtCounters\Custom_Fields::get_value( $post_id, 'statement_of_accounts' ) : ''; ?>
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
            <?php if ( $post_id ) : ?>
            <div class="tab-pane fade" id="tab-reports" role="tabpanel">
                <p class="description"><code>[whistleblower_form id="<?php echo esc_attr( $post_id ); ?>"]</code></p>
                <?php $reports = get_posts([
                    'post_type'   => \CouncilDebtCounters\Whistleblower_Form::CPT,
                    'numberposts' => -1,
                    'meta_key'    => 'council_id',
                    'meta_value'  => $post_id,
                ]); ?>
                <?php if ( empty( $reports ) ) : ?>
                    <p><?php esc_html_e( 'No reports yet.', 'council-debt-counters' ); ?></p>
                <?php else : ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Date', 'council-debt-counters' ); ?></th>
                            <th><?php esc_html_e( 'Description', 'council-debt-counters' ); ?></th>
                            <th><?php esc_html_e( 'Attachment', 'council-debt-counters' ); ?></th>
                            <th><?php esc_html_e( 'Email', 'council-debt-counters' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ( $reports as $r ) : ?>
                        <tr>
                            <td><?php echo esc_html( get_the_date( '', $r ) ); ?></td>
                            <td><?php echo esc_html( wp_trim_words( $r->post_content, 15, '…' ) ); ?></td>
                            <td>
                                <?php $aid = get_post_meta( $r->ID, 'attachment_id', true ); ?>
                                <?php if ( $aid ) : ?>
                                    <?php echo wp_get_attachment_link( $aid ); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( get_post_meta( $r->ID, 'contact_email', true ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php submit_button( __( 'Save Council', 'council-debt-counters' ) ); ?>
    </form>
    </div>
    <?php
    return;
}

$councils = get_posts([
    'post_type'   => 'council',
    'numberposts' => -1,
    'post_status' => [ 'publish', 'draft' ],
]);
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Councils', 'council-debt-counters' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=cdc-manage-councils&action=edit' ) ); ?>" class="btn btn-primary mb-3"><?php esc_html_e( 'Add New', 'council-debt-counters' ); ?></a>
    <table class="table table-striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Name', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Debt Counter', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'council-debt-counters' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $councils ) ) : ?>
                <tr><td colspan="3"><?php esc_html_e( 'No councils found.', 'council-debt-counters' ); ?></td></tr>
            <?php else : foreach ( $councils as $council ) : ?>
                <tr>
                    <td><?php echo esc_html( get_the_title( $council ) ); ?></td>
                    <td>
                        <?php echo do_shortcode( '[council_counter id="' . $council->ID . '"]' ); ?>
                        <code>[council_counter id="<?php echo esc_attr( $council->ID ); ?>"]</code>
                    </td>
                    <td>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=cdc-manage-councils&action=edit&post=' . $council->ID ) ); ?>" class="btn btn-sm btn-secondary"><?php esc_html_e( 'Edit', 'council-debt-counters' ); ?></a>
                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=cdc-manage-councils&action=delete&post=' . $council->ID ), 'cdc_delete_council_' . $council->ID ) ); ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?php esc_attr_e( 'Delete this council?', 'council-debt-counters' ); ?>');"><?php esc_html_e( 'Delete', 'council-debt-counters' ); ?></a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
