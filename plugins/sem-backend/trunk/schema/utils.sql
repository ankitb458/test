/**
 * Activatable status
 */
CREATE TYPE status_activatable AS enum (
	'trash',
	'inherit',
	'draft',
	'pending',
	'inactive',
	'future',
	'active'
	);

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
	'cleared'
	);

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
END $$ LANGUAGE plpgsql;

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
END $$ LANGUAGE plpgsql;

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
END $$ LANGUAGE plpgsql;

/**
 * Sluggable behavior
 *
 * Expects fields:
 * - {table}.ukey
 *
 * Creates procedures:
 * - {table}_ukey() and trigger (priority 10)
 */
CREATE OR REPLACE FUNCTION sluggable(varchar)
	RETURNS varchar
AS $$
DECLARE
	t_name		alias for $1;
BEGIN
	IF NOT column_exists(t_name, 'ukey')
	THEN
		EXECUTE $EXEC$
		ALTER TABLE $EXEC$ || quote_ident(t_name) || $EXEC$
			ADD COLUMN ukey varchar UNIQUE;
		$EXEC$;
	END IF;
	
	EXECUTE $EXEC$
	CREATE OR REPLACE FUNCTION $EXEC$ || quote_ident(t_name || '_ukey') || $EXEC$()
		RETURNS TRIGGER
	AS $DEF$
	DECLARE
		ukey_base	varchar;
		suffix		int := 1;
	BEGIN
		IF NEW.ukey IS NULL OR NEW.ukey = ''
		THEN
			NEW.ukey := NULL; -- forbid empty string as ukey
			RETURN NEW;
		END IF;
		
		-- todo:
		-- - scan for a min suffix instead of trying 2, then 3, etc.
		ukey_base = regexp_replace(NEW.ukey, E'-\\d+$', '');
		
		LOOP
			IF NOT EXISTS (
				SELECT	1
				FROM	$EXEC$ || quote_ident(t_name) || $EXEC$
				WHERE	ukey = NEW.ukey )
			THEN
				RETURN NEW;
			END IF;
			
			suffix := suffix + 1;
			NEW.ukey := ukey_base || '-' || suffix;
		END LOOP;
		
		RETURN NEW;
	END $DEF$ LANGUAGE plpgsql;
	$EXEC$;
	
	IF NOT trigger_exists(t_name || '_10_ukey')
	THEN
		EXECUTE $EXEC$
		CREATE TRIGGER $EXEC$ || quote_ident(t_name || '_10_ukey') || $EXEC$
			BEFORE INSERT OR UPDATE ON $EXEC$ || quote_ident(t_name) || $EXEC$
		FOR EACH ROW EXECUTE PROCEDURE $EXEC$ || quote_ident(t_name || '_ukey') || $EXEC$();
		$EXEC$;
	END IF;
	
	RETURN t_name;
END $$ LANGUAGE plpgsql;

/**
 * Timestampable behavior
 *
 * Adds fields:
 * - {table}.created_date
 * - {table}.modified_date
 *
 * Creates procedures:
 * - {table}_modified() and trigger (priority 10)
 */
CREATE OR REPLACE FUNCTION timestampable(varchar)
	RETURNS varchar
AS $$
DECLARE
	t_name		alias for $1;
BEGIN
	IF NOT column_exists(t_name, 'created_date')
	THEN
		EXECUTE $EXEC$
		ALTER TABLE $EXEC$ || quote_ident(t_name) || $EXEC$
			ADD COLUMN created_date timestamp(0) with time zone NOT NULL DEFAULT NOW();
		$EXEC$;
	END IF;
	
	IF NOT column_exists(t_name, 'modified_date')
	THEN
		EXECUTE $EXEC$
		ALTER TABLE $EXEC$ || quote_ident(t_name) || $EXEC$
			ADD COLUMN modified_date timestamp(0) with time zone NOT NULL DEFAULT NOW();
		$EXEC$;
	END IF;
	
	EXECUTE $EXEC$
	CREATE OR REPLACE FUNCTION $EXEC$ || quote_ident(t_name || '_modified') || $EXEC$()
		RETURNS TRIGGER
	AS $DEF$
	BEGIN
		NEW.modified_date := NOW();
		RETURN NEW;
	END $DEF$ LANGUAGE plpgsql;
	$EXEC$;
	
	IF NOT trigger_exists(t_name || '_10_modified')
	THEN
		EXECUTE $EXEC$
		CREATE TRIGGER $EXEC$ || quote_ident(t_name || '_10_modified') || $EXEC$
			BEFORE UPDATE ON $EXEC$ || quote_ident(t_name) || $EXEC$
		FOR EACH ROW EXECUTE PROCEDURE $EXEC$ || quote_ident(t_name || '_modified') || $EXEC$();
		$EXEC$;
	END IF;
	
	RETURN t_name;
END $$ LANGUAGE plpgsql;

/**
 * Searchable behavior
 *
 * Adds fields:
 * - {table}.tsv
 *
 * Creates procedures:
 * - {table}_tsv() and trigger (priority 20)
 */
CREATE OR REPLACE FUNCTION searchable(varchar)
	RETURNS varchar
AS $$
DECLARE
	t_name		alias for $1;
	stmt		text := '';
BEGIN
	IF NOT column_exists(t_name, 'tsv')
	THEN
		EXECUTE $EXEC$
		ALTER TABLE $EXEC$ || quote_ident(t_name) || $EXEC$
			ADD COLUMN tsv tsvector NOT NULL;
		$EXEC$;
	END IF;
	
	IF NOT index_exists(t_name, t_name || '_tsv')
	THEN
		EXECUTE $EXEC$
		CREATE INDEX $EXEC$ || quote_ident(t_name || '_tsv') || $EXEC$
			ON $EXEC$ || quote_ident(t_name) || $EXEC$ USING GIN(tsv);
		$EXEC$;
	END IF;
	
	stmt := $EXEC$
	CREATE OR REPLACE FUNCTION $EXEC$ || quote_ident(t_name || '_tsv') || $EXEC$()
		RETURNS TRIGGER
	AS $DEF$
	BEGIN
		NEW.tsv := ''::tsvector;$EXEC$;
	
	IF column_exists(t_name, 'name')
	THEN
		stmt := stmt || $EXEC$
		NEW.tsv := NEW.tsv ||
			setweight(to_tsvector(NEW.name), 'A');$EXEC$;
	END IF;
	
	IF column_exists(t_name, 'ukey')
	THEN
		stmt := stmt || $EXEC$
		NEW.tsv := NEW.tsv ||
			setweight(to_tsvector(COALESCE(regexp_replace(NEW.ukey, E'-\\d+$', ''), '')), 'B');$EXEC$;
	END IF;
	
	IF column_exists(t_name, 'memo')
	THEN
		stmt := stmt || $EXEC$
		NEW.tsv := NEW.tsv ||
			setweight(to_tsvector(NEW.memo), 'D');$EXEC$;
	END IF;
	
	stmt := stmt || $EXEC$
		
		RETURN NEW;
	END $DEF$ LANGUAGE plpgsql;
	$EXEC$;
	
	EXECUTE stmt;
	
	IF NOT trigger_exists(t_name || '_20_tsv')
	THEN
		EXECUTE $EXEC$
		CREATE TRIGGER $EXEC$ || quote_ident(t_name || '_20_tsv') || $EXEC$
			BEFORE INSERT OR UPDATE ON $EXEC$ || quote_ident(t_name) || $EXEC$
		FOR EACH ROW EXECUTE PROCEDURE $EXEC$ || quote_ident(t_name || '_tsv') || $EXEC$();
		$EXEC$;
	END IF;
	
	RETURN t_name;
END $$ LANGUAGE plpgsql;

/**
 * Trashable behavior
 *
 * Adds rules:
 * - {table}_check_trash
 */
CREATE OR REPLACE FUNCTION trashable(varchar)
	RETURNS varchar
AS $$
DECLARE
	t_name		alias for $1;
BEGIN	
	EXECUTE $EXEC$
	CREATE OR REPLACE FUNCTION $EXEC$ || quote_ident(t_name || '_check_trash') || $EXEC$()
		RETURNS TRIGGER
	AS $DEF$
	BEGIN
		IF OLD.status > 'inherit'
		THEN
			RAISE EXCEPTION 'Failed to delete $EXEC$ || t_name || $EXEC$.id = %. Trash it first.', OLD.id;
		END IF;
		
		RETURN OLD;
	END $DEF$ LANGUAGE plpgsql;
	$EXEC$;
	
	IF NOT trigger_exists(t_name || '_20_check_trash')
	THEN
		EXECUTE $EXEC$
		CREATE TRIGGER $EXEC$ || quote_ident(t_name || '_20_check_trash') || $EXEC$
			AFTER DELETE ON $EXEC$ || quote_ident(t_name) || $EXEC$
		FOR EACH ROW EXECUTE PROCEDURE $EXEC$ || quote_ident(t_name || '_check_trash') || $EXEC$();
		$EXEC$;
	END IF;
	
	RETURN t_name;
END $$ LANGUAGE plpgsql;

