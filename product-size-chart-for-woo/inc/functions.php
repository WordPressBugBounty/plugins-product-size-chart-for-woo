<?php
/**
 * Shared helpers for Product Size Chart for Woo.
 *
 * @package PSCWF
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'pscw_get_allowed_positions' ) ) {
	/**
	 * Allowed size-chart display positions.
	 *
	 * @return string[]
	 */
	function pscw_get_allowed_positions() {
		return array(
			'before_add_to_cart',
			'before_atc_after_variations',
			'after_add_to_cart',
			'pop-up',
			'product_tabs',
			'top_des',
			'bottom_des',
			'after_title',
			'after_meta',
			'none',
		);
	}
}

if ( ! function_exists( 'pscw_get_allowed_button_types' ) ) {
	/**
	 * Allowed popup button types.
	 *
	 * @return string[]
	 */
	function pscw_get_allowed_button_types() {
		return array( 'icon', 'text', 'icon_text' );
	}
}

if ( ! function_exists( 'pscw_get_allowed_icon_keys' ) ) {
	/**
	 * Allowed icon file basenames from assets/img/svg.
	 *
	 * @return string[]
	 */
	function pscw_get_allowed_icon_keys() {
		static $icons = null;
		if ( null !== $icons ) {
			return $icons;
		}
		$icons     = array();
		$icon_path = PSCW_CONST_F['plugin_dir'] . 'assets/img/svg';
		if ( ! is_dir( $icon_path ) ) {
			return $icons;
		}
		$files = scandir( $icon_path );
		if ( ! is_array( $files ) ) {
			return $icons;
		}
		foreach ( $files as $file ) {
			if ( ! is_file( $icon_path . '/' . $file ) ) {
				continue;
			}
			$info = pathinfo( $file );
			if ( empty( $info['filename'] ) || ( isset( $info['extension'] ) && strtolower( $info['extension'] ) !== 'svg' ) ) {
				continue;
			}
			$icons[] = sanitize_file_name( $info['filename'] );
		}

		return $icons;
	}
}

if ( ! function_exists( 'pscw_sanitize_position' ) ) {
	/**
	 * Sanitize a display position value.
	 *
	 * @param string $position Raw position.
	 * @param string $default  Fallback.
	 * @return string
	 */
	function pscw_sanitize_position( $position, $default = 'product_tabs' ) {
		$position = sanitize_text_field( (string) $position );

		return in_array( $position, pscw_get_allowed_positions(), true ) ? $position : $default;
	}
}

if ( ! function_exists( 'pscw_sanitize_css_color' ) ) {
	/**
	 * Sanitize a CSS color (hex or named safe fallback).
	 *
	 * @param mixed  $color   Raw color.
	 * @param string $default Fallback hex.
	 * @return string
	 */
	function pscw_sanitize_css_color( $color, $default = '#000000' ) {
		$color = is_string( $color ) ? trim( $color ) : '';
		$hex   = sanitize_hex_color( $color );
		if ( $hex ) {
			return $hex;
		}
		if ( preg_match( '/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $color ) ) {
			return $color;
		}

		return $default;
	}
}

if ( ! function_exists( 'pscw_sanitize_border_style' ) ) {
	/**
	 * Allowlist CSS border-style values.
	 *
	 * @param mixed  $style   Raw style.
	 * @param string $default Fallback.
	 * @return string
	 */
	function pscw_sanitize_border_style( $style, $default = 'solid' ) {
		$style   = sanitize_text_field( (string) $style );
		$allowed = array( 'none', 'solid', 'dashed', 'dotted', 'double', 'groove', 'ridge', 'inset', 'outset' );

		return in_array( $style, $allowed, true ) ? $style : $default;
	}
}

if ( ! function_exists( 'pscw_sanitize_css_number' ) ) {
	/**
	 * Cast a CSS numeric value.
	 *
	 * @param mixed $value   Raw value.
	 * @param float $default Fallback.
	 * @return float|int
	 */
	function pscw_sanitize_css_number( $value, $default = 0 ) {
		if ( is_numeric( $value ) ) {
			$num = (float) $value;

			return ( floor( $num ) === $num ) ? (int) $num : $num;
		}

		return $default;
	}
}

if ( ! function_exists( 'pscw_sanitize_css_unit' ) ) {
	/**
	 * Allowlist CSS length units.
	 *
	 * @param mixed  $unit    Raw unit.
	 * @param string $default Fallback.
	 * @return string
	 */
	function pscw_sanitize_css_unit( $unit, $default = 'px' ) {
		$unit    = sanitize_text_field( (string) $unit );
		$allowed = array( 'px', '%', 'em', 'rem', 'vh', 'vw' );

		return in_array( $unit, $allowed, true ) ? $unit : $default;
	}
}

if ( ! function_exists( 'pscw_sanitize_object_fit' ) ) {
	/**
	 * Allowlist CSS object-fit values.
	 *
	 * @param mixed  $fit     Raw value.
	 * @param string $default Fallback.
	 * @return string
	 */
	function pscw_sanitize_object_fit( $fit, $default = 'unset' ) {
		$fit     = sanitize_text_field( (string) $fit );
		$allowed = array( 'unset', 'contain', 'cover', 'fill', 'none', 'scale-down' );

		return in_array( $fit, $allowed, true ) ? $fit : $default;
	}
}

if ( ! function_exists( 'pscw_sanitize_spacing_array' ) ) {
	/**
	 * Sanitize a 4-value margin/padding array.
	 *
	 * @param mixed $values Raw values.
	 * @return array
	 */
	function pscw_sanitize_spacing_array( $values ) {
		if ( ! is_array( $values ) ) {
			return array( 0, 0, 0, 0 );
		}
		$out = array();
		for ( $i = 0; $i < 4; $i++ ) {
			$out[] = pscw_sanitize_css_number( $values[ $i ] ?? 0, 0 );
		}

		return $out;
	}
}

if ( ! function_exists( 'pscw_sanitize_interface' ) ) {
	/**
	 * Sanitize size-chart designer interface structure before storage.
	 *
	 * @param mixed $interface Raw decoded JSON.
	 * @return array
	 */
	function pscw_sanitize_interface( $interface ) {
		if ( ! is_array( $interface ) ) {
			return array();
		}
		$allowed_types = array( 'container', 'row', 'column', 'table', 'text', 'image', 'divider', 'accordion', 'tab' );
		$sanitized     = array(
			'layout'       => array(
				'type'     => 'container',
				'children' => array(),
			),
			'elementsById' => array(),
		);

		if ( isset( $interface['layout'] ) && is_array( $interface['layout'] ) ) {
			$sanitized['layout']['type'] = 'container';
			if ( ! empty( $interface['layout']['children'] ) && is_array( $interface['layout']['children'] ) ) {
				$sanitized['layout']['children'] = array_values(
					array_filter(
						array_map( 'sanitize_html_class', $interface['layout']['children'] )
					)
				);
			}
		}

		if ( empty( $interface['elementsById'] ) || ! is_array( $interface['elementsById'] ) ) {
			return $sanitized;
		}

		foreach ( $interface['elementsById'] as $element_id => $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}
			$type = isset( $element['type'] ) ? sanitize_key( $element['type'] ) : '';
			if ( ! in_array( $type, $allowed_types, true ) ) {
				continue;
			}
			$id = sanitize_html_class( isset( $element['id'] ) ? (string) $element['id'] : (string) $element_id );
			if ( '' === $id ) {
				continue;
			}
			$clean = array(
				'id'   => $id,
				'type' => $type,
			);
			if ( isset( $element['parent'] ) ) {
				$clean['parent'] = sanitize_html_class( (string) $element['parent'] );
			}
			if ( isset( $element['class'] ) ) {
				$clean['class'] = sanitize_html_class( (string) $element['class'] );
			}
			if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
				if ( 'accordion' === $type || 'tab' === $type ) {
					$clean['children'] = array();
					foreach ( $element['children'] as $child ) {
						if ( ! is_array( $child ) ) {
							continue;
						}
						$item = array(
							'id'      => sanitize_html_class( (string) ( $child['id'] ?? '' ) ),
							'title'   => sanitize_text_field( (string) ( $child['title'] ?? '' ) ),
							'content' => array(),
						);
						if ( ! empty( $child['content'] ) && is_array( $child['content'] ) ) {
							$item['content'] = array_values(
								array_filter(
									array_map( 'sanitize_html_class', $child['content'] )
								)
							);
						}
						$clean['children'][] = $item;
					}
				} else {
					$clean['children'] = array_values(
						array_filter(
							array_map( 'sanitize_html_class', $element['children'] )
						)
					);
				}
			}
			if ( isset( $element['settings'] ) && is_array( $element['settings'] ) ) {
				$clean['settings'] = map_deep( $element['settings'], 'sanitize_text_field' );
			}
			if ( isset( $element['margin'] ) ) {
				$clean['margin'] = pscw_sanitize_spacing_array( $element['margin'] );
			}
			if ( isset( $element['padding'] ) ) {
				$clean['padding'] = pscw_sanitize_spacing_array( $element['padding'] );
			}

			switch ( $type ) {
				case 'text':
					$clean['value'] = isset( $element['value'] ) ? wp_kses_post( (string) $element['value'] ) : '';
					break;
				case 'image':
					$clean['src']         = isset( $element['src'] ) ? esc_url_raw( (string) $element['src'] ) : '';
					$clean['alt']         = isset( $element['alt'] ) ? sanitize_text_field( (string) $element['alt'] ) : '';
					$clean['borderColor'] = pscw_sanitize_css_color( $element['borderColor'] ?? '#000000' );
					$clean['borderStyle'] = pscw_sanitize_border_style( $element['borderStyle'] ?? 'solid' );
					$clean['borderWidth'] = pscw_sanitize_css_number( $element['borderWidth'] ?? 0 );
					$clean['height']      = pscw_sanitize_css_number( $element['height'] ?? 100, 100 );
					$clean['heightUnit']  = pscw_sanitize_css_unit( $element['heightUnit'] ?? '%', '%' );
					$clean['width']       = pscw_sanitize_css_number( $element['width'] ?? 100, 100 );
					$clean['widthUnit']   = pscw_sanitize_css_unit( $element['widthUnit'] ?? '%', '%' );
					$clean['objectFit']   = pscw_sanitize_object_fit( $element['objectFit'] ?? 'unset' );
					break;
				case 'table':
					$clean['columns'] = array();
					if ( ! empty( $element['columns'] ) && is_array( $element['columns'] ) ) {
						foreach ( $element['columns'] as $col ) {
							$clean['columns'][] = sanitize_text_field( (string) $col );
						}
					}
					$clean['rows'] = array();
					if ( ! empty( $element['rows'] ) && is_array( $element['rows'] ) ) {
						foreach ( $element['rows'] as $row ) {
							if ( ! is_array( $row ) ) {
								continue;
							}
							$clean_row = array();
							foreach ( $row as $cell ) {
								$clean_row[] = sanitize_text_field( (string) $cell );
							}
							$clean['rows'][] = $clean_row;
						}
					}
					$header_col                         = isset( $element['headerColumn'] ) ? sanitize_key( (string) $element['headerColumn'] ) : 'row';
					$clean['headerColumn']              = in_array( $header_col, array( 'row', 'column', 'both' ), true ) ? $header_col : 'row';
					$clean['headerBackground']          = pscw_sanitize_css_color( $element['headerBackground'] ?? '#ffffff', '#ffffff' );
					$clean['textHeader']                = pscw_sanitize_css_color( $element['textHeader'] ?? '#000000' );
					$clean['headerTextBold']            = ! empty( $element['headerTextBold'] );
					$clean['headerTextSize']            = pscw_sanitize_css_number( $element['headerTextSize'] ?? 14, 14 );
					$clean['columnsStyle']              = ! empty( $element['columnsStyle'] );
					$clean['evenBackground']            = pscw_sanitize_css_color( $element['evenBackground'] ?? '#ffffff', '#ffffff' );
					$clean['evenText']                  = pscw_sanitize_css_color( $element['evenText'] ?? '#494949', '#494949' );
					$clean['oddBackground']             = pscw_sanitize_css_color( $element['oddBackground'] ?? '#ffffff', '#ffffff' );
					$clean['oddText']                   = pscw_sanitize_css_color( $element['oddText'] ?? '#494949', '#494949' );
					$clean['borderColor']               = pscw_sanitize_css_color( $element['borderColor'] ?? '#9D9D9D', '#9D9D9D' );
					$clean['cellTextSize']              = pscw_sanitize_css_number( $element['cellTextSize'] ?? 14, 14 );
					$clean['horizontalBorderWidth']     = pscw_sanitize_css_number( $element['horizontalBorderWidth'] ?? 1, 1 );
					$clean['horizontalBorderStyle']     = pscw_sanitize_border_style( $element['horizontalBorderStyle'] ?? 'solid' );
					$clean['verticalBorderWidth']       = pscw_sanitize_css_number( $element['verticalBorderWidth'] ?? 1, 1 );
					$clean['verticalBorderStyle']       = pscw_sanitize_border_style( $element['verticalBorderStyle'] ?? 'solid' );
					$clean['borderRadius']              = pscw_sanitize_spacing_array( $element['borderRadius'] ?? array( 0, 0, 0, 0 ) );
					$clean['tableMaxHeight']            = pscw_sanitize_css_number( $element['tableMaxHeight'] ?? 535, 535 );
					break;
				case 'divider':
					$clean['text']       = isset( $element['text'] ) ? sanitize_text_field( (string) $element['text'] ) : '';
					$clean['color']      = pscw_sanitize_css_color( $element['color'] ?? '#000000' );
					$clean['weight']     = pscw_sanitize_css_number( $element['weight'] ?? 1, 1 );
					$clean['weightUnit'] = pscw_sanitize_css_unit( $element['weightUnit'] ?? 'px' );
					break;
				case 'accordion':
					$clean['multiple']        = ! empty( $element['multiple'] );
					$clean['defaultFirstTab'] = ! empty( $element['defaultFirstTab'] );
					$arrow_style              = isset( $element['arrow_style'] ) ? sanitize_key( (string) $element['arrow_style'] ) : 'default';
					$arrow_direction          = isset( $element['arrow_direction'] ) ? sanitize_key( (string) $element['arrow_direction'] ) : 'right_down';
					$clean['arrow_style']     = in_array( $arrow_style, array( 'default', 'caret', 'chevron' ), true ) ? $arrow_style : 'default';
					$clean['arrow_direction'] = in_array( $arrow_direction, array( 'right_down', 'left_down', 'down' ), true ) ? $arrow_direction : 'right_down';
					$clean['backgroundColor'] = pscw_sanitize_css_color( $element['backgroundColor'] ?? '#3F51B5', '#3F51B5' );
					$clean['textColor']       = pscw_sanitize_css_color( $element['textColor'] ?? '#ffffff', '#ffffff' );
					$clean['borderColor']     = pscw_sanitize_css_color( $element['borderColor'] ?? '#3a4ba4', '#3a4ba4' );
					break;
			}

			$sanitized['elementsById'][ $id ] = $clean;
		}

		return $sanitized;
	}
}

if ( ! function_exists( 'pscw_sanitize_size_chart_interface' ) ) {
	/**
	 * Sanitize size chart interface data before saving.
	 *
	 * @param mixed $data Interface payload.
	 * @return array
	 */
	function pscw_sanitize_size_chart_interface( $data ) {
		return pscw_sanitize_interface( $data );
	}
}

if ( ! function_exists( 'pscw_search_post' ) ) {
	/**
	 * AJAX-style search for posts of a given type (Select2).
	 *
	 * @param string $post_type  Post type.
	 * @param string $search_key Search string.
	 */
	function pscw_search_post( $post_type, $search_key ) {
		$args = array(
			'post_type'      => $post_type,
			'orderby'        => 'title',
			'order'          => 'ASC',
			's'              => $search_key,
			'posts_per_page' => 50,
			'post_status'    => 'publish',
		);
		$query           = new \WP_Query( $args );
		$result          = $query->get_posts();
		$product_name_id = array();
		foreach ( $result as $val ) {
			$product_name_id[ $val->ID ] = $val->post_title;
		}
		if ( ! empty( $product_name_id ) ) {
			$results = array( 'results' => array() );
			foreach ( $product_name_id as $id => $name ) {
				$results['results'][] = array(
					'id'   => $id,
					'text' => $name,
				);
			}
			wp_send_json( $results );
		}
		wp_send_json( array( 'results' => array() ) );
	}
}

if ( ! function_exists( 'pscw_get_allow_svg' ) ) {
	/**
	 * Allowed SVG tags for size-chart icons.
	 *
	 * @return array
	 */
	function pscw_get_allow_svg() {
		$allowed_html = wp_kses_allowed_html( 'post' );

		$allowed_html['svg'] = array(
			'xmlns'       => true,
			'width'       => true,
			'height'      => true,
			'viewBox'     => true,
			'xmlns:xlink' => true,
			'version'     => true,
		);
		$allowed_html['g']   = array(
			'transform' => true,
			'data-name' => true,
			'id'        => true,
		);

		$allowed_html['rect'] = array(
			'x'      => true,
			'y'      => true,
			'width'  => true,
			'height' => true,
			'fill'   => true,
		);

		$allowed_html['path']   = array(
			'd'    => true,
			'fill' => true,
		);
		$allowed_html['circle'] = array(
			'cx'   => true,
			'cy'   => true,
			'r'    => true,
			'fill' => true,
		);
		$allowed_html['title']  = array();
		$allowed_html['desc']   = array();

		return $allowed_html;
	}
}

if ( ! function_exists( 'pscw_get_view_box' ) ) {
	/**
	 * SVG viewBox for a built-in icon key.
	 *
	 * @param string $key Icon filename without extension.
	 * @return string
	 */
	function pscw_get_view_box( $key ) {
		$view_box = array(
			'ruler-icon-2' => '0 0 71 71.6',
		);

		return $view_box[ $key ] ?? '0 0 100 100';
	}
}

if ( ! function_exists( 'pscw_default_size_chart_interface' ) ) {
	/**
	 * Default empty size-chart interface structure.
	 *
	 * @return array
	 */
	function pscw_default_size_chart_interface() {
		$row_id    = uniqid( 'pscw-row-ID_', true );
		$col_id    = uniqid( 'pscw-col-ID_', true );
		$text_id   = uniqid( 'pscw-text-ID_', true );
		$interface = array(
			'layout'       => array(
				'type'     => 'container',
				'children' => array(
					$row_id,
				),
			),
			'elementsById' => array(
				$col_id  => array(
					'id'       => $col_id,
					'class'    => 'pscw-col-l-12',
					'type'     => 'column',
					'parent'   => $row_id,
					'children' => array(
						$text_id,
					),
					'settings' => array(
						'class' => 'pscw-customize-col-12',
					),
				),
				$row_id  => array(
					'children' => array(
						$col_id,
					),
					'id'       => $row_id,
					'type'     => 'row',
				),
				$text_id => array(
					'id'     => $text_id,
					'type'   => 'text',
					'parent' => $col_id,
					'value'  => 'Enter your content',
					'margin' => array( 0, 0, 0, 0 ),
				),
			),
		);

		return $interface;
	}
}

if ( ! function_exists( 'pscw_migrate_size_chart_data' ) ) {
	/**
	 * Convert legacy free size-chart meta into interface JSON.
	 *
	 * @param \WP_Post $size_chart             Size chart post.
	 * @param array    $woo_sc_size_chart_data Legacy meta.
	 */
	function pscw_migrate_size_chart_data( $size_chart, $woo_sc_size_chart_data ) {
		$text_element  = array();
		$image_element = array();
		$table_element = array();
		$children      = array();

		$row_id = uniqid( 'pscw-row-ID_', true );
		$col_id = uniqid( 'pscw-col-ID_', true );

		if ( ! empty( $size_chart->post_content ) ) {
			$text_id      = uniqid( 'pscw-text-ID_', true );
			$children[]   = $text_id;
			$text_element = array(
				'id'     => $text_id,
				'type'   => 'text',
				'parent' => $col_id,
				'value'  => wp_kses_post( $size_chart->post_content ),
				'margin' => array( 0, 0, 0, 0 ),
			);
		}
		if ( ! empty( $woo_sc_size_chart_data['img_link'] ) ) {
			$image_id      = uniqid( 'pscw-image-ID_', true );
			$children[]    = $image_id;
			$image_element = array(
				'id'          => $image_id,
				'type'        => 'image',
				'parent'      => $col_id,
				'alt'         => '',
				'borderColor' => '#000000',
				'borderStyle' => 'solid',
				'borderWidth' => 0,
				'height'      => 100,
				'heightUnit'  => '%',
				'width'       => isset( $woo_sc_size_chart_data['img_width'] ) ? $woo_sc_size_chart_data['img_width'] : 100,
				'widthUnit'   => '%',
				'src'         => esc_url_raw( $woo_sc_size_chart_data['img_link'] ),
				'padding'     => array( 0, 0, 0, 0 ),
				'margin'      => array( 10, 0, 10, 0 ),
				'objectFit'   => 'unset',
			);
		}
		if ( isset( $woo_sc_size_chart_data['table_array'] ) ) {
			$table_id   = uniqid( 'pscw-table-ID_', true );
			$children[] = $table_id;
			$columns    = array( '' );
			$rows       = array( array( '' ) );
			if ( ! empty( $woo_sc_size_chart_data['table_array'] ) ) {
				$table_array = json_decode( $woo_sc_size_chart_data['table_array'], true );
				if ( is_array( $table_array ) && ! empty( $table_array ) ) {
					$columns = $table_array[0];
					unset( $table_array[0] );
					$rows = array_values( $table_array );
				}
			}
			$table_element = array(
				'id'                    => $table_id,
				'type'                  => 'table',
				'parent'                => $col_id,
				'columns'               => $columns,
				'rows'                  => $rows,
				'headerColumn'          => 'row',
				'headerBackground'      => isset( $woo_sc_size_chart_data['head_color'] ) ? $woo_sc_size_chart_data['head_color'] : '#ffffff',
				'textHeader'            => isset( $woo_sc_size_chart_data['text_head_color'] ) ? $woo_sc_size_chart_data['text_head_color'] : '#000000',
				'headerTextBold'        => false,
				'headerTextSize'        => 14,
				'columnsStyle'          => isset( $woo_sc_size_chart_data['woo_sc_cell_style'] ) && $woo_sc_size_chart_data['woo_sc_cell_style'] === 'columns',
				'evenBackground'        => isset( $woo_sc_size_chart_data['even_rows_color'] ) ? $woo_sc_size_chart_data['even_rows_color'] : '#ffffff',
				'evenText'              => isset( $woo_sc_size_chart_data['even_rows_text_color'] ) ? $woo_sc_size_chart_data['even_rows_text_color'] : '#494949',
				'oddBackground'         => isset( $woo_sc_size_chart_data['odd_rows_color'] ) ? $woo_sc_size_chart_data['odd_rows_color'] : '#ffffff',
				'oddText'               => isset( $woo_sc_size_chart_data['odd_rows_text_color'] ) ? $woo_sc_size_chart_data['odd_rows_text_color'] : '#494949',
				'borderColor'           => isset( $woo_sc_size_chart_data['border_color'] ) ? $woo_sc_size_chart_data['border_color'] : '#9D9D9D',
				'cellTextSize'          => 14,
				'horizontalBorderWidth' => isset( $woo_sc_size_chart_data['horizontal_width'] ) ? $woo_sc_size_chart_data['horizontal_width'] : 1,
				'horizontalBorderStyle' => isset( $woo_sc_size_chart_data['horizontal_border_style'] ) ? $woo_sc_size_chart_data['horizontal_border_style'] : 'solid',
				'verticalBorderWidth'   => isset( $woo_sc_size_chart_data['vertical_width'] ) ? $woo_sc_size_chart_data['vertical_width'] : 1,
				'verticalBorderStyle'   => isset( $woo_sc_size_chart_data['vertical_border_style'] ) ? $woo_sc_size_chart_data['vertical_border_style'] : 'solid',
				'margin'                => array( 0, 0, 0, 0 ),
				'borderRadius'          => ( isset( $woo_sc_size_chart_data['table_template'] ) && $woo_sc_size_chart_data['table_template'] === 'table_template_v1' ) ? array( 20, 20, 20, 20 ) : array( 0, 0, 0, 0 ),
			);
		}
		$pscw_interface = array(
			'layout'       => array(
				'type'     => 'container',
				'children' => array(
					$row_id,
				),
			),
			'elementsById' => array(
				$col_id => array(
					'id'       => $col_id,
					'class'    => 'pscw-col-l-12',
					'type'     => 'column',
					'parent'   => $row_id,
					'children' => $children,
					'settings' => array(
						'class' => 'pscw-customize-col-12',
					),
				),
				$row_id => array(
					'children' => array(
						$col_id,
					),
					'id'       => $row_id,
					'type'     => 'row',
				),
			),
		);

		if ( ! empty( $text_element ) ) {
			$pscw_interface['elementsById'][ $text_element['id'] ] = $text_element;
		}

		if ( ! empty( $image_element ) ) {
			$pscw_interface['elementsById'][ $image_element['id'] ] = $image_element;
		}

		if ( ! empty( $table_element ) ) {
			$pscw_interface['elementsById'][ $table_element['id'] ] = $table_element;
		}

		update_post_meta( $size_chart->ID, 'pscw_interface', $pscw_interface );
	}
}
