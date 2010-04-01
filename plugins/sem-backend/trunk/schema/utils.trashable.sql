/**
 * Trashable behavior
 *
 * Adds triggers:
 * - {table}_01__check_trash
 */
CREATE OR REPLACE FUNCTION trashable(varchar)
	RETURNS varchar
AS $$
DECLARE
	t_name		alias for $1;
BEGIN
	EXECUTE $EXEC$
	CREATE OR REPLACE FUNCTION $EXEC$ || quote_ident(t_name || '__check_trash') || $EXEC$()
		RETURNS TRIGGER
	AS $DEF$
	BEGIN
		IF	OLD.status > 'inherit'
		THEN
			RAISE EXCEPTION 'Cannot trash $EXEC$ || t_name || $EXEC$.id = %. It must be trashed first.',
				OLD.id;
		END IF;
		
		RETURN OLD;
	END;
	$DEF$ LANGUAGE plpgsql;
	$EXEC$;
	
	EXECUTE $EXEC$
	CREATE OR REPLACE RULE $EXEC$ || quote_ident(t_name || '__auto_trash') || $EXEC$
	AS ON DELETE TO $EXEC$ || quote_ident(t_name) || $EXEC$
	WHERE	status > 'inherit'
	DO INSTEAD
	UPDATE	 $EXEC$ || quote_ident(t_name) || $EXEC$
	SET		status = 'trash'
	WHERE	id = OLD.id;
	$EXEC$;
	
	IF	NOT trigger_exists(t_name || '_01__check_trash')
	THEN
		EXECUTE $EXEC$
		CREATE TRIGGER $EXEC$ || quote_ident(t_name || '_01__check_trash') || $EXEC$
			AFTER DELETE ON $EXEC$ || quote_ident(t_name) || $EXEC$
		FOR EACH ROW EXECUTE PROCEDURE $EXEC$ || quote_ident(t_name || '__check_trash') || $EXEC$();
		$EXEC$;
	END IF;
	
	RETURN t_name;
END;
$$ LANGUAGE plpgsql;
