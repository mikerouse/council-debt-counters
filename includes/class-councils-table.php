<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( '\WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Councils_Table extends \WP_List_Table {
    private $status;

    public function __construct( string $status = 'publish' ) {
        $this->status = $status;
        parent::__construct(
            [
                'singular' => 'council',
                'plural'   => 'councils',
                'ajax'     => false,
            ]
        );
    }

    public function get_columns() {
        return [
            'cb'           => '<input type="checkbox" />',
            'name'         => __( 'Name', 'council-debt-counters' ),
            'id'           => __( 'ID', 'council-debt-counters' ),
            'population'   => __( 'Population', 'council-debt-counters' ),
            'visits'       => __( 'Visits Last Hour', 'council-debt-counters' ),
            'last_updated' => __( 'Last Updated', 'council-debt-counters' ),
            'status'       => __( 'Status', 'council-debt-counters' ),
        ];
    }

    protected function get_sortable_columns() {
        return [
            'name'         => [ 'title', false ],
            'population'   => [ 'population', false ],
            'last_updated' => [ 'last_updated', false ],
            'status'       => [ 'post_status', false ],
        ];
    }

    protected function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="council[]" value="%d" />', intval( $item->ID ) );
    }

    protected function column_name( $item ) {
        $edit = admin_url( 'admin.php?page=' . Council_Admin_Page::PAGE_SLUG . '&action=edit&post=' . $item->ID );
        $actions = [
            'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( $edit ), __( 'Edit', 'council-debt-counters' ) ),
        ];
        $del = wp_nonce_url( admin_url( 'admin.php?page=' . Council_Admin_Page::PAGE_SLUG . '&action=delete&post=' . $item->ID ), 'cdc_delete_council_' . $item->ID );
        $actions['delete'] = sprintf( '<a href="%s" onclick="return confirm(\'%s\');">%s</a>', esc_url( $del ), esc_js( __( 'Delete this council?', 'council-debt-counters' ) ), __( 'Delete', 'council-debt-counters' ) );

        return sprintf( '<strong><a class="row-title" href="%s">%s</a></strong>%s', esc_url( $edit ), esc_html( get_the_title( $item ) ), $this->row_actions( $actions ) );
    }

    protected function column_id( $item ) {
        return intval( $item->ID );
    }

    protected function column_visits( $item ) {
        $counts = Stats_Page::get_visit_counts( $item->ID );
        return sprintf( '%d human / %d AI', $counts['human'], $counts['ai'] );
    }

    protected function column_shortcode( $item ) {
        $code = sprintf( '[council_counter id="%d"]', $item->ID );
        $live = do_shortcode( $code );
        return $live . '<code>' . esc_html( $code ) . '</code>';
    }

    protected function column_population( $item ) {
        $pop = (int) Custom_Fields::get_value( $item->ID, 'population', CDC_Utils::current_financial_year() );
        return $pop ? number_format_i18n( $pop ) : '&mdash;';
    }

    protected function column_last_updated( $item ) {
        return esc_html( get_the_modified_date( get_option( 'date_format' ), $item ) );
    }

    protected function column_status( $item ) {
        return esc_html( ucwords( str_replace( '_', ' ', $item->post_status ) ) );
    }

    public function get_bulk_actions() {
        return [
            'repair'     => __( 'Repair', 'council-debt-counters' ),
            'publish_na' => __( 'Publish as N/A', 'council-debt-counters' ),
        ];
    }

    public function process_bulk_action() {
        if ( 'repair' === $this->current_action() && ! empty( $_POST['council'] ) ) {
            $ids = array_map( 'intval', (array) $_POST['council'] );
            foreach ( $ids as $id ) {
                $title = get_the_title( $id );
                $meta  = Custom_Fields::get_value( $id, 'council_name', CDC_Utils::current_financial_year() );
                if ( empty( $title ) && ! empty( $meta ) ) {
                    wp_update_post( [ 'ID' => $id, 'post_title' => $meta ] );
                } elseif ( empty( $meta ) && ! empty( $title ) ) {
                    Custom_Fields::update_value( $id, 'council_name', $title, CDC_Utils::current_financial_year() );
                }
            }
            add_settings_error( 'cdc_messages', 'cdc_repair', __( 'Repair completed.', 'council-debt-counters' ), 'updated' );
        }

        if ( 'publish_na' === $this->current_action() && ! empty( $_POST['council'] ) ) {
            $ids     = array_map( 'intval', (array) $_POST['council'] );
            $fields  = Custom_Fields::get_fields();
            $enabled = (array) get_option( 'cdc_enabled_counters', array() );

            foreach ( $ids as $id ) {
                wp_update_post( [ 'ID' => $id, 'post_status' => 'publish' ] );

                foreach ( $enabled as $tab ) {
                    update_post_meta( $id, 'cdc_na_tab_' . $tab, '1' );
                }

                foreach ( $fields as $field ) {
                    update_post_meta( $id, 'cdc_na_' . $field->name, '1' );
                }
            }

            add_settings_error( 'cdc_messages', 'cdc_publish_na', __( 'Councils published as N/A.', 'council-debt-counters' ), 'updated' );
        }
    }

    public function prepare_items() {
        $per_page = 20;
        $paged    = $this->get_pagenum();

        $orderby = sanitize_key( $_REQUEST['orderby'] ?? 'title' );
        $order   = sanitize_key( $_REQUEST['order'] ?? 'asc' );

        $sortable   = $this->get_sortable_columns();
        $db_orderby = 'title';
        if ( isset( $sortable[ $orderby ] ) ) {
            $db_orderby = $sortable[ $orderby ][0];
        } elseif ( in_array( $orderby, [ 'title', 'post_status' ], true ) ) {
            $db_orderby = $orderby;
        }

        $order = 'desc' === strtolower( $order ) ? 'desc' : 'asc';

        $query_args = [
            'post_type'      => 'council',
            'post_status'    => $this->status,
            'posts_per_page' => -1,
            'orderby'        => $db_orderby,
            'order'          => $order,
        ];

        if ( ! empty( $_REQUEST['s'] ) ) {
            $query_args['s'] = sanitize_text_field( $_REQUEST['s'] );
        }

        $query = new \WP_Query( $query_args );

        $posts = $query->posts;

        if ( 'population' === $orderby ) {
            usort(
                $posts,
                function ( $a, $b ) use ( $order ) {
                    $year  = CDC_Utils::current_financial_year();
                    $val_a = (int) Custom_Fields::get_value( $a->ID, 'population', $year );
                    $val_b = (int) Custom_Fields::get_value( $b->ID, 'population', $year );
                    return 'asc' === $order ? $val_a <=> $val_b : $val_b <=> $val_a;
                }
            );
        } elseif ( 'last_updated' === $orderby ) {
            usort(
                $posts,
                function ( $a, $b ) use ( $order ) {
                    $val_a = strtotime( $a->post_modified );
                    $val_b = strtotime( $b->post_modified );
                    return 'asc' === $order ? $val_a <=> $val_b : $val_b <=> $val_a;
                }
            );
        }

        $total_items   = count( $posts );
        $offset        = ( $paged - 1 ) * $per_page;
        $this->items   = array_slice( $posts, $offset, $per_page );

        // Set up the column headers so the table renders correctly.
        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [ $columns, $hidden, $sortable ];

        $this->set_pagination_args(
            [
                'total_items' => $total_items,
                'per_page'    => $per_page,
            ]
        );
    }
}
