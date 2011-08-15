create table member_ids (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	member_id BIGINT NOT NULL,
	UNIQUE (member_id)
) ENGINE=InnoDB;
CREATE INDEX member_ids_member_id ON member_ids (member_id);

create table personals (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	member_id BIGINT NOT NULL,
	gender TEXT DEFAULT NULL CHECK (gender IS NULL OR gender='M' OR gender='F' OR gender='O'),
	date_of_birth DATETIME DEFAULT NULL,
	UNIQUE (member_id),
	FOREIGN KEY (member_id) REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX personals_member_id ON personals (member_id);

create table statuses (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	member_id BIGINT NOT NULL,
	member BOOLEAN NOT NULL DEFAULT FALSE,
	financial BOOLEAN NOT NULL DEFAULT FALSE,
	UNIQUE (member_id),
	FOREIGN KEY (member_id) REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX statuses_member_id ON statuses (member_id);

create table passwords (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	member_id BIGINT NOT NULL,
	salt TEXT NOT NULL,
	hash TEXT NOT NULL,
	ldap_hash TEXT NOT NULL,
	UNIQUE (member_id),
	FOREIGN KEY (member_id) REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX passwords_member_id ON passwords (member_id);

create table names (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	member_id BIGINT NOT NULL,
	type VARCHAR (16),
	given_names VARCHAR (256),
	family_name VARCHAR (256),
	UNIQUE (member_id, type, given_names, family_name),
	FOREIGN KEY (member_id) REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX names_member_id ON names (member_id);

create table addresses (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	member_id BIGINT NOT NULL,
	type VARCHAR (16),
	address VARCHAR (512),
	suburb VARCHAR (512),
	state VARCHAR (512),
	postcode VARCHAR (512),
	country VARCHAR (512),
	UNIQUE (member_id, type, address, suburb, state, postcode, country),
	FOREIGN KEY (member_id) REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX addresses_member_id ON addresses (member_id);

create table emails (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	member_id BIGINT NOT NULL,
	email VARCHAR (512),
	UNIQUE (member_id, email),
	FOREIGN KEY (member_id) REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX emails_member_id ON emails (member_id);

create table web_statuses (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	member_id BIGINT NOT NULL,
	UNIQUE (member_id),
	FOREIGN KEY (member_id) REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX web_statuses_member_id ON web_statuses (member_id);

create table ecpd_statuses (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	member_id BIGINT NOT NULL,
	participant BOOLEAN NOT NULL DEFAULT FALSE,
	coordinator BOOLEAN NOT NULL DEFAULT FALSE,
	UNIQUE (member_id),
	FOREIGN KEY (member_id) REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX ecpd_statuses_member_id ON ecpd_statuses (member_id);

create table epdp_statuses (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	member_id BIGINT NOT NULL,
	participant BOOLEAN NOT NULL DEFAULT FALSE,
	coordinator BOOLEAN NOT NULL DEFAULT FALSE,
	UNIQUE (member_id),
	FOREIGN KEY (member_id) REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX epdp_statuses_member_id ON epdp_statuses (member_id);

create table grades (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	member_id BIGINT NOT NULL,
	grade VARCHAR (512) NOT NULL,
	chartered BOOLEAN NOT NULL DEFAULT FALSE,
	UNIQUE (member_id),
	FOREIGN KEY (member_id) REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX grades_member_id ON grades (member_id);

create table divisions (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	member_id BIGINT NOT NULL,
	division VARCHAR (512) NOT NULL,
	UNIQUE (member_id),
	FOREIGN KEY (member_id) REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX divisions_member_id ON divisions (member_id);

create table colleges (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	member_id BIGINT NOT NULL,
	college VARCHAR (512),
	grade VARCHAR (512),
	UNIQUE (member_id, college),
	FOREIGN KEY (member_id) REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX colleges_member_id ON colleges (member_id);

create table societies (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	member_id BIGINT NOT NULL,
	society VARCHAR (512),
	grade VARCHAR (512),
	UNIQUE (member_id, society),
	FOREIGN KEY (member_id) REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX societies_member_id ON societies (member_id);

create table invoices (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	member_id BIGINT NOT NULL,
	batchid BIGINT,
	batchposition BIGINT,
	type TEXT,
	status TEXT,
	amount FLOAT,
	UNIQUE (batchid, batchposition),
	FOREIGN KEY (member_id) REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX invoices_member_id ON invoices (member_id);
CREATE INDEX invoices_batchid ON invoices (batchid);
CREATE INDEX invoices_batchposition ON invoices (batchposition);

create table receipts (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	member_id BIGINT NOT NULL,
	batchid BIGINT,
	batchposition BIGINT,
	type TEXT,
	status TEXT,
	amount FLOAT,
	UNIQUE (batchid, batchposition),
	FOREIGN KEY (member_id) REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX receipts_member_id ON receipts (member_id);
CREATE INDEX receipts_batchid ON receipts (batchid);
CREATE INDEX receipts_batchposition ON receipts (batchposition);

CREATE TABLE invoiceitems (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	invoice_id BIGINT NOT NULL,
	itemcode VARCHAR (32),
	subitemcode VARCHAR (32),
	quantity INT,
	unitamount FLOAT,
	amount FLOAT,
	UNIQUE (invoice_id, itemcode, subitemcode),
	FOREIGN KEY (invoice_id) REFERENCES invoices (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX invoices_invoice_id ON invoiceitems (invoice_id);
CREATE INDEX invoices_itemcode ON invoiceitems (itemcode);
CREATE INDEX invoices_subitemcode ON invoiceitems (subitemcode);

CREATE TABLE receiptallocations (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	receipt_id BIGINT NOT NULL,
	invoiceitem_id BIGINT NOT NULL,
	UNIQUE (receipt_id, invoiceitem_id),
	FOREIGN KEY (receipt_id) REFERENCES receipts (id) ON UPDATE CASCADE ON DELETE CASCADE,
	FOREIGN KEY (invoiceitem_id) REFERENCES invoiceitems (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX receiptallocations_invoiceitem_id ON receiptallocations (invoiceitem_id);
CREATE INDEX receiptallocations_receipt_id ON receiptallocations (receipt_id);

CREATE TABLE extract_processes (
	extract_id BIGINT AUTO_INCREMENT PRIMARY KEY,
	extract_pid TEXT NOT NULL,
	start_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	finished BOOLEAN DEFAULT FALSE,
	finish_date DATETIME,
	failed BOOLEAN DEFAULT FALSE,
	extractor VARCHAR (32) NOT NULL,
	models TEXT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE extract_full (
	extract_id BIGINT,
	source_path VARCHAR (512) NOT NULL,
	source_timestamp TIMESTAMP NOT NULL,
	source_md5 VARCHAR (128) NOT NULL,
	FOREIGN KEY (extract_id) REFERENCES extract_processes (extract_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE extract_latest (
	extract_id BIGINT,
	member_ids BIGINT,
	FOREIGN KEY (extract_id) REFERENCES extract_processes (extract_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE transform_processes (
	transform_id BIGINT AUTO_INCREMENT PRIMARY KEY,
	extract_id BIGINT,
	transform_pid TEXT NOT NULL,
	start_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	finished BOOLEAN DEFAULT FALSE,
	finish_date TIMESTAMP,
	failed BOOLEAN DEFAULT FALSE,
	FOREIGN KEY (extract_id) REFERENCES extract_processes (extract_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE chunks (
	chunk_id BIGINT AUTO_INCREMENT PRIMARY KEY,
	transform_id BIGINT,
	FOREIGN KEY (transform_id) REFERENCES transform_processes (transform_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE chunk_member_ids (
	chunk_id BIGINT,
	member_id BIGINT,
	FOREIGN KEY (chunk_id) REFERENCES chunks (chunk_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX chunk_member_ids_member_id ON chunk_member_ids (member_id);

CREATE TABLE history (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  change_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  data TEXT
);
CREATE INDEX history_change_date ON history (change_date);
