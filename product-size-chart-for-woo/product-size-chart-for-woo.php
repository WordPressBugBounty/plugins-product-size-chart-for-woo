<?php
/**
 * Plugin Name: Product Size Chart for WooCommerce
 * Plugin URI: https://villatheme.com/extensions/woo-product-size-chart/
 * Description: WooCommerce Size Chart lets customize and design size charts for specific products or categories, enhancing customer convenience and boosting sales.
 * Version: 1.2.6
 * Author URI: http://villatheme.com
 * Author: VillaTheme
 * Copyright 2021-2024 VillaTheme.com. All rights reserved.
 * Text Domain: product-size-chart-for-woo
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires Plugins: woocommerce
 * Tested up to: 6.5
 * WC requires at least: 7.0
 * WC tested up to: 8.9
 * Requires PHP: 7.0
 **/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PSCW_SIZE_CHART_VERSION', '1.2.6' );
define( 'PSCW_SIZE_CHART_MINIUM_WP_VERSION', '5.0' );
define( 'PSCW_SIZE_CHART_PLUGIN_FILE', __FILE__ );
define( 'PSCW_SIZE_CHART_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PSCW_SIZE_CHART_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PSCW_SIZE_CHART_LANGUAGES', PSCW_SIZE_CHART_PLUGIN_DIR . "languages" . DIRECTORY_SEPARATOR );

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if ( ! class_exists( 'PSCW_PRODUCT_SIZE_CHART_F_WOO' ) ) {
	class PSCW_PRODUCT_SIZE_CHART_F_WOO {
		public function __construct() {
			register_activation_hook( __FILE__, array( $this, 'install' ) );

			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'settings_link' ) );

			//compatible with 'High-Performance order storage (COT)'
			add_action( 'before_woocommerce_init', array( $this, 'before_woocommerce_init' ) );
		}

		function plugins_loaded() {
			if ( ! class_exists( 'VillaTheme_Require_Environment' ) ) {
				include_once PSCW_SIZE_CHART_PLUGIN_DIR . 'includes/support.php';
			}

			$environment = new \VillaTheme_Require_Environment( [
					'plugin_name'     => 'Product Size Chart for WooCommerce',
					'php_version'     => '7.0',
					'wp_version'      => '5.0',
					'wc_version'      => '7.0',
					'require_plugins' => [
						[
							'slug' => 'woocommerce',
							'name' => 'WooCommerce',
						],
					]
				]
			);

			if ( $environment->has_error() ) {
				return;
			}

			require_once ( PSCW_SIZE_CHART_PLUGIN_DIR . 'includes/define.php' );
        }
		public function before_woocommerce_init() {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			}
		}

		public function settings_link( $links ) {
			array_unshift( $links, '<a href="' . admin_url( 'edit.php?post_type=pscw-size-chart&page=pscw-size-chart-setting' ) . '">' .esc_html__( 'Settings', 'product-size-chart-for-woo' ) . '</a>' );
			return $links;
		}

		public function install() {
			$data_init = array(
				'enable'         => '1',
				'position'       => 'product_tabs',
				'woo_sc_name'    => '',
				'btn_horizontal' => 'right',
				'btn_vertical'   => '50',
				'multi_sc'       => '0',
				'button_type'    => 'text',
				'btn_color'      => '#2185d0',
				'text_color'     => '#ffffff',
				'custom_css'     => ''

			);
			if ( ! get_option( 'woo_sc_setting', '' ) ) {
				update_option( 'woo_sc_setting', $data_init );
			}
			$this->default_template();
		}

		/*default template*/
		public function default_template() {
			$default_template_option = get_option( 'woo_sc_template' );
			if ( empty( $default_template_option ) ) {
				$default_template_posts     = array(
					'mens_tshirt'   => esc_html__( "Men's T-shirts Size Chart", 'product-size-chart-for-woo' ),
					'womens_tshirt' => esc_html__( "Women's T-shirts Size Chart", 'product-size-chart-for-woo' ),
					'womens_dress'  => esc_html__( "Women's Dress Size Chart", 'product-size-chart-for-woo' ),
					'mens_shoes'    => esc_html__( "Men's Shoes Size Chart", 'product-size-chart-for-woo' ),
					'womens_shoes'  => esc_html__( "Women's Shoes Size Chart", 'product-size-chart-for-woo' )
				);
				$user_id                    = get_current_user_id();
				$default_template_posts_ids = array();
				foreach ( $default_template_posts as $default_template_posts_key => $default_template_posts_value ) {
					$template_content             = $this->template_html_content( $default_template_posts_key );
					$post_args                    = array(
						'post_author'  => $user_id,
						'post_content' => $template_content,
						'post_type'    => 'pscw-size-chart',
						'post_status'  => 'publish',
						'post_title'   => $default_template_posts_value,
					);
					$post_id                      = wp_insert_post( $post_args );
					$default_template_posts_ids[] = $post_id;
					if ( 0 !== $post_id ) {
						$this->default_template_table_post_meta( $post_id, $default_template_posts_key );
					}
				}
				update_option( 'woo_sc_template', 'default_template' );
			}
		}

		public function template_html_content( $template ) {
			ob_start();
			?>
            <div class="woo_sc_template_content">
				<?php esc_html_e( 'Measure your body as follows to choose the size that suits you best:', 'product-size-chart-for-woo' );
				switch ( $template ) {
					case "mens_tshirt":
						?>
                        <div>
                            <strong><?php esc_html_e( 'Chest : ', 'product-size-chart-for-woo' ); ?></strong>
                            <div>
								<?php esc_html_e( 'Measure around the fullest part, place the tape close under the arms and make sure the tape is flat across the back.', 'product-size-chart-for-woo' ); ?>
                            </div>
                        </div>
						<?php
						break;
					case "womens_tshirt":
						?>
                        <div>
                            <strong><?php esc_html_e( 'Chest: ', 'product-size-chart-for-woo' ); ?></strong>
                            <div>
								<?php esc_html_e( 'Measure under your arms, around the fullest part of the your chest.', 'product-size-chart-for-woo' ); ?>
                            </div>

                            <strong><?php esc_html_e( 'Waist: ', 'product-size-chart-for-woo' ); ?></strong>
                            <div>
								<?php esc_html_e( 'Measure around your natural waistline, keeping the tape a bit loose.', 'product-size-chart-for-woo' ); ?>
                            </div>
                        </div>
						<?php
						break;
					case "womens_dress":
						?>
                        <div>
                            <strong><?php esc_html_e( 'Chest: ', 'product-size-chart-for-woo' ); ?></strong>
                            <div>
								<?php esc_html_e( 'Measure under your arms, around the fullest part of the your chest.', 'product-size-chart-for-woo' ); ?>
                            </div>
                            <strong><?php esc_html_e( 'Waist: ', 'product-size-chart-for-woo' ); ?></strong>
                            <div>
								<?php esc_html_e( 'Measure around your natural waistline, keeping the tape a bit loose.', 'product-size-chart-for-woo' ); ?>
                            </div>
                            <strong><?php esc_html_e( 'Hips : ', 'product-size-chart-for-woo' ); ?></strong>
                            <div>
								<?php esc_html_e( 'Measure around the fullest part of your body at the top of your leg.', 'product-size-chart-for-woo' ); ?>
                            </div>
                        </div>
						<?php
						break;
					case "mens_shoes":
						?>
                        <div>
							<?php esc_html_e( 'Measure your foot length carefully.', 'product-size-chart-for-woo' ); ?>
							<?php esc_html_e( 'Please choose the correct size according to your foot length and chinese size.', 'product-size-chart-for-woo' ); ?>
							<?php esc_html_e( 'For example, if your foot length is 25.5 cm, you should choose the size 8.', 'product-size-chart-for-woo' ); ?>
                        </div>
						<?php
						break;
					case "womens_shoes":
						?>
                        <div>
							<?php esc_html_e( 'Measure your foot length carefully.', 'product-size-chart-for-woo' ); ?>
							<?php esc_html_e( 'Please choose the correct size according to your foot length', 'product-size-chart-for-woo' ); ?>
                        </div>
						<?php
						break;
				}
				?>
            </div>
			<?php
			return ob_get_clean();
		}

		public function default_template_table_post_meta( $post_id, $template ) {
			$meta_box_data = [
				'categories'              => "",
				'img_link'                => "",
				'template'                => "",
				'head_color'              => "#2185d0",
				'text_head_color'         => "#ffffff",
				'even_rows_color'         => "#cceafc",
				'even_rows_text_color'    => "#000000",
				'odd_rows_color'          => "#ffffff",
				'odd_rows_text_color'     => "#000000",
				'horizontal_width'        => "1",
				'vertical_width'          => "1",
				'horizontal_border_style' => "solid",
				'vertical_border_style'   => "solid",
				'border_color'            => "#cccccc",
				'table_array'             => "",
				'search_product'          => "",
				'hide'                    => "none",
				'img_width'               => ""
			];
			switch ( $template ) {
				case 'mens_tshirt':
					$meta_box_data['table_array'] = '[["SIZE","INCHES","CM"],["XXXS","30-32","76-81"],["XXS","32-34","81-86"],["XS","34-36","86-91"],["S","36-38","91-96"],["M","38-40","96-101"],["L","40-42","101-106"],["XL","42-44","106-111"],["XXL","44-46","111-116"],["XXXL","46-48","116-121"]]';
					break;
				case 'womens_tshirt':
					$meta_box_data['table_array'] = '[["UK SIZE","BUST","BUST","WAIST","WAIST","HIPS","HIPS"],["","INCHES","CM","INCHES","CM","INCHES","CM"],["4","31","78","24","60","33","83.5"],["6","32","80.5","25","62.5","34","86"],["8","33","83","26","65","35","88.5"],["10","35","88","28","70","37","93.5"],["12","37","93","30","75","39","98.5"],["14","39","98","31","80","41","103.5"],["16","41","103","33","85","43","08.5"],["18","44","110.5","36","92.5","46","116"]]';
					break;
				case 'womens_dress':
					$meta_box_data['table_array'] = '[["UK SIZE","BUST","BUST","WAIST","WAIST","HIPS","HIPS"],["","INCHES","CM","INCHES","CM","INCHES","CM"],["4","31","78","24","60","33","83.5"],["6","32","80.5","25","62.5","34","86"],["8","33","83","26","65","35","88.5"],["10","35","88","28","70","37","93.5"],["12","37","93","30","75","39","98.5"],["14","39","98","31","80","41","103.5"],["16","41","103","33","85","43","108.5"],["18","44","110.5","36","92.5","46","116"]]';
					break;
				case 'mens_shoes':
					$meta_box_data['table_array'] = '[["Heel To Toe (CM)","23","23.5","24","24.5","25","25.5","26","26.5","27","27.5","28","28.5","29","29.5","30"],["US","5","5.5","6","6.5","7","8","8.5","9.5","10","11","12","12.5","13","14","15"],["Chinese Size","36","37","38","39","40","41","42","43","44","45","46","47","48","49","50"]]';
					break;
				case 'womens_shoes':
					$meta_box_data['table_array'] = '[["Heel To Toe (CM)","22","22.5","23","23.5","24","24.5","25","25.5","26","26.5","27","27.5","28"],["US","4.5","5","6","6.5","7.5","8.5","9","9.5","10","10.5","12","13","14"],["EU","34","35","36","37","38","39","40","41","42","43","44","45","46"]]';
					break;
			}
			update_post_meta( $post_id, 'woo_sc_size_chart_data', $meta_box_data );
		}
	}

	new PSCW_PRODUCT_SIZE_CHART_F_WOO();
}
