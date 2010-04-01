/**
 * Timestampable behavior
 *
 * Adds fields:
 * - {table}.created
 * - {table}.modified
 *
 * Adds triggers:
 * - {table}_10__modified()
 */
CREATE OR REPLACE FUNCTION timestampable(varchar)
	RETURNS varchar
AS $$
DECLARE
	t_name		alias for $1;
BEGIN
	EXECUTE $EXEC$
	CREATE OR REPLACE FUNCTION $EXEC$ || quote_ident(t_name || '__modified') || $EXEC$()
		RETURNS TRIGGER
	AS $DEF$
	BEGIN
		NEW.modified := NOW();
		RETURN NEW;
	END;
	$DEF$ LANGUAGE plpgsql;
	$EXEC$;
	
	IF	NOT trigger_exists(t_name || '_10__modified')
	THEN
		EXECUTE $EXEC$
		CREATE TRIGGER $EXEC$ || quote_ident(t_name || '_10__modified') || $EXEC$
			BEFORE UPDATE ON $EXEC$ || quote_ident(t_name) || $EXEC$
		FOR EACH ROW EXECUTE PROCEDURE $EXEC$ || quote_ident(t_name || '__modified') || $EXEC$();
		$EXEC$;
	END IF;
	
	RETURN t_name;
END;
$$ LANGUAGE plpgsql;
