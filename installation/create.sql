create table member_ids (
	id BIGSERIAL PRIMARY KEY,
	member_id BIGINT NOT NULL,
	UNIQUE (member_id)
);
CREATE INDEX member_ids_member_id ON member_ids (member_id);

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
	UNIQUE (member_id)
);
CREATE INDEX ecpd_statuses_member_id ON ecpd_statuses (member_id);

create table grades (
	id BIGSERIAL PRIMARY KEY,
	member_id BIGINT NOT NULL REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE,
	grade TEXT NOT NULL,
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

create table invoices (
	id BIGSERIAL PRIMARY KEY,
	member_id BIGINT NOT NULL REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE,
	batch_hash TEXT,
	type TEXT,
	status TEXT,
	amount FLOAT,
	UNIQUE (member_id, batch_hash, status, amount)
);
CREATE INDEX invoices_member_id ON invoices (member_id);

create table receipts (
	id BIGSERIAL PRIMARY KEY,
	member_id BIGINT NOT NULL REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE,
	batch_hash TEXT,
	type TEXT,
	status TEXT,
	amount FLOAT,
	UNIQUE (member_id, batch_hash, status, amount)
);
CREATE INDEX receipts_member_id ON receipts (member_id);

CREATE VIEW member_invoices_sum AS (
	SELECT i.member_id, sum(i.amount) as sum FROM invoices i GROUP BY member_id
);
CREATE VIEW member_receipts_sum AS (
	SELECT r.member_id, sum(r.amount) as sum FROM receipts r GROUP BY member_id
);
CREATE VIEW member_balance AS (
	SELECT i.member_id, i.sum-r.sum as balance FROM member_invoices_sum i INNER JOIN member_receipts_sum r ON (r.member_id=i.member_id)
);


CREATE TABLE processes (
	process_id BIGSERIAL PRIMARY KEY
);
CREATE TABLE extract_processes(
	process_id BIGINT REFERENCES processes ON UPDATE CASCADE ON DELETE CASCADE,
	start_date TIMESTAMP NOT NULL DEFAULT now(),
	finished BOOLEAN DEFAULT FALSE,
	finish_date TIMESTAMP,
	failed BOOLEAN DEFAULT FALSE,
	source_path TEXT NOT NULL,
	source_timestamp TIMESTAMP NOT NULL,
	source_md5 TEXT NOT NULL,
	extract_pid TEXT NOT NULL
);
CREATE TABLE transform_processes(
	process_id BIGINT REFERENCES processes ON UPDATE CASCADE ON DELETE CASCADE,
	start_date TIMESTAMP NOT NULL DEFAULT now(),
	finished BOOLEAN DEFAULT FALSE,
	finish_date TIMESTAMP,
	failed BOOLEAN DEFAULT FALSE,
	transform_pid TEXT NOT NULL
);
CREATE TABLE chunks (
	chunk_id BIGSERIAL PRIMARY KEY,
	process_id BIGINT REFERENCES processes ON UPDATE CASCADE ON DELETE CASCADE
);
CREATE TABLE chunk_member_ids (
	chunk_id BIGINT REFERENCES chunks ON UPDATE CASCADE ON DELETE CASCADE,
	member_id BIGINT
);
CREATE INDEX chunk_member_ids_member_id ON chunk_member_ids (member_id);