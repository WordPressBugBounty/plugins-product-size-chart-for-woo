<?php

namespace PSCWF\Inc;

defined( 'ABSPATH' ) || exit;

class Short_Code {
	protected static $instance = null;
	protected $suffix = '';
	protected $setting;

	private function __construct() {
		$this->setting = Data::get_instance();
		$this->suffix  = WP_DEBUG ? '' : '.min';
		if ( $this->setting->get_params( 'enable' ) === '1' ) {
			add_shortcode( 'PSCW_SIZE_CHART', array( $this, 'short_code_content' ) );
		}
	}

	public static function instance() {
		return null === self::$instance ? self::$instance = new self() : self::$instance;
	}

	/**
	 * Allowed HTML for shortcode output.
	 *
	 * @return array
	 */
	protected function get_allowed_html() {
		$allowed_html = wp_kses_allowed_html( 'post' );

		if ( is_customize_preview() ) {
			$allowed_html['input'] = array(
				'class'       => true,
				'id'          => true,
				'name'        => true,
				'value'       => true,
				'type'        => true,
				'placeholder' => true,
				'style'       => true,
			);
		}

		return $allowed_html;
	}

	public function short_code_content( $atts ) {
		$atts = shortcode_atts( array( 'id' => '', 'signature' => '' ), $atts );
		if ( ! $atts['id'] ) {
			return '';
		}

		$chart_id = absint( $atts['id'] );
		$chart    = $chart_id ? get_post( $chart_id ) : null;
		if ( ! $chart || 'pscw-size-chart' !== $chart->post_type ) {
			return '';
		}
		if ( 'publish' !== $chart->post_status && ! current_user_can( 'edit_post', $chart_id ) ) {
			return '';
		}

		/* To determine size chart from customize */
		$signature = isset( $atts['signature'] ) ? sanitize_html_class( $atts['signature'] ) : '';

		wp_enqueue_style( 'pscw-shortcode', PSCW_CONST_F['css_url'] . 'shortcode' . $this->suffix . '.css', array(), PSCW_CONST_F['version'] );

		$interface = get_post_meta( $chart_id, 'pscw_interface', true );
		if ( empty( $interface ) || empty( $interface['elementsById'] ) || empty( $interface['layout'] ) ) {
			return '';
		}

		if ( ! is_customize_preview() ) {
			wp_add_inline_style( 'pscw-shortcode', $this->pscw_generate_style( $interface['elementsById'] ) );
		}
		$layout = $interface['layout'];
		$html   = '';
		if ( ! empty( $layout['children'] ) && is_array( $layout['children'] ) ) {
			foreach ( $layout['children'] as $child_id ) {
				$html .= $this->pscw_render_interface( $child_id, $interface['elementsById'] );
			}
		}
		ob_start();
		if ( is_customize_preview() ) {
			printf(
				'<div class="pscw-container pscw-customizing %s" id="pscw-container">',
				esc_attr( $signature )
			);
		} else {
			printf(
				'<div class="pscw-container" id="%s">',
				esc_attr( 'pscw-container-' . $chart_id )
			);
		}
		echo wp_kses( $html, $this->get_allowed_html() );
		echo is_customize_preview() ? '<ul id="pscw-preview-table-menu">
                <li data-action="add-row-below">' . esc_html__( 'Add row below', 'product-size-chart-for-woo' ) . '</li>
                <li data-action="add-col-right">' . esc_html__( 'Add col right', 'product-size-chart-for-woo' ) . '</li>
                <li data-action="delete-col">' . esc_html__( 'Delete col', 'product-size-chart-for-woo' ) . '</li>
                <li data-action="delete-row">' . esc_html__( 'Delete row', 'product-size-chart-for-woo' ) . '</li>
            </ul>' : '';
		echo '</div>';

		return ob_get_clean();
	}

	public function pscw_render_interface( $element_id, $elements_by_id ) {
		if ( ! isset( $elements_by_id[ $element_id ] ) ) {
			return '';
		}
		$element = $elements_by_id[ $element_id ];
		$html    = '';
		switch ( $element['type'] ) {
			case 'row':
				$html .= '<div class="pscw-row" id="' . esc_attr( $element['id'] ) . '">';
				foreach ( $element['children'] as $child_id ) {
					$html .= $this->pscw_render_interface( $child_id, $elements_by_id );
				}
				$html .= '</div>';
				break;

			case 'column':
				$html .= '<div class="pscw-cols ' . esc_attr( $element['class'] ) . '" id="' . esc_attr( $element['id'] ) . '">';
				foreach ( $element['children'] as $child_id ) {
					$html .= $this->pscw_render_interface( $child_id, $elements_by_id );
				}
				$html .= '</div>';
				break;

			case 'table':
				$html .= '<div class="woo_sc_table_scroll woo_sc_table100" id="' . esc_attr( $element['id'] ) . '">';
				$html .= '<div class="woo_sc_table100-body">';
				$html .= '<div class="woo_sc_view_table">';
				$html .= '<table class="woo_sc_view_table">';
				$html .= '<thead class="woo_sc_table100-head "><tr class="woo_sc_row100 head">';
				foreach ( $element['columns'] as $column ) {
					if ( is_customize_preview() ) {
						$html .= '<th class="woo_sc_cell100"><input type="text" placeholder="..." style="width: ' . esc_attr( ( strlen( $column ) === 0 ? 3 : strlen( $column ) + 1 ) ) . 'ch;" value="' . esc_attr( $column ) . '"></th>';
					} else {
						$html .= '<th class="woo_sc_cell100">' . esc_html( $column ) . '</th>';
					}
				}
				$html .= '</tr></thead>';
				$html .= '<tbody>';
				foreach ( $element['rows'] as $row ) {
					$html .= '<tr>';
					$i    = 0;
					foreach ( $row as $cell ) {
						if ( is_customize_preview() ) {
							$html .= '<td class="' . esc_attr( ( 0 === $i ) ? 'pscw-first-child' : '' ) . '"><input type="text" placeholder="..." style="width: ' . esc_attr( strlen( $cell ) === 0 ? 3 : strlen( $cell ) + 1 ) . 'ch;" value="' . esc_attr( $cell ) . '"></td>';
						} else {
							$html .= '<td>' . esc_html( $cell ) . '</td>';
						}
						$i++;
					}
					$html .= '</tr>';
				}
				$html .= '</tbody>';
				$html .= '</table>';
				$html .= '</div>';
				$html .= '</div>';
				$html .= $this->pscw_render_element_editing();
				$html .= '</div>';
				break;

			case 'text':
				$html .= '<div class="pscw-text-editor-container" id="' . esc_attr( $element['id'] ) . '">';
				$html .= '<div class="pscw-text-editor">' . wp_kses_post( $element['value'] ) . '</div>';
				$html .= $this->pscw_render_element_editing();
				$html .= '</div>';
				break;

			case 'image':
				$html .= '<div class="pscw-image-container" id="' . esc_attr( $element['id'] ) . '">';
				$html .= '<img src="' . esc_url( $element['src'] ) . '" alt="' . esc_attr( $element['alt'] ) . '">';
				$html .= $this->pscw_render_element_editing();
				$html .= '</div>';
				break;
		}

		return $html;
	}

	public function pscw_render_element_editing() {
		return is_customize_preview() ? '<span class="pscw-customizing-edit">&#9998;</span>' : '';
	}

	public function pscw_generate_style( $elements ) {
		$css = array();
		foreach ( $elements as $element ) {
			if ( empty( $element['type'] ) || empty( $element['id'] ) ) {
				continue;
			}
			$id = sanitize_html_class( (string) $element['id'] );
			if ( '' === $id ) {
				continue;
			}
			$margin     = pscw_sanitize_spacing_array( $element['margin'] ?? array( 0, 0, 0, 0 ) );
			$margin_css = implode(
				' ',
				array_map(
					static function ( $item ) {
						return pscw_sanitize_css_number( $item ) . 'px';
					},
					$margin
				)
			);

			switch ( $element['type'] ) {
				case 'table':
					$header_text_bold = ! empty( $element['headerTextBold'] ) ? 'bold' : '400';
					$header_bg        = pscw_sanitize_css_color( $element['headerBackground'] ?? '#ffffff', '#ffffff' );
					$text_header      = pscw_sanitize_css_color( $element['textHeader'] ?? '#000000' );
					$header_text_size = pscw_sanitize_css_number( $element['headerTextSize'] ?? 14, 14 );
					$even_bg          = pscw_sanitize_css_color( $element['evenBackground'] ?? '#ffffff', '#ffffff' );
					$even_text        = pscw_sanitize_css_color( $element['evenText'] ?? '#494949', '#494949' );
					$odd_bg           = pscw_sanitize_css_color( $element['oddBackground'] ?? '#ffffff', '#ffffff' );
					$odd_text         = pscw_sanitize_css_color( $element['oddText'] ?? '#494949', '#494949' );
					$cell_text_size   = pscw_sanitize_css_number( $element['cellTextSize'] ?? 14, 14 );
					$border_color     = pscw_sanitize_css_color( $element['borderColor'] ?? '#9D9D9D', '#9D9D9D' );
					$h_border_w       = pscw_sanitize_css_number( $element['horizontalBorderWidth'] ?? 1, 1 );
					$v_border_w       = pscw_sanitize_css_number( $element['verticalBorderWidth'] ?? 1, 1 );
					$h_border_s       = pscw_sanitize_border_style( $element['horizontalBorderStyle'] ?? 'solid' );
					$v_border_s       = pscw_sanitize_border_style( $element['verticalBorderStyle'] ?? 'solid' );
					$columns_style    = ! empty( $element['columnsStyle'] );

					$css[] = '#' . $id . ' table.woo_sc_view_table th.woo_sc_cell100 {background: ' . $header_bg . ';color: ' . $text_header . ';font-weight: ' . $header_text_bold . ';font-size: ' . $header_text_size . 'px}';
					if ( $columns_style ) {
						$css[] = '#' . $id . ' table.woo_sc_view_table tbody tr td:nth-child(even) {background: ' . $even_bg . ';color: ' . $even_text . '}';
						$css[] = '#' . $id . ' table.woo_sc_view_table tbody tr td:nth-child(odd) {background: ' . $odd_bg . ';color: ' . $odd_text . '}';
					} else {
						$css[] = '#' . $id . ' table.woo_sc_view_table tbody tr:nth-child(odd) td {background: ' . $odd_bg . ';color: ' . $odd_text . '}';
						$css[] = '#' . $id . ' table.woo_sc_view_table tbody tr:nth-child(even) td {background: ' . $even_bg . ';color: ' . $even_text . '}';
					}

					$css[] = '#' . $id . ' table.woo_sc_view_table th.woo_sc_cell100, #' . $id . ' .woo_sc_view_table tbody td {border-color: ' . $border_color . '; border-width: ' . $h_border_w . 'px ' . $v_border_w . 'px; border-style: ' . $h_border_s . ' ' . $v_border_s . ';}';
					$css[] = '#' . $id . ' table.woo_sc_view_table tbody td {font-size: ' . $cell_text_size . 'px}';

					$radius     = pscw_sanitize_spacing_array( $element['borderRadius'] ?? array( 0, 0, 0, 0 ) );
					$radius_css = array_map(
						static function ( $item ) {
							return pscw_sanitize_css_number( $item ) . 'px';
						},
						$radius
					);
					$css[]      = '#' . $id . ' {margin: ' . $margin_css . '; border-radius: ' . implode( ' ', $radius_css ) . ';}';

					if ( is_rtl() ) {
						$css[] = '#' . $id . ' table.woo_sc_view_table thead tr th:first-child{ border-top-right-radius: ' . $radius_css[0] . '}';
						$css[] = '#' . $id . ' table.woo_sc_view_table thead tr th:last-child{ border-top-left-radius: ' . $radius_css[1] . '}';
						$css[] = '#' . $id . ' table.woo_sc_view_table tbody tr:last-child td:first-child{ border-bottom-right-radius: ' . $radius_css[2] . '}';
						$css[] = '#' . $id . ' table.woo_sc_view_table tbody tr:last-child td:last-child{ border-bottom-left-radius: ' . $radius_css[3] . ' }';
					} else {
						$css[] = '#' . $id . ' table.woo_sc_view_table thead tr th:first-child{ border-top-left-radius: ' . $radius_css[0] . '}';
						$css[] = '#' . $id . ' table.woo_sc_view_table thead tr th:last-child{ border-top-right-radius: ' . $radius_css[1] . '}';
						$css[] = '#' . $id . ' table.woo_sc_view_table tbody tr:last-child td:first-child{ border-bottom-left-radius: ' . $radius_css[2] . '}';
						$css[] = '#' . $id . ' table.woo_sc_view_table tbody tr:last-child td:last-child{ border-bottom-right-radius: ' . $radius_css[3] . ' }';
					}
					$table_max_height = pscw_sanitize_css_number( $element['tableMaxHeight'] ?? 535, 535 );
					$css[]            = '#' . $id . ' .woo_sc_table100-body {max-height: ' . $table_max_height . 'px;}';
					break;
				case 'image':
					$padding     = pscw_sanitize_spacing_array( $element['padding'] ?? array( 0, 0, 0, 0 ) );
					$padding_css = implode(
						' ',
						array_map(
							static function ( $item ) {
								return pscw_sanitize_css_number( $item ) . 'px';
							},
							$padding
						)
					);
					$width        = pscw_sanitize_css_number( $element['width'] ?? 100, 100 );
					$width_unit   = pscw_sanitize_css_unit( $element['widthUnit'] ?? '%', '%' );
					$height       = pscw_sanitize_css_number( $element['height'] ?? 100, 100 );
					$height_unit  = pscw_sanitize_css_unit( $element['heightUnit'] ?? '%', '%' );
					$border_width = pscw_sanitize_css_number( $element['borderWidth'] ?? 0 );
					$border_color = pscw_sanitize_css_color( $element['borderColor'] ?? '#000000' );
					$border_style = pscw_sanitize_border_style( $element['borderStyle'] ?? 'solid' );
					$object_fit   = pscw_sanitize_object_fit( $element['objectFit'] ?? 'unset' );
					$css[]        = '#' . $id . ' img {width: ' . $width . $width_unit . '; height: ' . $height . $height_unit . '; border-width: ' . $border_width . 'px; border-color: ' . $border_color . '; border-style: ' . $border_style . '}';
					$css[]        = '#' . $id . ' {padding: ' . $padding_css . '; margin: ' . $margin_css . ';}';
					$css[]        = '#' . $id . ' img {object-fit: ' . $object_fit . ';}';
					break;
				case 'text':
					$css[] = '#' . $id . ' {margin: ' . $margin_css . ';}';
					break;
			}
		}

		return implode( '', $css );
	}
}
