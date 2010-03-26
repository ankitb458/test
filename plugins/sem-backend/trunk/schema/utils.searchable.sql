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
