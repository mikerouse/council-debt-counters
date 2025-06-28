<?php
use CouncilDebtCounters\CDC_Utils;
use CouncilDebtCounters\Custom_Fields;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$year  = CDC_Utils::current_financial_year();
$years = CDC_Utils::council_years();
$query = new WP_Query([
    'post_type'      => 'council',
    'post_status'    => 'under_review',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'asc',
]);
// Fields to display/edit in the Power Editor. We include the core debt
// figures plus PFI, spending, deficit and income so power users can
// rapidly work through the most common data points.
$fields = [
'population',
'current_liabilities',
'long_term_liabilities',
'finance_lease_pfi_liabilities',
'annual_spending',
'annual_deficit',
'non_council_tax_income',
'council_tax_general_grants_income',
'government_grants_income',
'all_other_income',
'interest_paid',
'council_closed',
];
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Power Editor', 'council-debt-counters' ); ?></h1>
    <div id="cdc-pe-header" class="d-flex align-items-center mb-3">
        <select id="cdc-pe-year" class="form-select me-2" style="width:auto;">
            <?php foreach ( $years as $y ) : ?>
                <option value="<?php echo esc_attr( $y ); ?>" <?php selected( $year, $y ); ?>><?php echo esc_html( $y ); ?></option>
            <?php endforeach; ?>
        </select>
        <input id="cdc-pe-search" type="search" class="form-control" placeholder="<?php esc_attr_e( 'Search councils…', 'council-debt-counters' ); ?>" style="max-width:200px;" />
        <span id="cdc-pe-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
    </div>
    <table class="table table-striped table-hover" id="cdc-power-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'ID', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Council', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Population', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Current Liabilities', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Long-Term Liabilities', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'PFI & Finance Lease Liabilities', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Expenditure on Services', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Reported Net Deficit (or Surplus)', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Non-Council Tax Income', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Income from Council Tax and General Grants', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Government Grants', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'All Other Income', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Interest Paid', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Closed', 'council-debt-counters' ); ?></th>
                <th><?php esc_html_e( 'Confirm', 'council-debt-counters' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $query->posts as $p ) : ?>
                <tr data-cid="<?php echo esc_attr( $p->ID ); ?>">
                    <td><?php echo intval( $p->ID ); ?></td>
                    <td><?php echo esc_html( get_the_title( $p ) ); ?></td>
                    <?php foreach ( $fields as $f ) : ?>
                        <?php $val = Custom_Fields::get_value( $p->ID, $f, $year ); ?>
                        <?php if ( 'council_closed' === $f ) : ?>
                            <td class="text-center"><input type="checkbox" class="form-check-input cdc-pe-input" data-field="<?php echo esc_attr( $f ); ?>" <?php checked( $val, '1' ); ?>></td>
                        <?php else : ?>
                            <td><input type="text" class="form-control form-control-sm cdc-pe-input" data-field="<?php echo esc_attr( $f ); ?>" value="<?php echo esc_attr( $val ); ?>" /></td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <td><button type="button" class="btn btn-sm btn-success cdc-pe-confirm"><?php esc_html_e( 'Confirm', 'council-debt-counters' ); ?></button></td>
                </tr>
            <?php endforeach; wp_reset_postdata(); ?>
        </tbody>
    </table>
</div>
