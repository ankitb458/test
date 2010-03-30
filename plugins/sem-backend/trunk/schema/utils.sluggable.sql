/**
 * "Slugifies" a string
 */
CREATE OR REPLACE FUNCTION to_slug(varchar)
	RETURNS varchar
AS $$
DECLARE
	str			varchar := $1;
BEGIN
	str := trim(str);
	-- When PG 9 is around, we'll be able to use unaccent
	-- http://developer.postgresql.org/pgdocs/postgres/unaccent.html
	str := translate(str,
		'âãäåāăąÁÂÃÄÅĀĂĄèééêëēĕėęěĒĔĖĘĚìíîïìĩīĭÌÍÎÏÌĨĪĬóôõöōŏőÒÓÔÕÖŌŎŐùúûüũūŭůÙÚÛÜŨŪŬŮ',
		'aaaaaaaaaaaaaaaeeeeeeeeeeeeeeeiiiiiiiiiiiiiiiiooooooooooooooouuuuuuuuuuuuuuuu');
	str := lower(str);
	str := regexp_replace(str, '[^a-z0-9]+', '-', 'g');
	str := regexp_replace(str, '-+', '-', 'g');
	str := trim(both '-' from str);
	
	IF	str = ''
	THEN
		RETURN NULL;
	ELSE
		RETURN str;
	END IF;
END $$ LANGUAGE plpgsql IMMUTABLE STRICT;

/**
 * Sluggable behavior
 *
 * Adds fields:
 * - {table}.ukey
 *
 * Adds triggers:
 * - {table}_10__ukey()
 */
CREATE OR REPLACE FUNCTION sluggable(varchar)
	RETURNS varchar
AS $$
DECLARE
	t_name		alias for $1;
BEGIN
	IF	NOT column_exists(t_name, 'ukey')
	THEN
		EXECUTE $EXEC$
		ALTER TABLE $EXEC$ || quote_ident(t_name) || $EXEC$
			ADD COLUMN ukey varchar UNIQUE;
		$EXEC$;
	END IF;
	
	EXECUTE $EXEC$
	CREATE OR REPLACE FUNCTION $EXEC$ || quote_ident(t_name || '__ukey') || $EXEC$()
		RETURNS TRIGGER
	AS $DEF$
	DECLARE
		ukey_base	varchar;
		suffix		int := 1;
	BEGIN
		IF	NEW.ukey IS NULL OR NEW.ukey = ''
		THEN
			NEW.ukey := NULL; -- forbid empty string as ukey
			RETURN NEW;
		ELSEIF TG_OP = 'UPDATE'
		THEN
			IF	NEW.ukey IS NOT DISTINCT FROM OLD.ukey
			THEN
				RETURN NEW;
			END IF;
		END IF;
		
		NEW.ukey := COALESCE(to_slug(NEW.ukey), NEW.id || '-1');
		
		-- todo:
		-- - scan for a min suffix instead of trying 2, then 3, etc.
		ukey_base = regexp_replace(NEW.ukey, E'-\\d+$', '');
		
		LOOP
			IF	NOT EXISTS (
				SELECT	1
				FROM	$EXEC$ || quote_ident(t_name) || $EXEC$
				WHERE	ukey = NEW.ukey
				)
			THEN
				RETURN NEW;
			END IF;
			
			suffix := suffix + 1;
			NEW.ukey := ukey_base || '-' || suffix;
		END LOOP;
		
		RETURN NEW;
	END;
	$DEF$ LANGUAGE plpgsql;
	$EXEC$;
	
	IF	NOT trigger_exists(t_name || '_10__ukey')
	THEN
		EXECUTE $EXEC$
		CREATE TRIGGER $EXEC$ || quote_ident(t_name || '_10__ukey') || $EXEC$
			BEFORE INSERT OR UPDATE ON $EXEC$ || quote_ident(t_name) || $EXEC$
		FOR EACH ROW EXECUTE PROCEDURE $EXEC$ || quote_ident(t_name || '__ukey') || $EXEC$();
		$EXEC$;
	END IF;
	
	RETURN t_name;
END;
$$ LANGUAGE plpgsql;
