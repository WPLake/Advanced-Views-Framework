<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Cpt;

use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Plugin_Cpt;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt;
use Org\Wplake\Advanced_Views\Parents\Query_Arguments;

defined( 'ABSPATH' ) || exit;

class Post_Selections_Cpt extends Cpt {

	private Post_Selections_Settings_Storage $post_selections_settings_storage;

	public function __construct( Plugin_Cpt $plugin_cpt, Post_Selections_Settings_Storage $post_selections_settings_storage ) {
		parent::__construct( $plugin_cpt, $post_selections_settings_storage );

		$this->post_selections_settings_storage = $post_selections_settings_storage;
	}

	protected function get_cards_data_storage(): Post_Selections_Settings_Storage {
		return $this->post_selections_settings_storage;
	}

	public function add_cpt(): void {
		$labels        = $this->plugin_cpt->labels();
		$singular_name = $labels->singular_name();
		$plural_name   = $labels->plural_name();

		$description = sprintf(
			// translators: %s - singular name of the CPT.
			__(
				'Add a %s and select a set of posts or import a pre-built component.',
				'acf-views'
			),
			$singular_name
		);
		$description .= '<br>';
		$description .=
			sprintf(
			// translators: %s - singular name of the CPT.
				__(
					'<a target="_blank" href="https://docs.advanced-views.com/getting-started/introduction/key-aspects#id-2.-integration-approaches">Attach the %s</a> to the target place, for example using <a target="_blank" href="https://docs.advanced-views.com/shortcode-attributes/card-shortcode">the shortcode</a>, to display queried items with their fields.',
					'acf-views'
				),
				$singular_name
			) .
						'<br>'
						. __( '(The assigned Layout determines which fields are displayed)', 'acf-views' );

		$description .= '<br><br>';
		$description .= $this->get_storage_label();

		$cpt_args = array(
			'label'        => $plural_name,
			'description'  => $description,
			'labels'       => $this->get_labels(),
			'show_in_menu' => sprintf( 'edit.php?post_type=%s', Hard_Layout_Cpt::cpt_name() ),
			'menu_icon'    => 'dashicons-layout',
		);

		$this->register_cpt( $cpt_args );
	}

	/**
	 * @param array<string,array<int,string>> $messages
	 *
	 * @return array<string,array<int,string>>
	 */
	public function replace_post_updated_message( array $messages ): array {
		global $post;

		$restored_message   = '';
		$scheduled_message  = __( 'Card scheduled for:', 'acf-views' );
		$scheduled_message .= sprintf(
			' <strong>%1$s</strong>',
			date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) )
		);

		$revision_id = Query_Arguments::get_int_for_non_action( 'revision' );

		if ( 0 !== $revision_id ) {
			$restored_message  = __( 'Card restored to revision from', 'acf-views' );
			$restored_message .= ' ' . wp_post_revision_title( $revision_id, false );
		}

		$messages[ $this->get_cpt_name() ] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Card updated.', 'acf-views' ),
			2  => __( 'Custom field updated.', 'acf-views' ),
			3  => __( 'Custom field deleted.', 'acf-views' ),
			4  => __( 'Card updated.', 'acf-views' ),
			5  => $restored_message,
			6  => __( 'Card published.', 'acf-views' ),
			7  => __( 'Card saved.', 'acf-views' ),
			8  => __( 'Card submitted.', 'acf-views' ),
			9  => $scheduled_message,
			10 => __( 'Card draft updated.', 'acf-views' ),
		);

		return $messages;
	}
}
