<?php
// stub for the multilingual detection 
// labels and descriptions will be translated on the fly (from the PHPDoc comments) 

// Card_Data.php : labels

__("General", "acf-views");
__("View", "acf-views");
__("Source", "acf-views");
__("Post Type", "acf-views");
__("Post Status", "acf-views");
__("Maximum number of posts", "acf-views");
__("Sort by", "acf-views");
__("Sort by Meta Field Group", "acf-views");
__("Sort by Meta Field", "acf-views");
__("Sort order", "acf-views");
__("Advanced", "acf-views");
__("Description", "acf-views");
__("No Posts Found Message", "acf-views");
__("Pool of posts", "acf-views");
__("Exclude posts", "acf-views");
__("Ignore Sticky Posts", "acf-views");
__("Template Engine", "acf-views");
__("Web Component Type", "acf-views");
__("Classes generation", "acf-views");
__("Use the Post ID as the Card ID in the markup", "acf-views");
__("Query Preview", "acf-views");
__("Custom Data", "acf-views");
__("Template", "acf-views");
__("Default Template", "acf-views");
__("Custom Template", "acf-views");
__("BEM Unique Name", "acf-views");
__("CSS classes", "acf-views");
__("CSS & JS", "acf-views");
__("CSS Code", "acf-views");
__("JS Code", "acf-views");
__("Layout", "acf-views");
__("Enable Slider", "acf-views");
__("Enable Layout rules", "acf-views");
__("Layout Rules", "acf-views");
__("Meta Filters", "acf-views");
__("Taxonomy Filters", "acf-views");
__("Rules", "acf-views");
__("Pagination", "acf-views");
__("With Pagination", "acf-views");
__("Pagination Type", "acf-views");
__("'Load More' button label", "acf-views");
__("Posts Per Page", "acf-views");
__("Preview", "acf-views");
__("Preview", "acf-views");

// Card_Data.php : descriptions

__("Assigned View is used to display every post from the query results.", "acf-views");
__("'Posts Query (WP_query)' will get posts based on the defined query, while 'Page Context (archive pages)' will get posts from where the Card is inserted (e.g. archive, author or category).", "acf-views");
__("Filter by post type. Multiple types can be selected.", "acf-views");
__("Filter by post status. Multiple types can be selected.", "acf-views");
__("Use '-1' for unlimited.", "acf-views");
__("Sort results by selecting an option. <br> 'Default' keeps the default order: latest first, while sticky flags may affect it.", "acf-views");
__("Select a target group.", "acf-views");
__("Select a target field.", "acf-views");
__("Defines the sorting order of posts.", "acf-views");
__("Add a short description for your Card's purpose. Only seen on the admin Cards list.", "acf-views");
__("Add a message that will be displayed if there are no results. Leave empty for no message.", "acf-views");
__("Manually assign specific posts to limit the query to. Only this pool of posts will then be considered for other filters. The 'Pool of posts' option can be selected to 'Sort by'.", "acf-views");
__("Here you can manually exclude specific posts from the query. It means the query will ignore posts from this list, even if they fit the filters. Warning : this field can't be used together with 'Pool of posts'.", "acf-views");
__("If unchecked then sticky posts will be at the top of results. <a target='_blank' href='https://wordpress.org/support/article/sticky-posts/'>Learn more about Sticky Posts</a>.", "acf-views");
__("Choose one of the <a target='_blank' href='https://docs.acfviews.com/templates/template-engines'>supported template engines</a>, which will be used for this Card.", "acf-views");
__("By default, every Card is a <a target='_blank' href='https://docs.acfviews.com/templates/css-and-js#web-components-for-js-code'>web component</a>, which allows you to work easily with the element in the JS code field. <br><br> Set it to 'None' if you're going to use the <a target='_blank' href='https://docs.acfviews.com/templates/wordpress-interactivity-api'>WP Interactivity API</a>.", "acf-views");
__("Controls classes generation in the Default Template.", "acf-views");
__("Note: For backward compatibility purposes only. Enable this option if you have external CSS selectors that rely on outdated digital IDs.", "acf-views");
__("For debug purposes. Here you can see the query that will be executed to get posts for this card. Important! Publish or Update your card and reload the page to see the latest query.", "acf-views");
__("Using the Custom Card Data PHP snippet you can add extra variables to the template, extra arguments to the <a target='_blank' href='https://developer.wordpress.org/reference/classes/wp_query/#parameters'>WP_Query instance</a>, and define the ajax handler. <a target='_blank' href='https://docs.acfviews.com/query-content/custom-data-pro'>Read more</a> <br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace).", "acf-views");
__("Output preview of the generated <a target='_blank' href='https://docs.acfviews.com/templates/template-engines/twig'>Twig</a> or <a target='_blank' href='https://docs.acfviews.com/templates/template-engines/blade'>Blade</a> template. <br> Important! Publish or Update your view to see the latest markup.", "acf-views");
__("Write your own template with full control over the HTML markup. <br> Copy the Default Template code and make your changes. <br><br> Check out our Docs to learn more about <a target='_blank' href='https://docs.acfviews.com/templates/template-engines/twig'>Twig</a> or <a target='_blank' href='https://docs.acfviews.com/templates/template-engines/blade'>Blade</a> features. <br><br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace). <br><br> Make sure you've retained all the default classes; otherwise, pagination won't work.", "acf-views");
__("Define a unique <a target='_blank' href='https://getbem.com/introduction/'>BEM name</a> for the element that will be used in the markup, or leave it empty to use the default ('acf-card').", "acf-views");
__("Add a class name without a dot (e.g. 'class-name') or multiple classes with single space as a delimiter (e.g. 'class-name1 class-name2'). These classes are added to the wrapping HTML element. <a target='_blank' href='https://www.w3schools.com/cssref/sel_class.asp'>Learn more about CSS Classes</a>.", "acf-views");
__("Define your CSS style rules. <br> This will be added within &lt;style&gt;&lt;/style&gt; tags ONLY to pages that have this card. <br><br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace). <br><br> Don't style the View fields here, each View has its own CSS field for this goal. <br><br> Magic shortcuts are available (and will use the BEM Unique Name if defined) : <br><br> '#card' will be replaced with '.acf-card--id--X' (or '.bem-name'). <br> '#this__' will be replaced with '.acf-card__' (or '.bem-name__'). <br><br> We recommend using #card { #this__items { //... }, #this__heading { //... } } format, which is possible thanks to the <a target='_blank' href='https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_nesting/Using_CSS_nesting'>built-in CSS nesting</a>. <br><br> Alternatively, you can use '#card__', will be replaced with '.acf-card--id--X .acf-card__' (or '.bem-name .bem-name__').", "acf-views");
__("Add Custom Javascript code to your Card.<br><br> By default, the Card is a <a target='_blank' href='https://docs.acfviews.com/templates/css-and-js#id-4.1-web-components'>web component</a>, so this code will be executed once for every instance, and 'this', that refers to the current instance, is available. <br><br> If the Web Component Type is set to none, the js code here is plain, and can be used for any goals, including <a target='_blank' href='https://docs.acfviews.com/templates/wordpress-interactivity-api'>WP Interactivity API</a>. <br><br> The code snippet will be added within &lt;script type='module'&gt;&lt;/script&gt; tags ONLY to pages that have this Card. <br><br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace).", "acf-views");
__("Select the slider library to enable. <br> Customize the slider after saving, by editing the JS Code in the CSS & JS tab.", "acf-views");
__("When enabled CSS layout styles are added to the CSS Code field. These styles are automatically updated each time you save. <br>Tip: If you’d like to edit the Layout CSS manually, simply disable this option. Disabling this does not remove the previously added CSS Code.", "acf-views");
__("The rules control the layout of Card items. <br>Note: These rules are inherited from small to large. For example: If you’ve set up 'Mobile' and 'Desktop' screen rules, then 'Tablet' will have the same rules as 'Mobile' and 'Large Desktop' will have the same rules as 'Desktop'.", "acf-views");
__("If enabled then the selected pagination type is applied and the 'Posts per page' rule takes effect. <a target='_blank' href='https://docs.acfviews.com/query-content/pagination-pro'>Read more</a>.", "acf-views");
__("Defines a way in which user can load more. For 'Load More Button' and 'Page Numbers' cases a special markup will be added to the card automatically, you can style it (using the 'CSS Code' field in the 'Advanced' tab).", "acf-views");
__("Define a Custom label for the load more button.", "acf-views");
__("Controls how many posts will be displayed initially and how many posts will be appended every time when user triggers 'Load More'. Total amount of posts is limited by the 'Maximum number of posts' field in the 'General' tab.", "acf-views");
__("See an output preview of your Card, where you can test some CSS styles. <a target='_blank' href='https://docs.acfviews.com/getting-started/introduction/plugin-interface#preview-1'>Read more</a> <br> Styles from your front page are included in the preview (some differences may appear). <br>Note: Press 'Update' if you have changed Custom Markup (in the Template tab) to see the latest preview. <br> After testing: Copy and paste the Card styles to the CSS Code field. <br> Important! Don't style your View here, instead use the CSS Code field in your View for this goal.", "acf-views");

// Card_Data.php : buttons

__("Add Rule", "acf-views");

// Card_Data.php : choices

__("Posts Query (WP_Query)", "acf-views");
__("Page context (archive pages)", "acf-views");
__("Default", "acf-views");
__("ID", "acf-views");
__("Menu order", "acf-views");
__("Meta value", "acf-views");
__("Meta value numeric", "acf-views");
__("Author", "acf-views");
__("Title", "acf-views");
__("Name", "acf-views");
__("Type", "acf-views");
__("Date", "acf-views");
__("Modified", "acf-views");
__("Parent", "acf-views");
__("Random", "acf-views");
__("Comment count", "acf-views");
__("Pool of posts", "acf-views");
__("Ascending", "acf-views");
__("Descending", "acf-views");
__("Twig", "acf-views");
__("Blade (requires PHP >= 8.2.0)", "acf-views");
__("Classic (no CSS isolation)", "acf-views");
__("Declarative Shadow DOM (CSS isolated", "acf-views");
__("", "acf-views");
__("JS Shadow DOM (CSS isolated", "acf-views");
__("", "acf-views");
__("None", "acf-views");
__("BEM style", "acf-views");
__("None", "acf-views");
__("None", "acf-views");
__("Splide v4 (29.8KB js", "acf-views");
__("", "acf-views");
__("Load More Button", "acf-views");
__("Infinity Scroll", "acf-views");
__("Page Numbers", "acf-views");

// Card_Layout_Data.php : labels

__("Screen Size", "acf-views");
__("Layout", "acf-views");
__("Amount of Columns", "acf-views");
__("Horizontal gap", "acf-views");
__("Vertical gap", "acf-views");

// Card_Layout_Data.php : descriptions

__("Controls to which screen size the rule applies", "acf-views");
__("Change the type of layout", "acf-views");
__("Define how many columns each row should have. By default, columns have equal width", "acf-views");
__("Horizontal gap between items. Format: '10px'. Possible units are 'px', '%', 'em/rem'", "acf-views");
__("Vertical gap between items. Format: '10px'. Possible units are 'px', '%', 'em/rem'", "acf-views");

// Card_Layout_Data.php : choices

__("Mobile", "acf-views");
__("Tablet (> 576px)", "acf-views");
__("Desktop (> 992px)", "acf-views");
__("Large Desktop (> 1400px)", "acf-views");
__("Row", "acf-views");
__("Column", "acf-views");
__("Grid", "acf-views");

// Demo_Group.php : labels

__("Brand", "acf-views");
__("Model", "acf-views");
__("Price", "acf-views");
__("Website link", "acf-views");

// Demo_Group.php : choices

__("Samsung", "acf-views");
__("Nokia", "acf-views");
__("HTC", "acf-views");
__("Xiaomi", "acf-views");

// Field_Data.php : labels

__("Field", "acf-views");
__("Label", "acf-views");
__("Link Label", "acf-views");
__("Image Size", "acf-views");
__("View", "acf-views");
__("Gallery layout", "acf-views");
__("Enable Lightbox", "acf-views");
__("Enable Slider", "acf-views");
__("Field Options", "acf-views");
__("Identifier", "acf-views");
__("Default Value", "acf-views");
__("Show When Empty", "acf-views");
__("Open link in a new tab", "acf-views");
__("Map Marker Icon", "acf-views");
__("Map Marker icon title", "acf-views");
__("Hide Map", "acf-views");
__("Show address from the map", "acf-views");
__("Values delimiter", "acf-views");
__("Map address format", "acf-views");
__("Masonry: Row Min Height", "acf-views");
__("Masonry: Gutter", "acf-views");
__("Masonry: Mobile Gutter", "acf-views");
__("With Lightbox", "acf-views");

// Field_Data.php : descriptions

__("Select a target field", "acf-views");
__("If filled will be added to the markup as a prefix label of the field above", "acf-views");
__("You can set the link label here. Leave empty to use the default", "acf-views");
__("Controls the size of the image, it changes the image src", "acf-views");
__("If filled then data within this field will be displayed using the selected View. <a target='_blank' href='https://docs.acfviews.com/display-acf-fields/relational-group/relationship#display-fields-from-related-post-pro-feature'>Read more</a>", "acf-views");
__("Select the gallery layout type. Customize the layout after saving, by editing the JS Code in the CSS & JS tab", "acf-views");
__("Select the lightbox library to enable. Customize the lightbox after saving, by editing the JS Code in the CSS & JS tab", "acf-views");
__("Select the slider library to enable. <br> Customize the slider after saving, by editing the JS Code in the CSS & JS tab", "acf-views");
__("Used in the markup. <br> Allowed symbols : letters, numbers, underline and dash. <br> Important! Should be unique within the View", "acf-views");
__("Set up default value, only used when the field is empty", "acf-views");
__("By default, empty fields are hidden. <br> Turn on to show even when field has no value", "acf-views");
__("By default, this setting is inherited from ACF, if available. Turn it on to always open in a new tab", "acf-views");
__("Customize the Map Marker by using your own icon or uploading an image from <a target='_blank' href='https://www.flaticon.com/free-icons/google-maps'>Flaticon</a> (.png, .jpg allowed). <br> Dimensions of 32x32px is recommended", "acf-views");
__("Shown when mouse hovers on Map Marker", "acf-views");
__("The Map is shown by default. Turn this on to hide the map", "acf-views");
__("The address is hidden by default. Turn this on to show the address from the map", "acf-views");
__("If multiple values are chosen, you can define their delimiter here. HTML is supported", "acf-views");
__("Use these variables to format your map address: <br> &#36;street_number&#36;, &#36;street_name&#36;, &#36;city&#36;, &#36;state&#36;, &#36;post_code&#36;, &#36;country&#36; <br> HTML is also supported. If left empty the address is not shown.", "acf-views");
__("Minimum height of a row in px", "acf-views");
__("Margin between items in px", "acf-views");
__("Margin between items on mobile in px", "acf-views");
__("If enabled, image(s) will include a zoom icon on hover, and when clicked, a popup with a larger image will appear", "acf-views");

// Field_Data.php : choices

__("None", "acf-views");
__("Classic Masonry (macy v2", "acf-views");
__("", "acf-views");
__("Flat Masonry (acf-views", "acf-views");
__("", "acf-views");
__("Inline-Gallery (lightgallery v2", "acf-views");
__("", "acf-views");
__("", "acf-views");
__("None", "acf-views");
__("LightGallery v2 (47.1KB js", "acf-views");
__("", "acf-views");
__("Simple (no settings", "acf-views");
__("", "acf-views");
__("None", "acf-views");
__("Splide v4 (29.8KB js", "acf-views");
__("", "acf-views");

// Git_Repository.php : labels

__("Repository ID", "acf-views");
__("Access Token", "acf-views");
__("Repository Name", "acf-views");

// Git_Repository.php : descriptions

__("To retrieve your GitLab repository ID, follow these steps: 1. Open your repository. 2. Look for the 'Project Information' block on the right-hand side. 3. Click on the gear icon (project settings). 4. On the new page, copy the project ID field value.", "acf-views");
__("To retrieve your GitLab access token, follow these steps: 1. Open your GitLab profile. 2. Click on the 'Access Tokens' tab. 3. Create a new token with the 'api' scope. 4. Copy the token value. (You can also use Group and Project tokens if you've a paid GitLab account)", "acf-views");
__("Assign a name to your repository, which will appear as a new tab in the list table.", "acf-views");

// Item_Data.php : labels

__("Field", "acf-views");
__("Group", "acf-views");
__("Sub Fields", "acf-views");
__("Sub fields", "acf-views");

// Item_Data.php : descriptions

__("Select a target group", "acf-views");
__("Setup sub fields here", "acf-views");

// Item_Data.php : buttons

__("Add Sub Field", "acf-views");

// Meta_Field_Data.php : labels

__("Group", "acf-views");
__("Field", "acf-views");
__("Comparison", "acf-views");
__("Value", "acf-views");

// Meta_Field_Data.php : descriptions

__("Select a target group", "acf-views");
__("Select a target field", "acf-views");
__("Controls how field value will be compared", "acf-views");
__("Value that will be compared.<br>Can be empty, in case you want to compare with empty string.<br>Use <strong>&#36;post&#36;</strong> to pick up the actual ID or <strong>&#36;post&#36;.field-name</strong> to pick up field value dynamically. <br>Use <strong>&#36;now&#36;</strong> to pick up the current datetime dynamically. <br>Use <strong>&#36;query&#36;.my-field</strong> to pick up the query value (from &#36;_GET) dynamically. <br>Use <strong>&#36;custom-arguments&#36;.my-field</strong> to pick up the <a target='_blank' href='https://docs.acfviews.com/shortcode-attributes/common-arguments#custom-argument'>custom shortcode argument</a> value dynamically.", "acf-views");

// Meta_Field_Data.php : choices

__("Equal to", "acf-views");
__("Not Equal to", "acf-views");
__("Bigger than", "acf-views");
__("Bigger than or Equal to", "acf-views");
__("Less than", "acf-views");
__("Less than or Equal to", "acf-views");
__("Contains", "acf-views");
__("Does Not Contain", "acf-views");
__("Exists", "acf-views");
__("Does Not Exist", "acf-views");

// Meta_Filter_Data.php : labels

__("Relation", "acf-views");
__("Rules", "acf-views");

// Meta_Filter_Data.php : descriptions

__("Controls how meta rules will be joined within the meta query", "acf-views");
__("Rules for the meta query. Multiple rules are supported. <a target='_blank' href='https://docs.acfviews.com/query-content/meta-filters-pro'>Read more</a> <br>If you want to see the query that was created by your input, update the Card and reload the page. After have a look at the 'Query Preview' field in the 'Advanced' tab", "acf-views");

// Meta_Filter_Data.php : buttons

__("Add Rule", "acf-views");

// Meta_Filter_Data.php : choices

__("AND", "acf-views");
__("OR", "acf-views");

// Meta_Rule_Data.php : labels

__("Relation", "acf-views");
__("Fields", "acf-views");

// Meta_Rule_Data.php : descriptions

__("Controls how the meta fields will be joined within the meta rule", "acf-views");
__("Fields for the meta rule. Multiple fields are supported", "acf-views");

// Meta_Rule_Data.php : buttons

__("Add Field", "acf-views");

// Meta_Rule_Data.php : choices

__("AND", "acf-views");
__("OR", "acf-views");

// Mount_Point_Data.php : labels

__("Specific posts", "acf-views");
__("Post Types", "acf-views");
__("Mount Point", "acf-views");
__("Mount Position", "acf-views");
__("Shortcode Arguments", "acf-views");

// Mount_Point_Data.php : descriptions

__("Limit the mount point to only specific posts. Leave empty and use the 'Post Types' field to limit to specific post types", "acf-views");
__("Specific post types, to all items of which the shortcode should be mounted. Leave empty if you want to add to specific items only and use the 'Specific posts' field", "acf-views");
__("To which unique Word, String or HTML piece to Mount to. Together with the 'Mount Position' controls the placement. If left empty all the content will be used as a mount point", "acf-views");
__("Where the shortcode should be mounted", "acf-views");
__("Add arguments to the shortcode, e.g. 'user-with-roles'. Only the view/card 'id' argument is filled by default", "acf-views");

// Mount_Point_Data.php : choices

__("Before", "acf-views");
__("After", "acf-views");
__("Instead (replace)", "acf-views");

// Repeater_Field_Data.php : labels

__("Field", "acf-views");
__("Sub Field", "acf-views");

// Repeater_Field_Data.php : descriptions

__("This list contains fields for the selected repeater or group. <a target='_blank' href='https://www.advancedcustomfields.com/resources/repeater/'>Learn more about Repeater Fields</a>", "acf-views");

// Settings_Data.php : labels

__("General", "acf-views");
__("Development mode", "acf-views");
__("File system storage", "acf-views");
__("Live Reload mode: interval (in seconds)", "acf-views");
__("Live Reload mode: inactive delay (in seconds)", "acf-views");
__("Disable automatic reports", "acf-views");
__("Defaults", "acf-views");
__("Template engine", "acf-views");
__("Web components type", "acf-views");
__("Classes generation", "acf-views");
__("Sass Template (for File System Storage)", "acf-views");
__("TypeScript Template (for File System Storage)", "acf-views");
__("Git repositories", "acf-views");
__("Git Repositories", "acf-views");
__("Debugging", "acf-views");
__("Enhanced compatible mode", "acf-views");
__("Error logs", "acf-views");
__("Internal logs", "acf-views");
__("Generate debug dump", "acf-views");
__("Include specific Views data in your debug dump", "acf-views");
__("Include specific Cards data in your debug dump", "acf-views");

// Settings_Data.php : descriptions

__("Enable to display quick access links on the front and make error messages more detailed (both for admins only).", "acf-views");
__("Enable to store View and Card data inside the theme folder (instead of the database). <br> This allows you to edit files using your favourite editor (IDE), and do version control with auto sync. <a target='_blank' href='https://docs.acfviews.com/templates/file-system-storage'>Read more</a>", "acf-views");
__("Controls how often the refresh requests are sent when on-page Live Reload Mode is enabled. A smaller number means faster updates, but it also increases server load.", "acf-views");
__("Controls the period after which Live Reload Mode is paused when no mouse events are registered. A smaller number decreases server load but may increase your waiting time.", "acf-views");
__("Automatic error and usage reports to developers, enabling faster issue resolution and plugin improvement. <br> The reports do not include any private or sensitive information. <br> Note: In the Pro edition, the license key/domain pair is always sent, regardless of this setting.", "acf-views");
__("Controls the <a target='_blank' href='https://docs.acfviews.com/templates/template-engines'>template engine</a> setting for new Views and Cards.", "acf-views");
__("Controls the web component setting for new Views and Cards.", "acf-views");
__("Controls classes generation in the Default Template for new Views and Cards.", "acf-views");
__("When present, this value is used as the default for the 'style.scss' file of View and Card, which is useful e.g. when <a target='_blank' href='https://docs.acfviews.com/templates/file-system-storage#tailwind-usage'>Tailwind is in use</a>. <br> If skipped, 'style.scss' creation will be omitted.", "acf-views");
__("When present, this value is used as the default for the 'script.ts' file of View and Card. <br> If skipped, 'script.ts' creation will be omitted.", "acf-views");
__("By saving Views and Cards in your GitLab repository, you can create your own library and reuse them on other websites. <br> <a target='_blank' href='https://docs.acfviews.com/templates/reusable-components-library-pro'>Read more</a>", "acf-views");
__("Enable when you're stuck at the View or Card loading screen. <br> Reason: Advanced Views, by default, reduces unused scripts to speed up the editor loading, and this may cause issues in some rare cases.", "acf-views");
__("Contains PHP warnings and errors related to the plugin. The error logs are deleted upon plugin upgrade or deactivation.", "acf-views");
__("Contains plugin warnings and debug messages if the development mode is enabled. The logs are deleted upon plugin deactivation.", "acf-views");
__("Turn this on and click 'Save changes' to download the file. The above logs and other information about your server environment will be included. <br> Send this to Advanced Views Support on request.", "acf-views");
__("Select the View items related to your issue to include them in the debug dump.", "acf-views");
__("Select the Card items related to your issue to include them in the debug dump.", "acf-views");

// Settings_Data.php : buttons

__("Add Repository", "acf-views");

// Settings_Data.php : choices

__("Twig", "acf-views");
__("Blade (requires PHP >= 8.2.0)", "acf-views");
__("Classic (no CSS isolation)", "acf-views");
__("Declarative Shadow DOM (CSS isolated", "acf-views");
__("", "acf-views");
__("JS Shadow DOM (CSS isolated", "acf-views");
__("", "acf-views");
__("None", "acf-views");
__("BEM style", "acf-views");
__("None", "acf-views");

// Tax_Field_Data.php : labels

__("Taxonomy", "acf-views");
__("Comparison", "acf-views");
__("Static Term", "acf-views");
__("Dynamic Term", "acf-views");
__("Source meta group", "acf-views");
__("Source meta field", "acf-views");
__("Custom argument name", "acf-views");

// Tax_Field_Data.php : descriptions

__("Select a target taxonomy", "acf-views");
__("Controls how taxonomy will be compared", "acf-views");
__("Static term that will be compared.", "acf-views");
__("Dynamic term that will be compared.", "acf-views");
__("Choose a Group that contains the source meta field.", "acf-views");
__("Choose a Term field whose value should be used in the query.", "acf-views");
__("Enter the <a target='_blank' href='https://docs.acfviews.com/shortcode-attributes/common-arguments#custom-arguments'>custom shortcode argument</a> name which will be used in the query.", "acf-views");

// Tax_Field_Data.php : choices

__("Equal to", "acf-views");
__("Not Equal to", "acf-views");
__("Exists", "acf-views");
__("Does Not Exist", "acf-views");

// Tax_Filter_Data.php : labels

__("Relation", "acf-views");
__("Rules", "acf-views");

// Tax_Filter_Data.php : descriptions

__("Controls how taxonomy rules will be joined within the taxonomy query", "acf-views");
__("Rules for the taxonomy query. Multiple rules are supported. <a target='_blank' href='https://docs.acfviews.com/query-content/taxonomy-filters-pro'>Read more</a> <br> If you want to see the query that was created by your input, update the Card and reload the page. After have a look at the 'Query Preview' field in the 'Advanced' tab", "acf-views");

// Tax_Filter_Data.php : buttons

__("Add Rule", "acf-views");

// Tax_Filter_Data.php : choices

__("AND", "acf-views");
__("OR", "acf-views");

// Tax_Rule_Data.php : labels

__("Relation", "acf-views");
__("Taxonomies", "acf-views");

// Tax_Rule_Data.php : descriptions

__("Controls how the taxonomies will be joined within the taxonomy rule", "acf-views");
__("Taxonomies for the taxonomy rule. Multiple taxonomies are supported", "acf-views");

// Tax_Rule_Data.php : buttons

__("Add Taxonomy", "acf-views");

// Tax_Rule_Data.php : choices

__("AND", "acf-views");
__("OR", "acf-views");

// Tools_Data.php : labels

__("Export", "acf-views");
__("Export All Views", "acf-views");
__("Export All Cards", "acf-views");
__("Export Views", "acf-views");
__("Export Cards", "acf-views");
__("Import", "acf-views");
__("Select a file to import", "acf-views");

// Tools_Data.php : descriptions

__("Select Views to be exported", "acf-views");
__("Select Cards to be exported", "acf-views");
__("Note: Views and Cards with the same IDs are overridden.", "acf-views");

// View_Data.php : labels

__("Fields", "acf-views");
__("Fields", "acf-views");
__("Parent group (for Nested repeater or Flexible layout)", "acf-views");
__("Parent field", "acf-views");
__("Template", "acf-views");
__("Default Template", "acf-views");
__("Custom Template", "acf-views");
__("BEM Unique Name", "acf-views");
__("CSS classes", "acf-views");
__("Add classification classes to the markup", "acf-views");
__("Do not skip unused wrappers", "acf-views");
__("Custom Data", "acf-views");
__("CSS & JS", "acf-views");
__("CSS Code", "acf-views");
__("JS Code", "acf-views");
__("Options", "acf-views");
__("Description", "acf-views");
__("Register Gutenberg Block", "acf-views");
__("Template Engine", "acf-views");
__("Web Component Type", "acf-views");
__("Classes generation", "acf-views");
__("Render template when it's empty", "acf-views");
__("Use the Post ID as the View ID in the markup", "acf-views");
__("Use the Post ID in the Gutenberg block's name", "acf-views");
__("Preview", "acf-views");
__("Preview Object", "acf-views");
__("Preview", "acf-views");
__("With Gutenberg Block (ACF)", "acf-views");

// View_Data.php : descriptions

__("Assign fields to your View. <br> Tip: hover mouse on the field number column and drag to reorder.", "acf-views");
__("Choose a Parent group when setting up Nested repeater or Flexible layout.", "acf-views");
__("If you're making an internal View for the <a target='_blank' href='https://docs.acfviews.com/display-content/meta-fields/layout-fields/repeater-pro'>group</a>, <a target='_blank' href='https://docs.acfviews.com/display-content/meta-fields/layout-fields/repeater-pro'>repeater</a> or <a target='_blank' href='https://docs.acfviews.com/display-content/meta-fields/layout-fields/flexible-pro'>flexible</a> field, then fill out this field.", "acf-views");
__("Output preview of the generated <a target='_blank' href='https://docs.acfviews.com/templates/template-engines/twig'>Twig</a> or <a target='_blank' href='https://docs.acfviews.com/templates/template-engines/blade'>Blade</a> template. <br> Important! Publish or Update your view to see the latest markup.", "acf-views");
__("Write your own template with full control over the HTML markup. <br> Copy the Default Template code and make your changes. <br><br> Check out our Docs to learn more about <a target='_blank' href='https://docs.acfviews.com/templates/template-engines/twig'>Twig</a> or <a target='_blank' href='https://docs.acfviews.com/templates/template-engines/blade'>Blade</a> features. <br>Note: WordPress shortcodes inside the template are only supported in the Pro version. <br><br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace).", "acf-views");
__("Define a unique <a target='_blank' href='https://getbem.com/introduction/'>BEM name</a> for the element that will be used in the markup, or leave it empty to use the default ('acf-view').", "acf-views");
__("Add a class name without a dot (e.g. “class-name”) or multiple classes with single space as a delimiter (e.g. “class-name1 class-name2”). <br> These classes are added to the wrapping HTML element. <a target='_blank' href='https://www.w3schools.com/cssref/sel_class.asp'>Learn more about CSS Classes</a>.", "acf-views");
__("By default, the field name is added as a prefix to all inner classes. For example, the image within the 'avatar' field will have the '__avatar-image' class. <br> Enabling this setting adds the generic class as well, such as '__image'. This feature can be useful if you want to apply styles based on field types.", "acf-views");
__("By default, empty wrappers in the markup are skipped to optimize the output. For example, the '__row' wrapper will be skipped if there is no field label. <br> Enable this feature if you need all the wrappers in the output.", "acf-views");
__("Using the Custom View Data PHP snippet you can add custom variables to the template and define the ajax handler. <a target='_blank' href='https://docs.acfviews.com/display-content/custom-data-pro'>Read more</a> <br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace).", "acf-views");
__("Define your CSS style rules. <br> Rules defined here will be added within &lt;style&gt;&lt;/style&gt; tags ONLY to pages that have this View. <br><br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace). <br><br> Magic shortcuts are available (and will use the BEM Unique Name if defined) : <br><br> '#view' will be replaced with '.acf-view--id--X' (or '.bem-name'). <br> '#this__' will be replaced with '.acf-view__' (or '.bem-name__'). <br><br> We recommend using #view { #this__first-field { //... }, #this__second-field { //... } } format, which is possible thanks to the <a target='_blank' href='https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_nesting/Using_CSS_nesting'>built-in CSS nesting</a>. <br><br> Alternatively, you can use '#view__', which will be replaced with '.acf-view--id--X .acf-view__' (or '.bem-name .bem-name__').", "acf-views");
__("Add Custom Javascript code to your View. <br><br> By default, the View is a <a target='_blank' href='https://docs.acfviews.com/templates/css-and-js#id-4.1-web-components'>web component</a>, so this code will be executed once for every instance, and 'this', that refers to the current instance, is available. <br><br> If the Web Component Type is set to none, the js code here is plain, and can be used for any goals, including <a target='_blank' href='https://docs.acfviews.com/templates/wordpress-interactivity-api'>WP Interactivity API</a>. <br><br> The code snippet will be added within &lt;script type='module'&gt;&lt;/script&gt; tags ONLY to pages that have this View. <br><br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace).", "acf-views");
__("Add a short description for your Views’ purpose. <br> Note : This description is only seen on the admin Advanced Views list.", "acf-views");
__("If a block vendor is selected then a separate Gutenberg block for this View will be available. <a target='_blank' href='https://docs.acfviews.com/display-content/custom-gutenberg-blocks-pro'>Read more</a>.", "acf-views");
__("Choose one of the <a target='_blank' href='https://docs.acfviews.com/templates/template-engines'>supported template engines</a>, which will be used for this View.", "acf-views");
__("By default, every Card is a <a target='_blank' href='https://docs.acfviews.com/templates/css-and-js#web-components-for-js-code'>web component</a>, which allows you to work easily with the element in the JS code field. <br><br> Set it to 'None' if you're going to use the <a target='_blank' href='https://docs.acfviews.com/templates/wordpress-interactivity-api'>WP Interactivity API</a>.", "acf-views");
__("Controls classes generation in the Default Template.", "acf-views");
__("By default, if all the selected fields are empty, the Twig template won't be rendered. <br> Enable this option if you have specific logic inside the template and you want to render it even when all the fields are empty.", "acf-views");
__("Note: For backward compatibility purposes only. Enable this option if you have external CSS selectors that rely on outdated digital IDs.", "acf-views");
__("Note: For backward compatibility purposes only.", "acf-views");
__("Select a data object (which field values will be used) and update the View. After reload the page to see the markup in the preview.", "acf-views");
__("Here you can see the preview of the view and play with CSS rules. <a target='_blank' href='https://docs.acfviews.com/getting-started/introduction/plugin-interface#preview-1'>Read more</a><br>Important! Update the View after changes and reload the page to see the latest markup here. <br>Your changes to the preview won't be applied to the view automatically, if you want to keep them copy amended CSS to the 'CSS Code' field and press the 'Update' button. <br> Note: styles from your front page are included in the preview (some differences may appear).", "acf-views");
__("If checked, a separate Gutenberg block for this view will be available. <a target='_blank' href='https://docs.acfviews.com/display-content/custom-gutenberg-blocks-pro'>Read more</a>.", "acf-views");

// View_Data.php : buttons

__("Add Field", "acf-views");

// View_Data.php : choices

__("Off", "acf-views");
__("ACF Block", "acf-views");
__("Meta Box Block", "acf-views");
__("Pods Block", "acf-views");
__("Twig", "acf-views");
__("Blade (requires PHP >= 8.2.0)", "acf-views");
__("Classic (no CSS isolation)", "acf-views");
__("Declarative Shadow DOM (CSS isolated", "acf-views");
__("", "acf-views");
__("JS Shadow DOM (CSS isolated", "acf-views");
__("", "acf-views");
__("None", "acf-views");
__("BEM style", "acf-views");
__("None", "acf-views");