<?php

namespace PSCWF\Inc;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy woo_sc_ filter names.

defined( 'ABSPATH' ) || exit;

class Enqueue {
	protected static $instance = null;
	protected static $setting;
	protected $slug;
	protected $suffix = '';

	protected $lib_styles = array(
		'button',
		'tab',
		'input',
		'segment',
		'image',
		'modal',
		'dimmer',
		'transition',
		'menu',
		'grid',
		'search',
		'message',
		'loader',
		'label',
		'select2',
		'header',
		'accordion',
		'dropdown',
		'checkbox',
		'form',
		'icon',
		'step',
	);

	protected $lib_scripts = array(
		'tab',
		'form',
		'checkbox',
		'dropdown',
		'transition',
		'select2',
	);

	private function __construct() {
		$this->slug    = PSCW_CONST_F['assets_slug'];
		self::$setting = Data::get_instance();
		$this->suffix  = WP_DEBUG ? '' : '.min';
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'client_enqueue_scripts' ) );
	}

	public static function instance() {
		return null === self::$instance ? self::$instance = new self() : self::$instance;
	}

	public function register_common_scripts() {
		$lib_styles = $this->lib_styles;

		foreach ( $lib_styles as $style ) {
			wp_register_style( $this->slug . $style, PSCW_CONST_F['libs_url'] . $style . '.min.css', '', PSCW_CONST_F['version'] );
		}

		$lib_scripts = $this->lib_scripts;

		foreach ( $lib_scripts as $script => $depend ) {
			if ( is_array( $depend ) ) {
				wp_register_script( $this->slug . $script, PSCW_CONST_F['libs_url'] . $script . '.min.js', $depend, PSCW_CONST_F['version'], false );
			} else {
				wp_register_script( $this->slug . $depend, PSCW_CONST_F['libs_url'] . $depend . '.min.js', array( 'jquery' ), PSCW_CONST_F['version'], false );
			}
		}
	}

	public function register_admin_scripts() {
		$admin_styles = array( 'settings', 'size-chart', 'product', 'setup-wizard' );

		foreach ( $admin_styles as $style ) {
			wp_register_style( $this->slug . $style, PSCW_CONST_F['css_url'] . $style . $this->suffix . '.css', '', PSCW_CONST_F['version'] );
		}

		$admin_scripts = array(
			'settings'       => array( 'jquery', 'wp-color-picker' ),
			'size-chart'     => array( 'jquery' ),
			'all-size-chart' => array( 'jquery' ),
			'product'        => array( 'jquery' ),
			'setup-wizard'   => array( 'jquery' ),
		);

		foreach ( $admin_scripts as $script => $depend ) {
			wp_register_script( $this->slug . $script, PSCW_CONST_F['js_url'] . $script . $this->suffix . '.js', $depend, PSCW_CONST_F['version'], true );
		}
	}

	public function admin_enqueue_scripts() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return;
		}
		$screen_id = $screen->id;

		$this->register_common_scripts();
		$this->register_admin_scripts();

		$enqueue_scripts = $enqueue_styles = array();
		switch ( $screen_id ) {
			case 'pscw-size-chart_page_pscw-size-chart-setting':
				$enqueue_styles  = array(
					'icon',
					'segment',
					'button',
					'menu',
					'form',
					'dropdown',
					'checkbox',
					'transition',
					'label',
					'input',
					'settings',
				);
				$enqueue_scripts = array(
					'tab',
					'checkbox',
					'form',
					'dropdown',
					'transition',
					'settings',
				);
				wp_enqueue_style( 'wp-color-picker' );
				break;
			case 'pscw-size-chart':
				$enqueue_styles  = array(
					'form',
					'icon',
					'checkbox',
					'transition',
					'segment',
					'menu',
					'select2',
					'size-chart',
				);
				$enqueue_scripts = array(
					'form',
					'checkbox',
					'transition',
					'select2',
					'size-chart',
				);
				break;
			case 'edit-pscw-size-chart':
				wp_register_style( 'pscw-dummy-handle', false, array(), PSCW_CONST_F['version'], false );
				wp_enqueue_style( 'pscw-dummy-handle' );
				wp_add_inline_style( 'pscw-dummy-handle', 'table.wp-list-table tr td .woo_sc_short_code {cursor: pointer;}.icon.input .woo_sc_copied {background-color: black;color: #fff;width: 120px;text-align: center;border-radius: 6px;padding: 5px 0;list-style: none;position: absolute;z-index: 9;top: 100%;left: 50%;margin-left: -60px;opacity: 0.7;visibility: hidden;} table.wp-list-table tr td .vi-ui.input{min-width:269px}' );
				$enqueue_styles  = array(
					'input',
					'icon',
					'size-chart',
				);
				$enqueue_scripts = array(
					'all-size-chart',
				);
				break;
			case 'product':
				$enqueue_styles  = array(
					'select2',
					'product',
				);
				$enqueue_scripts = array(
					'select2',
					'product',
				);
				break;
			case 'dashboard_page_pscw-setup':
				$enqueue_styles  = array( 'button', 'header', 'transition', 'dropdown', 'segment', 'step', 'checkbox', 'setup-wizard' );
				$enqueue_scripts = array( 'dropdown', 'transition', 'checkbox', 'setup-wizard' );
				break;
		}

		foreach ( $enqueue_styles as $style ) {
			wp_enqueue_style( $this->slug . $style );
		}

		foreach ( $enqueue_scripts as $script ) {
			wp_enqueue_script( $this->slug . $script );
		}

		$params = array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'pscw_nonce' ),
		);
		wp_localize_script( $this->slug . 'size-chart', 'VicPscwParams', $params );
		wp_localize_script( $this->slug . 'product', 'VicPscwParams', $params );
		wp_localize_script( $this->slug . 'setup-wizard', 'VicPscwParams', $params );
		wp_localize_script( $this->slug . 'all-size-chart', 'VicPscwParams', $params );
	}

	public function register_client_scripts() {
		$client_styles = array( 'frontend' );

		foreach ( $client_styles as $style ) {
			wp_register_style( $this->slug . $style, PSCW_CONST_F['css_url'] . $style . $this->suffix . '.css', '', PSCW_CONST_F['version'] );
		}

		$scripts = array( 'frontend' => array( 'jquery' ) );

		foreach ( $scripts as $script => $depend ) {
			wp_register_script( $this->slug . $script, PSCW_CONST_F['js_url'] . $script . $this->suffix . '.js', $depend, PSCW_CONST_F['version'], true );
		}
	}

	public function client_enqueue_scripts() {
		$this->register_client_scripts();
		$enqueue_styles  = array( 'frontend' );
		$enqueue_scripts = array( 'frontend' );
		$inline_style    = 'frontend';
		$css             = '';

		if ( is_product() && $this->get_prams( 'enable' ) ) {

			foreach ( $enqueue_styles as $style ) {
				wp_enqueue_style( $this->slug . $style );
			}

			foreach ( $enqueue_scripts as $script ) {
				wp_enqueue_script( $this->slug . $script );
			}

			$button_css  = '';
			$button_icon = '';
			$position    = pscw_sanitize_position( $this->get_prams( 'position' ) );
			if ( 'pop-up' === $position ) {
				$button_css = '.woo_sc_price_btn_popup.woo_sc_btn_popup {
				  position: fixed;
				  z-index: 999998;
				}';
			}

			$btn_horizontal = $this->get_prams( 'btn_horizontal' );
			$btn_horizontal = in_array( $btn_horizontal, array( 'left', 'right' ), true ) ? $btn_horizontal : 'right';
			$btn_vertical   = absint( $this->get_prams( 'btn_vertical' ) );
			$btn_color      = pscw_sanitize_css_color( $this->get_prams( 'btn_color' ), '#2185d0' );
			$text_color     = pscw_sanitize_css_color( $this->get_prams( 'text_color' ), '#ffffff' );

			$css .= '#woo_sc_show_popup{top:' . esc_attr( (string) $btn_vertical ) . '%;' . esc_attr( $btn_horizontal ) . ':0%}
			    .woo_sc_data_content{clear:both; margin:15px auto;}
				' . $button_icon . '
				.woo_sc_call_popup{background-color:' . esc_attr( $btn_color ) . ';color:' . esc_attr( $text_color ) . '
				}' . $button_css . '.woo_sc_size_icon svg {vertical-align: unset;fill: ' . esc_attr( $text_color ) . '}';

			$css .= self::$setting->get_params( 'custom_css' );
			$css  = apply_filters( 'woo_sc_filter_style', $css );
			wp_add_inline_style( $this->slug . $inline_style, esc_html( $css ) );

			wp_localize_script(
				$this->slug . 'frontend',
				'ViPscwFontParams',
				array(
					'isCustomize'       => is_customize_preview(),
					'pscwSizeChartMode' => get_user_meta( get_current_user_id(), 'pscw_sizechart_mode', true ),
				)
			);
		}
	}

	public function get_prams( $param ) {
		return self::$setting->get_params( $param );
	}
}
