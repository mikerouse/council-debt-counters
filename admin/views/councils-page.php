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
    $args = [
        'post_id'     => $post_id ? $post_id : 'new_post',
        'new_post'    => [
            'post_type'   => 'council',
            'post_status' => 'publish',
        ],
        'return'      => admin_url( 'admin.php?page=cdc-manage-councils' ),
        'submit_value' => __( 'Save Council', 'council-debt-counters' ),
    ];
    if ( function_exists( 'acf_form' ) ) {
        acf_form( $args );
    }
    echo '</div>';
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
                <th><?php esc_html_e( 'Actions', 'council-debt-counters' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $councils ) ) : ?>
                <tr><td colspan="2"><?php esc_html_e( 'No councils found.', 'council-debt-counters' ); ?></td></tr>
            <?php else : foreach ( $councils as $council ) : ?>
                <tr>
                    <td><?php echo esc_html( get_the_title( $council ) ); ?></td>
                    <td>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=cdc-manage-councils&action=edit&post=' . $council->ID ) ); ?>" class="btn btn-sm btn-secondary"><?php esc_html_e( 'Edit', 'council-debt-counters' ); ?></a>
                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=cdc-manage-councils&action=delete&post=' . $council->ID ), 'cdc_delete_council_' . $council->ID ) ); ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?php esc_attr_e( 'Delete this council?', 'council-debt-counters' ); ?>');"><?php esc_html_e( 'Delete', 'council-debt-counters' ); ?></a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    <p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=acf-field-group' ) ); ?>"><?php esc_html_e( 'Manage field groups', 'council-debt-counters' ); ?></a></p>
</div>
