-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 12. Mrz 2018 um 15:55
-- Server-Version: 5.7.21-0ubuntu0.16.04.1
-- PHP-Version: 7.0.25-0ubuntu0.16.04.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `indexer_main`
--

-- --------------------------------------------------------

--
-- Stellvertreter-Struktur des Views `cachefiles`
--
CREATE TABLE `cachefiles` (
`bestandid` bigint(20)
,`sessionid` bigint(20)
,`fileid` bigint(20)
,`cachefile` varchar(290)
);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `file`
--

CREATE TABLE `file` (
  `sessionid` bigint(20) NOT NULL,
  `fileid` bigint(20) NOT NULL,
  `parentid` bigint(20) NOT NULL,
  `name` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
  `localcopy` char(34) COLLATE utf8_unicode_ci DEFAULT NULL,
  `filetype` set('dir','file','link','other','archive') COLLATE utf8_unicode_ci DEFAULT NULL,
  `level` int(11) NOT NULL,
  `filesize` bigint(11) NOT NULL DEFAULT '0',
  `sha256` char(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `md5` char(33) COLLATE utf8_unicode_ci DEFAULT NULL,
  `filectime` datetime DEFAULT NULL,
  `filemtime` datetime DEFAULT NULL,
  `fileatime` datetime DEFAULT NULL,
  `stat` text COLLATE utf8_unicode_ci,
  `relevance` int(11) NOT NULL DEFAULT '50',
  `access` int(11) NOT NULL DEFAULT '0',
  `comment` text COLLATE utf8_unicode_ci,
  `archivetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mtime` datetime DEFAULT NULL,
  `lock` tinyint(1) NOT NULL DEFAULT '0',
  `status` set('unknown','green','yellow','red') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'unknown',
  `readstate` set('start','warn','error','ok','skip') COLLATE utf8_unicode_ci NOT NULL,
  `ingesttime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `info_antiword`
--

CREATE TABLE `info_antiword` (
  `sessionid` bigint(20) NOT NULL,
  `fileid` bigint(20) NOT NULL,
  `status` set('start','warn','error','ok') COLLATE utf8_unicode_ci NOT NULL,
  `indextime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `info_avconv`
--

CREATE TABLE `info_avconv` (
  `sessionid` bigint(20) NOT NULL,
  `fileid` bigint(20) NOT NULL,
  `thumb` blob,
  `fullinfo` text COLLATE utf8_unicode_ci,
  `status` set('start','warn','error','ok') COLLATE utf8_unicode_ci NOT NULL,
  `indextime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `info_gvfs_info`
--

CREATE TABLE `info_gvfs_info` (
  `sessionid` bigint(20) NOT NULL,
  `fileid` bigint(20) NOT NULL,
  `mimetype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fullinfo` text COLLATE utf8_unicode_ci,
  `status` set('start','warn','error','ok') COLLATE utf8_unicode_ci NOT NULL,
  `indextime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `info_imagick`
--

CREATE TABLE `info_imagick` (
  `sessionid` bigint(20) NOT NULL,
  `fileid` bigint(20) NOT NULL,
  `magick` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `xres` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `yres` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fullinfo` text COLLATE utf8_unicode_ci,
  `status` set('start','warn','error','ok') COLLATE utf8_unicode_ci NOT NULL,
  `indextime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `info_libmagic`
--

CREATE TABLE `info_libmagic` (
  `sessionid` bigint(20) NOT NULL,
  `fileid` bigint(20) NOT NULL,
  `mimetype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mimeencoding` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` varchar(2048) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` set('start','warn','error','ok') COLLATE utf8_unicode_ci NOT NULL,
  `indextime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `info_nsrl`
--

CREATE TABLE `info_nsrl` (
  `sessionid` bigint(20) NOT NULL,
  `fileid` bigint(20) NOT NULL,
  `FileName` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FileSize` bigint(20) DEFAULT NULL,
  `ProductCode` bigint(20) DEFAULT NULL,
  `OpSystemcode` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Specialcode` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` set('start','warn','error','ok') COLLATE utf8_unicode_ci NOT NULL,
  `indextime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `info_tika`
--

CREATE TABLE `info_tika` (
  `sessionid` bigint(20) NOT NULL,
  `fileid` bigint(20) NOT NULL,
  `mimetype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mimeencoding` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fullinfo` text COLLATE utf8_unicode_ci,
  `hascontent` tinyint(1) DEFAULT '0',
  `status` set('start','warn','error','ok') COLLATE utf8_unicode_ci NOT NULL,
  `indexertime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log`
--

CREATE TABLE `log` (
  `logid` bigint(20) NOT NULL,
  `sessionid` bigint(20) NOT NULL,
  `fileid` bigint(20) DEFAULT NULL,
  `status` set('info','warn','error') COLLATE utf8_unicode_ci NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `ltime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Stellvertreter-Struktur des Views `logfile`
--
CREATE TABLE `logfile` (
`logid` bigint(20)
,`lstatus` set('info','warn','error')
,`message` text
,`ltime` timestamp
,`sessionid` bigint(20)
,`fileid` bigint(20)
,`parentid` bigint(20)
,`name` varchar(512)
,`path` varchar(1024)
,`localcopy` char(34)
,`filetype` set('dir','file','link','other','archive')
,`level` int(11)
,`filesize` bigint(11)
,`sha256` char(64)
,`md5` char(33)
,`filectime` datetime
,`filemtime` datetime
,`fileatime` datetime
,`stat` text
,`relevance` int(11)
,`access` int(11)
,`comment` text
,`archivetime` timestamp
,`mtime` datetime
,`lock` tinyint(1)
,`status` set('unknown','green','yellow','red')
,`readstate` set('start','warn','error','ok','skip')
,`ingesttime` datetime
);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `NSRLMfg`
--

CREATE TABLE `NSRLMfg` (
  `MfgCode` varchar(15) DEFAULT NULL,
  `MfgName` varchar(59) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `NSRLOS`
--

CREATE TABLE `NSRLOS` (
  `OpSystemCode` varchar(15) DEFAULT NULL,
  `OpSystemName` varchar(68) DEFAULT NULL,
  `OpSystemVersion` varchar(15) DEFAULT NULL,
  `MfgCode` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `NSRLProd`
--

CREATE TABLE `NSRLProd` (
  `ProductCode` int(5) DEFAULT NULL,
  `ProductName` varchar(150) DEFAULT NULL,
  `ProductVersion` varchar(15) DEFAULT NULL,
  `OpSystemCode` varchar(15) DEFAULT NULL,
  `MfgCode` varchar(15) DEFAULT NULL,
  `Language` varchar(149) DEFAULT NULL,
  `ApplicationType` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `session`
--

CREATE TABLE `session` (
  `bestandid` bigint(20) NOT NULL,
  `sessionid` bigint(20) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `basepath` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  `datapath` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `localpath` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mountpoint` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mount` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `umount` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fscharset` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ISO-8859-1',
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `parent` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `group` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `solrpath` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `solrtime` datetime DEFAULT NULL,
  `ingesttime` datetime DEFAULT NULL,
  `ignore` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `Tabelle1`
--

CREATE TABLE `Tabelle1` (
  `bestandid` int(1) DEFAULT NULL,
  `sessionid` int(4) DEFAULT NULL,
  `name` varchar(12) DEFAULT NULL,
  `basepath` varchar(10) DEFAULT NULL,
  `datapath` varchar(107) DEFAULT NULL,
  `localpath` varchar(32) DEFAULT NULL,
  `mountpoint` varchar(4) DEFAULT NULL,
  `mount` varchar(61) DEFAULT NULL,
  `umount` varchar(21) DEFAULT NULL,
  `fscharset` varchar(10) DEFAULT NULL,
  `description` varchar(151) DEFAULT NULL,
  `parent` varchar(10) DEFAULT NULL,
  `group` varchar(2) DEFAULT NULL,
  `solrpath` varchar(13) DEFAULT NULL,
  `solartime` varchar(10) DEFAULT NULL,
  `ingesttime` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Stellvertreter-Struktur des Views `throughput`
--
CREATE TABLE `throughput` (
`sessionid` bigint(20)
,`start` datetime
,`end` datetime
,`seconds` bigint(21)
,`files` bigint(21)
,`size` decimal(41,0)
);

-- --------------------------------------------------------

--
-- Struktur des Views `cachefiles`
--
DROP TABLE IF EXISTS `cachefiles`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cachefiles`  AS  select `s`.`bestandid` AS `bestandid`,`s`.`sessionid` AS `sessionid`,`f`.`fileid` AS `fileid`,concat(`s`.`localpath`,`f`.`localcopy`) AS `cachefile` from (`session` `s` join `file` `f`) where ((`s`.`sessionid` = `f`.`sessionid`) and (`f`.`localcopy` is not null)) ;

-- --------------------------------------------------------

--
-- Struktur des Views `logfile`
--
DROP TABLE IF EXISTS `logfile`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `logfile`  AS  select `l`.`logid` AS `logid`,`l`.`status` AS `lstatus`,`l`.`message` AS `message`,`l`.`ltime` AS `ltime`,`f`.`sessionid` AS `sessionid`,`f`.`fileid` AS `fileid`,`f`.`parentid` AS `parentid`,`f`.`name` AS `name`,`f`.`path` AS `path`,`f`.`localcopy` AS `localcopy`,`f`.`filetype` AS `filetype`,`f`.`level` AS `level`,`f`.`filesize` AS `filesize`,`f`.`sha256` AS `sha256`,`f`.`md5` AS `md5`,`f`.`filectime` AS `filectime`,`f`.`filemtime` AS `filemtime`,`f`.`fileatime` AS `fileatime`,`f`.`stat` AS `stat`,`f`.`relevance` AS `relevance`,`f`.`access` AS `access`,`f`.`comment` AS `comment`,`f`.`archivetime` AS `archivetime`,`f`.`mtime` AS `mtime`,`f`.`lock` AS `lock`,`f`.`status` AS `status`,`f`.`readstate` AS `readstate`,`f`.`ingesttime` AS `ingesttime` from (`file` `f` join `log` `l`) where ((`f`.`sessionid` = `l`.`sessionid`) and (`f`.`fileid` = `l`.`fileid`)) ;

-- --------------------------------------------------------

--
-- Struktur des Views `throughput`
--
DROP TABLE IF EXISTS `throughput`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `throughput`  AS  select `file`.`sessionid` AS `sessionid`,min(`file`.`ingesttime`) AS `start`,max(`file`.`ingesttime`) AS `end`,timestampdiff(SECOND,min(`file`.`ingesttime`),max(`file`.`ingesttime`)) AS `seconds`,count(0) AS `files`,sum(`file`.`filesize`) AS `size` from `file` group by `file`.`sessionid` ;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `file`
--
ALTER TABLE `file`
  ADD PRIMARY KEY (`fileid`),
  ADD KEY `sessionid` (`sessionid`),
  ADD KEY `name` (`name`),
  ADD KEY `sha256` (`sha256`),
  ADD KEY `filectime` (`filectime`),
  ADD KEY `filemtime` (`filemtime`),
  ADD KEY `fileatime` (`fileatime`),
  ADD KEY `filetype` (`filetype`),
  ADD KEY `parentid` (`parentid`),
  ADD KEY `archivetime` (`archivetime`),
  ADD KEY `localcopy` (`localcopy`),
  ADD KEY `solrtime` (`mtime`),
  ADD KEY `relevance` (`relevance`),
  ADD KEY `blocked` (`access`),
  ADD KEY `md5` (`md5`),
  ADD KEY `lock` (`lock`),
  ADD KEY `status` (`status`),
  ADD KEY `ingesttime` (`ingesttime`),
  ADD KEY `readstate` (`readstate`),
  ADD KEY `path` (`path`);

--
-- Indizes für die Tabelle `info_antiword`
--
ALTER TABLE `info_antiword`
  ADD UNIQUE KEY `fileid` (`fileid`),
  ADD KEY `sessionid` (`sessionid`),
  ADD KEY `status` (`status`),
  ADD KEY `indextime` (`indextime`);

--
-- Indizes für die Tabelle `info_avconv`
--
ALTER TABLE `info_avconv`
  ADD UNIQUE KEY `fileid` (`fileid`),
  ADD KEY `sessionid` (`sessionid`),
  ADD KEY `status` (`status`),
  ADD KEY `indextime` (`indextime`);

--
-- Indizes für die Tabelle `info_gvfs_info`
--
ALTER TABLE `info_gvfs_info`
  ADD UNIQUE KEY `fileid` (`fileid`),
  ADD KEY `mimetype` (`mimetype`),
  ADD KEY `sessionid` (`sessionid`),
  ADD KEY `status` (`status`),
  ADD KEY `indextime` (`indextime`);

--
-- Indizes für die Tabelle `info_imagick`
--
ALTER TABLE `info_imagick`
  ADD UNIQUE KEY `fileid` (`fileid`),
  ADD KEY `sessionid` (`sessionid`),
  ADD KEY `magick` (`magick`),
  ADD KEY `width` (`width`),
  ADD KEY `height` (`height`),
  ADD KEY `status` (`status`),
  ADD KEY `indextime` (`indextime`);

--
-- Indizes für die Tabelle `info_libmagic`
--
ALTER TABLE `info_libmagic`
  ADD UNIQUE KEY `fileid` (`fileid`),
  ADD KEY `mimetype` (`mimetype`),
  ADD KEY `mimeencoding` (`mimeencoding`),
  ADD KEY `sessionid` (`sessionid`),
  ADD KEY `status` (`status`),
  ADD KEY `indextime` (`indextime`);

--
-- Indizes für die Tabelle `info_nsrl`
--
ALTER TABLE `info_nsrl`
  ADD PRIMARY KEY (`fileid`),
  ADD KEY `sessionid` (`sessionid`),
  ADD KEY `ProductCode` (`ProductCode`),
  ADD KEY `OpSystemcode` (`OpSystemcode`),
  ADD KEY `status` (`status`),
  ADD KEY `indextime` (`indextime`);

--
-- Indizes für die Tabelle `info_tika`
--
ALTER TABLE `info_tika`
  ADD UNIQUE KEY `fileid` (`fileid`),
  ADD KEY `mimetype` (`mimetype`),
  ADD KEY `mimeencoding` (`mimeencoding`),
  ADD KEY `sessionid` (`sessionid`),
  ADD KEY `status` (`status`),
  ADD KEY `hascontent` (`hascontent`),
  ADD KEY `indexertime` (`indexertime`);

--
-- Indizes für die Tabelle `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`logid`),
  ADD KEY `sessionid` (`sessionid`),
  ADD KEY `fileid` (`fileid`),
  ADD KEY `ltime` (`ltime`),
  ADD KEY `status` (`status`);

--
-- Indizes für die Tabelle `NSRLMfg`
--
ALTER TABLE `NSRLMfg`
  ADD KEY `MfgCode` (`MfgCode`);

--
-- Indizes für die Tabelle `NSRLOS`
--
ALTER TABLE `NSRLOS`
  ADD KEY `OpSystemCode` (`OpSystemCode`);

--
-- Indizes für die Tabelle `NSRLProd`
--
ALTER TABLE `NSRLProd`
  ADD KEY `ProductCode` (`ProductCode`),
  ADD KEY `OpSystemCode` (`OpSystemCode`),
  ADD KEY `MfgCode` (`MfgCode`),
  ADD KEY `ProductName` (`ProductName`),
  ADD KEY `Language` (`Language`);

--
-- Indizes für die Tabelle `session`
--
ALTER TABLE `session`
  ADD PRIMARY KEY (`sessionid`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `basepath` (`basepath`(255)),
  ADD KEY `parent` (`parent`,`group`),
  ADD KEY `collection` (`solrpath`),
  ADD KEY `solrtime` (`solrtime`),
  ADD KEY `ingesttime` (`ingesttime`),
  ADD KEY `ignore` (`ignore`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `file`
--
ALTER TABLE `file`
  MODIFY `fileid` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3280431;
--
-- AUTO_INCREMENT für Tabelle `log`
--
ALTER TABLE `log`
  MODIFY `logid` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1755;
--
-- AUTO_INCREMENT für Tabelle `session`
--
ALTER TABLE `session`
  MODIFY `sessionid` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4407;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
