<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Acf\Groups;

use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Group;

defined( 'ABSPATH' ) || exit;

class Git_Repository extends Group {
	// to fix the group name in case class name changes.
	const CUSTOM_GROUP_NAME = self::GROUP_NAME_PREFIX . 'git-repository';

	/**
	 * @label Repository ID
	 * @instructions To retrieve your GitLab repository ID, follow these steps: 1. Open your repository. 2. Look for the 'Project Information' block on the right-hand side. 3. Click on the three dots icon above the block. 4. Click on the 'Copy project ID' item.
	 */
	public string $id;
	/**
	 * @label Access Token
	 * @instructions To retrieve your GitLab access token, follow these steps: 1. Open your GitLab profile. 2. In the left menu, click on the 'Access -> Personal Access Tokens' tab. 3. Create a new token with the 'api' scope. 4. Copy the token value. (You can also use Group and Project tokens if you've a paid GitLab account)
	 */
	public string $access_token;
	/**
	 * @label Repository Name
	 * @instructions Assign a name to your repository, which will appear as a new tab in the list table.
	 */
	public string $name;
}
