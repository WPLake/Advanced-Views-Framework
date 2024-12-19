<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents\Cpt;

use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Html;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;

defined( 'ABSPATH' ) || exit;

abstract class Cpt_Meta_Boxes implements Hooks_Interface {
	private Html $html;

	public function __construct( Html $html ) {
		$this->html = $html;
	}

	abstract protected function get_cpt_name(): string;

	protected function get_html(): Html {
		return $this->html;
	}

	public function add_meta_boxes(): void {
		add_meta_box(
			'acf-views_review',
			__( 'Rate & Review', 'acf-views' ),
			function () {
				$this->html->print_postbox_review();
			},
			array(
				$this->get_cpt_name(),
			),
			'side',
			'low'
		);

		add_meta_box(
			'acf-views_support',
			__( 'Having issues?', 'acf-views' ),
			function () {
				$this->html->print_postbox_support();
			},
			array(
				$this->get_cpt_name(),
			),
			'side',
			'low'
		);
	}

	public function print_mount_points( Cpt_Data $cpt_data ): void {
		$post_types      = array();
		$safe_post_links = array();

		foreach ( $cpt_data->mount_points as $mount_point ) {
			$post_types      = array_merge( $post_types, $mount_point->post_types );
			$safe_post_links = array_merge( $safe_post_links, $mount_point->posts );
		}

		$post_types      = array_unique( $post_types );
		$safe_post_links = array_unique( $safe_post_links );

		foreach ( $safe_post_links as $index => $post ) {
			$post_url  = get_the_permalink( $post );
			$post_url  = false !== $post_url ?
				$post_url :
				'';
			$post_info = sprintf(
				'<a target="_blank" href="%s">%s</a>',
				esc_url( $post_url ),
				esc_html( get_the_title( $post ) )
			);

			$safe_post_links[ $index ] = $post_info;
		}

		if ( array() !== $post_types ) {
			echo esc_html(
				__( 'Post Types:', 'acf-views' ) . ' ' . join( ', ', $post_types )
			);
		}

		if ( array() !== $safe_post_links ) {
			if ( array() !== $post_types ) {
				echo '<br>';
			}

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo __( 'Pages:', 'acf-views' ) . ' ' . join( ', ', $safe_post_links );
		}
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_admin() ) {
			return;
		}

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
	}
}
