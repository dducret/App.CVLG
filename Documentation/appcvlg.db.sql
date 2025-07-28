BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "Address" (
	"id"	INTEGER,
	"street"	TEXT,
	"streetNumber"	TEXT,
	"postalCode"	TEXT,
	"city"	TEXT,
	"country"	TEXT,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "Booking" (
	"id"	INTEGER,
	"journey"	INTEGER,
	"member"	INTEGER,
	"ticket"	INTEGER,
	"disable"	INTEGER,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "BookingRule" (
	"id"	INTEGER,
	"rule"	INTEGER,
	"sequence"	INTEGER,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "Content" (
	"id"	INTEGER,
	"timestamp"	TEXT,
	"msgFrom"	INTEGER,
	"label"	TEXT,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "Driver" (
	"id"	INTEGER,
	"person"	INTEGER,
	"status"	INTEGER,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "Journal" (
	"id"	INTEGER,
	"timestamp"	TEXT,
	"person"	INTEGER,
	"label"	TEXT,
	"disableTimestamp"	TEXT,
	"disabledBy"	INTEGER,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "Journey" (
	"id"	INTEGER,
	"bookingRule"	INTEGER,
	"driver"	INTEGER,
	"vehicule"	INTEGER,
	"Label"	TEXT,
	"dateFrom"	TEXT,
	"dateTo"	TEXT,
	"timeStart"	TEXT,
	"timeEnd"	TEXT,
	"started"	INTEGER,
	"ended"	INTEGER,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "Manager" (
	"id"	INTEGER,
	"person"	INTEGER,
	"rights"	INTEGER
);
CREATE TABLE IF NOT EXISTS "Member" (
	"id"	INTEGER,
	"person"	INTEGER,
	"type"	INTEGER,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "MemberYearFee" (
	"id"	INTEGER,
	"member"	INTEGER,
	"yearFee"	INTEGER,
	"date"	TEXT,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "Message" (
	"id"	INTEGER,
	"content"	INTEGER,
	"msgFrom"	INTEGER,
	"msgTo"	INTEGER,
	"validSince"	TEXT,
	"validUntil"	TEXT,
	"sent"	TEXT,
	"received"	TEXT,
	"checked"	TEXT,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "Person" (
	"id"	INTEGER,
	"firstName"	TEXT,
	"lastName"	TEXT,
	"birthday"	TEXT,
	"gender"	TEXT,
	"email"	TEXT,
	"password"	TEXT,
	"mobile"	TEXT,
	"fsvl"	TEXT,
	"address"	INTEGER,
	"image"	TEXT,
	"language"	TEXT,
	"iban"	TEXT,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "Rule" (
	"id"	INTEGER,
	"label"	TEXT,
	"para"	TEXT,
	"chronology"	INTEGER,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "Ticket" (
	"id"	INTEGER,
	"person"	INTEGER,
	"quantity"	INTEGER,
	"price"	NUMERIC,
	"date"	TEXT,
	"used"	INTEGER,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "Vehicule" (
	"id"	INTEGER,
	"name"	TEXT,
	"registration"	TEXT,
	"label"	TEXT,
	"seats"	INTEGER,
	"status"	INTEGER,
	PRIMARY KEY("id")
);
CREATE TABLE IF NOT EXISTS "YearFee" (
	"id"	INTEGER,
	"year"	INTEGER,
	"type"	INTEGER,
	"price"	NUMERIC,
	PRIMARY KEY("id")
);
COMMIT;
