<?php
namespace CouncilDebtCounters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Custom_Fields {
    const TABLE_FIELDS = 'cdc_fields';
    const TABLE_VALUES = 'cdc_field_values';
    const PAGE_SLUG = 'cdc-custom-fields';

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
    }

    /**
     * Create tables if they do not exist.
     */
    public static function maybe_install() {
        global $wpdb;
        $fields_table = $wpdb->prefix . self::TABLE_FIELDS;
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $fields_table ) ) !== $fields_table ) {
            self::install();
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

    public static function get_fields() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}" . self::TABLE_FIELDS . " ORDER BY id" );
    }

    public static function get_field_by_name( string $name ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . self::TABLE_FIELDS . " WHERE name = %s", $name ) );
    }

    public static function add_field( string $name, string $label, string $type ) {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . self::TABLE_FIELDS, [
            'name'  => $name,
            'label' => $label,
            'type'  => $type,
        ], [ '%s', '%s', '%s' ] );
    }

    public static function delete_field( int $id ) {
        global $wpdb;
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
            $name  = sanitize_key( $_POST['name'] );
            $label = sanitize_text_field( $_POST['label'] );
            $type  = sanitize_key( $_POST['type'] );
            self::add_field( $name, $label, $type );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Field added.', 'council-debt-counters' ) . '</p></div>';
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
                        <th><?php esc_html_e( 'Actions', 'council-debt-counters' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $fields as $field ) : ?>
                    <tr>
                        <td><?php echo esc_html( $field->label ); ?></td>
                        <td><?php echo esc_html( $field->name ); ?></td>
                        <td><?php echo esc_html( $field->type ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&delete=' . $field->id ), 'cdc_delete_field_' . $field->id ) ); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Delete this field?', 'council-debt-counters' ); ?>');"><?php esc_html_e( 'Delete', 'council-debt-counters' ); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
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
                </table>
                <?php submit_button( __( 'Add Field', 'council-debt-counters' ) ); ?>
            </form>
        </div>
        <?php
    }
}
