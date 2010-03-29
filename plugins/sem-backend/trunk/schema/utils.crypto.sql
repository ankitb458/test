/**
 * Password hashing functions
 *
 * DB-centric password hashing that can be used in combination
 * with phpass v.0.2 using a blowfish 2^10 salt.
 */

CREATE OR REPLACE FUNCTION crypt(text, text)
RETURNS text
AS '$libdir/pgcrypto', 'pg_crypt'
LANGUAGE C IMMUTABLE STRICT;

CREATE OR REPLACE FUNCTION gen_salt(text)
RETURNS text
AS '$libdir/pgcrypto', 'pg_gen_salt'
LANGUAGE C VOLATILE STRICT;

CREATE OR REPLACE FUNCTION gen_salt(text, int4)
RETURNS text
AS '$libdir/pgcrypto', 'pg_gen_salt_rounds'
LANGUAGE C VOLATILE STRICT;
