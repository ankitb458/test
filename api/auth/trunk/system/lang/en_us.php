<?php
#
# MC System Captions
# ------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

# security
dict::set('security_threat', 'Security threat detected');

# sql errors
dict::set('err_db_connect', 'Failed to connect to the database');
dict::set('err_sql_error', '{$err_msg}: <pre>{$sql}</pre>');

# template errors
dict::set('undefined_template', 'Undefined template: {$template}');
dict::set('undefined_widget_method', 'Undefined widget method: {$method} in {$widget}');

# system templates
dict::set('login_template', 'Login Template');
dict::set('system_template', 'System Template');

# form errors
dict::set('required_field', '{$field} is a required field');
dict::set('invalid_email', '{$field} is not a valid email');
dict::set('field_mismatch', '{$field} mismatch');

dict::set('view', 'View');
dict::set('edit', 'Edit');
dict::set('page', 'Page');
dict::set('all', 'All');

dict::set('request_timeout', 'Request Timeout');

# user
dict::set('guest', 'Guest');

dict::set('user_id', 'ID');
dict::set('user_name', 'Name');
dict::set('user_phone', 'Phone');
dict::set('user_email', 'Email');
dict::set('user_pass', 'Password');
dict::set('new_pass', 'New Password');
dict::set('user_pass', 'Password');
dict::set('confirm_pass', 'Confirm it');
dict::set('expires', 'Expires');

dict::set('api_key', 'API Key');
dict::set('contact_details', 'Contact Details');

dict::set('new_user', 'New User');
dict::set('edit_user', 'Edit {$user_name}');
dict::set('save_user', 'Save User');
dict::set('save_profile', 'Save Profile');
dict::set('user_saved', '{$user_name} Saved');
dict::set('user_exists', 'A user with this email exists already');

dict::set('not_found', 'Not Found');
dict::set('user_not_found', 'User not found!');

dict::set('profiles', 'Profiles');
dict::set('profile_overview', 'Overview');
dict::set('profile_name', 'Name');
dict::set('profile_key', 'Key');
dict::set('profile_desc', 'Description');

dict::set('new_profile', 'New Profile');
dict::set('save_profile', 'Save Profile');
dict::set('profile_saved', 'Profile saved');

dict::set('domains', 'Domains');
dict::set('domain_overview', 'Overview');
dict::set('domain_name', 'Name');
dict::set('domain_desc', 'Description');
dict::set('domain_urls', 'Urls');

dict::set('new_domain', 'New Domain');
dict::set('domain_saved', '{$domain_name} saved');
dict::set('save_domain', 'Save Domain');

dict::set('grant_access', 'Grant Access');
dict::set('restrict_access', 'Restrict Access');
dict::set('invalid_url_handle', 'Ignoring invalid url: {$url_handle}');

dict::set('welcome', 'Welcome, {$user_name}!');
dict::set('goodbye', 'Goodbye, {$user_name}!');

dict::set('remind', 'Password Reminder');
dict::set('lost_password', 'Lost Password');
dict::set('send_details', 'Send Details');
dict::set('details_sent', 'Login details sent');

dict::set('login_failed', 'Login Failed');
dict::set('permission_denied', 'Permission Denied');
dict::set('permission_denied_details', 'This page is access-restricted.');

dict::set('register', 'Register');
dict::set('login', 'Login');
dict::set('profile', 'Profile');
dict::set('logout', 'Logout');

dict::set('profiles', 'Profiles');

dict::set('users', 'Users');


dict::set('caps', 'Capabilities');


dict::set('send_user_password_title',
	'Your {$site_name} details'
	);
dict::set('send_user_password_message',
	'Hello {$user_name} and welcome to {$site_name}!' . "\n"
	. "\n"
	. 'For reference, your registration details were:' . "\n"
	. "\n"
	. '- Site:     {$site_url}' . "\n"
	. '- Email:    {$user_email}' . "\n"
	. '- Password: {$user_pass}' . "\n"
	. "\n"
	. '{$site_signature}'
	);


dict::set('send_user_key_title',
	'{$site_name} details reminder'
	);
dict::set('send_user_key_message',
	'Hello {$user_name}!' . "\n"
	. "\n"
	. 'You\'ve requested your site details for {$site_name}.' . "\n"
	. "\n"
	. 'To log into the site using your API key, visit:' . "\n"
	. "\n"
	. '{$site_url}?user_key={$user_key}' . "\n"
	. "\n"
	. '{$site_signature}'
	);


dict::set('manage_users', 'Manage Users');
dict::set('manage_access', 'Manage Access');

dict::set('new', 'New');
dict::set('manage', 'Manage');
dict::set('user', 'User');
dict::set('domain', 'Domain');


dict::set('delete', 'Delete');
dict::set('node_deleted', '{$node_name} deleted');
?>