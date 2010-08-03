
--DROP TABLE transaction_lines CASCADE;
--DROP TABLE transactions CASCADE;
DROP TABLE movement_lines CASCADE;
DROP TABLE movements CASCADE;
DROP TABLE payment_lines CASCADE;
DROP TABLE payments CASCADE;
DROP TABLE order_lines CASCADE;
DROP TABLE orders CASCADE;
DROP TABLE campaigns CASCADE;
DROP TABLE products CASCADE;
DROP TABLE users CASCADE;

DROP DOMAIN datetime CASCADE;
DROP DOMAIN slug CASCADE;
DROP DOMAIN email CASCADE;

DROP TYPE code_currency CASCADE;
DROP TYPE code_country CASCADE;
DROP TYPE code_state CASCADE;

DROP TYPE status_activatable CASCADE;
DROP TYPE status_authenticatable CASCADE;
DROP TYPE status_payable CASCADE;

DROP TYPE type_transaction CASCADE;
DROP TYPE type_account CASCADE;

DROP TYPE type_invoice CASCADE;
DROP TYPE type_payment CASCADE;
DROP TYPE method_payment CASCADE;
