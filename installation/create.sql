create table member_ids (
	id BIGSERIAL PRIMARY KEY,
	member_id BIGINT NOT NULL,
	UNIQUE (member_id)
);
CREATE INDEX member_ids_member_id ON member_ids (member_id);

create table personals (
	id BIGSERIAL PRIMARY KEY,
	member_id BIGINT NOT NULL REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE,
	gender TEXT DEFAULT NULL CHECK (gender IS NULL OR gender='M' OR gender='F' OR gender='O'),
	date_of_birth TIMESTAMP DEFAULT NULL,
	UNIQUE (member_id)
);
CREATE INDEX personals_member_id ON personals (member_id);

create table statuses (
	id BIGSERIAL PRIMARY KEY,
	member_id BIGINT NOT NULL REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE,
	member BOOLEAN NOT NULL DEFAULT FALSE,
	financial BOOLEAN NOT NULL DEFAULT FALSE,
	UNIQUE (member_id)
);
CREATE INDEX statuses_member_id ON statuses (member_id);

create table passwords (
	id BIGSERIAL PRIMARY KEY,
	member_id BIGINT NOT NULL REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE,
	salt TEXT NOT NULL,
	hash TEXT NOT NULL,
	ldap_hash TEXT NOT NULL,
	UNIQUE (member_id)
);
CREATE INDEX passwords_member_id ON passwords (member_id);

create table names (
	id BIGSERIAL PRIMARY KEY,
	member_id BIGINT NOT NULL REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE,
	type TEXT,
	given_names TEXT,
	family_name TEXT,
	UNIQUE (member_id, type, given_names, family_name)
);
CREATE INDEX names_member_id ON names (member_id);

create table addresses (
	id BIGSERIAL PRIMARY KEY,
	member_id BIGINT NOT NULL REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE,
	type TEXT,
	address TEXT,
	suburb TEXT,
	state TEXT,
	postcode TEXT,
	country TEXT,
	UNIQUE (member_id, type, address, suburb, state, postcode, country)
);
CREATE INDEX addresses_member_id ON addresses (member_id);

create table emails (
	id BIGSERIAL PRIMARY KEY,
	member_id BIGINT NOT NULL REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE,
	email TEXT,
	UNIQUE (member_id, email)
);
CREATE INDEX emails_member_id ON emails (member_id);

create table web_statuses (
	id BIGSERIAL PRIMARY KEY,
	member_id BIGINT NOT NULL REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE,
	UNIQUE (member_id)
);
CREATE INDEX web_statuses_member_id ON web_statuses (member_id);

create table ecpd_statuses (
	id BIGSERIAL PRIMARY KEY,
	member_id BIGINT NOT NULL REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE,
	participant BOOLEAN NOT NULL DEFAULT FALSE,
	coordinator BOOLEAN NOT NULL DEFAULT FALSE,
	UNIQUE (member_id)
);
CREATE INDEX ecpd_statuses_member_id ON ecpd_statuses (member_id);

create table epdp_statuses (
	id BIGSERIAL PRIMARY KEY,
	member_id BIGINT NOT NULL REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE,
	participant BOOLEAN NOT NULL DEFAULT FALSE,
	coordinator BOOLEAN NOT NULL DEFAULT FALSE,
	UNIQUE (member_id)
);
CREATE INDEX epdp_statuses_member_id ON epdp_statuses (member_id);

create table grades (
	id BIGSERIAL PRIMARY KEY,
	member_id BIGINT NOT NULL REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE,
	grade TEXT NOT NULL,
	chartered BOOLEAN NOT NULL DEFAULT FALSE,
	UNIQUE (member_id)
);
CREATE INDEX grades_member_id ON grades (member_id);

create table divisions (
	id BIGSERIAL PRIMARY KEY,
	member_id BIGINT NOT NULL REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE,
	division TEXT NOT NULL,
	UNIQUE (member_id)
);
CREATE INDEX divisions_member_id ON divisions (member_id);

create table colleges (
	id BIGSERIAL PRIMARY KEY,
	member_id BIGINT NOT NULL REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE,
	college TEXT,
	grade TEXT,
	UNIQUE (member_id, college)
);
CREATE INDEX colleges_member_id ON colleges (member_id);

create table societies (
	id BIGSERIAL PRIMARY KEY,
	member_id BIGINT NOT NULL REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE,
	society TEXT,
	grade TEXT,
	UNIQUE (member_id, society)
);
CREATE INDEX societies_member_id ON societies (member_id);

create table invoices (
	id BIGSERIAL PRIMARY KEY,
	member_id BIGINT NOT NULL REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE,
	batchid BIGINT,
	batchposition BIGINT,
	type TEXT,
	status TEXT,
	amount FLOAT,
	UNIQUE (batchid, batchposition)
);
CREATE INDEX invoices_member_id ON invoices (member_id);
CREATE INDEX invoices_batchid ON invoices (batchid);
CREATE INDEX invoices_batchposition ON invoices (batchposition);

create table receipts (
	id BIGSERIAL PRIMARY KEY,
	member_id BIGINT NOT NULL REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE,
	batchid BIGINT,
	batchposition BIGINT,
	type TEXT,
	status TEXT,
	amount FLOAT,
	UNIQUE (batchid, batchposition)
);
CREATE INDEX receipts_member_id ON receipts (member_id);
CREATE INDEX receipts_batchid ON receipts (batchid);
CREATE INDEX receipts_batchposition ON receipts (batchposition);

CREATE TABLE invoiceitems (
	id BIGSERIAL PRIMARY KEY,
	invoice_id BIGINT NOT NULL REFERENCES invoices (id) ON UPDATE CASCADE ON DELETE CASCADE,
	itemcode TEXT,
	subitemcode TEXT,
	quantity INT,
	unitamount FLOAT,
	amount FLOAT,
	UNIQUE (invoice_id, itemcode, subitemcode)
);
CREATE INDEX invoices_invoice_id ON invoiceitems (invoice_id);
CREATE INDEX invoices_itemcode ON invoiceitems (itemcode);
CREATE INDEX invoices_subitemcode ON invoiceitems (subitemcode);

CREATE TABLE receiptallocations (
	id BIGSERIAL PRIMARY KEY,
	receipt_id BIGINT NOT NULL REFERENCES receipts (id) ON UPDATE CASCADE ON DELETE CASCADE,
	invoiceitem_id BIGINT NOT NULL REFERENCES invoiceitems (id) ON UPDATE CASCADE ON DELETE CASCADE,
	UNIQUE (receipt_id, invoiceitem_id)
);
CREATE INDEX receiptallocations_invoiceitem_id ON receiptallocations (invoiceitem_id);
CREATE INDEX receiptallocations_receipt_id ON receiptallocations (receipt_id);

CREATE TABLE extract_processes (
	extract_id BIGSERIAL PRIMARY KEY,
	extract_pid TEXT NOT NULL,
	start_date TIMESTAMP NOT NULL DEFAULT now(),
	finished BOOLEAN DEFAULT FALSE,
	finish_date TIMESTAMP,
	failed BOOLEAN DEFAULT FALSE,
	extractor TEXT NOT NULL,
	models TEXT NOT NULL
);
CREATE TABLE extract_full (
	extract_id BIGINT REFERENCES extract_processes ON UPDATE CASCADE ON DELETE CASCADE,
	source_path TEXT NOT NULL,
	source_timestamp TIMESTAMP NOT NULL,
	source_md5 TEXT NOT NULL
);
CREATE TABLE extract_latest (
	extract_id BIGINT REFERENCES extract_processes ON UPDATE CASCADE ON DELETE CASCADE,
	member_ids TEXT
);
CREATE TABLE transform_processes (
	transform_id BIGSERIAL PRIMARY KEY,
	extract_id BIGINT REFERENCES extract_processes ON UPDATE CASCADE ON DELETE CASCADE,
	transform_pid TEXT NOT NULL,
	start_date TIMESTAMP NOT NULL DEFAULT now(),
	finished BOOLEAN DEFAULT FALSE,
	finish_date TIMESTAMP,
	failed BOOLEAN DEFAULT FALSE
);
CREATE TABLE chunks (
	chunk_id BIGSERIAL PRIMARY KEY,
	transform_id BIGINT REFERENCES transform_processes ON UPDATE CASCADE ON DELETE CASCADE
);
CREATE TABLE chunk_member_ids (
	chunk_id BIGINT REFERENCES chunks ON UPDATE CASCADE ON DELETE CASCADE,
	member_id BIGINT
);
CREATE INDEX chunk_member_ids_chunk_id ON chunk_member_ids (chunk_id);
CREATE INDEX chunk_member_ids_member_id ON chunk_member_ids (member_id);

CREATE TABLE history (
  id BIGSERIAL PRIMARY KEY,
  change_date TIMESTAMP NOT NULL DEFAULT now(),
  data TEXT
);
CREATE INDEX history_change_date ON history (change_date);
