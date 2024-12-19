<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Comment;

defined( 'ABSPATH' ) || exit;

class Comment_Fields {
	const GROUP_NAME = '$comment$';
	// all fields have ids like 'field_x', so no conflicts possible.
	const PREFIX = '_comment_';

	const FIELD_AUTHOR_EMAIL     = '_comment_author_email';
	const FIELD_AUTHOR_NAME      = '_comment_author_name';
	const FIELD_AUTHOR_NAME_LINK = '_comment_author_name_link';
	const FIELD_CONTENT          = '_comment_content';
	const FIELD_DATE             = '_comment_date';
	const FIELD_STATUS           = '_comment_status';
	const FIELD_PARENT           = '_comment_parent';
	const FIELD_USER             = '_comment_user';
}
