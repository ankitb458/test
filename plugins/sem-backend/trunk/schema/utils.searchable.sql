/**
 * Searchable behavior
 *
 * Adds fields:
 * - {table}.tsv
 *
 * Adds triggers:
 * - {table}_20__tsv()
 */
CREATE OR REPLACE FUNCTION searchable(varchar)
	RETURNS varchar
AS $$
DECLARE
	t_name		alias for $1;
	stmt		text := '';
BEGIN
	IF	NOT index_exists(t_name, t_name || '_tsv')
	THEN
		EXECUTE $EXEC$
		CREATE INDEX $EXEC$ || quote_ident(t_name || '_tsv') || $EXEC$
			ON $EXEC$ || quote_ident(t_name) || $EXEC$ USING GIN(tsv);
		$EXEC$;
	END IF;
	
	stmt := $EXEC$
	CREATE OR REPLACE FUNCTION $EXEC$ || quote_ident(t_name || '__tsv') || $EXEC$()
		RETURNS TRIGGER
	AS $DEF$
	BEGIN
		IF	TG_OP = 'UPDATE'
		THEN
			IF	NEW.tsv IS NOT DISTINCT FROM OLD.tsv$EXEC$;
	
	IF	column_exists(t_name, 'name')
	THEN
		stmt := stmt || $EXEC$ AND
				NEW.name IS NOT DISTINCT FROM OLD.name$EXEC$;
	END IF;
	
	IF	column_exists(t_name, 'ukey')
	THEN
		stmt := stmt || $EXEC$ AND
				NEW.ukey IS NOT DISTINCT FROM OLD.ukey$EXEC$;
	END IF;
	
	IF	column_exists(t_name, 'memo')
	THEN
		stmt := stmt || $EXEC$ AND
				NEW.memo IS NOT DISTINCT FROM OLD.memo$EXEC$;
	END IF;
	
	stmt := stmt || $EXEC$
			THEN
				RETURN NEW;
			END IF;
		END IF;
		
		NEW.tsv := ''::tsvector;$EXEC$;
	
	IF	column_exists(t_name, 'name')
	THEN
		stmt := stmt || $EXEC$
		NEW.tsv := NEW.tsv ||
			setweight(to_tsvector(NEW.name), 'A');$EXEC$;
	END IF;
	
	IF	column_exists(t_name, 'ukey')
	THEN
		stmt := stmt || $EXEC$
		NEW.tsv := NEW.tsv ||
			setweight(to_tsvector(COALESCE(regexp_replace(NEW.ukey, E'-\\d+$', ''), '')), 'B');$EXEC$;
	END IF;
	
	IF	column_exists(t_name, 'memo')
	THEN
		stmt := stmt || $EXEC$
		NEW.tsv := NEW.tsv ||
			setweight(to_tsvector(NEW.memo), 'D');$EXEC$;
	END IF;
	
	stmt := stmt || $EXEC$
		
		RETURN NEW;
	END;
	$DEF$ LANGUAGE plpgsql;
	$EXEC$;
	
	EXECUTE stmt;
	
	IF	NOT trigger_exists(t_name || '_20__tsv')
	THEN
		EXECUTE $EXEC$
		CREATE TRIGGER $EXEC$ || quote_ident(t_name || '_20__tsv') || $EXEC$
			BEFORE INSERT OR UPDATE ON $EXEC$ || quote_ident(t_name) || $EXEC$
		FOR EACH ROW EXECUTE PROCEDURE $EXEC$ || quote_ident(t_name || '__tsv') || $EXEC$();
		$EXEC$;
	END IF;
	
	RETURN t_name;
END;
$$ LANGUAGE plpgsql;
