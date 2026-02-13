# Define table and fields since it has no TCA
CREATE TABLE sys_assist_messages (
	 uuid uuid NOT NULL,
	 datetime datetime(3) NOT NULL,
	 user_id int(11) unsigned NOT NULL,
	 payload longblob,

	 PRIMARY KEY (uuid),
	 KEY user (user_id)
);
