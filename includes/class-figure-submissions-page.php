<?php
namespace CouncilDebtCounters;

use CouncilDebtCounters\Figure_Submission_Form;
use CouncilDebtCounters\Custom_Fields;
use CouncilDebtCounters\Moderation_Log;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Figure_Submissions_Page {
	const SLUG = 'cdc-figure-submissions';

        public static function init() {
                add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
                add_action( 'admin_post_cdc_fig_approve', array( __CLASS__, 'handle_approve' ) );
                add_action( 'admin_post_cdc_fig_reject', array( __CLASS__, 'handle_reject' ) );
                add_action( 'admin_post_cdc_fig_review', array( __CLASS__, 'handle_review' ) );
                add_action( 'admin_post_cdc_fig_reapply', array( __CLASS__, 'handle_reapply' ) );
        }

	public static function add_menu() {
		add_submenu_page(
			'council-debt-counters',
			__( 'Figure Submissions', 'council-debt-counters' ),
			__( 'Figure Submissions', 'council-debt-counters' ),
			'manage_options',
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}

	public static function handle_approve() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'council-debt-counters' ) );
		}
		check_admin_referer( 'cdc_fig_action' );
		$id   = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
		$post = get_post( $id );
		if ( ! $post || Figure_Submission_Form::CPT !== $post->post_type ) {
				wp_die( esc_html__( 'Submission not found.', 'council-debt-counters' ) );
		}
                $cid     = (int) get_post_meta( $id, 'council_id', true );
                $figures = get_post_meta( $id, 'figures', true );
                if ( is_array( $figures ) && 0 !== $cid ) {
                        $year = get_post_meta( $id, 'financial_year', true );
                        if ( '' === $year ) {
                                $year = CDC_Utils::current_financial_year();
                        }
                        foreach ( $figures as $key => $val ) {
                                        Custom_Fields::update_value( $cid, $key, $val, $year );
                        }
			wp_update_post(
				array(
					'ID'          => $id,
					'post_status' => 'publish',
				)
			);
		}
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG ) );
		exit;
	}

	public static function handle_reject() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'council-debt-counters' ) );
		}
		check_admin_referer( 'cdc_fig_action' );
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
		if ( get_post_type( $id ) === Figure_Submission_Form::CPT ) {
			wp_trash_post( $id );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG ) );
			exit;
	}

        public static function handle_review() {
                if ( ! current_user_can( 'manage_options' ) ) {
                                wp_die( esc_html__( 'Permission denied.', 'council-debt-counters' ) );
                }
                        check_admin_referer( 'cdc_fig_review' );
			$id   = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
			$post = get_post( $id );
		if ( ! $post || Figure_Submission_Form::CPT !== $post->post_type ) {
				wp_die( esc_html__( 'Submission not found.', 'council-debt-counters' ) );
		}
			$cid     = (int) get_post_meta( $id, 'council_id', true );
			$figures = (array) get_post_meta( $id, 'figures', true );
			$choices = $_POST['use'] ?? array();
			$changed = array();
                if ( $cid ) {
                        $year = get_post_meta( $id, 'financial_year', true );
                        if ( '' === $year ) {
                                $year = CDC_Utils::current_financial_year();
                        }
                        foreach ( $figures as $key => $val ) {
                                if ( isset( $choices[ $key ] ) && 'submitted' === $choices[ $key ] ) {
                                        Custom_Fields::update_value( $cid, $key, $val, $year );
                                        $changed[] = $key;
                                }
                        }
				wp_update_post(
					array(
						'ID'          => $id,
						'post_status' => 'publish',
					)
				);
		}
			$user = wp_get_current_user();
			Moderation_Log::log_action( sprintf( 'Submission %d reviewed by %s (%d). Fields changed: %s', $id, $user->user_login, $user->ID, implode( ',', $changed ) ) );
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG ) );
                        exit;
        }

        /**
         * Reapply the figures from a submission to the associated council.
         */
        public static function handle_reapply() {
                if ( ! current_user_can( 'manage_options' ) ) {
                        wp_die( esc_html__( 'Permission denied.', 'council-debt-counters' ) );
                }
                check_admin_referer( 'cdc_fig_reapply' );

                $id   = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
                $post = get_post( $id );
                if ( ! $post || Figure_Submission_Form::CPT !== $post->post_type ) {
                        wp_die( esc_html__( 'Submission not found.', 'council-debt-counters' ) );
                }

                $cid     = (int) get_post_meta( $id, 'council_id', true );
                $figures = (array) get_post_meta( $id, 'figures', true );
                if ( $cid && ! empty( $figures ) ) {
                        $year = get_post_meta( $id, 'financial_year', true );
                        if ( '' === $year ) {
                                $year = CDC_Utils::current_financial_year();
                        }

                        foreach ( $figures as $key => $val ) {
                                Custom_Fields::update_value( $cid, $key, $val, $year );
                                delete_post_meta( $cid, 'cdc_na_' . $key );
                                $tab = Custom_Fields::get_field_tab( $key );
                                delete_post_meta( $cid, 'cdc_na_tab_' . $tab );
                        }
                        foreach ( (array) get_option( 'cdc_enabled_counters', array() ) as $tab_key ) {
                                delete_post_meta( $cid, 'cdc_na_tab_' . $tab_key );
                        }
                        wp_update_post( [ 'ID' => $cid, 'post_status' => 'publish' ] );
                        delete_post_meta( $cid, 'cdc_under_review' );
                }

                wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&submission=' . $id . '&reapplied=1' ) );
                exit;
        }

        public static function render() {
                        $sub_id = isset( $_GET['submission'] ) ? intval( $_GET['submission'] ) : 0;
            $filter_ip = isset( $_GET['ip'] ) ? sanitize_text_field( wp_unslash( $_GET['ip'] ) ) : '';
            if ( $sub_id ) {
                                self::render_detail( $sub_id );
                                return;
                }

                        $query_args = array(
                                'post_type'   => Figure_Submission_Form::CPT,
                                'numberposts' => -1,
                                'post_status' => array( 'private', 'publish' ),
                        );
            if ( $filter_ip ) {
                $query_args['meta_query'] = array(
                        array(
                                'key'   => 'ip_address',
                                'value' => $filter_ip,
                        ),
                );
            }
                        $subs = get_posts( $query_args );
		?>
                <div class="wrap">
                        <h1><?php esc_html_e( 'Figure Submissions', 'council-debt-counters' ); ?></h1>
            <?php if ( $filter_ip ) : ?>
                <p><?php printf( esc_html__( 'Filtering submissions from IP %s', 'council-debt-counters' ), esc_html( $filter_ip ) ); ?></p>
            <?php endif; ?>
                <?php if ( empty( $subs ) ) : ?>
				<p><?php esc_html_e( 'No submissions found.', 'council-debt-counters' ); ?></p>
			<?php else : ?>
			<table class="widefat fixed striped">
				<thead>
                                        <tr>
                                                <th><?php esc_html_e( 'Date', 'council-debt-counters' ); ?></th>
                                                <th><?php esc_html_e( 'Council', 'council-debt-counters' ); ?></th>
                                                <th><?php esc_html_e( 'Year', 'council-debt-counters' ); ?></th>
                                                <th><?php esc_html_e( 'Figures', 'council-debt-counters' ); ?></th>
                                                <th><?php esc_html_e( 'Actions', 'council-debt-counters' ); ?></th>
                                        </tr>
				</thead>
				<tbody>
                                        <?php foreach ( $subs as $s ) : ?>
                                        <?php $cid  = (int) get_post_meta( $s->ID, 'council_id', true ); ?>
                                        <?php $figs = get_post_meta( $s->ID, 'figures', true ); ?>
                                        <?php $yr   = get_post_meta( $s->ID, 'financial_year', true ); ?>
                                        <tr>
                                                <td><?php echo esc_html( get_the_date( '', $s ) ); ?></td>
                                                <td><?php echo $cid ? esc_html( get_the_title( $cid ) ) : ''; ?></td>
                                                <td><?php echo esc_html( $yr ); ?></td>
                                                <td>
                                                        <?php
                                                        if ( is_array( $figs ) ) {
								foreach ( $figs as $k => $v ) {
									echo '<div>' . esc_html( $k . ': ' . $v ) . '</div>';
								}
							}
							?>
						</td>
                                                <td>
                                                       <?php $auto = get_post_meta( $s->ID, 'auto_approved', true ); ?>
                                                       <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG . '&submission=' . $s->ID ) ); ?>" class="button me-1"><?php esc_html_e( 'View', 'council-debt-counters' ); ?></a>
                                                       <?php if ( $auto ) : ?>
                                                               <?php esc_html_e( 'Auto Approved', 'council-debt-counters' ); ?>
                                                       <?php elseif ( 'publish' !== $s->post_status ) : ?>
                                                               <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cdc_fig_approve&id=' . $s->ID ), 'cdc_fig_action' ) ); ?>" class="button me-1"><?php esc_html_e( 'Approve', 'council-debt-counters' ); ?></a>
                                                               <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cdc_fig_reject&id=' . $s->ID ), 'cdc_fig_action' ) ); ?>" class="button"><?php esc_html_e( 'Reject', 'council-debt-counters' ); ?></a>
                                                       <?php else : ?>
                                                               <?php esc_html_e( 'Approved', 'council-debt-counters' ); ?>
                                                       <?php endif; ?>
                                               </td>
                                       </tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
				<?php
	}

        private static function render_detail( int $id ) {
                $post = get_post( $id );
                if ( ! $post || Figure_Submission_Form::CPT !== $post->post_type ) {
                        echo '<div class="wrap"><p>' . esc_html__( 'Submission not found.', 'council-debt-counters' ) . '</p></div>';
                        return;
                }

                $cid     = (int) get_post_meta( $id, 'council_id', true );
                $figures = (array) get_post_meta( $id, 'figures', true );
                $sources = (array) get_post_meta( $id, 'sources', true );
                $ip      = get_post_meta( $id, 'ip_address', true );
                $yr      = get_post_meta( $id, 'financial_year', true );
                ?>
                <div class="wrap">
                        <h1><?php esc_html_e( 'Submission Details', 'council-debt-counters' ); ?></h1>
                        <?php if ( isset( $_GET['reapplied'] ) ) : ?>
                                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Figures reapplied successfully.', 'council-debt-counters' ); ?></p></div>
                        <?php endif; ?>
                        <p><strong><?php esc_html_e( 'Council:', 'council-debt-counters' ); ?></strong> <?php echo $cid ? esc_html( get_the_title( $cid ) ) : esc_html__( 'Unknown', 'council-debt-counters' ); ?></p>
                        <p><strong><?php esc_html_e( 'IP Address:', 'council-debt-counters' ); ?></strong> <?php echo esc_html( $ip ); ?></p>
                        <p><strong><?php esc_html_e( 'Financial Year:', 'council-debt-counters' ); ?></strong> <?php echo esc_html( $yr ); ?></p>

                        <?php if ( 'publish' !== $post->post_status ) : ?>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                        <input type="hidden" name="action" value="cdc_fig_review" />
                                        <input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>" />
                                        <?php wp_nonce_field( 'cdc_fig_review' ); ?>
                                        <table class="widefat striped">
                                                <thead>
                                                        <tr>
                                                                <th><?php esc_html_e( 'Field', 'council-debt-counters' ); ?></th>
                                                                <th><?php esc_html_e( 'Existing', 'council-debt-counters' ); ?></th>
                                                                <th><?php esc_html_e( 'Submitted', 'council-debt-counters' ); ?></th>
                                                                <th><?php esc_html_e( 'Use', 'council-debt-counters' ); ?></th>
                                                        </tr>
                                                </thead>
                                                <tbody>
                                                        <?php foreach ( $figures as $key => $val ) :
                                                                $label   = $key;
                                                                $field   = Custom_Fields::get_field_by_name( $key );
                                                                if ( $field ) {
                                                                        $label = $field->label;
                                                                }
                                                                $current = $cid ? Custom_Fields::get_value( $cid, $key, $yr ?: CDC_Utils::current_financial_year() ) : '';
                                                                $source  = $sources[ $key ] ?? '';
                                                                ?>
                                                                <tr>
                                                                        <td><?php echo esc_html( $label ); ?></td>
                                                                        <td><code class="text-danger">- <?php echo esc_html( $current ); ?></code></td>
                                                                        <td><code class="text-success">+ <?php echo esc_html( $val ); ?></code><?php if ( $source ) : ?><br><small><?php echo esc_html( $source ); ?></small><?php endif; ?></td>
                                                                        <td>
                                                                                <label><input type="radio" name="use[<?php echo esc_attr( $key ); ?>]" value="existing" checked /> <?php esc_html_e( 'Original', 'council-debt-counters' ); ?></label><br>
                                                                                <label><input type="radio" name="use[<?php echo esc_attr( $key ); ?>]" value="submitted" /> <?php esc_html_e( 'Submitted', 'council-debt-counters' ); ?></label>
                                                                        </td>
                                                                </tr>
                                                        <?php endforeach; ?>
                                                </tbody>
                                        </table>
                                        <p>
                                                <button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'council-debt-counters' ); ?></button>
                                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'council-debt-counters' ); ?></a>
                                        </p>
                                </form>
                        <?php else : ?>
                                <table class="widefat striped">
                                        <thead>
                                                <tr>
                                                        <th><?php esc_html_e( 'Field', 'council-debt-counters' ); ?></th>
                                                        <th><?php esc_html_e( 'Submitted Value', 'council-debt-counters' ); ?></th>
                                                </tr>
                                        </thead>
                                        <tbody>
                                                <?php foreach ( $figures as $key => $val ) :
                                                        $label = $key;
                                                        $field = Custom_Fields::get_field_by_name( $key );
                                                        if ( $field ) {
                                                                $label = $field->label;
                                                        }
                                                        $source = $sources[ $key ] ?? '';
                                                        ?>
                                                        <tr>
                                                                <td><?php echo esc_html( $label ); ?></td>
                                                                <td><?php echo esc_html( $val ); ?><?php if ( $source ) : ?><br><small><?php echo esc_html( $source ); ?></small><?php endif; ?></td>
                                                        </tr>
                                                <?php endforeach; ?>
                                        </tbody>
                                </table>
                                <p>
                                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cdc_fig_reapply&id=' . $id ), 'cdc_fig_reapply' ) ); ?>" class="button button-primary me-2"><?php esc_html_e( 'Re-apply Figures', 'council-debt-counters' ); ?></a>
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ); ?>" class="button"><?php esc_html_e( 'Back', 'council-debt-counters' ); ?></a>
                                </p>
                        <?php endif; ?>
                </div>
                <?php
        }
}
