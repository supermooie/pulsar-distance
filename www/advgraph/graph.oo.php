<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2006-2007 Zack Bloom                                      |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU Lesser General Public              |
 | License as published by the Free Software Foundation; either            |
 | version 2.1 of the License, or (at your option) any later version. 	   |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU Lesser General Public License for more details.                     |
 |                                                                         |
 | You should have received a copy of the GNU Lesser General Public        |
 | License along with this library; if not, write to the Free Software     |
 | Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA           |
 | 02110-1301, USA                                                         |
 |                                                                         |
 +-------------------------------------------------------------------------+
 | Version 1.8.0 - April 7th, 2007                                         |
 +-------------------------------------------------------------------------+
 | Special Thanks to:                                                      |
 |   Miles Kaufmann - EvalMath Class Library                               |
 |   Walter Zorn    - Java Graph Library                                   |
 |   Andreas Gorh   - PHP4 Backport                                        |
 |   All Those Who Love PHP :)                                             |
 +-------------------------------------------------------------------------+
 | Code updates and additional features are released frequently, the most  |
 | updated version can always be found at: http://www.zackbloom.org.       |
 |                                                                         |
 | Email me at: zackbloom@gmail.com with any comments, questions, BUGS!!!  |
 |                                                                         |
 | Works with PHP 4 & 5 with GD and TTF support.                           |
 +-------------------------------------------------------------------------+
 | - Advanced Graph Class Library - http://www.zackbloom.org/              |
 +-------------------------------------------------------------------------+
*/

/* version check.  PHP4 does not support true OO syntax */
if (substr(PHP_VERSION,0,strpos(PHP_VERSION,'.'))<5) {
	include("advgraph4.class.php");
}else{	include("advgraph5.class.php");
}

?>

