<?php
use CouncilDebtCounters\Whistleblower_Form;
use CouncilDebtCounters\Whistleblower_Reports_Page;

if ( ! defined( 'ABSPATH' ) ) exit;

$report_id = isset( $_GET['report'] ) ? intval( $_GET['report'] ) : 0;

if ( $report_id ) {
    $report = get_post( $report_id );
    if ( $report && $report->post_type === Whistleblower_Form::CPT ) {
        $council_id   = (int) get_post_meta( $report_id, 'council_id', true );
        $email        = get_post_meta( $report_id, 'contact_email', true );
        $attachment_id = get_post_meta( $report_id, 'attachment_id', true );
        $ip           = get_post_meta( $report_id, 'ip_address', true );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Whistleblower Report', 'council-debt-counters' ); ?></h1>
            <p><strong><?php esc_html_e( 'Date:', 'council-debt-counters' ); ?></strong> <?php echo esc_html( get_the_date( '', $report ) ); ?></p>
            <p><strong><?php esc_html_e( 'Council:', 'council-debt-counters' ); ?></strong> <?php echo $council_id ? esc_html( get_the_title( $council_id ) ) : esc_html__( 'Unknown', 'council-debt-counters' ); ?></p>
            <p><strong><?php esc_html_e( 'Contact Email:', 'council-debt-counters' ); ?></strong> <?php echo esc_html( $email ); ?></p>
            <p><strong><?php esc_html_e( 'IP Address:', 'council-debt-counters' ); ?></strong> <?php echo esc_html( $ip ); ?></p>
            <p><strong><?php esc_html_e( 'Description:', 'council-debt-counters' ); ?></strong></p>
            <div class="card p-3 mb-3"><?php echo wp_kses_post( nl2br( esc_html( $report->post_content ) ) ); ?></div>
            <?php if ( $attachment_id ) : ?>
                <p><strong><?php esc_html_e( 'Attachment:', 'council-debt-counters' ); ?></strong> <?php echo wp_get_attachment_link( $attachment_id ); ?></p>
            <?php endif; ?>
            <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Whistleblower_Reports_Page::SLUG ) ); ?>" class="button"><?php esc_html_e( 'Back to list', 'council-debt-counters' ); ?></a></p>
        </div>
        <?php
    } else {
        echo '<div class="wrap"><p>' . esc_html__( 'Report not found.', 'council-debt-counters' ) . '</p></div>';
    }
    return;
}

$reports = get_posts([
    'post_type'   => Whistleblower_Form::CPT,
    'numberposts' => -1,
]);
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Whistleblower Reports', 'council-debt-counters' ); ?></h1>
    <?php if ( empty( $reports ) ) : ?>
        <p><?php esc_html_e( 'No reports found.', 'council-debt-counters' ); ?></p>
    <?php else : ?>
    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Date', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Council', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Summary', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Email', 'council-debt-counters' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $reports as $r ) : ?>
            <tr>
                <td><?php echo esc_html( get_the_date( '', $r ) ); ?></td>
                <td><?php $cid = get_post_meta( $r->ID, 'council_id', true ); echo $cid ? esc_html( get_the_title( $cid ) ) : esc_html__( 'Unknown', 'council-debt-counters' ); ?></td>
                <td><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Whistleblower_Reports_Page::SLUG . '&report=' . $r->ID ) ); ?>"><?php echo esc_html( wp_trim_words( $r->post_content, 10, '…' ) ); ?></a></td>
                <td><?php echo esc_html( get_post_meta( $r->ID, 'contact_email', true ) ); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
