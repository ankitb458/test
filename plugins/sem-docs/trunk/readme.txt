
Extending Semiologic Pro Features
=================================

This piece of documentation is for use by resellers who wish to extend Semiologic's Feature screen for their customers.


1. Registering features
-----------------------

add to the skin.php or custom.php file:

	sem_features::register($feature_sets);

where:

	$feature_sets = array(
		'feature_set_key1' => array (
			'feature_key1',
			'feature_key2',
			),
		'feature_set_key2' => array
			(
			'feature_key3',
			'feature_key4',
			)
		);



2. Registering feature handlers
-------------------------------

add to the skin.php or custom.php file:

	sem_features::set_handler(
		$feature_key,
		$plugin_file,
		$activate,
		$deactivate,
		$is_enabled,
		);

where:

- $feature_key is the feature's key
- $plugin_files is a plugin file, e.g. 'my_plugin.php' (optional, can also be an array of files)
- $activate is a callback function that gets called when you activate (optional)
- $deactivate is a callback function that gets called when you deactivate (optional)
- $is_enabled is a callback function that gets called when you check if the feature is active (optional)


3. Extending docs
-----------------

The docs table's schema is the following.

CREATE TABLE IF NOT EXISTS $wpdb->sem_docs (
	doc_id			int PRIMARY KEY AUTO_INCREMENT,
	doc_cat			varchar(128) NOT NULL DEFAULT '',
	doc_key			varchar(128) NOT NULL DEFAULT '',
	doc_version		varchar(32) NOT NULL DEFAULT '',
	doc_name		varchar(256) NOT NULL DEFAULT '',
	doc_excerpt		text NOT NULL DEFAULT '',
	doc_content		text NOT NULL DEFAULT '',
	UNIQUE ( doc_cat, doc_key, doc_version )
	);

where:

- doc_cat is in admin, tips, features, feature_sets