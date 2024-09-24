<?php

if ( ! function_exists( 'pscw_get_value_combined' ) ) {
	function pscw_get_value_combined( $type, $key ) {
		$value   = array();
		$value[] = $key;
		switch ( $type ) {
			case 'products':
				$get_product = wc_get_product( $key );
				if ( $get_product ) {
					$value[] = $get_product->get_name();
				}
				break;
			case 'product_cat':
				$get_term = get_term_by( 'slug', $key, 'product_cat' );
				if ( $get_term ) {
					$value[] = $get_term->name;
				}
				break;
		}

		return $value;

	}
}

if ( ! function_exists( 'pscw_search_post' ) ) {
	function pscw_search_post( $post_type, $search_key ) {
		$args   = array(
			'post_type' => $post_type,
			'order'     => 'name',
			's'         => $search_key
		);
		$query  = new \WP_Query ( $args );
		$result = $query->get_posts();
		foreach ( $result as $val ) {
			$product_name_id[ $val->ID ] = $val->post_title;
		}
		if ( ! empty( $product_name_id ) ) {
			$results = array();
			foreach ( $product_name_id as $id => $name ) {
				$a['id']            = $id;
				$a['text']          = $name;
				$b[]                = $a;
				$results['results'] = $b;
			}
			wp_send_json( $results );
		}
	}
}

if ( ! function_exists( 'pscw_get_allow_svg' ) ) {
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
	function pscw_get_view_box( $key ) {
		$view_box = array(
			'ruler-icon-2'   => '0 0 71 71.6',
		);

		return $view_box[ $key ];
	}
}