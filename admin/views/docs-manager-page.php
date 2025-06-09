<?php
use CouncilDebtCounters\Docs_Manager;
use CouncilDebtCounters\License_Manager;

if ( ! defined( 'ABSPATH' ) ) exit;

$docs = Docs_Manager::list_documents();
$can_upload = Docs_Manager::can_upload();
$is_pro = License_Manager::is_valid();
$upload_error = '';

if ( isset( $_POST['cdc_delete_doc'] ) && isset( $_POST['cdc_doc_name'] ) ) {
    Docs_Manager::delete_document( sanitize_file_name( $_POST['cdc_doc_name'] ) );
    echo '<div class="notice notice-success"><p>' . esc_html__( 'Document deleted.', 'council-debt-counters' ) . '</p></div>';
    $docs = Docs_Manager::list_documents();
}

if ( isset( $_FILES['cdc_upload_doc'] ) && $_FILES['cdc_upload_doc']['size'] > 0 ) {
    $result = Docs_Manager::upload_document( $_FILES['cdc_upload_doc'] );
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
    <p><?php echo $is_pro
        ? esc_html__( 'You have unlimited document uploads (Pro version).', 'council-debt-counters' )
        : esc_html__( 'Free version: Maximum 10 documents allowed.', 'council-debt-counters' ); ?></p>
    <?php if ( $upload_error ) : ?>
        <div class="notice notice-error"><p><?php echo esc_html( $upload_error ); ?></p></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="cdc_upload_doc" accept=".csv,.pdf,.xlsx" <?php if ( ! $can_upload ) echo 'disabled'; ?> />
        <button type="submit" class="button button-primary" <?php if ( ! $can_upload ) echo 'disabled'; ?>><?php esc_html_e( 'Upload', 'council-debt-counters' ); ?></button>
        <?php if ( ! $can_upload ) : ?>
            <p class="description" style="color:red;"><?php esc_html_e( 'Free version limit reached. Delete a document or upgrade to Pro.', 'council-debt-counters' ); ?></p>
        <?php endif; ?>
    </form>
    <h2><?php esc_html_e( 'Uploaded Documents', 'council-debt-counters' ); ?></h2>
    <table class="widefat">
        <thead>
            <tr>
                <th><?php esc_html_e( 'File Name', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'council-debt-counters' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $docs ) ) : ?>
                <tr><td colspan="2"><?php esc_html_e( 'No documents uploaded.', 'council-debt-counters' ); ?></td></tr>
            <?php else : foreach ( $docs as $doc ) : ?>
                <tr>
                    <td><?php echo esc_html( $doc ); ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="cdc_doc_name" value="<?php echo esc_attr( $doc ); ?>" />
                            <button type="submit" name="cdc_delete_doc" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this document?', 'council-debt-counters' ); ?>');"><?php esc_html_e( 'Delete', 'council-debt-counters' ); ?></button>
                        </form>
                        <a href="<?php echo esc_url( plugins_url( 'docs/' . $doc, dirname( __DIR__, 2 ) . '/council-debt-counters.php' ) ); ?>" target="_blank" rel="noopener noreferrer" class="button">View</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
