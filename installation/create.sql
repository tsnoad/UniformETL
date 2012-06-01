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
CREATE TABLE extract_full_staging (
	extract_id BIGINT REFERENCES extract_processes ON UPDATE CASCADE ON DELETE CASCADE,
	source_path TEXT NOT NULL,
	source_timestamp TIMESTAMP NOT NULL,
	source_md5 TEXT NOT NULL
);
CREATE TABLE extract_latest_staging (
	extract_id BIGINT REFERENCES extract_processes ON UPDATE CASCADE ON DELETE CASCADE,
	member_ids TEXT
);
CREATE TABLE extract_full_staged (
	extract_id BIGINT REFERENCES extract_processes ON UPDATE CASCADE ON DELETE CASCADE,
	source_path TEXT NOT NULL,
	source_timestamp TIMESTAMP NOT NULL,
	source_md5 TEXT NOT NULL
);
CREATE TABLE extract_latest_staged (
	extract_id BIGINT REFERENCES extract_processes ON UPDATE CASCADE ON DELETE CASCADE,
	source_path TEXT NOT NULL,
	source_timestamp TIMESTAMP NOT NULL,
	source_md5 TEXT NOT NULL,
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

CREATE TABLE extract_reports (
  extract_report_id BIGSERIAL PRIMARY KEY,
  extract_id BIGINT REFERENCES extract_processes ON UPDATE CASCADE ON DELETE CASCADE,
  reported BOOLEAN DEFAULT TRUE,
  UNIQUE (extract_id)
);
CREATE TABLE transform_reports (
  transform_report_id BIGSERIAL PRIMARY KEY,
  transform_id BIGINT REFERENCES transform_processes ON UPDATE CASCADE ON DELETE CASCADE,
  reported BOOLEAN DEFAULT TRUE,
  UNIQUE (transform_id)
);
