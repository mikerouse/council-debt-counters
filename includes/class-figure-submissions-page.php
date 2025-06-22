<?php
namespace CouncilDebtCounters;

use CouncilDebtCounters\Figure_Submission_Form;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Figure_Submissions_Page {
	const SLUG = 'cdc-figure-submissions';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_post_cdc_fig_approve', array( __CLASS__, 'handle_approve' ) );
		add_action( 'admin_post_cdc_fig_reject', array( __CLASS__, 'handle_reject' ) );
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
			foreach ( $figures as $key => $val ) {
					Custom_Fields::update_value( $cid, $key, $val );
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

	public static function render() {
		$subs = get_posts(
			array(
				'post_type'   => Figure_Submission_Form::CPT,
				'numberposts' => -1,
				'post_status' => array( 'private', 'publish' ),
			)
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Figure Submissions', 'council-debt-counters' ); ?></h1>
			<?php if ( empty( $subs ) ) : ?>
				<p><?php esc_html_e( 'No submissions found.', 'council-debt-counters' ); ?></p>
			<?php else : ?>
			<table class="widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'council-debt-counters' ); ?></th>
						<th><?php esc_html_e( 'Council', 'council-debt-counters' ); ?></th>
						<th><?php esc_html_e( 'Figures', 'council-debt-counters' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'council-debt-counters' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $subs as $s ) : ?>
					<?php $cid = (int) get_post_meta( $s->ID, 'council_id', true ); ?>
					<?php $figs = get_post_meta( $s->ID, 'figures', true ); ?>
					<tr>
						<td><?php echo esc_html( get_the_date( '', $s ) ); ?></td>
						<td><?php echo $cid ? esc_html( get_the_title( $cid ) ) : ''; ?></td>
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
							<?php if ( 'publish' !== $s->post_status ) : ?>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cdc_fig_approve&id=' . $s->ID ), 'cdc_fig_action' ) ); ?>" class="button"><?php esc_html_e( 'Approve', 'council-debt-counters' ); ?></a>
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
}
