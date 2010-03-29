/**
 * email domain
 */

CREATE DOMAIN email as varchar CHECK ( is_email(value) );

/**
 * Validates against rfc822
 */
CREATE OR REPLACE FUNCTION is_email(varchar)
	RETURNS boolean
AS $$
	use strict;
	use Email::Valid;
	my $address = $_[0];
	my $checks = {
	   -address => $address,
	   -mxcheck => 0,
	   -tldcheck => 0,
	   -rfc822 => 1,
	};
	if ( defined Email::Valid->address(%$checks) ) {
		return 'true'
	}
	else {
		#elog(WARNING, "$address failed $Email::Valid::Details check.");
		return 'false';
	}
$$ LANGUAGE plperlu IMMUTABLE STRICT;