<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups_Integration;

use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Tax_Field_Settings;
use WP_Term;

defined( 'ABSPATH' ) || exit;

class Tax_Field_Settings_Integration extends Acf_Integration {
	private Data_Vendors $data_vendors;

	public function __construct( string $target_cpt_name, Data_Vendors $data_vendors ) {
		parent::__construct( $target_cpt_name );

		$this->data_vendors = $data_vendors;
	}

	/**
	 * @return array<string,string>
	 */
	protected function get_taxonomy_choices(): array {
		$tax_choices = array(
			'' => __( 'Select', 'acf-views' ),
		);

		$taxonomies = get_taxonomies( array(), 'objects' );

		foreach ( $taxonomies as $taxonomy ) {
			$tax_choices[ $taxonomy->name ] = $taxonomy->label;
		}

		return $tax_choices;
	}

	/**
	 * @return array<string,string>
	 */
	protected function get_term_choices(): array {
		$term_choices = array(
			'' => __( 'Select', 'acf-views' ),
		);

		/**
		 * @var string[] $taxonomy_names
		 */
		$taxonomy_names = get_taxonomies();

		foreach ( $taxonomy_names as $taxonomy_name ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy_name,
					'hide_empty' => false,
				)
			);

			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$full_tax_id                  = Tax_Field_Settings::create_key( $taxonomy_name, $term->term_id );
					$term_choices[ $full_tax_id ] = $term->name;
				}
			}
		}

		return $term_choices;
	}

	protected function set_field_choices(): void {
		self::add_filter(
			'acf/load_field/name=' . Tax_Field_Settings::getAcfFieldName( Tax_Field_Settings::FIELD_TAXONOMY ),
			function ( array $field ) {
				$field['choices'] = $this->get_taxonomy_choices();

				return $field;
			}
		);

		self::add_filter(
			'acf/load_field/name=' . Tax_Field_Settings::getAcfFieldName( Tax_Field_Settings::FIELD_TERM ),
			function ( array $field ) {
				$field['choices'] = $this->get_term_choices();

				return $field;
			}
		);

		self::add_filter(
			'acf/load_field/name=' . Tax_Field_Settings::getAcfFieldName( Tax_Field_Settings::FIELD_DYNAMIC_TERM ),
			function ( array $field ) {
				$field['choices'] = array(
					''                  => __( 'Select', 'acf-views' ),
					'$current$'         => __( '$current$ (archive and category pages)', 'acf-views' ),
					'$meta$'            => __( '$meta$ (from specific meta field)', 'acf-views' ),
					'$custom-argument$' => __( '$custom-argument$ (from the shortcode arguments)', 'acf-views' ),
				);

				return $field;
			}
		);

		self::add_filter(
			'acf/load_field/name=' . Tax_Field_Settings::getAcfFieldName( Tax_Field_Settings::FIELD_META_GROUP ),
			function ( array $field ) {
				$field['choices'] = $this->data_vendors->get_group_choices( true );

				return $field;
			}
		);

		self::add_filter(
			'acf/load_field/name=' . Tax_Field_Settings::getAcfFieldName( Tax_Field_Settings::FIELD_META_FIELD ),
			function ( array $field ) {
				$field['choices'] = $this->data_vendors->get_field_choices( true );

				return $field;
			}
		);
	}
}
