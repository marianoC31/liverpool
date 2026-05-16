-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 08, 2015 at 01:21 PM
-- Server version: 5.5.41-0ubuntu0.14.04.1
-- PHP Version: 5.5.9-1ubuntu4.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `Tec`
--

-- --------------------------------------------------------

--
-- Table structure for table `auto2`
--

CREATE TABLE IF NOT EXISTS `auto` (
  `idauto` int(11) NOT NULL AUTO_INCREMENT,
  `nombrec` varchar(20) DEFAULT NULL,
  `idmarca` int(11) DEFAULT NULL,
  `ac` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`idauto`),
  KEY `idmarca` (`idmarca`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `auto2`
--

INSERT INTO `auto` (`idauto`, `nombrec`, `idmarca`, `ac`) VALUES
(2, 'civic', 1, 1),
(3, 'acord', 1, 0),
(4, 'jetta', 2, 0),
(5, 'bora', 2, 1),
(29, 'crv', 1, 1),
(30, 'Tiguan', 2, 0);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `auto2`
--
ALTER TABLE `auto`
  ADD CONSTRAINT `auto2_ibfk_1` FOREIGN KEY (`idmarca`) REFERENCES `marca` (`idmarca`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
