CREATE USER 'pinba'@'%' IDENTIFIED BY 'pinba';
GRANT ALL ON *.* TO 'pinba'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;

USE pinba;

CREATE TABLE `report_by_script_name` (
    `script` varchar(64) NOT NULL,
    `req_count` int(10) unsigned NOT NULL,
    `req_per_sec` float NOT NULL,
    `req_percent` float,
    `req_time_total` float NOT NULL,
    `req_time_per_sec` float NOT NULL,
    `req_time_percent` float,
    `ru_utime_total` float NOT NULL,
    `ru_utime_per_sec` float NOT NULL,
    `ru_utime_percent` float,
    `ru_stime_total` float NOT NULL,
    `ru_stime_per_sec` float NOT NULL,
    `ru_stime_percent` float,
    `traffic_total` bigint(20) unsigned NOT NULL,
    `traffic_per_sec` float NOT NULL,
    `traffic_percent` float,
    `memory_footprint` bigint(20) NOT NULL,
    `memory_per_sec` float NOT NULL,
    `memory_percent` float
) ENGINE=PINBA DEFAULT CHARSET=latin1 COMMENT='v2/request/60/~script/no_percentiles/no_filters';
