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
            'cb'        => '<input type="checkbox" />',
            'name'      => __( 'Name', 'council-debt-counters' ),
            'shortcode' => __( 'Shortcode', 'council-debt-counters' ),
            'status'    => __( 'Status', 'council-debt-counters' ),
        ];
    }

    protected function get_sortable_columns() {
        return [
            'name'   => [ 'title', false ],
            'status' => [ 'post_status', false ],
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

    protected function column_shortcode( $item ) {
        $code = sprintf( '[council_counter id="%d"]', $item->ID );
        $live = do_shortcode( $code );
        return $live . '<code>' . esc_html( $code ) . '</code>';
    }

    protected function column_status( $item ) {
        return esc_html( ucwords( str_replace( '_', ' ', $item->post_status ) ) );
    }

    public function get_bulk_actions() {
        return [
            'repair' => __( 'Repair', 'council-debt-counters' ),
        ];
    }

    public function process_bulk_action() {
        if ( 'repair' === $this->current_action() && ! empty( $_POST['council'] ) ) {
            $ids = array_map( 'intval', (array) $_POST['council'] );
            foreach ( $ids as $id ) {
                $title = get_the_title( $id );
                $meta  = Custom_Fields::get_value( $id, 'council_name' );
                if ( empty( $title ) && ! empty( $meta ) ) {
                    wp_update_post( [ 'ID' => $id, 'post_title' => $meta ] );
                } elseif ( empty( $meta ) && ! empty( $title ) ) {
                    Custom_Fields::update_value( $id, 'council_name', $title );
                }
            }
            add_settings_error( 'cdc_messages', 'cdc_repair', __( 'Repair completed.', 'council-debt-counters' ), 'updated' );
        }
    }

    public function prepare_items() {
        $per_page = 20;
        $paged    = $this->get_pagenum();

        $orderby = sanitize_key( $_REQUEST['orderby'] ?? 'title' );
        $order   = sanitize_key( $_REQUEST['order'] ?? 'asc' );
        $allowed = [ 'title', 'post_status' ];
        if ( ! in_array( $orderby, $allowed, true ) ) {
            $orderby = 'title';
        }
        $order = 'desc' === strtolower( $order ) ? 'desc' : 'asc';

        $query_args = [
            'post_type'      => 'council',
            'post_status'    => $this->status,
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'orderby'        => $orderby,
            'order'          => $order,
        ];

        if ( ! empty( $_REQUEST['s'] ) ) {
            $query_args['s'] = sanitize_text_field( $_REQUEST['s'] );
        }

        $query = new \WP_Query( $query_args );

        $this->items = $query->posts;

        $this->set_pagination_args( [
            'total_items' => $query->found_posts,
            'per_page'    => $per_page,
        ] );
    }
}
