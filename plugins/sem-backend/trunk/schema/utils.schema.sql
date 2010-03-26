/**
 * uuid()
 *
 * Used to set defaults for uuid fields.
 */
CREATE OR REPLACE FUNCTION uuid()
	RETURNS uuid
AS '$libdir/uuid-ossp', 'uuid_generate_v4'
VOLATILE STRICT LANGUAGE C;

/**
 * @param string The table's name
 * @param string The column's name
 * @return boolean Whether table has that column already
 */
CREATE OR REPLACE FUNCTION column_exists(varchar, varchar)
	RETURNS boolean
AS $$
DECLARE
	t_name		alias for $1;
	c_name		alias for $2;
BEGIN
	RETURN EXISTS (
		SELECT	1
		FROM	information_schema.columns
		WHERE	table_name = t_name
		AND		column_name = c_name );
END;
$$ LANGUAGE plpgsql;

/**
* @param string The table's name
* @param string The index' name
* @return boolean Whether table has that trigger already
 */
CREATE OR REPLACE FUNCTION index_exists(varchar, varchar)
	RETURNS boolean
AS $$
DECLARE
	t_name		alias for $1;
	i_name		alias for $2;
BEGIN
	RETURN EXISTS (
		SELECT	1
		FROM	pg_catalog.pg_class c
		JOIN	pg_catalog.pg_index i
		ON		i.indexrelid = c.oid
		JOIN	pg_catalog.pg_class c2
		ON		i.indrelid = c2.oid
		LEFT JOIN pg_catalog.pg_namespace n
		ON		n.oid = c.relnamespace
		WHERE	n.nspname = 'public'
		AND		c.relkind = 'i'
		AND		n.nspname NOT IN ('pg_catalog', 'pg_toast')
		AND		pg_catalog.pg_table_is_visible(c.oid)
		AND		c.relname = i_name
		AND		c2.relname = t_name );
END;
$$ LANGUAGE plpgsql;

/**
* @param string The trigger's name
* @return boolean Whether the trigger already
 */
CREATE OR REPLACE FUNCTION trigger_exists(varchar)
	RETURNS boolean
AS $$
DECLARE
	tg_name		alias for $1;
BEGIN
	RETURN EXISTS (
		SELECT	1
		FROM	information_schema.triggers
		WHERE	trigger_name = quote_literal(tg_name) );
END;
$$ LANGUAGE plpgsql;
