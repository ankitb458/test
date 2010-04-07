
DROP TABLE transaction_lines CASCADE;
DROP TABLE transactions CASCADE;
DROP TABLE invoice_lines CASCADE;
DROP TABLE invoices CASCADE;
DROP TABLE order_lines CASCADE;
DROP TABLE orders CASCADE;
DROP TABLE campaigns CASCADE;
DROP TABLE products CASCADE;
DROP TABLE users CASCADE;

DROP DOMAIN datetime CASCADE;
DROP DOMAIN slug CASCADE;
DROP DOMAIN email CASCADE;

DROP TYPE currency_code CASCADE;
DROP TYPE country_code CASCADE;
DROP TYPE state_code CASCADE;

DROP TYPE status_activatable CASCADE;
DROP TYPE status_authenticatable CASCADE;
DROP TYPE status_payable CASCADE;

DROP TYPE type_transaction CASCADE;
DROP TYPE type_account CASCADE;

DROP TYPE type_payment CASCADE;
DROP TYPE method_payment CASCADE;
