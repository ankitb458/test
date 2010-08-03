/*
 * Movements
 */
CREATE TABLE movements (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_payable NOT NULL DEFAULT 'draft',
	name			varchar NOT NULL,
	movement_type	type_movement NOT NULL DEFAULT 'shipment',
	movement_ref	varchar UNIQUE,
	order_id		bigint REFERENCES orders(id),
	user_id			bigint REFERENCES users(id),
	issue_date		datetime,
	due_date		datetime,
	due_amount		numeric(8,2) NOT NULL DEFAULT 0,
	cleared_date	datetime,
	cleared_amount	numeric(8,2) NOT NULL DEFAULT 0,
	created_date	datetime NOT NULL DEFAULT NOW(),
	modified_date	datetime NOT NULL DEFAULT NOW(),
	memo			text NOT NULL DEFAULT '',
--	tsv				tsvector NOT NULL,
	CONSTRAINT valid_name
		CHECK ( name <> '' AND name = trim(name) ),
	CONSTRAINT valid_flow
		CHECK ( NOT ( issue_date IS NULL AND status > 'draft' ) AND
			NOT ( due_date IS NULL AND status > 'draft' ) AND
			NOT ( cleared_date IS NULL AND status = 'cleared' ) ),
	CONSTRAINT valid_movement_type
		CHECK ( movement_type = 'shipment' OR order_id IS NULL ),
	CONSTRAINT valid_payment_method
		CHECK ( payment_ref IS NULL OR payment_method IS NOT NULL ),
	CONSTRAINT valid_payment_ref
		CHECK ( payment_ref <> '' AND payment_ref = trim(payment_ref) ),
	CONSTRAINT valid_amounts
		CHECK ( due_amount >= 0 AND cleared_amount >= 0 )
);

SELECT	timestampable('movements'),
		shippable('movements'),
--		searchable('movements'),
		trashable('movements');

COMMENT ON TABLE movements IS E'Movements

- due and cleared dates have absolutely no relationship with one another.
  It is possible to advance deliver, late deliver, etc.';

/**
 * Process read-only fields
 */
CREATE OR REPLACE FUNCTION movements_readonly()
	RETURNS trigger
AS $$
BEGIN
	IF	ROW(NEW.id, NEW.movement_type, NEW.order_id)
		IS DISTINCT FROM
		ROW(OLD.id, OLD.movement_type, OLD.order_id)
	THEN
		RAISE EXCEPTION 'Can''t edit readonly field in movements.id = %', NEW.id;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE CONSTRAINT TRIGGER movements_01_readonly
	AFTER UPDATE ON movements
FOR EACH ROW EXECUTE PROCEDURE movements_readonly();