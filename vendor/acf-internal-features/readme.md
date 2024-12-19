### ACF Pro Features Dependency

#### 1. Reasons

Advanced Views was initially developed as an ACF addon, and its user interface relies on ACF Pro features for essential
functionality.

Some of these features, such as Repeater, Clone fields, and Settings pages, are prerequisites for the plugin to operate.

#### 2. Legal Aspects

The license for ACF Pro (GPL v2 or later) is fully compatible with the licensing terms of Advanced Views.

This compatibility allows for the seamless integration and use of ACF Pro within the current context.

#### 3. Ethical Considerations

We hold great respect for the ACF team's contributions and have ensured that the inclusion of ACF Pro features in
Advanced Views does not compromise or undermine their efforts.

We have incorporated these features in a private manner, only on admin pages related to our plugin, making them
unavailable to the end user.
In addition, the following done:

* 'acf_add_options_page' function is removed
* Repeater and Clone fields have been marked as private
* The 'Gutenberg block' feature is exclusively accessible with the 'Advanced Custom Fields PRO' plugin.

#### 4. Performance

ACF Pro features are exclusively required on the admin pages of the Advanced Views plugin. They are not loaded on the
frontend or any other administrative pages, thereby minimizing any impact on performance outside the plugin's designated
functionalities.

#### 5. Version Compatibility

These dependencies are based on ACF Pro version 6.2.1 and above.