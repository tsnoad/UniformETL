<?php

Class SybaseDataStructures {
	public static $structures = array(
		"structure1" => array(
			"Customer" => array(
				"CustomerId" => "CustomerId",
				"Field1" => "Sex",
				"Field2" => "DOB",
			),
			"CPGCustomer" => array(
				"CustomerId" => "CustomerId",
				"Field1" => "DivisionID",
				"Field2" => "GradeID",
				"Field3" => "CPGID",
				"Field4" => "CustStatusID",
			),
			"Address" => array(
				"CustomerId" => "CustomerId",
				"Field1" => "AddrTypeID",
				"Field2" => "Line1",
				"Field3" => "Line2",
				"Field4" => "Line3",
				"Field5" => "Suburb",
				"Field6" => "State",
				"Field7" => "Postcode",
				"Field8" => "CountryId",
				"Field9" => "Valid",
			),
			"GroupMember" => array(
				"CustomerId" => "CustomerId",
				"Field1" => "GroupID",
				"Field2" => "SubGroupID",
				"Field3" => "RetirementDate",
			),
			"Email" => array(
				"CustomerId" => "CustomerId",
				"Field1" => "EmailAddress",
				"Field2" => "EmailTypeID",
			),
			"Name" => array(
				"CustomerId" => "CustomerId",
				"Field1" => "NameTypeID",
				"Field2" => "NameLine2",
				"Field3" => "NameLine1",
			),
		)
	);
}

?>