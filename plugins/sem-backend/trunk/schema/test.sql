BEGIN;
\i ./reset.sql
\i ./init.sql

\i ./products.test.sql

COMMIT;
