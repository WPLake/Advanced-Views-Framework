<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Cpt;

use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Data_Storage;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt;
use Org\Wplake\Advanced_Views\Parents\Query_Arguments;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layouts_Cpt;

defined( 'ABSPATH' ) || exit;

class Post_Selections_Cpt extends Cpt {

	const NAME = 'acf_cards';

	private Post_Selections_Data_Storage $card_data_storage;

	public function __construct( Post_Selections_Data_Storage $cards_data_storage ) {
		parent::__construct( $cards_data_storage );

		$this->card_data_storage = $cards_data_storage;
	}

	protected function get_cards_data_storage(): Post_Selections_Data_Storage {
		return $this->card_data_storage;
	}

	public function add_cpt(): void {
		// translators: %1$s - link opening tag, %2$s - link closing tag.
		$not_found_label = __( 'No Cards yet. %1$s Add New Card %2$s', 'acf-views' );

		$labels = array(
			'name'               => __( 'Cards', 'acf-views' ),
			'singular_name'      => __( 'Card', 'acf-views' ),
			'menu_name'          => __( 'Cards', 'acf-views' ),
			'parent_item_colon'  => __( 'Parent Card', 'acf-views' ),
			'all_items'          => __( 'Cards', 'acf-views' ),
			'view_item'          => __( 'Browse Card', 'acf-views' ),
			'add_new_item'       => __( 'Add New Card', 'acf-views' ),
			'add_new'            => __( 'Add New', 'acf-views' ),
			'item_updated'       => __( 'Card updated.', 'acf-views' ),
			'edit_item'          => __( 'Edit Card', 'acf-views' ),
			'update_item'        => __( 'Update Card', 'acf-views' ),
			'search_items'       => __( 'Search Card', 'acf-views' ),
			'not_found'          => $this->inject_add_new_item_link( $not_found_label ),
			'not_found_in_trash' => __( 'Not Found In Trash', 'acf-views' ),
		);

		$description  = __(
			'Add a Card and select a set of posts or import a pre-built component.',
			'acf-views'
		);
		$description .= '<br>';
		$description .= __(
			'<a target="_blank" href="https://docs.advanced-views.com/getting-started/introduction/key-aspects#id-2.-integration-approaches">Attach the Card</a> to the target place, for example using <a target="_blank" href="https://docs.advanced-views.com/shortcode-attributes/card-shortcode">the shortcode</a>, to display queried items with their fields.',
			'acf-views'
		) .
						'<br>'
						. __( '(The assigned View determines which fields are displayed)', 'acf-views' );

		$description .= '<br><br>';
		$description .= $this->get_storage_label();

		$cpt_args = array(
			'label'        => __( 'Cards', 'acf-views' ),
			'description'  => $description,
			'labels'       => $labels,
			'show_in_menu' => sprintf( 'edit.php?post_type=%s', Layouts_Cpt::NAME ),
			'menu_icon'    => 'dashicons-layout',
		);

		$this->register_cpt( $cpt_args );
	}

	public function get_title_placeholder( string $title ): string {
		$screen = get_current_screen()->post_type ?? '';
		if ( self::NAME !== $screen ) {
			return $title;
		}

		return __( 'Name your Card here (required)', 'acf-views' );
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

		$messages[ self::NAME ] = array(
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
