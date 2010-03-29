/**
 * Authenticatable status
 */
CREATE TYPE status_authenticatable AS enum (
	'trash',
	'inherit',
	'pending',
	'banned',
	'inactive',
	'locked',
	'active'
	);
