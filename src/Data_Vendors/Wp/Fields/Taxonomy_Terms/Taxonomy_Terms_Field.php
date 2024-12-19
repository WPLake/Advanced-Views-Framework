<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Taxonomy_Terms;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Custom_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Taxonomy_Field;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;

defined( 'ABSPATH' ) || exit;

class Taxonomy_Terms_Field extends Taxonomy_Field {
	use Custom_Field;

	/**
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	protected function get_value( Field_Meta_Interface $field_meta, $value ) {
		// do not call the parent method, as we always have a single item here.

		$post = $this->get_post( $value );

		if ( null === $post ) {
			return array();
		}

		$taxonomy_name = substr( $field_meta->get_field_id(), strlen( Taxonomy_Term_Fields::PREFIX ) );
		$post_terms    = get_the_terms( $post, $taxonomy_name );

		if ( false === $post_terms ||
			is_wp_error( $post_terms ) ) {
			return array();
		}

		return array_column( $post_terms, 'term_id' );
	}
}
