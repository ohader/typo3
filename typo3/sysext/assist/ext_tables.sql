# Define table and fields since it has no TCA
CREATE TABLE sys_assist_progress (
	 uuid uuid NOT NULL,
	 model varchar(255) DEFAULT '' NOT NULL,
	 initiator varchar(255) DEFAULT '' NOT NULL,
	 user_id int(11) unsigned DEFAULT '0' NOT NULL,
	 timestamp datetime(3) DEFAULT CURRENT_TIMESTAMP NOT NULL,

	 PRIMARY KEY (uuid),
	 KEY user_id_timestamp (user_id, timestamp)
);

CREATE TABLE sys_assist_progress_item (
	 uuid uuid NOT NULL,
	 progress uuid NOT NULL,
	 sequence int(11) unsigned DEFAULT '0' NOT NULL,
	 type varchar(10) DEFAULT '' NOT NULL,
	 timestamp datetime(3) DEFAULT CURRENT_TIMESTAMP NOT NULL,
	 payload longblob,

	 PRIMARY KEY (uuid),
	 UNIQUE KEY progress_sequence (progress, sequence)
);

