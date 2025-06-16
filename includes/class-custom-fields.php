<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Custom_Fields {
    const TABLE_FIELDS = 'cdc_fields';
    const TABLE_VALUES = 'cdc_field_values';
    const PAGE_SLUG = 'cdc-custom-fields';

    const DEFAULT_FIELDS = [
        ['name' => 'council_name', 'label' => 'Council Name', 'type' => 'text', 'required' => 1],
        ['name' => 'council_type', 'label' => 'Council Type', 'type' => 'text', 'required' => 0],
        ['name' => 'population', 'label' => 'Population', 'type' => 'number', 'required' => 0],
        ['name' => 'households', 'label' => 'Households', 'type' => 'number', 'required' => 0],
        ['name' => 'current_liabilities', 'label' => 'Current Liabilities', 'type' => 'money', 'required' => 1],
        ['name' => 'long_term_liabilities', 'label' => 'Long-Term Liabilities', 'type' => 'money', 'required' => 1],
        ['name' => 'finance_lease_pfi_liabilities', 'label' => 'PFI or Finance Lease Liabilities', 'type' => 'money', 'required' => 1],
        ['name' => 'interest_paid_on_debt', 'label' => 'Interest Paid on Debt', 'type' => 'money', 'required' => 1],
        ['name' => 'minimum_revenue_provision', 'label' => 'Minimum Revenue Provision (Debt Repayment)', 'type' => 'money', 'required' => 1],
        ['name' => 'total_debt', 'label' => 'Total Debt', 'type' => 'money', 'required' => 0],
        ['name' => 'manual_debt_entry', 'label' => 'Manual Debt Entry', 'type' => 'money', 'required' => 0],
    ];

    /**
     * Fields that must always exist and cannot be removed.
     * The field type and name for these are locked.
     */
    const IMMUTABLE_FIELDS = [
        'council_name',
        'current_liabilities',
        'long_term_liabilities',
    ];

    public static function init() {
        // Ensure this submenu appears after the main menu is registered.
        add_action( 'admin_menu', [ __CLASS__, 'admin_menu' ], 11 );
        // Verify tables exist in case the plugin was updated without reactivation.
        add_action( 'init', [ __CLASS__, 'maybe_install' ] );
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
            value longtext NULL,
            PRIMARY KEY  (id),
            KEY council_id (council_id),
            KEY field_id (field_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_fields );
        dbDelta( $sql_values );

        self::ensure_default_fields();
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
            $columns = $wpdb->get_col( "DESC $fields_table", 0 );
            if ( ! in_array( 'required', $columns, true ) ) {
                $wpdb->query( "ALTER TABLE $fields_table ADD required tinyint(1) NOT NULL DEFAULT 0" );
            }
        }

        foreach ( self::IMMUTABLE_FIELDS as $name ) {
            $wpdb->update( $fields_table, [ 'required' => 1 ], [ 'name' => $name ], [ '%d' ], [ '%s' ] );
        }

        self::ensure_default_fields();
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
            if ( ! self::get_field_by_name( $def['name'] ) ) {
                self::add_field( $def['name'], $def['label'], $def['type'], $def['required'] );
            }
        }
    }

    public static function get_fields() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}" . self::TABLE_FIELDS . " ORDER BY id" );
    }

    public static function get_field( int $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::TABLE_FIELDS . " WHERE id = %d", $id ) );
    }

    public static function get_field_by_name( string $name ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::TABLE_FIELDS . " WHERE name = %s", $name ) );
    }

    public static function add_field( string $name, string $label, string $type, int $required = 0 ) {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . self::TABLE_FIELDS, [
            'name'     => $name,
            'label'    => $label,
            'type'     => $type,
            'required' => $required,
        ], [ '%s', '%s', '%s', '%d' ] );
    }

    public static function update_field( int $id, array $data ) {
        global $wpdb;
        $allowed = [ 'name', 'label', 'type', 'required' ];
        $update  = [];
        $formats = [];
        foreach ( $allowed as $key ) {
            if ( isset( $data[ $key ] ) ) {
                $update[ $key ] = $data[ $key ];
                $formats[]      = ( $key === 'required' ) ? '%d' : '%s';
            }
        }
        if ( ! empty( $update ) ) {
            $wpdb->update( $wpdb->prefix . self::TABLE_FIELDS, $update, [ 'id' => $id ], $formats, [ '%d' ] );
        }
    }

    public static function delete_field( int $id ) {
        global $wpdb;
        $field = $wpdb->get_row( $wpdb->prepare( "SELECT name, required FROM {$wpdb->prefix}" . self::TABLE_FIELDS . " WHERE id = %d", $id ) );
        if ( ! $field ) {
            return;
        }
        if ( $field->required || in_array( $field->name, self::IMMUTABLE_FIELDS, true ) ) {
            return;
        }
        $wpdb->delete( $wpdb->prefix . self::TABLE_FIELDS, [ 'id' => $id ], [ '%d' ] );
        $wpdb->delete( $wpdb->prefix . self::TABLE_VALUES, [ 'field_id' => $id ], [ '%d' ] );
    }

    public static function get_value( int $council_id, string $name ) {
        $field = self::get_field_by_name( $name );
        if ( ! $field ) {
            return '';
        }
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( "SELECT value FROM {$wpdb->prefix}" . self::TABLE_VALUES . " WHERE council_id = %d AND field_id = %d", $council_id, $field->id ) );
    }

    public static function update_value( int $council_id, string $name, $value ) {
        $field = self::get_field_by_name( $name );
        if ( ! $field ) {
            return false;
        }
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_VALUES;
        $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE council_id = %d AND field_id = %d", $council_id, $field->id ) );
        if ( $existing ) {
            $wpdb->update( $table, [ 'value' => maybe_serialize( $value ) ], [ 'id' => $existing ], [ '%s' ], [ '%d' ] );
        } else {
            $wpdb->insert( $table, [
                'council_id' => $council_id,
                'field_id'   => $field->id,
                'value'      => maybe_serialize( $value ),
            ], [ '%d', '%d', '%s' ] );
        }
        return true;
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

                self::update_value( $post->ID, $key, $value );
            }
        }
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
            self::add_field( $name, $label, $type, $required );
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
                self::update_field( $id, $data );
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
                        <th><?php esc_html_e( 'Required', 'council-debt-counters' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'council-debt-counters' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $fields as $field ) : ?>
                    <tr>
                        <td><?php echo esc_html( $field->label ); ?></td>
                        <td><?php echo esc_html( $field->name ); ?></td>
                        <td><?php echo esc_html( $field->type ); ?></td>
                        <td><?php echo $field->required ? esc_html__( 'Yes', 'council-debt-counters' ) : esc_html__( 'No', 'council-debt-counters' ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&edit=' . $field->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'council-debt-counters' ); ?></a>
                            <?php if ( ! $field->required && ! in_array( $field->name, self::IMMUTABLE_FIELDS, true ) ) : ?>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&delete=' . $field->id ), 'cdc_delete_field_' . $field->id ) ); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Delete this field?', 'council-debt-counters' ); ?>');"><?php esc_html_e( 'Delete', 'council-debt-counters' ); ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ( isset( $_GET['edit'] ) ) : ?>
                <?php $edit_field = self::get_field( intval( $_GET['edit'] ) ); ?>
                <?php if ( $edit_field ) : ?>
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
