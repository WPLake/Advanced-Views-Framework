<?php

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Taxonomy_Terms;

defined( 'ABSPATH' ) || exit;

class Taxonomy_Term_Fields {
	const GROUP_NAME = '$taxonomy$';
	// all fields have ids like 'field_x', so no conflicts possible.
	const PREFIX = '_taxonomy_';

	const FIELD_TERMS = '_taxonomy_terms';
}
