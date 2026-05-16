-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 08, 2015 at 01:22 PM
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
-- Table structure for table `marca2`
--

CREATE TABLE IF NOT EXISTS `marca` (
  `idmarca` int(11) NOT NULL AUTO_INCREMENT,
  `nombrem` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`idmarca`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=7;

--
-- Dumping data for table `marca2`
--
ALTER TABLE `marca` MODIFY `idmarca` INT(11) AUTO_INCREMENT; 

INSERT INTO `marca` (`idmarca`, `nombrem`) VALUES
(1, 'Honda'),
(2, 'Volkswagen'),
(3, 'Ford'),
(4, 'Suzuki'),
(5, 'Mazda'),
(6, 'Acura');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
