### 1. Folder name

The `vendor` folder is a necessary name, to exclude all the content from the `wp i18n` tool check during `translate.wordpress.org`
release updates.

### 2. ACF Dependency

#### 2.1) Reasons

Advanced Views was originally developed as an ACF addon, and its user interface is closely integrated with ACF plugin
features. Therefore, the ACF plugin is a required dependency.

The ACF plugin's license (GPL v2 or later) is fully compatible with the license of Advanced Views, allowing its use in
the current context.

#### 2.2) Performance

ACF is only required on the admin pages of the Advanced Views plugin. It is not loaded on the frontend or any other
admin pages, ensuring that it has a minimal impact on performance outside of the plugin's specific functionalities.

### 3.) ACF internal features 

See the readme inside the `acf-internal-features` folder.