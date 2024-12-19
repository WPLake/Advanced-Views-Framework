<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\User;

defined( 'ABSPATH' ) || exit;

class User_Fields {
	const GROUP_NAME = '$user$';
	// all fields have ids like 'field_x', so no conflicts possible.
	const PREFIX = '_user_';

	const FIELD_FIRST_NAME   = '_user_first_name';
	const FIELD_LAST_NAME    = '_user_last_name';
	const FIELD_DISPLAY_NAME = '_user_display_name';
	const FIELD_EMAIL        = '_user_email';
	const FIELD_BIO          = '_user_bio';
	const FIELD_AUTHOR_LINK  = '_user_author_link';
	const FIELD_WEBSITE      = '_user_website';
}
