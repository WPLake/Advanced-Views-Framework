<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Common;

defined( 'ABSPATH' ) || exit;

class Related_Groups_Import_Result {
	/**
	 * @var array<string, string[]> vendorName => groupIds (like group_x, not wp post ids)
	 */
	private array $group_ids;

	public function __construct() {
		$this->group_ids = array();
	}

	public function add_group( string $vendor_name, string $group_id ): void {
		$this->group_ids[ $vendor_name ]   = $this->group_ids[ $vendor_name ] ?? array();
		$this->group_ids[ $vendor_name ][] = $group_id;
	}

	public function merge( Related_Groups_Import_Result $related_groups_import_result ): void {
		foreach ( $related_groups_import_result->group_ids as $vendor_name => $group_ids ) {
			$this->group_ids[ $vendor_name ] = $this->group_ids[ $vendor_name ] ?? array();
			$this->group_ids[ $vendor_name ] = array_merge( $this->group_ids[ $vendor_name ], $group_ids );
		}
	}

	public function get_query_string_value(): string {
		$vendor_parts = array();

		foreach ( $this->group_ids as $vendor_name => $group_ids ) {
			$vendor_parts[] = sprintf( '%s:%s', $vendor_name, implode( ',', $group_ids ) );
		}

		return implode( ';', $vendor_parts );
	}

	public function from_query_string( string $query_string ): void {
		$items = explode( ';', $query_string );

		foreach ( $items as $item ) {
			$parts = explode( ':', $item );

			if ( 2 !== count( $parts ) ) {
				continue;
			}

			$vendor_name = $parts[0];
			$group_ids   = explode( ',', $parts[1] );

			$this->group_ids[ $vendor_name ] = $group_ids;
		}
	}

	/**
	 * @return  array<string, string[]> vendorName => groupIds (like group_x, not wp post ids)
	 */
	public function get_group_ids(): array {
		return $this->group_ids;
	}
}
