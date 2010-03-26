/**
 * Billable status
 */
CREATE TYPE status_billable AS enum (
	'trash',
	'inherit',
	'draft',
	'pending',
	'cancelled',
	'reversed',
	'future',
	'cleared'
	);

