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
CREATE TABLE extract_processes_extract_id_seq (current_val BIGINT);
INSERT INTO extract_processes_extract_id_seq (current_val) VALUES (0);

CREATE TABLE extract_full (
	extract_id BIGINT,
	source_path VARCHAR (512) NOT NULL,
	source_timestamp TIMESTAMP NOT NULL,
	source_md5 VARCHAR (128) NOT NULL,
	FOREIGN KEY (extract_id) REFERENCES extract_processes (extract_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE extract_latest (
	extract_id BIGINT,
	member_ids TEXT,
	FOREIGN KEY (extract_id) REFERENCES extract_processes (extract_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE extract_full_staging (
	extract_id BIGINT,
	source_path VARCHAR (512) NOT NULL,
	source_timestamp TIMESTAMP NOT NULL,
	source_md5 VARCHAR (128) NOT NULL,
	FOREIGN KEY (extract_id) REFERENCES extract_processes (extract_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE extract_latest_staging (
	extract_id BIGINT,
	member_ids TEXT,
	FOREIGN KEY (extract_id) REFERENCES extract_processes (extract_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE extract_full_staged (
	extract_id BIGINT,
	source_path VARCHAR (512) NOT NULL,
	source_timestamp TIMESTAMP NOT NULL,
	source_md5 VARCHAR (128) NOT NULL,
	FOREIGN KEY (extract_id) REFERENCES extract_processes (extract_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE extract_latest_staged (
	extract_id BIGINT,
	source_path VARCHAR (512) NOT NULL,
	source_timestamp TIMESTAMP NOT NULL,
	source_md5 VARCHAR (128) NOT NULL,
	member_ids TEXT,
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
CREATE TABLE transform_processes_transform_id_seq (current_val BIGINT);
INSERT INTO transform_processes_transform_id_seq (current_val) VALUES (0);

CREATE TABLE chunks (
	chunk_id BIGINT AUTO_INCREMENT PRIMARY KEY,
	transform_id BIGINT,
	FOREIGN KEY (transform_id) REFERENCES transform_processes (transform_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE chunks_chunk_id_seq (current_val BIGINT);
INSERT INTO chunks_chunk_id_seq (current_val) VALUES (0);

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
) ENGINE=InnoDB;
CREATE INDEX history_change_date ON history (change_date);

CREATE TABLE extract_reports (
  extract_report_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  extract_id BIGINT,
  reported BOOLEAN DEFAULT TRUE,
  UNIQUE (extract_id),
  FOREIGN KEY (extract_id) REFERENCES extract_processes (extract_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE transform_reports (
  transform_report_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  transform_id BIGINT,
  reported BOOLEAN DEFAULT TRUE,
  UNIQUE (transform_id),
  FOREIGN KEY (transform_id) REFERENCES transform_processes (transform_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
