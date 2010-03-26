BEGIN;
\i ./utils.reset.sql
\i ./utils.init.sql
COMMIT;

\i ./products.test.sql