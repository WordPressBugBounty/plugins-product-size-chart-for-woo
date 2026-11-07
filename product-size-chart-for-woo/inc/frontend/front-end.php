<?php

namespace PSCWF\Inc\Frontend;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy woo_sc_ filter names.

use PSCWF\Inc\Data;

defined( 'ABSPATH' ) || exit;

class Front_End {

	protected static $instance = null;
	protected $settings;
	/**
	 * Request-level chart match cache keyed by product id.
	 *
	 * @var array<int, array{0: string, 1: array<int>}>
	 */
	protected $size_chart_match_cache = array();

	private function __construct() {
		$this->settings = Data::get_instance();
		if ( ! $this->settings->get_params( 'enable' ) ) {
			return;
		}
		$this->handle_sc_button_position();
		$this->handle_sc_show_popup();
	}

	public static function instance() {
		return null === self::$instance ? self::$instance = new self() : self::$instance;
	}

	public function handle_sc_button_position() {
		switch ( $this->get_params( 'position' ) ) {
			case 'before_add_to_cart':
				add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'show_sc_button' ) );
				break;
			case 'after_add_to_cart':
				add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'show_sc_button' ) );
				break;
			case 'pop-up':
				add_action( 'wp_footer', array( $this, 'show_sc_button' ) );
				break;
			case 'product_tabs':
				add_filter( 'woocommerce_product_tabs', array( $this, 'custom_product_tabs' ) );
				break;
		}
	}

	public function pscw_helper_show_size_chart( $product_id, $update_option_editing = false ) {
		$product_id = absint( $product_id );
		if ( isset( $this->size_chart_match_cache[ $product_id ] ) ) {
			list( $product_sc_mode, $size_charts_allow ) = $this->size_chart_match_cache[ $product_id ];
			$this->maybe_update_size_charts_editing( $size_charts_allow, $update_option_editing );

			return array( $product_sc_mode, $size_charts_allow );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return array( 'disable', array() );
		}
		$size_charts_allow = array();
		$product_sc_mode   = $product->get_meta( 'pscw_mode' ) ?: 'global';
		if ( 'disable' === $product_sc_mode && ! is_customize_preview() ) {
			$this->size_chart_match_cache[ $product_id ] = array( $product_sc_mode, $size_charts_allow );

			return array( $product_sc_mode, $size_charts_allow );
		}
		if ( 'override' === $product_sc_mode ) {
			$product_sc_override = $product->get_meta( 'pscw_override' ) ?: array();
			$size_chart_id       = is_array( $product_sc_override ) ? array_map( 'absint', array_values( $product_sc_override ) ) : array();
		} else {
			$size_charts = get_posts(
				array(
					'post_type'   => 'pscw-size-chart',
					'post_status' => 'publish',
					'numberposts' => -1,
					'fields'      => 'ids',
				)
			);

			$product_cate = wc_get_product_cat_ids( $product_id );
			if ( ! is_array( $product_cate ) ) {
				$product_cate = array();
			}
			$product_cate  = array_map( 'absint', $product_cate );
			$size_chart_id = array();
			foreach ( $size_charts as $sc_id ) {
				$pscw_data = get_post_meta( $sc_id, 'pscw_data', true );
				if ( ! is_array( $pscw_data ) ) {
					continue;
				}
				if ( isset( $pscw_data['assign'] ) ) {
					$apply = self::size_chart_is_available_old( $product_id, $product_cate, $pscw_data['assign'] ?? '', $pscw_data['condition'] ?? array() );
				} else {
					$apply = self::size_chart_is_available( $product_id, $product_cate, $pscw_data );
				}
				if ( $apply ) {
					$size_chart_id[] = absint( $sc_id );
				}
			}
		}
		if ( is_array( $size_chart_id ) && ! empty( $size_chart_id ) ) {
			foreach ( $size_chart_id as $pscw_id ) {
				if ( ! $pscw_id ) {
					continue;
				}
				$pscw_data_ct = get_post_meta( $pscw_id, 'pscw_data', true );
				if ( ! is_array( $pscw_data_ct ) || empty( $pscw_data_ct ) ) {
					continue;
				}
				$size_charts_allow[] = $pscw_id;
			}
		}

		$this->size_chart_match_cache[ $product_id ] = array( $product_sc_mode, $size_charts_allow );
		$this->maybe_update_size_charts_editing( $size_charts_allow, $update_option_editing );

		return array( $product_sc_mode, $size_charts_allow );
	}

	/**
	 * Update customizer editing option/user meta when requested.
	 *
	 * @param array $size_charts_allow Matched chart ids.
	 * @param bool  $update_option_editing Whether this call asked for the editing update.
	 */
	protected function maybe_update_size_charts_editing( $size_charts_allow, $update_option_editing ) {
		if ( ! $update_option_editing || ! is_customize_preview() ) {
			return;
		}

		$user_id        = get_current_user_id();
		$sizechart_mode = get_user_meta( $user_id, 'pscw_sizechart_mode', true );
		$editing_sc     = absint( get_user_meta( $user_id, 'pscw_current_editing_sc', true ) );

		if ( $sizechart_mode && $editing_sc ) {
			$editing_list = ! empty( $size_charts_allow ) ? $size_charts_allow : array( $editing_sc );
			if ( ! in_array( $editing_sc, array_map( 'absint', (array) $editing_list ), true ) ) {
				array_unshift( $editing_list, $editing_sc );
			}
			update_option( 'pscw_size_charts_editing', $editing_list );
		} elseif ( is_array( $size_charts_allow ) && ! empty( $size_charts_allow ) ) {
			update_option( 'pscw_size_charts_editing', $size_charts_allow );
			if ( ! $editing_sc || ! in_array( $editing_sc, array_map( 'absint', $size_charts_allow ), true ) ) {
				update_user_meta( $user_id, 'pscw_current_editing_sc', $size_charts_allow[0] );
			}
		} else {
			update_option( 'pscw_size_charts_editing', '' );
			if ( ! $sizechart_mode ) {
				update_user_meta( $user_id, 'pscw_current_editing_sc', '' );
			}
		}
	}

	/**
	 * Whether a size chart applies to a product (include products/cats OR when both set).
	 *
	 * @param int   $product_id   Product ID.
	 * @param array $product_cate Product category IDs.
	 * @param array $pscw_data    Size chart assign meta.
	 * @return bool
	 */
	public static function size_chart_is_available( $product_id, $product_cate, $pscw_data ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return false;
		}
		if ( ! is_array( $pscw_data ) ) {
			return false;
		}
		if ( ! empty( $pscw_data['all_products'] ?? '1' ) ) {
			return true;
		}
		$has_products = ! empty( $pscw_data['include_products'] ) && is_array( $pscw_data['include_products'] );
		$has_cats     = ! empty( $pscw_data['include_product_cat'] ) && is_array( $pscw_data['include_product_cat'] );
		if ( ! $has_products && ! $has_cats ) {
			return false;
		}

		$product_id   = absint( $product_id );
		$include_ids  = $has_products ? array_map( 'absint', $pscw_data['include_products'] ) : array();
		$include_cats = $has_cats ? array_map( 'absint', $pscw_data['include_product_cat'] ) : array();

		// When both products and categories are set, match if either applies (union / OR).
		if ( $has_products && $has_cats ) {
			$in_products = in_array( $product_id, $include_ids, true );
			$in_cats     = ! empty( array_intersect( $product_cate, $include_cats ) );

			return $in_products || $in_cats;
		}
		if ( $has_products ) {
			return in_array( $product_id, $include_ids, true );
		}

		return ! empty( array_intersect( $product_cate, $include_cats ) );
	}

	public static function size_chart_is_available_old( $product_id, $product_cate, $assign, $condition ) {
		$apply   = false;
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return $apply;
		}
		if ( ! is_array( $condition ) ) {
			$condition = array();
		}
		switch ( $assign ) {
			case 'all':
				$apply = true;
				break;
			case 'product_cat':
				$apply = ! empty( array_intersect( $product_cate, array_map( 'absint', $condition ) ) );
				break;
			case 'products':
				$apply = in_array( absint( $product_id ), array_map( 'absint', $condition ), true );
				break;
		}

		return $apply;
	}

	public function handle_sc_show_popup() {
		$position = $this->get_params( 'position' );
		/* Exception for none position */
		$exception = $position === 'none' ?? false;
		if ( $exception || $position === 'before_add_to_cart' || $position === 'before_atc_after_variations' || $position === 'after_add_to_cart' || $position === 'pop-up' || $position === 'after_title' || $position === 'after_meta' ) {
			add_action( 'wp_footer', array( $this, 'pscw_size_chart_popup' ) );
		}
	}

	public function show_sc_button() {
		if ( ! is_customize_preview() ) {
			list( $pscw_mode, $size_chart_id ) = $this->pscw_helper_show_size_chart( get_the_ID() );
			if ( 'disable' === $pscw_mode || empty( $size_chart_id ) ) {
				return;
			}
		}
		if ( ! is_product() ) {
			return;
		}
		?>
        <div class="woo_sc_frontend_btn">
            <div id="woo_sc_show_popup"
                 class="woo_sc_price_btn_popup woo_sc_btn_popup woo_sc_btn_span woo_sc_call_popup">
                <div class="woo_sc_text_icon">
					<?php
					$size_chart_name = ! empty( $this->get_params( 'woo_sc_name' ) ) ? $this->get_params( 'woo_sc_name' ) : esc_html__( 'Size Chart', 'product-size-chart-for-woo' );
					?>
                    <span class="woo_sc_text"><?php echo esc_html( $size_chart_name ); ?></span>
                </div>
            </div>
        </div>
		<?php
	}

	public function custom_product_tabs( $tabs ) {
		global $product;
		if ( ! empty( $this->get_params( 'woo_sc_name' ) ) ) {
			$title = $this->get_params( 'woo_sc_name' );
		} else {
			$title = esc_html__( 'Size Chart', 'product-size-chart-for-woo' );
		}
		list( $product_sc_mode, $size_charts_allow_countries ) = $this->pscw_helper_show_size_chart( $product->get_id(), true );
		if ( is_customize_preview() ) {
			if ( 'product_tabs' === $this->get_params( 'position' ) ) {
				$tabs['size_chart_tab'] = array(
					'title'    => esc_html( $title ),
					'priority' => apply_filters( 'woo_sc_size_chart_tab_priority', 50 ),
				);
			}
		} else {
			if ( ( ! empty( $size_charts_allow_countries ) && 'disable' !== $product_sc_mode ) ) {
				if ( 'product_tabs' === $this->get_params( 'position' ) ) {
					$tabs['size_chart_tab'] = array(
						'title'    => esc_html( $title ),
						'priority' => apply_filters( 'woo_sc_size_chart_tab_priority', 50 ),
						'callback' => function () use ( $size_charts_allow_countries ) {
							$this->custom_product_tabs_content( $size_charts_allow_countries );
						},
					);
				}
			}
		}

		return $tabs;
	}

	public function custom_product_tabs_content( $size_chart_id ) {
		if ( is_customize_preview() ) {
			return;
		}
		$is_multiple = $this->get_params( 'multi_sc' );
		if ( $is_multiple ) {
			foreach ( $size_chart_id as $sc_id ) {
				echo do_shortcode( '[PSCW_SIZE_CHART id=' . absint( $sc_id ) . ']' );
			}
		} else {
			echo do_shortcode( '[PSCW_SIZE_CHART id=' . absint( $size_chart_id[0] ) . ']' );
		}
	}

	public function pscw_size_chart_popup() {
		$product_id = get_the_ID();
		list( $product_sc_mode, $size_chart_id ) = $this->pscw_helper_show_size_chart( $product_id, true );
		if ( ! is_customize_preview() ) {
			$position = $this->get_params( 'position' );
			if ( 'none' === $position ) {
				return;
			}

			if ( ! ( ( ! empty( $size_chart_id ) && 'disable' !== $product_sc_mode ) ) ) {
				return;
			}
		}
		if ( ! is_product() ) {
			return;
		}

		?>
        <div id="woo_sc_modal" class="woo_sc_modal">
            <div class="woo_sc_modal_content">
                <span class="woo_sc_modal_close">&times;</span>
                <div class="woo_sc_scroll_content">
					<?php
					if ( ! is_customize_preview() ) {
						if ( isset( $size_chart_id[0] ) ) {
							echo do_shortcode( '[PSCW_SIZE_CHART id=' . absint( $size_chart_id[0] ) . ']' );
						}
					}
					?>
                </div>
            </div>
        </div>
		<?php
	}

	public function get_params( $param ) {
		return $this->settings->get_params( $param );
	}

}
