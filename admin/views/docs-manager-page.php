<?php
use CouncilDebtCounters\Docs_Manager;

if ( ! defined( 'ABSPATH' ) ) exit;

$docs = Docs_Manager::list_documents();
$can_upload = Docs_Manager::can_upload();
$upload_error = '';

if ( isset( $_POST['cdc_assign_doc'], $_POST['cdc_doc_name'], $_POST['cdc_council'], $_POST['cdc_doc_type'], $_POST['cdc_assign_nonce'] ) && wp_verify_nonce( $_POST['cdc_assign_nonce'], 'cdc_assign_doc' ) ) {
    $file = sanitize_file_name( $_POST['cdc_doc_name'] );
    $council = intval( $_POST['cdc_council'] );
    $type = sanitize_key( $_POST['cdc_doc_type'] );
    $year = sanitize_text_field( $_POST['cdc_doc_year'] );
    Docs_Manager::assign_document( $file, $council, $type, $year );
    if ( in_array( $type, [ 'draft_statement_of_accounts', 'audited_statement_of_accounts' ], true ) ) {
        \CouncilDebtCounters\Custom_Fields::update_value( $council, 'statement_of_accounts', $file, \CouncilDebtCounters\CDC_Utils::current_financial_year() );
    }
    echo '<div class="notice notice-success"><p>' . esc_html__( 'Document assigned.', 'council-debt-counters' ) . '</p></div>';
    $docs = Docs_Manager::list_documents();
}

if ( isset( $_POST['cdc_delete_doc'], $_POST['cdc_doc_name'], $_POST['cdc_delete_doc_nonce'] ) && wp_verify_nonce( $_POST['cdc_delete_doc_nonce'], 'cdc_delete_doc' ) ) {
    Docs_Manager::delete_document( sanitize_file_name( $_POST['cdc_doc_name'] ) );
    echo '<div class="notice notice-success"><p>' . esc_html__( 'Document deleted.', 'council-debt-counters' ) . '</p></div>';
    $docs = Docs_Manager::list_documents();
}

if ( isset( $_FILES['cdc_upload_doc'] ) && $_FILES['cdc_upload_doc']['size'] > 0 ) {
    $year = sanitize_text_field( $_POST['cdc_upload_year'] ?? Docs_Manager::current_financial_year() );
    $result = Docs_Manager::upload_document( $_FILES['cdc_upload_doc'], '', 0, $year );
    if ( $result === true ) {
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Document uploaded.', 'council-debt-counters' ) . '</p></div>';
        $docs = Docs_Manager::list_documents();
    } else {
        $upload_error = $result;
    }
}
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Manage Documents', 'council-debt-counters' ); ?></h1>
    <p><?php esc_html_e( 'Upload, replace, or delete financial documents. Only XLSX, CSV, and PDF files are allowed for security reasons.', 'council-debt-counters' ); ?></p>
    <p><?php esc_html_e( 'Document uploads are unlimited.', 'council-debt-counters' ); ?></p>
    <?php if ( $upload_error ) : ?>
        <div class="notice notice-error"><p><?php echo esc_html( $upload_error ); ?></p></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="cdc_upload_doc" accept=".csv,.pdf,.xlsx" <?php if ( ! $can_upload ) echo 'disabled'; ?> />
        <select name="cdc_upload_year" style="margin-left:10px;">
            <?php foreach ( Docs_Manager::financial_years() as $y ) : ?>
                <option value="<?php echo esc_attr( $y ); ?>" <?php selected( Docs_Manager::current_financial_year(), $y ); ?>><?php echo esc_html( $y ); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="button button-primary" <?php if ( ! $can_upload ) echo 'disabled'; ?>><?php esc_html_e( 'Upload', 'council-debt-counters' ); ?></button>
        <?php if ( ! $can_upload ) : ?>
            <p class="description" style="color:red;"><?php esc_html_e( 'Upload limit reached.', 'council-debt-counters' ); ?></p>
        <?php endif; ?>
    </form>
    <h2><?php esc_html_e( 'Uploaded Documents', 'council-debt-counters' ); ?></h2>
    <table class="widefat">
        <thead>
            <tr>
                <th><?php esc_html_e( 'File Name', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Council', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Year', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Type', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'council-debt-counters' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $docs ) ) : ?>
                <tr><td colspan="4"><?php esc_html_e( 'No documents uploaded.', 'council-debt-counters' ); ?></td></tr>
            <?php else : foreach ( $docs as $doc ) : ?>
                <tr>
                    <td><?php echo esc_html( $doc->filename ); ?></td>
                    <td>
                        <?php echo $doc->council_id ? esc_html( get_the_title( $doc->council_id ) ) : esc_html__( 'Unassigned', 'council-debt-counters' ); ?>
                    </td>
                    <td><?php echo esc_html( $doc->financial_year ); ?></td>
                    <td><?php echo esc_html( Docs_Manager::doc_type_labels()[ $doc->doc_type ] ?? $doc->doc_type ); ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="cdc_doc_name" value="<?php echo esc_attr( $doc->filename ); ?>" />
                            <?php wp_nonce_field( 'cdc_delete_doc', 'cdc_delete_doc_nonce' ); ?>
                            <button type="submit" name="cdc_delete_doc" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this document?', 'council-debt-counters' ); ?>');"><?php esc_html_e( 'Delete', 'council-debt-counters' ); ?></button>
                        </form>
                        <a href="<?php echo esc_url( plugins_url( 'docs/' . $doc->filename, dirname( __DIR__, 2 ) . '/council-debt-counters.php' ) ); ?>" target="_blank" rel="noopener noreferrer" class="button">View</a>
                        <?php if ( $doc->council_id == 0 ) : ?>
                            <form method="post" style="display:inline; margin-left:10px;">
                                <?php wp_nonce_field( 'cdc_assign_doc', 'cdc_assign_nonce' ); ?>
                                <input type="hidden" name="cdc_doc_name" value="<?php echo esc_attr( $doc->filename ); ?>" />
                                <select name="cdc_council">
                                    <?php foreach ( get_posts( [ 'post_type' => 'council', 'numberposts' => -1 ] ) as $c ) : ?>
                                        <option value="<?php echo esc_attr( $c->ID ); ?>"><?php echo esc_html( $c->post_title ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="cdc_doc_type">
                                    <option value="draft_statement_of_accounts"><?php esc_html_e( 'Draft Statement', 'council-debt-counters' ); ?></option>
                                    <option value="audited_statement_of_accounts"><?php esc_html_e( 'Audited Statement', 'council-debt-counters' ); ?></option>
                                </select>
                                <select name="cdc_doc_year">
                                    <?php foreach ( Docs_Manager::financial_years() as $y ) : ?>
                                        <option value="<?php echo esc_attr( $y ); ?>" <?php selected( Docs_Manager::current_financial_year(), $y ); ?>><?php echo esc_html( $y ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="cdc_assign_doc" class="button button-primary"><?php esc_html_e( 'Assign', 'council-debt-counters' ); ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
