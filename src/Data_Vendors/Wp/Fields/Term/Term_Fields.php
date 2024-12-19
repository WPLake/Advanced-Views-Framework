<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Term;

defined( 'ABSPATH' ) || exit;

class Term_Fields {
	const GROUP_NAME = '$term$';

	const PREFIX = '_term_';

	const FIELD_NAME        = '_term_name';
	const FIELD_SLUG        = '_term_slug';
	const FIELD_DESCRIPTION = '_term_description';
	const FIELD_NAME_LINK   = '_term_name_link';
}
