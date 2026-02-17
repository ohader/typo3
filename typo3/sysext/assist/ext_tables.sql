# Define table and fields since it has no TCA
CREATE TABLE sys_assist_progress (
	 uuid uuid NOT NULL,
	 model varchar(255) DEFAULT '' NOT NULL,
	 initiator varchar(255) DEFAULT '' NOT NULL,
	 sequence int(11) unsigned DEFAULT '0' NOT NULL,
	 type varchar(10) DEFAULT '' NOT NULL,
	 timestamp datetime(3) NOT NULL,
	 payload longblob,

	 PRIMARY KEY (uuid, sequence)
);

