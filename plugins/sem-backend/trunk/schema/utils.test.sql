\set QUIET on
\set ON_ERROR_STOP off
\pset tuples_only off

ROLLBACK;
\i ./utils.reset.sql

\set ON_ERROR_STOP on

BEGIN;
\i ./utils.init.sql
COMMIT;

\set ON_ERROR_STOP off
\pset tuples_only on

\i ./users.test.sql
\i ./products.test.sql
\i ./campaigns.test.sql

\pset tuples_only off
\set QUIET off
