CREATE OR REPLACE FUNCTION digest(text, text)
RETURNS bytea
AS '$libdir/pgcrypto', 'pg_digest'
LANGUAGE C IMMUTABLE STRICT;

CREATE OR REPLACE FUNCTION digest(bytea, text)
RETURNS bytea
AS '$libdir/pgcrypto', 'pg_digest'
LANGUAGE C IMMUTABLE STRICT;

CREATE OR REPLACE FUNCTION hmac(text, text, text)
RETURNS bytea
AS '$libdir/pgcrypto', 'pg_hmac'
LANGUAGE C IMMUTABLE STRICT;

CREATE OR REPLACE FUNCTION hmac(bytea, bytea, text)
RETURNS bytea
AS '$libdir/pgcrypto', 'pg_hmac'
LANGUAGE C IMMUTABLE STRICT;

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

CREATE OR REPLACE FUNCTION encrypt(bytea, bytea, text)
RETURNS bytea
AS '$libdir/pgcrypto', 'pg_encrypt'
LANGUAGE C IMMUTABLE STRICT;

CREATE OR REPLACE FUNCTION decrypt(bytea, bytea, text)
RETURNS bytea
AS '$libdir/pgcrypto', 'pg_decrypt'
LANGUAGE C IMMUTABLE STRICT;

CREATE OR REPLACE FUNCTION encrypt_iv(bytea, bytea, bytea, text)
RETURNS bytea
AS '$libdir/pgcrypto', 'pg_encrypt_iv'
LANGUAGE C IMMUTABLE STRICT;

CREATE OR REPLACE FUNCTION decrypt_iv(bytea, bytea, bytea, text)
RETURNS bytea
AS '$libdir/pgcrypto', 'pg_decrypt_iv'
LANGUAGE C IMMUTABLE STRICT;

CREATE OR REPLACE FUNCTION gen_random_bytes(int4)
RETURNS bytea
AS '$libdir/pgcrypto', 'pg_random_bytes'
LANGUAGE 'C' VOLATILE STRICT;
