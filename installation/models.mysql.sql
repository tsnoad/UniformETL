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

create table nmep_statuses (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	member_id BIGINT NOT NULL,
	participant BOOLEAN NOT NULL DEFAULT FALSE,
	UNIQUE (member_id),
	FOREIGN KEY (member_id) REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX nmep_statuses_member_id ON nmep_statuses (member_id);

create table grades (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	member_id BIGINT NOT NULL,
	grade VARCHAR (512) NOT NULL,
	chartered BOOLEAN NOT NULL DEFAULT FALSE,
	UNIQUE (member_id),
	FOREIGN KEY (member_id) REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX grades_member_id ON grades (member_id);

create table grade_names_postnominals (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	grade VARCHAR (512) NOT NULL,
	name TEXT,
	postnominals TEXT,
	chartered_postnominals TEXT,
	UNIQUE (grade)
) ENGINE=InnoDB;
CREATE INDEX grade_names_postnominals_grade ON grade_names_postnominals (grade);

INSERT INTO grade_names_postnominals (grade, name, postnominals, chartered_postnominals) VALUES ('AFIL', 'Affiliate', 'AffilIEAust', '');
INSERT INTO grade_names_postnominals (grade, name, postnominals, chartered_postnominals) VALUES ('COMP', 'Companion', 'CompIEAust ', '');
INSERT INTO grade_names_postnominals (grade, name, postnominals, chartered_postnominals) VALUES ('FELL', 'Fellow', 'FIEAust', 'CPEng');
INSERT INTO grade_names_postnominals (grade, name, postnominals, chartered_postnominals) VALUES ('GRAD', 'Graduate', 'GradIEAust ', '');
INSERT INTO grade_names_postnominals (grade, name, postnominals, chartered_postnominals) VALUES ('HONF', 'Honorary Fellow', 'HonFIEAust ', 'CPEng');
INSERT INTO grade_names_postnominals (grade, name, postnominals, chartered_postnominals) VALUES ('MEMB', 'Member', 'MIEAust', 'CPEng');
INSERT INTO grade_names_postnominals (grade, name, postnominals, chartered_postnominals) VALUES ('OFEL', 'Officer Fellow', 'OFIEAust', 'CEngO');
INSERT INTO grade_names_postnominals (grade, name, postnominals, chartered_postnominals) VALUES ('OGRA', 'Officer Graduate', 'GradOIEAust', '');
INSERT INTO grade_names_postnominals (grade, name, postnominals, chartered_postnominals) VALUES ('OMEM', 'Officer Member', 'OMIEAust', 'CEngO');
INSERT INTO grade_names_postnominals (grade, name, postnominals, chartered_postnominals) VALUES ('OSTU', 'Officer Student', 'StudIEAust', '');
INSERT INTO grade_names_postnominals (grade, name, postnominals, chartered_postnominals) VALUES ('SNRM', 'Senior Member', 'SMIEAust', 'CPEng');
INSERT INTO grade_names_postnominals (grade, name, postnominals, chartered_postnominals) VALUES ('STUD', 'Student (IEAust)', 'StudIEAust', '');
INSERT INTO grade_names_postnominals (grade, name, postnominals, chartered_postnominals) VALUES ('TFEL', 'Technologist Fellow', 'TFIEAust', 'CEngT');
INSERT INTO grade_names_postnominals (grade, name, postnominals, chartered_postnominals) VALUES ('TGRA', 'Technologist Graduate', 'GradTIEAust', '');
INSERT INTO grade_names_postnominals (grade, name, postnominals, chartered_postnominals) VALUES ('TMEM', 'Technologist Member', 'TMIEAust', 'CEngT');
INSERT INTO grade_names_postnominals (grade, name, postnominals, chartered_postnominals) VALUES ('TSTU', 'Technologist Student', 'StudIEAust', '');

create table divisions (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	member_id BIGINT NOT NULL,
	division VARCHAR (512) NOT NULL,
	UNIQUE (member_id),
	FOREIGN KEY (member_id) REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX divisions_member_id ON divisions (member_id);

create table division_names (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	division VARCHAR (512) NOT NULL,
	name TEXT,
	UNIQUE (division)
) ENGINE=InnoDB;
CREATE INDEX division_names_division ON division_names (division);

INSERT INTO division_names (division, name) VALUES ('CBR', 'Canberra Division');
INSERT INTO division_names (division, name) VALUES ('NAT', 'IEAust National Office');
INSERT INTO division_names (division, name) VALUES ('NEWC', 'Newcastle Division');
INSERT INTO division_names (division, name) VALUES ('NT', 'Northern Division');
INSERT INTO division_names (division, name) VALUES ('QLD', 'Queensland Division');
INSERT INTO division_names (division, name) VALUES ('SA', 'South Australia Division');
INSERT INTO division_names (division, name) VALUES ('SYD', 'Sydney Division');
INSERT INTO division_names (division, name) VALUES ('TAS', 'Tasmania Division');
INSERT INTO division_names (division, name) VALUES ('VIC', 'Victoria Division');
INSERT INTO division_names (division, name) VALUES ('WA', 'Western Australia Division');

create table colleges (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	member_id BIGINT NOT NULL,
	college VARCHAR (512),
	grade VARCHAR (512),
	UNIQUE (member_id, college),
	FOREIGN KEY (member_id) REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX colleges_member_id ON colleges (member_id);

create table college_names (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	college VARCHAR (512),
	name TEXT,
	UNIQUE (college)
) ENGINE=InnoDB;
CREATE INDEX college_names_college ON college_names (college);

INSERT INTO college_names (college, name) VALUES ('BIOM', 'Biomedical College');
INSERT INTO college_names (college, name) VALUES ('CHEM', 'Chemical College');
INSERT INTO college_names (college, name) VALUES ('CIVL', 'Civil College');
INSERT INTO college_names (college, name) VALUES ('ELEC', 'Electrical College');
INSERT INTO college_names (college, name) VALUES ('ENVI', 'Environmental College');
INSERT INTO college_names (college, name) VALUES ('ITEL', 'Info Telecom & Electronics Eng College');
INSERT INTO college_names (college, name) VALUES ('MECH', 'Mechanical College');
INSERT INTO college_names (college, name) VALUES ('STRU', 'Structural College');

create table societies (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	member_id BIGINT NOT NULL,
	society VARCHAR (512),
	grade VARCHAR (512),
	UNIQUE (member_id, society),
	FOREIGN KEY (member_id) REFERENCES member_ids (member_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX societies_member_id ON societies (member_id);

create table society_names (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	society VARCHAR (512),
	name TEXT,
	UNIQUE (society)
) ENGINE=InnoDB;
CREATE INDEX society_names_society ON society_names (society);

INSERT INTO society_names (society, name) VALUES ('TS01', 'Australasian Association for Engineering Education');
INSERT INTO society_names (society, name) VALUES ('TS02', 'Mine Subsidence Technological Society');
INSERT INTO society_names (society, name) VALUES ('TS03', 'Aust Society for Defence Engineering');
INSERT INTO society_names (society, name) VALUES ('TS04', 'Society for Engineering Management Australia');
INSERT INTO society_names (society, name) VALUES ('TS05', 'Materials Australia');
INSERT INTO society_names (society, name) VALUES ('TS06', 'Info, Telecoms & Electronics Engineering Society');
INSERT INTO society_names (society, name) VALUES ('TS07', 'Australian Geomechanics Society');
INSERT INTO society_names (society, name) VALUES ('TS08', 'Australasian Tunneling Society');
INSERT INTO society_names (society, name) VALUES ('TS09', 'Process Control Society');
INSERT INTO society_names (society, name) VALUES ('TS10', 'Society for Engineering in Agriculture');
INSERT INTO society_names (society, name) VALUES ('TS11', 'Australian Earthquake Engineering Society');
INSERT INTO society_names (society, name) VALUES ('TS12', 'Australian Composite Structures Society');
INSERT INTO society_names (society, name) VALUES ('TS13', 'Asset Management Council');
INSERT INTO society_names (society, name) VALUES ('TS14', 'Risk Engineering Society');
INSERT INTO society_names (society, name) VALUES ('TS15', 'Industrial Engineering Society');
INSERT INTO society_names (society, name) VALUES ('TS16', 'IEAust International Association');
INSERT INTO society_names (society, name) VALUES ('TS17', 'Society of Fire Safety');
INSERT INTO society_names (society, name) VALUES ('TS18', 'Australasian Fluid and Thermal Engineering Society');
INSERT INTO society_names (society, name) VALUES ('TS19', 'Society for Sustainability and Environmental Engineering');
INSERT INTO society_names (society, name) VALUES ('TS20', 'Systems Engineering Society of Australia');
INSERT INTO society_names (society, name) VALUES ('TS21', 'Red R Australia');
INSERT INTO society_names (society, name) VALUES ('TS22', 'Australian Cost Engineering Society');
INSERT INTO society_names (society, name) VALUES ('TS23', 'Maritime Engineering Society of Australia');
INSERT INTO society_names (society, name) VALUES ('TS24', 'Society for Building Services Engineering');
INSERT INTO society_names (society, name) VALUES ('TS25', 'Manufacturing Society of Australia');
INSERT INTO society_names (society, name) VALUES ('TS26', 'Railway Technical Society of Australia');
INSERT INTO society_names (society, name) VALUES ('TS27', 'Australian Society for Bulk Solids Handling');
INSERT INTO society_names (society, name) VALUES ('TS28', 'Electromagnetic Compatibility Society of Australia');
INSERT INTO society_names (society, name) VALUES ('TS29', 'PIANC Australia');
INSERT INTO society_names (society, name) VALUES ('TS30', 'Forensic Engineering Society');
INSERT INTO society_names (society, name) VALUES ('TS31', 'Electric Energy Society of Australia');
INSERT INTO society_names (society, name) VALUES ('TS32', 'Australasian Particle Technology Society');
INSERT INTO society_names (society, name) VALUES ('TS33', 'Mining Electrical and Mining Mechanical Engineering Society');
INSERT INTO society_names (society, name) VALUES ('TS35', 'Aerospace Technical Society');

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