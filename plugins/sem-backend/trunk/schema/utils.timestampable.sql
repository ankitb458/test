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
