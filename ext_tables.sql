#
# Table structure for table 'tx_evonginxboost'
#
CREATE TABLE tx_evonginxboost (
	page_uid int(11) DEFAULT '0' NOT NULL,
	user_uid int(11) DEFAULT '0' NOT NULL,
	timeout int(11) DEFAULT '0' NOT NULL,
	request_uri text NOT NULL,
	tags text DEFAULT '' NOT NULL,
	#bit_tags int(11) DEFAULT '0' NOT NULL,
	
	PRIMARY KEY (request_uri(333)),
	KEY page_uid_key (page_uid)
);
# WARNING varchar is available up to 65,535 chars in 5.0.3 and later versions

#
# Table structure for extend table 'pages'
#
CREATE TABLE pages (
	tx_evonginxboost_nocache smallint(3) DEFAULT '0' NOT NULL
	tx_evonginxboost_user_timeout int(11) DEFAULT '-2' NOT NULL
	tx_evonginxboost_guest_timeout int(11) DEFAULT '-2' NOT NULL
);


#CREATE TABLE tx_evonginxboost_hash (
#	hash binary(16) NOT NULL,
#	page int(11) NOT NULL,
#	expires int(11) NOT NULL,

#	PRIMARY KEY (hash,page),
#	KEY PAGE (page),
#	KEY EXPIRES (expires)
#) ENGINE=MyISAM
#PARTITION BY LINEAR HASH (page)
#PARTITIONS 4;
