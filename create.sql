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


CREATE TABLE chunks (
	chunk_id BIGSERIAL PRIMARY KEY
);
CREATE TABLE chunk_member_ids (
	chunk_id BIGINT REFERENCES chunks ON UPDATE CASCADE ON DELETE CASCADE,
	member_id BIGINT
);
CREATE INDEX chunk_member_ids_member_id ON chunk_member_ids (member_id);