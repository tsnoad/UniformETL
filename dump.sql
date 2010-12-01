

DROP TABLE dump_cpgcustomer;
CREATE TABLE dump_cpgcustomer (
  customerid TEXT,
  cpgid TEXT,
  custstatusid TEXT,
  custsubstatusid TEXT,
  finstatus TEXT,
  gradetypeid TEXT,
  gradeid TEXT,
  gradevalidfrom TEXT,
  divisionid TEXT,
  divisionlock TEXT,
  note TEXT,
  filenumber TEXT,
  profpracnote TEXT,
  ticlassid TEXT,
  validfrom TEXT,
  validto TEXT,
  datecreated TEXT,
  dormant TEXT,
  parentid TEXT,
  parentcpgid TEXT,
  employerpays TEXT,
  employercontact TEXT,
  crtotal TEXT,
  dbtotal TEXT,
  lastupdated TEXT,
  updatedby TEXT,
  timestamp TEXT,
  supppnenabled TEXT,
  stringvalue1 TEXT,
  stringvalue2 TEXT,
  stringvalue3 TEXT,
  stringvalue4 TEXT
);

COPY dump_cpgcustomer (customerid, cpgid, custstatusid, custsubstatusid, finstatus, gradetypeid, gradeid, gradevalidfrom, divisionid, divisionlock, note, filenumber, profpracnote, ticlassid, validfrom, validto, datecreated, dormant, parentid, parentcpgid, employerpays, employercontact, crtotal, dbtotal, lastupdated, updatedby, timestamp, supppnenabled, stringvalue1, stringvalue2, stringvalue3, stringvalue4) FROM '/home/user/hotel/dumps/taboutcpgCustomer.sql' DELIMITER '|' NULL AS '' CSV QUOTE AS $$'$$ ESCAPE AS $$\$$;

DROP TABLE dump_name;
CREATE TABLE dump_name (
  customerid TEXT,
  nametypeid TEXT,
  nametitleid TEXT,
  nameline1 TEXT,
  nameline2 TEXT,
  personalpn TEXT,
  appointmentpn TEXT,
  lastupdated TEXT,
  updatedby TEXT,
  timestamp TEXT
);

COPY dump_name (customerid, nametypeid, nametitleid, nameline1, nameline2, personalpn, appointmentpn, lastupdated, updatedby, timestamp) FROM '/home/user/hotel/dumps/taboutName.sql' DELIMITER '|' NULL AS '' CSV QUOTE AS $$'$$ ESCAPE AS $$\$$;

DROP TABLE dump_address;
CREATE TABLE dump_address (
  customerid TEXT,
  addressnumber TEXT,
  postcode TEXT,
  countryid TEXT,
  addrtypeid TEXT,
  mailbar TEXT,
  valid TEXT,
  orgname TEXT,
  line1 TEXT,
  line2 TEXT,
  line3 TEXT,
  suburb TEXT,
  state TEXT,
  lastupdated TEXT,
  updatedby TEXT,
  timestamp TEXT,
  dpid TEXT
);

COPY dump_address (customerid, addressnumber, postcode, countryid, addrtypeid, mailbar, valid, orgname, line1, line2, line3, suburb, state, lastupdated, updatedby, timestamp, dpid) FROM '/home/user/hotel/dumps/taboutAddress.sql' DELIMITER '|' NULL AS '' CSV QUOTE AS $$'$$ ESCAPE AS $$\$$;

DROP TABLE dump_email;
CREATE TABLE dump_email (
  customerid TEXT,
  emailid TEXT,
  emailtypeid TEXT,
  emailaddress TEXT,
  valid TEXT,
  emailbar TEXT,
  lastupdated TEXT,
  updatedby TEXT,
  timestamp TEXT,
  emailusagelabel TEXT
);

COPY dump_email (customerid, emailid, emailtypeid, emailaddress, valid, emailbar, lastupdated, updatedby, timestamp, emailusagelabel) FROM '/home/user/hotel/dumps/taboutEMail.sql' DELIMITER '|' NULL AS '' CSV QUOTE AS $$'$$ ESCAPE AS $$\$$;

DROP TABLE dump_groupmember;
CREATE TABLE dump_groupmember (
  customerid TEXT,
  cpgid TEXT,
  groupid TEXT,
  subgroupid TEXT,
  roleid TEXT,
  memberdate TEXT,
  retirementdate TEXT,
  createdby TEXT,
  datecreated TEXT,
  lastupdated TEXT,
  updatedby TEXT
);

COPY dump_groupmember (customerid, cpgid, groupid, subgroupid, roleid, memberdate, retirementdate, createdby, datecreated, lastupdated, updatedby) FROM '/home/user/hotel/dumps/taboutGroupMember.sql' DELIMITER '|' NULL AS '' CSV QUOTE AS $$'$$ ESCAPE AS $$\$$;

CREATE INDEX dump_cpgcustomer_cpgid ON dump_cpgcustomer (cpgid) WHERE (cpgid='IEA');
CREATE INDEX dump_cpgcustomer_customerid ON dump_cpgcustomer (cast(customerid AS BIGINT)) WHERE (cpgid='IEA');
CREATE INDEX dump_cpgcustomer_custstatusid ON dump_cpgcustomer (custstatusid) WHERE (custstatusid='MEMB');
CREATE INDEX dump_name_customerid ON dump_name (cast(customerid AS BIGINT));
CREATE INDEX dump_address_customerid ON dump_address (cast(customerid AS BIGINT));
CREATE INDEX dump_email_emailtypeid ON dump_email (emailtypeid) WHERE (emailtypeid='INET');
CREATE INDEX dump_email_customerid ON dump_email (cast(customerid AS BIGINT)) WHERE (emailtypeid='INET');
CREATE INDEX dump_groupmember_groupid ON dump_groupmember (groupid) WHERE (groupid='6052');
CREATE INDEX dump_groupmember_customerid ON dump_groupmember (cast(customerid AS BIGINT)) WHERE (groupid='6052');
