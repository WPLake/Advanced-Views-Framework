<?php

defined( 'ABSPATH' ) || exit;

$view               ??= array();
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
				echo esc_html( __( 'Import Demo to Get Started in Seconds', 'acf-views' ) );
				?>
			</p>
			<p class="av-introduction__description">
				<?php
				echo esc_html__(
					"Whether you're new to Advanced Views or just want a quick way to set up the basics, this demo import will automatically create a working example for you.",
					'acf-views'
				);
				echo '<br>';
				echo esc_html__( "It's perfect for exploring how Layouts, Post Selections, and ACF fields work together.", 'acf-views' );
				?>
				<br>
			</p>

			<hr>

			<p>
				<b>
				<?php
				echo esc_html__( 'What this demo does', 'acf-views' );
				?>
				</b>
				<br>
			</p>
			<p><b>
					<?php
					echo esc_html( __( "Display page's ACF fields on the same page", 'acf-views' ) );
					?>
				</b></p>
			<ol class="av-introduction__description av-introduction__ol">
				<li>
					<?php
					echo esc_html(
						__(
							"Creates 'draft' pages for 'Samsung Galaxy A53', 'Nokia X20' and 'Xiaomi 12T'.",
							'acf-views'
						)
					);
					?>
				</li>
				<li>
					<?php
					echo esc_html(
						__(
							'Creates an ACF Field Group called "Phone" assigned to those pages.',
							'acf-views'
						)
					);
					?>
				</li>
				<li>
					<?php
					echo esc_html(
						__(
							'Creates a Layout called "Phone" that displays fields from the "Phone" Field Group.',
							'acf-views'
						)
					);
					?>
				</li>
				<li>
					<?php
					echo esc_html(
						__(
							'Populates each page’s ACF fields with sample text and adds the Layout shortcode to the page content.',
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
					echo esc_html__( 'Creates a "draft" page called "Article about Samsung"', 'acf-views' );
					?>
				</li>
				<li>
					<?php
					echo esc_html(
						__(
							'Adds a Layout shortcode to the page content with "object-id" parameter pointing to "Samsung Galaxy A53".',
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
							'Display multiple items using Post Selections',
							'acf-views'
						)
					);
					?>
				</b></p>
			<ol class="av-introduction__description av-introduction__ol">
				<li>
					<?php
					echo esc_html(
						__(
							'Creates a Post Selection called "List of Phones" using the "Phone" Layout and filtering for the phone pages.',
							'acf-views'
						)
					);
					?>
				</li>
				<li>
					<?php
					echo esc_html(
						__(
							"Create a 'draft' page called 'Most popular phones in 2022' and adds the Post Selection shortcode to the page content.",
							'acf-views'
						)
					);
					?>
				</li>
			</ol>

			<hr>
			<p>
				<b>
					<?php
					echo esc_html__( 'Importing', 'acf-views' );
					?>
				</b>
				<br>
			</p>

			<p class="av-introduction__description">
				<?php
				echo esc_html( __( 'Click Import Demo and wait a few seconds.', 'acf-views' ) );
				?>
				<?php
				echo esc_html(
					__(
						"When done, you'll see quick links to edit all imported items.",
						'acf-views'
					)
				);
				?>
			</p>

			<p>
				<b>
					<?php
					echo esc_html__( 'After Import', 'acf-views' );
					?>
				</b>
				<br>
			</p>

			<p>
			<?php
					echo esc_html__( 'A Delete Demo button will appear if you’d like to remove the demo content later.', 'acf-views' );
			?>
				<br>
			</p>

			<hr>

			<br><br>
			
			<button class="button button-primary button-large" name="_import" value="import">
				<?php
				echo esc_html( __( 'Import Demo Now', 'acf-views' ) );
				?>
			</button>
			<?php
		}
		?>
		</div>
	</div>
</form>
