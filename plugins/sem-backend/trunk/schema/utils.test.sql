\set QUIET on
\set ON_ERROR_STOP off

ROLLBACK;
\i ./utils.reset.sql

\set ON_ERROR_STOP on

BEGIN;
\i ./utils.init.sql
COMMIT;

\set ON_ERROR_STOP off

\i ./users.test.sql
\i ./products.test.sql

\set QUIET off
