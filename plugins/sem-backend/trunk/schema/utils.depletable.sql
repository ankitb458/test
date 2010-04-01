/**
 * Depletable behavior
 *
 * Adds fields:
 * - {field}
 *
 * Adds constraint:
 * - valid_{field}
 */
CREATE OR REPLACE FUNCTION depletable(varchar, varchar)
	RETURNS varchar
AS $$
DECLARE
	t_name		alias for $1;
	t_field		alias for $2;
BEGIN
	IF	NOT column_exists(t_name, t_field)
	THEN
		EXECUTE $EXEC$
		ALTER TABLE $EXEC$ || quote_ident(t_name) || $EXEC$
			ADD COLUMN $EXEC$ || quote_ident(t_field) || $EXEC$ int;
		$EXEC$;
	END IF;
	
	IF	NOT constraint_exists(t_name, 'valid_' || t_field)
	THEN
		EXECUTE $EXEC$
		ALTER TABLE $EXEC$ || quote_ident(t_name) || $EXEC$
			ADD CONSTRAINT $EXEC$ || quote_ident('valid_' || t_field) || $EXEC$
				CHECK ( $EXEC$ || quote_ident(t_field) || $EXEC$ IS NULL OR
					$EXEC$ || quote_ident(t_field) || $EXEC$ >= 0 );
		$EXEC$;
	END IF;
	
	RETURN t_name;
END;
$$ LANGUAGE plpgsql;
