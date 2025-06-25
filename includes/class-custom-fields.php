<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Custom_Fields {
    const TABLE_FIELDS = 'cdc_fields';
    const TABLE_VALUES = 'cdc_field_values';
    const PAGE_SLUG = 'cdc-custom-fields';

    const TAB_OPTIONS = [
        'general',
        'debt',
        'spending',
        'income',
        'deficit',
        'interest',
        'reserves',
        'consultancy',
    ];

    const DEFAULT_FIELD_TABS = [
        'current_liabilities'           => 'debt',
        'long_term_liabilities'         => 'debt',
        'finance_lease_pfi_liabilities' => 'debt',
        'manual_debt_entry'             => 'debt',
        'minimum_revenue_provision'     => 'debt',
        'total_debt'                    => 'debt',
        'annual_spending'               => 'spending',
        'total_income'                  => 'income',
        'annual_deficit'                => 'deficit',
        'interest_paid'                 => 'interest',
        'usable_reserves'               => 'reserves',
        'consultancy_spend'             => 'consultancy',
    ];

    const DEFAULT_FIELDS = [
        ['name' => 'council_name', 'label' => 'Council Name', 'type' => 'text', 'required' => 1],
        ['name' => 'council_type', 'label' => 'Council Type', 'type' => 'text', 'required' => 0],
        ['name' => 'council_location', 'label' => 'Council Location', 'type' => 'text', 'required' => 0],
        ['name' => 'population', 'label' => 'Population', 'type' => 'number', 'required' => 0],
        ['name' => 'households', 'label' => 'Households', 'type' => 'number', 'required' => 0],
        // Liability figures are only mandatory when the Debt tab is enabled and
        // the values are not marked as "Not available".
        ['name' => 'current_liabilities', 'label' => 'Current Liabilities', 'type' => 'money', 'required' => 0],
        ['name' => 'long_term_liabilities', 'label' => 'Long-Term Liabilities', 'type' => 'money', 'required' => 0],
        ['name' => 'finance_lease_pfi_liabilities', 'label' => 'PFI or Finance Lease Liabilities', 'type' => 'money', 'required' => 0],
        ['name' => 'minimum_revenue_provision', 'label' => 'Minimum Revenue Provision', 'type' => 'money', 'required' => 0],
        ['name' => 'total_debt', 'label' => 'Total Debt', 'type' => 'money', 'required' => 0],
        ['name' => 'manual_debt_entry', 'label' => 'Manual Debt Entry', 'type' => 'money', 'required' => 0],
        ['name' => 'annual_spending', 'label' => 'Annual Spending', 'type' => 'money', 'required' => 0],
        ['name' => 'total_income', 'label' => 'Total Income', 'type' => 'money', 'required' => 0],
        ['name' => 'annual_deficit', 'label' => 'Annual Deficit', 'type' => 'money', 'required' => 0],
        ['name' => 'interest_paid', 'label' => 'Interest Paid on Debt', 'type' => 'money', 'required' => 0],
        ['name' => 'capital_financing_requirement', 'label' => 'Capital Financing Requirement', 'type' => 'money', 'required' => 0],
        ['name' => 'usable_reserves', 'label' => 'Usable Reserves', 'type' => 'money', 'required' => 0],
        ['name' => 'consultancy_spend', 'label' => 'Consultancy Spend', 'type' => 'money', 'required' => 0],
        ['name' => 'waste_report_count', 'label' => 'Waste Report Count', 'type' => 'number', 'required' => 0],
        ['name' => 'statement_of_accounts', 'label' => 'Statement of Accounts (PDF)', 'type' => 'text', 'required' => 0],
        ['name' => 'financial_data_source_url', 'label' => 'Financial Data Source URL', 'type' => 'text', 'required' => 0],
        ['name' => 'status_message', 'label' => 'Status Message', 'type' => 'text', 'required' => 0],
        ['name' => 'status_message_type', 'label' => 'Status Message Type', 'type' => 'text', 'required' => 0],
    ];

    /**
     * Fields that must always exist and cannot be removed.
     * The field type and name for these are locked.
     */
    const IMMUTABLE_FIELDS = [
        'council_name',
        'current_liabilities',
        'long_term_liabilities',
        'statement_of_accounts',
    ];

    /**
     * Fields that are calculated by the system and are not editable via the UI.
     */
    const READONLY_FIELDS = [
        'total_debt',
    ];

    public static function init() {
        // Ensure this submenu appears after the main menu is registered.
        add_action( 'admin_menu', [ __CLASS__, 'admin_menu' ], 11 );
        // Verify tables exist in case the plugin was updated without reactivation.
        add_action( 'init', [ __CLASS__, 'maybe_install' ] );
        add_action( 'init', [ __CLASS__, 'register_meta_fields' ] );
    }

    public static function install() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $fields_table = $wpdb->prefix . self::TABLE_FIELDS;
        $sql_fields = "CREATE TABLE $fields_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            label varchar(255) NOT NULL,
            type varchar(20) NOT NULL,
            required tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY name (name)
        ) $charset_collate;";

        $values_table = $wpdb->prefix . self::TABLE_VALUES;
        $sql_values = "CREATE TABLE $values_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            council_id bigint(20) NOT NULL,
            field_id mediumint(9) NOT NULL,
            financial_year varchar(9) NOT NULL,
            value longtext NULL,
            financial_year varchar(9) NOT NULL DEFAULT '" . CDC_Utils::current_financial_year() . "',
            PRIMARY KEY  (id),
            KEY council_id (council_id),
            KEY field_id (field_id),
            KEY financial_year (financial_year)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_fields );
        dbDelta( $sql_values );

        self::ensure_default_fields();
        self::ensure_default_tabs();
        // Ensure legacy interest fields are merged even if the plugin is
        // updated without reactivation.
        self::migrate_interest_paid_field();
    }

    /**
     * Create tables if they do not exist.
     */
    public static function maybe_install() {
        global $wpdb;
        $fields_table = $wpdb->prefix . self::TABLE_FIELDS;
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $fields_table ) ) !== $fields_table ) {
            self::install();
        } else {
            $columns = $wpdb->get_col( 'DESC ' . $fields_table, 0 );
            if ( ! in_array( 'required', $columns, true ) ) {
                $wpdb->query( 'ALTER TABLE ' . $fields_table . ' ADD required tinyint(1) NOT NULL DEFAULT 0' );
            }
        }

        $values_table = $wpdb->prefix . self::TABLE_VALUES;
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $values_table ) ) === $values_table ) {
            $columns = $wpdb->get_col( 'DESC ' . $values_table, 0 );
            if ( ! in_array( 'financial_year', $columns, true ) ) {
                $wpdb->query( "ALTER TABLE $values_table ADD financial_year varchar(9) NOT NULL DEFAULT '" . CDC_Utils::current_financial_year() . "'" );
                $wpdb->query( "ALTER TABLE $values_table ADD KEY financial_year (financial_year)" );
            }
        }

        foreach ( self::IMMUTABLE_FIELDS as $name ) {
            $wpdb->update( $fields_table, [ 'required' => 1 ], [ 'name' => $name ], [ '%d' ], [ '%s' ] );
        }

        self::ensure_default_fields();
        self::ensure_default_tabs();
        self::migrate_interest_paid_field();
    }

    /**
     * Register custom meta keys for REST and sanitisation.
     */
    public static function register_meta_fields() {
        $fields = self::get_fields();
        foreach ( $fields as $field ) {
            $field = (array) $field;
            register_meta( 'post', $field['name'], [
                'object_subtype' => 'council',
                'type'           => in_array( $field['type'], [ 'number', 'money' ], true ) ? 'number' : 'string',
                'single'         => true,
                'show_in_rest'   => true,
                'sanitize_callback' => in_array( $field['type'], [ 'number', 'money' ], true ) ? 'floatval' : 'sanitize_text_field',
            ] );
        }
    }

    public static function admin_menu() {
        add_submenu_page(
            'council-debt-counters',
            __( 'Custom Fields', 'council-debt-counters' ),
            __( 'Custom Fields', 'council-debt-counters' ),
            'manage_options',
            self::PAGE_SLUG,
            [ __CLASS__, 'render_page' ]
        );
    }

    private static function ensure_default_fields() {
        foreach ( self::DEFAULT_FIELDS as $def ) {
            $existing = self::get_field_by_name( $def['name'] );
            if ( ! $existing ) {
                self::add_field( $def['name'], $def['label'], $def['type'], $def['required'] );
            } else {
                $update = [];
                if ( $existing->type !== $def['type'] ) {
                    $update['type'] = $def['type'];
                }
                if ( (int) $existing->required !== (int) $def['required'] ) {
                    $update['required'] = $def['required'];
                }
                if ( ! empty( $update ) ) {
                    self::update_field( (int) $existing->id, $update );
                }
            }
        }
    }

    private static function ensure_default_tabs() {
        $map     = get_option( 'cdc_field_tabs', array() );
        $changed = false;
        foreach ( self::DEFAULT_FIELD_TABS as $name => $tab ) {
            if ( ! isset( $map[ $name ] ) ) {
                $map[ $name ] = $tab;
                $changed      = true;
            }
        }
        if ( $changed ) {
            update_option( 'cdc_field_tabs', $map );
        }
    }

    public static function get_fields() {
        global $wpdb;
        return $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . self::TABLE_FIELDS . ' ORDER BY id' );
    }

    public static function get_field( int $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . self::TABLE_FIELDS . ' WHERE id = %d', $id ) );
    }

    public static function get_field_by_name( string $name ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . self::TABLE_FIELDS . ' WHERE name = %s', $name ) );
    }

    public static function add_field( string $name, string $label, string $type, int $required = 0 ) {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . self::TABLE_FIELDS, [
            'name'     => $name,
            'label'    => $label,
            'type'     => $type,
            'required' => $required,
        ], [ '%s', '%s', '%s', '%d' ] );
        self::set_field_tab( $name, self::DEFAULT_FIELD_TABS[ $name ] ?? 'general' );
    }

    public static function update_field( int $id, array $data ) {
        global $wpdb;
        $field = self::get_field( $id );
        if ( $field && in_array( $field->name, self::READONLY_FIELDS, true ) ) {
            return;
        }
        $allowed = [ 'name', 'label', 'type', 'required' ];
        $update  = [];
        $formats = [];
        foreach ( $allowed as $key ) {
            if ( isset( $data[ $key ] ) ) {
                $update[ $key ] = $data[ $key ];
                $formats[]      = ( 'required' === $key ) ? '%d' : '%s';
            }
        }
        if ( ! empty( $update ) ) {
            $wpdb->update( $wpdb->prefix . self::TABLE_FIELDS, $update, [ 'id' => $id ], $formats, [ '%d' ] );
        }
        if ( isset( $data['name'] ) && $data['name'] !== $field->name ) {
            self::rename_field_tab( $field->name, $data['name'] );
        }
    }

    public static function delete_field( int $id ) {
        global $wpdb;
        $field = $wpdb->get_row( $wpdb->prepare( 'SELECT name, required FROM ' . $wpdb->prefix . self::TABLE_FIELDS . ' WHERE id = %d', $id ) );
        if ( ! $field ) {
            return;
        }
        if ( $field->required || in_array( $field->name, self::IMMUTABLE_FIELDS, true ) || in_array( $field->name, self::READONLY_FIELDS, true ) ) {
            return;
        }
        $wpdb->delete( $wpdb->prefix . self::TABLE_FIELDS, [ 'id' => $id ], [ '%d' ] );
        $wpdb->delete( $wpdb->prefix . self::TABLE_VALUES, [ 'field_id' => $id ], [ '%d' ] );
        $map = get_option( 'cdc_field_tabs', array() );
        unset( $map[ $field->name ] );
        update_option( 'cdc_field_tabs', $map );
    }

    public static function get_field_tab( string $name ) : string {
        $map = get_option( 'cdc_field_tabs', array() );
        $tab = isset( $map[ $name ] ) ? sanitize_key( $map[ $name ] ) : '';
        if ( ! in_array( $tab, self::TAB_OPTIONS, true ) ) {
            $tab = self::DEFAULT_FIELD_TABS[ $name ] ?? 'general';
        }
        return $tab;
    }

    public static function set_field_tab( string $name, string $tab ) {
        if ( ! in_array( $tab, self::TAB_OPTIONS, true ) ) {
            $tab = 'general';
        }
        $map         = get_option( 'cdc_field_tabs', array() );
        $map[ $name ] = $tab;
        update_option( 'cdc_field_tabs', $map );
    }

    public static function rename_field_tab( string $old, string $new ) {
        $map = get_option( 'cdc_field_tabs', array() );
        if ( isset( $map[ $old ] ) ) {
            $map[ $new ] = $map[ $old ];
            unset( $map[ $old ] );
            update_option( 'cdc_field_tabs', $map );
        }
    }

    public static function get_value( int $council_id, string $name, string $financial_year = '' ) {
        if ( empty( $financial_year ) ) {
            $financial_year = CDC_Utils::current_financial_year();
        }
        $field = self::get_field_by_name( $name );
        if ( ! $field ) {
            return '';
        }
        global $wpdb;
        $raw = $wpdb->get_var( $wpdb->prepare( 'SELECT value FROM ' . $wpdb->prefix . self::TABLE_VALUES . ' WHERE council_id = %d AND field_id = %d AND financial_year = %s', $council_id, $field->id, $financial_year ) );
        return maybe_unserialize( $raw );
    }

    public static function update_value( int $council_id, string $name, $value, string $financial_year = '' ) {
        if ( empty( $financial_year ) ) {
            $financial_year = CDC_Utils::current_financial_year();
        }
        $field = self::get_field_by_name( $name );
        if ( ! $field ) {
            return false;
        }
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_VALUES;
        $existing = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . $table . ' WHERE council_id = %d AND field_id = %d AND financial_year = %s', $council_id, $field->id, $financial_year ) );
        if ( $existing ) {
            $wpdb->update( $table, [ 'value' => maybe_serialize( $value ) ], [ 'id' => $existing ], [ '%s' ], [ '%d' ] );
        } else {
            $wpdb->insert( $table, [
                'council_id' => $council_id,
                'field_id'   => $field->id,
                'financial_year' => $financial_year,
                'value'      => maybe_serialize( $value ),
            ], [ '%d', '%d', '%s', '%s' ] );
        }
        return true;
    }

    /**
     * Get the summed value of a field across all councils.
     */
    public static function get_total_value( string $name, string $financial_year = '' ) : float {
        if ( empty( $financial_year ) ) {
            $financial_year = CDC_Utils::current_financial_year();
        }
        $field = self::get_field_by_name( $name );
        if ( ! $field ) {
            return 0.0;
        }
        $posts = get_posts([
            'post_type'   => 'council',
            'numberposts' => -1,
            'fields'      => 'ids',
        ]);
        $total = 0.0;
        foreach ( $posts as $id ) {
            if ( get_post_meta( (int) $id, 'cdc_parent_council', true ) ) {
                continue;
            }
            $val = self::get_value( (int) $id, $name, $financial_year );
            if ( is_numeric( $val ) ) {
                $total += (float) $val;
            }
        }
        return $total;
    }

    /**
     * Migrate legacy ACF post meta values into the custom field tables.
     */
    public static function migrate_from_meta() {
        $posts = get_posts([
            'post_type'   => 'council',
            'numberposts' => -1,
        ]);

        foreach ( $posts as $post ) {
            $meta = get_post_meta( $post->ID );
            foreach ( $meta as $key => $values ) {
                if ( empty( $values ) || strpos( $key, '_' ) === 0 ) {
                    continue;
                }

                $value = maybe_unserialize( $values[0] );

                $field = self::get_field_by_name( $key );
                if ( ! $field ) {
                    $label = ucwords( str_replace( '_', ' ', $key ) );
                    $type  = is_numeric( $value ) ? 'number' : 'text';
                    self::add_field( $key, $label, $type );
                }

                self::update_value( $post->ID, $key, $value, CDC_Utils::current_financial_year() );
            }
        }
    }

    /**
     * Merge any legacy `interest_paid_on_debt` values into the current
     * `interest_paid` field. This runs during installation and on every
     * initialisation so older data is always moved to the new tab.
     */
    private static function migrate_interest_paid_field() {
        global $wpdb;
        $old = self::get_field_by_name( 'interest_paid_on_debt' );
        $new = self::get_field_by_name( 'interest_paid' );
        if ( ! $old ) {
            if ( $new ) {
                self::set_field_tab( 'interest_paid', 'interest' );
                self::update_field( (int) $new->id, [ 'label' => 'Interest Paid on Debt' ] );
            }
            return;
        }

        if ( ! $new ) {
            self::update_field( (int) $old->id, [ 'name' => 'interest_paid', 'label' => 'Interest Paid on Debt', 'required' => 0 ] );
            self::set_field_tab( 'interest_paid', 'interest' );
            return;
        }

        $values_table = $wpdb->prefix . self::TABLE_VALUES;
        $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT council_id, value FROM ' . $values_table . ' WHERE field_id = %d', $old->id ), ARRAY_A );
        foreach ( $rows as $row ) {
            $existing = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . $values_table . ' WHERE council_id = %d AND field_id = %d', $row['council_id'], $new->id ) );
            if ( $existing ) {
                $wpdb->update( $values_table, [ 'value' => $row['value'] ], [ 'id' => $existing ], [ '%s' ], [ '%d' ] );
            } else {
                $wpdb->insert( $values_table, [
                    'council_id' => $row['council_id'],
                    'field_id'   => $new->id,
                    'value'      => $row['value'],
                ], [ '%d', '%d', '%s' ] );
            }
        }

        $wpdb->delete( $values_table, [ 'field_id' => $old->id ], [ '%d' ] );
        $wpdb->delete( $wpdb->prefix . self::TABLE_FIELDS, [ 'id' => $old->id ], [ '%d' ] );
        self::set_field_tab( 'interest_paid', 'interest' );
        self::update_field( (int) $new->id, [ 'label' => 'Interest Paid on Debt' ] );
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( isset( $_GET['delete'] ) ) {
            $id = intval( $_GET['delete'] );
            check_admin_referer( 'cdc_delete_field_' . $id );
            self::delete_field( $id );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Field deleted.', 'council-debt-counters' ) . '</p></div>';
        }

        if ( isset( $_POST['cdc_add_field_nonce'] ) && wp_verify_nonce( $_POST['cdc_add_field_nonce'], 'cdc_add_field' ) ) {
            $name     = sanitize_key( $_POST['name'] );
            $label    = sanitize_text_field( $_POST['label'] );
            $type     = sanitize_key( $_POST['type'] );
            $required = isset( $_POST['required'] ) ? 1 : 0;
            $tab      = sanitize_key( $_POST['tab'] ?? 'general' );
            self::add_field( $name, $label, $type, $required );
            self::set_field_tab( $name, $tab );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Field added.', 'council-debt-counters' ) . '</p></div>';
        }

        if ( isset( $_POST['cdc_edit_field_nonce'] ) && wp_verify_nonce( $_POST['cdc_edit_field_nonce'], 'cdc_edit_field' ) ) {
            $id    = intval( $_POST['field_id'] );
            $field = self::get_field( $id );
            if ( $field ) {
                $data  = [ 'label' => sanitize_text_field( $_POST['label'] ) ];
                if ( ! in_array( $field->name, self::IMMUTABLE_FIELDS, true ) ) {
                    $data['name']     = sanitize_key( $_POST['name'] );
                    $data['type']     = sanitize_key( $_POST['type'] );
                    $data['required'] = isset( $_POST['required'] ) ? 1 : 0;
                }
                $tab = sanitize_key( $_POST['tab'] ?? self::get_field_tab( $field->name ) );
                self::update_field( $id, $data );
                self::set_field_tab( $data['name'] ?? $field->name, $tab );
                echo '<div class="notice notice-success"><p>' . esc_html__( 'Field updated.', 'council-debt-counters' ) . '</p></div>';
            }
        }

        $fields = self::get_fields();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Custom Fields', 'council-debt-counters' ); ?></h1>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Label', 'council-debt-counters' ); ?></th>
                        <th><?php esc_html_e( 'Name', 'council-debt-counters' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'council-debt-counters' ); ?></th>
                        <th><?php esc_html_e( 'Tab', 'council-debt-counters' ); ?></th>
                        <th><?php esc_html_e( 'Required', 'council-debt-counters' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'council-debt-counters' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $fields as $field ) : ?>
                    <?php $readonly = in_array( $field->name, self::READONLY_FIELDS, true ); ?>
                    <tr>
                        <td><?php echo esc_html( $field->label ); ?></td>
                        <td><?php echo esc_html( $field->name ); ?></td>
                        <td><?php echo esc_html( $field->type ); ?></td>
                        <td><?php echo esc_html( ucfirst( self::get_field_tab( $field->name ) ) ); ?></td>
                        <td><?php echo $field->required ? esc_html__( 'Yes', 'council-debt-counters' ) : esc_html__( 'No', 'council-debt-counters' ); ?></td>
                        <td>
                            <?php if ( ! $readonly ) : ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&edit=' . $field->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'council-debt-counters' ); ?></a>
                                <?php if ( ! $field->required && ! in_array( $field->name, self::IMMUTABLE_FIELDS, true ) ) : ?>
                                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&delete=' . $field->id ), 'cdc_delete_field_' . $field->id ) ); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Delete this field?', 'council-debt-counters' ); ?>');"><?php esc_html_e( 'Delete', 'council-debt-counters' ); ?></a>
                                <?php endif; ?>
                            <?php else : ?>
                                <?php esc_html_e( 'System', 'council-debt-counters' ); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ( isset( $_GET['edit'] ) ) : ?>
                <?php $edit_field = self::get_field( intval( $_GET['edit'] ) ); ?>
                <?php if ( $edit_field && ! in_array( $edit_field->name, self::READONLY_FIELDS, true ) ) : ?>
                <h2><?php esc_html_e( 'Edit Field', 'council-debt-counters' ); ?></h2>
                <form method="post">
                    <?php wp_nonce_field( 'cdc_edit_field', 'cdc_edit_field_nonce' ); ?>
                    <input type="hidden" name="field_id" value="<?php echo esc_attr( $edit_field->id ); ?>">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="cdc-edit-label"><?php esc_html_e( 'Label', 'council-debt-counters' ); ?></label></th>
                            <td><input name="label" id="cdc-edit-label" type="text" class="regular-text" value="<?php echo esc_attr( $edit_field->label ); ?>" required></td>
                        </tr>
                        <?php if ( ! in_array( $edit_field->name, self::IMMUTABLE_FIELDS, true ) ) : ?>
                        <tr>
                            <th scope="row"><label for="cdc-edit-name"><?php esc_html_e( 'Name', 'council-debt-counters' ); ?></label></th>
                            <td><input name="name" id="cdc-edit-name" type="text" class="regular-text" value="<?php echo esc_attr( $edit_field->name ); ?>" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cdc-edit-type"><?php esc_html_e( 'Type', 'council-debt-counters' ); ?></label></th>
                            <td>
                                <select name="type" id="cdc-edit-type">
                                    <option value="text" <?php selected( $edit_field->type, 'text' ); ?>><?php esc_html_e( 'Text', 'council-debt-counters' ); ?></option>
                                    <option value="number" <?php selected( $edit_field->type, 'number' ); ?>><?php esc_html_e( 'Number', 'council-debt-counters' ); ?></option>
                                    <option value="money" <?php selected( $edit_field->type, 'money' ); ?>><?php esc_html_e( 'Monetary', 'council-debt-counters' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cdc-edit-tab"><?php esc_html_e( 'Tab', 'council-debt-counters' ); ?></label></th>
                            <td>
                                <select name="tab" id="cdc-edit-tab">
                                    <?php foreach ( self::TAB_OPTIONS as $tab ) : ?>
                                        <option value="<?php echo esc_attr( $tab ); ?>" <?php selected( self::get_field_tab( $edit_field->name ), $tab ); ?>><?php echo esc_html( ucfirst( $tab ) ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cdc-edit-required"><?php esc_html_e( 'Required', 'council-debt-counters' ); ?></label></th>
                            <td><input type="checkbox" id="cdc-edit-required" name="required" value="1" <?php checked( $edit_field->required, 1 ); ?>></td>
                        </tr>
                        <?php else : ?>
                            <input type="hidden" name="name" value="<?php echo esc_attr( $edit_field->name ); ?>">
                            <input type="hidden" name="type" value="<?php echo esc_attr( $edit_field->type ); ?>">
                            <input type="hidden" name="required" value="1">
                        <?php endif; ?>
                    </table>
                    <?php submit_button( __( 'Save Field', 'council-debt-counters' ) ); ?>
                </form>
                <?php endif; ?>
            <?php endif; ?>
            <h2><?php esc_html_e( 'Add Field', 'council-debt-counters' ); ?></h2>
            <form method="post">
                <?php wp_nonce_field( 'cdc_add_field', 'cdc_add_field_nonce' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="cdc-field-label"><?php esc_html_e( 'Label', 'council-debt-counters' ); ?></label></th>
                        <td><input name="label" id="cdc-field-label" type="text" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cdc-field-name"><?php esc_html_e( 'Name', 'council-debt-counters' ); ?></label></th>
                        <td><input name="name" id="cdc-field-name" type="text" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cdc-field-type"><?php esc_html_e( 'Type', 'council-debt-counters' ); ?></label></th>
                        <td>
                            <select name="type" id="cdc-field-type">
                                <option value="text"><?php esc_html_e( 'Text', 'council-debt-counters' ); ?></option>
                                <option value="number"><?php esc_html_e( 'Number', 'council-debt-counters' ); ?></option>
                                <option value="money"><?php esc_html_e( 'Monetary', 'council-debt-counters' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cdc-field-tab"><?php esc_html_e( 'Tab', 'council-debt-counters' ); ?></label></th>
                        <td>
                            <select name="tab" id="cdc-field-tab">
                                <?php foreach ( self::TAB_OPTIONS as $tab ) : ?>
                                    <option value="<?php echo esc_attr( $tab ); ?>"><?php echo esc_html( ucfirst( $tab ) ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cdc-field-required"><?php esc_html_e( 'Required', 'council-debt-counters' ); ?></label></th>
                        <td><input type="checkbox" id="cdc-field-required" name="required" value="1"></td>
                    </tr>
                </table>
                <?php submit_button( __( 'Add Field', 'council-debt-counters' ) ); ?>
            </form>
        </div>
        <?php
    }
}
