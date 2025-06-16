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
    ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="cdc_save_council">
        <?php wp_nonce_field( 'cdc_save_council' ); ?>
        <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
        <table class="form-table" role="presentation">
            <?php foreach ( $fields as $field ) :
                $val = $post_id ? \CouncilDebtCounters\Custom_Fields::get_value( $post_id, $field->name ) : '';
                $type = $field->type === 'text' ? 'text' : 'number';
                $required = $field->required ? 'required' : '';
                $readonly = in_array( $field->name, \CouncilDebtCounters\Custom_Fields::READONLY_FIELDS, true );
            ?>
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
