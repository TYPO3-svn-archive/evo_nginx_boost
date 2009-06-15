
#CREATE TABLE tx_evonginxboost_hash (
#	hash binary(16) NOT NULL,
#	expires int(11) NOT NULL,
#	PRIMARY KEY (hash),
#	KEY EXPIRES (expires)
#) ENGINE=MyISAM
#PARTITION BY LINEAR HASH (page)
#PARTITIONS 4;

CREATE TABLE log_evonginxboost (
	log_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	type varchar(16) DEFAULT 'INFO' NOT NULL,
	user varchar(64) DEFAULT '' NOT NULL,
	message text DEFAULT '' NOT NULL,
	url text DEFAULT '' NOT NULL,
	ip varchar(16) DEFAULT '' NOT NULL,
	ses varchar(32) DEFAULT '' NOT NULL
);

