<?php

defined( 'ABSPATH' ) || exit;

$view                 = $view ?? array();
$is_has_demo_objects  = $view['isHasDemoObjects'] ?? false;
$form_nonce           = $view['formNonce'] ?? '';
$is_with_form_message = $view['isWithFormMessage'] ?? '';

?>

<form action="" method="post" class="av-dashboard">
	<input type="hidden" name="_av-page" value="import">
	<?php
	printf( '<input type="hidden" name="_wpnonce" value="%s">', esc_attr( $form_nonce ) );
	?>
	<div class="av-dashboard__main">

		<?php
		if ( $is_with_form_message ) {
			?>
			<div class="av-introduction av-dashboard__block av-dashboard__block--medium">
				<?php

				$view = array(
					'demoImport' => $view['demoImport'] ?? null,
				);
				include __DIR__ . '/import_result.php';

				if ( $is_has_demo_objects ) {
					?>
					<br><br>
					<button class="button button-primary button-large av-dashboard__button av-dashboard__button--red"
							name="_delete" value="delete">
						<?php
						echo esc_html__( 'Delete imported objects', 'acf-views' );
						?>
					</button>
					<?php
				}
				?>
			</div>
			<?php
		}
		?>

		<?php
		if ( ! $is_has_demo_objects ) {
			?>
		<div class="av-introduction av-dashboard__block">
			<p class="av-introduction__title">
				<?php
				echo esc_html( __( 'Import Demo to get started in seconds', 'acf-views' ) );
				?>
			</p>
			<p class="av-introduction__description">
				<?php
				echo esc_html__(
					'Whether you are new to Advanced Views or you just want to get the basic setup quickly then this tool will help you with the following scenarios:',
					'acf-views'
				);
				?>
				<br><br>
			</p>
			<p><b>
					<?php
					echo esc_html( __( 'Display page\'s ACF fields on the same page', 'acf-views' ) );
					?>
				</b></p>
			<ol class="av-introduction__description av-introduction__ol">
				<li>
					<?php
					echo esc_html(
						__(
							"Create 'draft' pages for 'Samsung Galaxy A53', 'Nokia X20' and 'Xiaomi 12T'.",
							'acf-views'
						)
					);
					?>
				</li>
				<li>
					<?php
					echo esc_html(
						__(
							'Create an ACF Field Group called "Phone" with location set to those pages.',
							'acf-views'
						)
					);
					?>
				</li>
				<li>
					<?php
					echo esc_html(
						__(
							'Create a View called "Phone" with fields assigned from the "Phone" Field Group.',
							'acf-views'
						)
					);
					?>
				</li>
				<li>
					<?php
					echo esc_html(
						__(
							'Fill each page’s ACF fields with text and add the View shortcode to the page content.',
							'acf-views'
						)
					);
					?>
				</li>
			</ol>
			<p><b>
					<?php
					echo esc_html( __( 'Display a specific post, page or CPT item with its fields', 'acf-views' ) );
					?>
				</b></p>
			<ol class="av-introduction__description av-introduction__ol">
				<li>
					<?php
					echo esc_html__( 'Create a "draft" page called "Article about Samsung"', 'acf-views' );
					?>
				</li>
				<li>
					<?php
					echo esc_html(
						__(
							'Add the View shortcode to the page content with "object-id" argument to "Samsung Galaxy A53".',
							'acf-views'
						)
					);
					?>
				</li>
			</ol>
			<p><b>
					<?php
					echo esc_html(
						__(
							'Display specific posts, pages or CPT items and their fields by using filters',
							'acf-views'
						)
					);
					?>
					<br>
					<?php
					echo esc_html( __( 'or by manually assigning items', 'acf-views' ) );
					?>
				</b></p>
			<ol class="av-introduction__description av-introduction__ol">
				<li>
					<?php
					echo esc_html(
						__(
							'Create a Card for "List of Phones" with View "Phone" assigned and filtered to.',
							'acf-views'
						)
					);
					?>
				</li>
				<li>
					<?php
					echo esc_html(
						__(
							"Create a 'draft' page called 'Most popular phones in 2022' and add the Card shortcode to the page content.",
							'acf-views'
						)
					);
					?>
				</li>
			</ol>

			<p class="av-introduction__description">
				<br>
				<?php
				echo esc_html( __( 'Press the Import button and wait a few seconds.', 'acf-views' ) );
				?>
				<br><br>
				<?php
				echo esc_html(
					__(
						"When the process has completed, you'll see links to all the items for quick editing.",
						'acf-views'
					)
				);
				?>
				<br><br>
				<b>
					<?php
					echo esc_html(
						__(
							'Note: After the import, a delete button will appear, that can be used to remove the imported items.',
							'acf-views'
						)
					);
					?>
				</b><br><br>
			</p>
			<button class="button button-primary button-large" name="_import" value="import">
				<?php
				echo esc_html( __( 'Import demo now', 'acf-views' ) );
				?>
			</button>
			<?php
		}
		?>
		</div>
	</div>
</form>
