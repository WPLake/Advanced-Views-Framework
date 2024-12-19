<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups;

use Org\Wplake\Advanced_Views\Parents\Group;

defined( 'ABSPATH' ) || exit;

class Git_Repository extends Group {
	// to fix the group name in case class name changes.
	const CUSTOM_GROUP_NAME = self::GROUP_NAME_PREFIX . 'git-repository';

	/**
	 * @label Repository ID
	 * @instructions To retrieve your GitLab repository ID, follow these steps: 1. Open your repository. 2. Look for the 'Project Information' block on the right-hand side. 3. Click on the gear icon (project settings). 4. On the new page, copy the project ID field value.
	 */
	public string $id;
	/**
	 * @label Access Token
	 * @instructions To retrieve your GitLab access token, follow these steps: 1. Open your GitLab profile. 2. Click on the 'Access Tokens' tab. 3. Create a new token with the 'api' scope. 4. Copy the token value. (You can also use Group and Project tokens if you've a paid GitLab account)
	 */
	public string $access_token;
	/**
	 * @label Repository Name
	 * @instructions Assign a name to your repository, which will appear as a new tab in the list table.
	 */
	public string $name;
}
