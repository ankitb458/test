/**
 * Payable status
 */
CREATE TYPE status_payable AS enum (
	'trash',
	'inherit',
	'draft',
	'pending',
	'cancelled',
	'reversed',
	'future',
	'cleared'
	);

