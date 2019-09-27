#
# Table structure for table 'tx_translatelabels_domain_model_translation'
#
CREATE TABLE tx_translatelabels_domain_model_translation (

	labelkey varchar(255) DEFAULT '' NOT NULL,
	translation text,
	KEY idx_labelkey (labelkey)
);
