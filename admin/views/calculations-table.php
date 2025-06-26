<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$fields = CouncilDebtCounters\Custom_Fields::get_fields();
?>
<h2><?php echo esc_html( get_the_title( $cid ) ); ?></h2>
<table class="widefat striped">
    <thead>
        <tr>
            <th><?php esc_html_e( 'Field', 'council-debt-counters' ); ?></th>
            <th><?php esc_html_e( '2023/24', 'council-debt-counters' ); ?></th>
            <th><?php esc_html_e( '2025/26', 'council-debt-counters' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $fields as $f ) : ?>
            <tr>
                <td><?php echo esc_html( $f->label ); ?></td>
                <td><?php echo esc_html( CouncilDebtCounters\Custom_Fields::get_value( $cid, $f->name, '2023/24' ) ); ?></td>
                <td><?php echo esc_html( CouncilDebtCounters\Custom_Fields::get_value( $cid, $f->name, '2025/26' ) ); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
